<?php

namespace App\Http\Controllers;

use App\Contracts\Entitlements;
use App\Enums\Module;
use App\Landing\Capacity;
use App\Landing\DatabaseLandingSource;
use App\Landing\EventConfig;
use App\Landing\RegistrationForm;
use App\Landing\Theme;
use App\Models\Event;
use App\Models\Registration;
use App\Payments\PaymentStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    /** How long the signed review link (the hand-off to /pay) stays valid. */
    public const REVIEW_LINK_HOURS = 24;

    public function __construct(private DatabaseLandingSource $landing) {}

    /** Creates a Pending registration from the landing form, then hands off to payment. */
    public function store(Request $request, string $org, string $slug): RedirectResponse
    {
        $event = $this->landing->event($org, $slug);
        $config = $this->landing->load($org, $slug);
        $form = new RegistrationForm($config);

        if (! self::isOpen($config) || ! $this->registrationEnabled($event)) {
            throw ValidationException::withMessages(['distance' => __('Registration for this event is closed.')]);
        }

        $input = $request->validate($form->rules(), [], $form->attributes());
        $distance = $form->distance($input['distance']);

        $answers = $this->answers($form, $input['answers'] ?? []);
        if (! empty($input['dob'])) {
            $age = $form->ageOnRaceDay($input['dob']);
            $band = $form->bandFor($age);

            if ($config->ageBands !== [] && $band === null) {
                throw ValidationException::withMessages([
                    'dob' => __('You will be :age on race day. This event is open to ages :range.', ['age' => $age, 'range' => $form->ageRange()]),
                ]);
            }

            $answers += ['dob' => $input['dob'], 'age' => $age, 'age_band' => $band?->label];
        }

        $registration = DB::transaction(function () use ($event, $distance, $input, $answers) {
            // Serialise registrations for this event so two last-place requests cannot both win.
            Event::whereKey($event->id)->lockForUpdate()->first();

            if (Capacity::isFull($event, $distance)) {
                throw ValidationException::withMessages([
                    'distance' => __(':distance is full.', ['distance' => $distance->label]),
                ]);
            }

            return Registration::create([
                'event_id' => $event->id,
                'pass_no' => Registration::newPassNo(),
                'status' => PaymentStatus::Pending,
                'participant_name' => $input['participant_name'],
                'email' => $input['email'],
                'phone' => $input['phone'],
                'distance_code' => $distance->code,
                'amount_paise' => $distance->pricePaise(),
                'answers' => $answers,
            ]);
        });

        return redirect(URL::temporarySignedRoute(
            'registrations.review',
            now()->addHours(self::REVIEW_LINK_HOURS),
            ['registration' => $registration],
        ));
    }

    /** Signed summary page whose button POSTs to /pay/{registration}. */
    public function review(Registration $registration): View
    {
        $event = $registration->event()->with('org')->firstOrFail();
        $config = $this->landing->load($event->org->slug, $event->slug);

        return view('landing.review', [
            'registration' => $registration,
            'event' => $event,
            'config' => $config,
            'distance' => (new RegistrationForm($config))->distance($registration->distance_code),
            'theme' => Theme::for($config->branding),
        ]);
    }

    public static function isOpen(EventConfig $config): bool
    {
        return $config->date->isFuture();
    }

    /** Season Pass gate; skipped until an Entitlements implementation is bound. */
    private function registrationEnabled(Event $event): bool
    {
        return ! app()->bound(Entitlements::class)
            || app(Entitlements::class)->enabled($event->org, Module::Registration);
    }

    /**
     * Answers for the configured fields only, blanks dropped.
     *
     * @param  array<string, mixed>  $posted
     * @return array<string, mixed>
     */
    private function answers(RegistrationForm $form, array $posted): array
    {
        $answers = [];

        foreach ($form->fields() as $field) {
            $value = $posted[$field->key] ?? null;

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $answers[$field->key] = match ($field->type) {
                'number' => $value + 0,
                'checkbox' => $field->options === [] ? (bool) $value : array_values($value),
                default => $value,
            };
        }

        return $answers;
    }
}
