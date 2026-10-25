<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Payments\PaymentStatus;
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

        return view('dashboard.index', [
            'events' => $events,
            'totals' => [
                'registrations' => (int) $events->sum('registrations_count'),
                'paid' => (int) $events->sum('paid_count'),
                'paid_total_paise' => (int) $events->sum('paid_total_paise'),
                'checked_in' => (int) $events->sum('checked_in_count'),
            ],
        ]);
    }

    public function participants(Request $request): View
    {
        [$event, $status] = $this->filters($request);

        return view('dashboard.participants', [
            'events' => Event::orderByDesc('date')->get(['id', 'name']),
            'event' => $event,
            'status' => $status,
            'statuses' => PaymentStatus::cases(),
            'registrations' => $this->participantsQuery($event, $status)->paginate(50)->withQueryString(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$event, $status] = $this->filters($request);
        $query = $this->participantsQuery($event, $status);

        // Custom answer columns: every key any exported registration answered.
        $answerKeys = $query->clone()->pluck('answers')
            ->flatMap(fn (?array $answers) => array_keys($answers ?? []))
            ->unique()->sort()->values()->all();

        $filename = ($event?->slug ?? 'all-events').'-participants-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query, $answerKeys) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Pass no', 'Name', 'Email', 'Phone', 'Event', 'Distance', 'Status', 'Amount (INR)', 'Paid at', 'Checked in at', ...$answerKeys]);

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
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
        ]);

        return [
            $request->filled('event') ? Event::findOrFail($request->integer('event')) : null,
            $request->enum('status', PaymentStatus::class),
        ];
    }

    private function participantsQuery(?Event $event, ?PaymentStatus $status): Builder
    {
        return Registration::query()
            ->with('event:id,name')
            ->when($event, fn (Builder $q) => $q->whereBelongsTo($event))
            ->when($status, fn (Builder $q) => $q->where('status', $status))
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
