<?php

namespace App\Http\Controllers;

use App\Events\RegistrationPaid;
use App\Landing\Capacity;
use App\Landing\ConfigCompiler;
use App\Landing\Distance;
use App\Landing\InvalidEventConfig;
use App\Models\Event;
use App\Models\Registration;
use App\Payments\PaymentStatus;
use App\Saas\PlanLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Registrations → Add participant (Session P1): entries taken on the phone, at a desk or as
 * free places. They are ordinary registrations (source = manual): they show under the paid /
 * unpaid filters, count against places and the plan cap, get an EF- pass when paid or
 * complimentary, and unpaid ones can get payment reminders.
 */
class ManualRegistrationController extends Controller
{
    public const PAYMENTS = ['paid', 'unpaid', 'complimentary'];

    public function __construct(private ConfigCompiler $compiler) {}

    public function create(Request $request): View|RedirectResponse
    {
        $events = Event::query()->orderByDesc('date')->get();
        if ($events->isEmpty()) {
            return redirect()->route('organiser.events.create');
        }

        $distances = $events->mapWithKeys(fn (Event $e) => [$e->id => array_map(fn (Distance $d) => [
            'code' => $d->code,
            'label' => $d->label,
            'price_inr' => $d->price_inr,
        ], $this->distances($e))])->all();

        $selected = $events->firstWhere('id', $request->integer('event')) ?? $events->first();

        return view('dashboard.participants-create', [
            'events' => $events,
            'distances' => $distances,
            'selected' => $selected,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'event' => ['required', 'integer'],
            'distance' => ['required', 'string', 'max:20'],
            'participant_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::requiredIf(fn () => $request->input('payment') === 'unpaid' && blank($request->input('phone')))],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'payment' => ['required', Rule::in(self::PAYMENTS)],
            'amount_inr' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'email.required' => __('Add an email or a mobile number so the participant can get a payment link.'),
        ], [
            'participant_name' => __('name'),
            'amount_inr' => __('amount'),
        ]);

        $event = Event::findOrFail($input['event']);
        $distance = collect($this->distances($event))->first(fn (Distance $d) => $d->code === $input['distance'])
            ?? throw ValidationException::withMessages(['distance' => __('Pick a distance of :event.', ['event' => $event->name])]);

        $status = $input['payment'] === 'unpaid' ? PaymentStatus::Pending : PaymentStatus::Paid;
        $amount = match ($input['payment']) {
            'complimentary' => 0,
            default => isset($input['amount_inr']) ? (int) $input['amount_inr'] * 100 : $distance->pricePaise(),
        };

        $registration = DB::transaction(function () use ($event, $distance, $input, $status, $amount, $request) {
            Event::whereKey($event->id)->lockForUpdate()->first();

            if (PlanLimits::atParticipantCap($event)) {
                throw ValidationException::withMessages(['distance' => __(':event has reached its plan limit. Buy a participant pack to add more.', ['event' => $event->name])]);
            }
            if (Capacity::isFull($event, $distance)) {
                throw ValidationException::withMessages(['distance' => __(':distance is full. Raise its place limit in Edit event first.', ['distance' => $distance->label])]);
            }

            return Registration::create([
                'event_id' => $event->id,
                'status' => $status,
                'source' => Registration::SOURCE_MANUAL,
                'payment_method' => match ($input['payment']) {
                    'complimentary' => Registration::COMPLIMENTARY,
                    'paid' => Registration::PAID_OFFLINE,
                    default => null,
                },
                'participant_name' => trim($input['participant_name']),
                'email' => filled($input['email'] ?? null) ? mb_strtolower(trim($input['email'])) : null,
                'phone' => filled($input['phone'] ?? null) ? trim($input['phone']) : null,
                'distance_code' => $distance->code,
                'amount_paise' => $amount,
                'answers' => [],
                'notes' => $input['notes'] ?? null,
                'added_by' => $request->user()->id,
                'paid_at' => $status === PaymentStatus::Paid ? now() : null,
            ]);
        });

        // Paid and complimentary entries get their EF- pass (and email) like online ones.
        if ($registration->status === PaymentStatus::Paid) {
            event(new RegistrationPaid($registration->id));
        }

        return redirect()
            ->route('dashboard.participants', ['event' => $event->id])
            ->with('participant_added', ['name' => $registration->participant_name, 'pass' => $registration->pass_no, 'status' => $input['payment']]);
    }

    /** @return list<Distance> The event's distances (empty if its config does not compile). */
    private function distances(Event $event): array
    {
        try {
            return $this->compiler->compile($event->config ?? [], "event {$event->id}")->distances;
        } catch (InvalidEventConfig) {
            return [];
        }
    }
}
