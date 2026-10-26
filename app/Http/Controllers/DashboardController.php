<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Payments\PaymentStatus;
use App\Reminders\ReminderSchedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Org-scoped organiser dashboard. Every query here goes through OrgScope,
 * so another org's events and registrations are invisible (404 on direct ids).
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $paid = fn (Builder $q) => $q->where('status', PaymentStatus::Paid);

        $events = Event::query()
            ->withCount([
                'registrations',
                'registrations as paid_count' => $paid,
                'registrations as checked_in_count' => fn (Builder $q) => $q->whereNotNull('checked_in_at'),
            ])
            ->withSum(['registrations as paid_total_paise' => $paid], 'amount_paise')
            ->orderByDesc('date')
            ->get();

        $next = $events->filter(fn (Event $e) => $e->date->isAfter(now()->subDay()))->sortBy('date')->first();

        return view('dashboard.index', [
            'events' => $events,
            'totals' => [
                'registrations' => (int) $events->sum('registrations_count'),
                'paid' => (int) $events->sum('paid_count'),
                'paid_total_paise' => (int) $events->sum('paid_total_paise'),
                'checked_in' => (int) $events->sum('checked_in_count'),
            ],
            // Daylight Track dashboard: next race card, week trend, 30-day bars, "worth doing next".
            'next' => $next,
            'nextPlaces' => $next ? $this->places($next) : null,
            'thisWeek' => Registration::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'unpaid' => Registration::query()->whereIn('status', ReminderSchedule::UNPAID)
                ->whereHas('event', fn (Builder $q) => $q->where('date', '>', now()))->count(),
            'daily' => $this->daily(30),
        ]);
    }

    /** Registrations per day for the last $days days (oldest first), IST dates. */
    private function daily(int $days): array
    {
        $from = now('Asia/Kolkata')->startOfDay()->subDays($days - 1);
        $counts = Registration::query()->where('created_at', '>=', $from->copy()->utc())->pluck('created_at')
            ->countBy(fn ($at) => $at->setTimezone('Asia/Kolkata')->toDateString());

        return collect(range(0, $days - 1))->map(fn (int $i) => [
            'date' => $from->copy()->addDays($i),
            'count' => (int) ($counts[$from->copy()->addDays($i)->toDateString()] ?? 0),
        ])->all();
    }

    /** Paid places vs the race's capacity (event capacity, else the sum of capped distances). */
    private function places(Event $event): array
    {
        $capacity = $event->capacity ?: (collect($event->config['distances'] ?? [])->every(fn ($d) => ! empty($d['capacity']))
            ? (int) collect($event->config['distances'] ?? [])->sum('capacity') : null);

        return ['paid' => (int) $event->paid_count, 'capacity' => $capacity ?: null];
    }

    public function participants(Request $request): View
    {
        [$event, $status, $search] = $this->filters($request);

        return view('dashboard.participants', [
            'search' => $search,
            'counts' => [
                '' => $this->participantsQuery($event, null, $search)->count(),
                'paid' => $this->participantsQuery($event, PaymentStatus::Paid, $search)->count(),
                'unpaid' => $this->participantsQuery($event, 'unpaid', $search)->count(),
            ],
            'events' => Event::orderByDesc('date')->get(['id', 'name']),
            'event' => $event,
            'status' => $status,
            'statuses' => PaymentStatus::cases(),
            'registrations' => $this->participantsQuery($event, $status, $search)->with('reminderLogs', 'event:id,name,date,payment_reminders')->paginate(50)->withQueryString(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$event, $status, $search] = $this->filters($request);
        $query = $this->participantsQuery($event, $status, $search);

        // Custom answer columns: every key any exported registration answered.
        $answerKeys = $query->clone()->pluck('answers')
            ->flatMap(fn (?array $answers) => array_keys($answers ?? []))
            ->unique()->sort()->values()->all();

        $filename = ($event?->slug ?? 'all-events').'-participants-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query, $answerKeys) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Pass no', 'Name', 'Email', 'Phone', 'Event', 'Distance', 'Status', 'Amount (INR)', 'Paid at', 'Checked in at', 'Source', 'Notes', ...$answerKeys]);

            foreach ($query->lazy(500) as $r) {
                fputcsv($out, array_map($this->csvCell(...), [
                    $r->pass_no,
                    $r->participant_name,
                    $r->email,
                    $r->phone,
                    $r->event?->name,
                    $r->distance_code,
                    $r->status->value,
                    number_format($r->amount_paise / 100, 2, '.', ''),
                    $r->paid_at?->toDateTimeString(),
                    $r->checked_in_at?->toDateTimeString(),
                    $r->isComplimentary() ? 'manual (complimentary)' : ($r->source ?? 'online'),
                    $r->notes,
                    ...array_map(fn (string $key) => $this->answer($r->answers[$key] ?? null), $answerKeys),
                ]));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: ?Event, 1: ?PaymentStatus} */
    private function filters(Request $request): array
    {
        $request->validate([
            'event' => ['nullable', 'integer'],
            // "unpaid" = pending or failed (Session N2 payment reminders).
            'status' => ['nullable', Rule::in([...array_column(PaymentStatus::cases(), 'value'), 'unpaid'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return [
            $request->filled('event') ? Event::findOrFail($request->integer('event')) : null,
            $request->input('status') === 'unpaid' ? 'unpaid' : $request->enum('status', PaymentStatus::class),
            trim((string) $request->input('q')) ?: null,
        ];
    }

    private function participantsQuery(?Event $event, PaymentStatus|string|null $status, ?string $search = null): Builder
    {
        return Registration::query()
            ->with('event:id,name')
            ->when($search, fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('participant_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('pass_no', 'like', "%{$search}%")))
            ->when($event, fn (Builder $q) => $q->whereBelongsTo($event))
            ->when($status === 'unpaid', fn (Builder $q) => $q->whereIn('status', ReminderSchedule::UNPAID))
            ->when($status instanceof PaymentStatus, fn (Builder $q) => $q->where('status', $status))
            ->latest('id');
    }

    private function answer(mixed $value): string
    {
        return is_array($value) ? implode('; ', $value) : (string) $value;
    }

    /** Neutralise spreadsheet formulas (CSV injection). */
    private function csvCell(mixed $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
