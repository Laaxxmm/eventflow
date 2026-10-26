<?php

namespace App\Http\Controllers;

use App\Mail\PaymentReminderMail;
use App\Models\Event;
use App\Models\Org;
use App\Models\Registration;
use App\Models\RegistrationReminderLog;
use App\Notifications\EmailBrand;
use App\Notifications\NotificationSettings;
use App\Reminders\ReminderSchedule;
use App\Reminders\ReminderSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Event → Settings → Communications (Session N2): the per-event "Payment reminders" switch
 * (off by default), cadence, cap, editable subject and message, channel status, email and
 * WhatsApp previews, and the latest reminders sent.
 */
class EventCommunicationsController extends Controller
{
    public function show(Request $request, Event $event): View
    {
        $org = $this->org($request, $event);
        $settings = NotificationSettings::for($org);
        $reminders = ReminderSettings::for($event);
        $sample = $this->sample($event);
        $brand = EmailBrand::for($sample);
        $values = PaymentReminderMail::values($sample, $brand);

        return view('events.communications', [
            'event' => $event,
            'org' => $org,
            'reminders' => $reminders,
            'featureOn' => ReminderSchedule::featureOn(),
            'maxCap' => (int) config('reminders.max_reminders'),
            'mail' => match (true) {
                $settings->usesCustomSmtp($org) => 'smtp',
                $settings->usesPlatformMail($org) => 'platform',
                default => 'held',
            },
            'whatsapp' => match (true) {
                ! $settings->usesWhatsApp($org) => 'off',
                blank($settings->whatsapp_template_payment) => 'no-template',
                default => 'on',
            },
            'previewSubject' => ReminderSettings::fill($reminders->subject, $values),
            'previewWhatsApp' => __('Hi :name, your entry for :event (:date) is waiting for payment. Complete it here: :link', [
                'name' => $values['name'], 'event' => $values['event'], 'date' => $brand->date?->format('D j M Y, g:i A') ?? '', 'link' => url('/r/…/review'),
            ]),
            'unpaid' => ReminderSchedule::unpaidQuery($event)->count(),
            'logs' => RegistrationReminderLog::query()
                ->whereIn('registration_id', Registration::query()->where('event_id', $event->id)->select('id'))
                ->with('registration:id,participant_name,email')
                ->latest('id')->limit(20)->get(),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->org($request, $event);
        $max = (int) config('reminders.max_reminders');
        $data = $request->validate([
            'enabled' => ['boolean'],
            'delays_hours' => ['required', 'array', 'min:1', "max:{$max}"],
            'delays_hours.*' => ['required', 'integer', 'between:1,720'],
            'max' => ['required', 'integer', "between:1,{$max}"],
            'before_event_hours' => ['required', 'integer', 'between:0,336'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $event->forceFill(['payment_reminders' => ReminderSettings::fromArray($data)->toArray()])->save();

        return redirect()->route('organiser.events.communications', $event)
            ->with('status', ($data['enabled'] ?? false) ? 'reminders-on' : 'reminders-saved');
    }

    /** The reminder email as participants see it, with the latest unpaid (or any) registration. */
    public function preview(Request $request, Event $event): Response
    {
        $this->org($request, $event);
        $sample = $this->sample($event);
        abort_unless($sample->exists, 404, __('A preview needs one registration for this event.'));

        return response((new PaymentReminderMail($sample->id))->render());
    }

    /** Latest unpaid registration, else any, else an unsaved placeholder for the text previews. */
    private function sample(Event $event): Registration
    {
        $registration = ReminderSchedule::unpaidQuery($event)->latest('id')->first()
            ?? Registration::withoutGlobalScopes()->where('event_id', $event->id)->latest('id')->first();

        if ($registration === null) {
            $registration = new Registration(['participant_name' => __('Asha'), 'distance_code' => null]);
            $registration->setRelation('event', $event);
        }

        return $registration;
    }

    private function org(Request $request, Event $event): Org
    {
        $user = $request->user();
        abort_if($user->org_id === null || $event->org_id !== $user->org_id, 404);

        return $user->org;
    }
}
