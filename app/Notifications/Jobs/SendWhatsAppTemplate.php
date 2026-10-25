<?php

namespace App\Notifications\Jobs;

use App\Models\Registration;
use App\Notifications\EmailBrand;
use App\Notifications\NotificationDelivery;
use App\Notifications\NotificationSettings;
use App\Services\WhatsApp\PhoneNumber;
use App\Services\WhatsApp\WhatsAppGatewayFactory;
use App\Services\WhatsApp\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * Sends one WhatsApp template to a participant. Re-checks the module and settings at
 * send time; WhatsApp problems never affect the registration or its email.
 *
 * Template parameters (approve the templates in WhatsApp Manager with these):
 *  registration_confirmed: header DOCUMENT (pass PDF); body {{1}} name, {{2}} event, {{3}} pass no, {{4}} date
 *  event_reminder:        body {{1}} name, {{2}} event, {{3}} date, {{4}} venue
 */
class SendWhatsAppTemplate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const CONFIRMED = 'registration_confirmed';

    public const REMINDER = 'event_reminder';

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [60, 300, 900, 1800];

    public function __construct(public int $registrationId, public string $type) {}

    public function handle(WhatsAppGatewayFactory $gateways): void
    {
        $registration = Registration::withoutGlobalScopes()->with('event.org')->find($this->registrationId);
        if (! $registration?->event?->org) {
            return;
        }

        $org = $registration->event->org;
        $settings = NotificationSettings::for($org);
        $to = PhoneNumber::toWhatsApp($registration->phone);
        $delivery = NotificationDelivery::track($org->id, $registration->id, 'whatsapp', $this->type, (string) ($to ?? $registration->phone));

        if ($delivery->status === NotificationDelivery::SENT) {
            return; // already delivered (e.g. a repeated webhook)
        }

        $template = $this->type === self::REMINDER ? $settings->whatsapp_template_reminder : $settings->whatsapp_template_confirmed;
        if (! $settings->usesWhatsApp($org) || blank($template)) {
            $delivery->markSkipped('WhatsApp is off or not set up for this org.');

            return;
        }
        if ($to === null) {
            $delivery->markSkipped('No valid mobile number.');

            return;
        }

        $brand = EmailBrand::for($registration);
        $date = $brand->date?->format('D j M Y, g:i A') ?? '';

        $message = $this->type === self::REMINDER
            ? new WhatsAppMessage($to, $template, $settings->whatsapp_language, [
                $registration->participant_name, $brand->eventName, $date, $brand->venue,
            ])
            : new WhatsAppMessage($to, $template, $settings->whatsapp_language, [
                $registration->participant_name, $brand->eventName, (string) $registration->pass_no, $date,
            ], documentUrl: EmailBrand::passUrl($registration), documentFilename: 'Pass-'.$registration->pass_no.'.pdf');

        try {
            $result = $gateways->for($settings)->sendTemplate($message);
        } catch (Throwable $e) {
            $delivery->markFailed($e, final: $this->attempts() >= $this->tries);
            throw $e;
        }

        $delivery->markSent($result->messageId);
    }

    public function failed(Throwable $e): void
    {
        NotificationDelivery::withoutGlobalScopes()
            ->where('registration_id', $this->registrationId)
            ->where('channel', 'whatsapp')
            ->where('type', $this->type)
            ->first()
            ?->markFailed($e, final: true);
    }
}
