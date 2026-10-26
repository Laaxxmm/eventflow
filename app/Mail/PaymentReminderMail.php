<?php

namespace App\Mail;

use App\Models\Registration;
use App\Notifications\EmailBrand;
use App\Reminders\ReminderLinks;
use App\Reminders\ReminderSettings;
use Illuminate\Mail\Mailables\Content;

/**
 * Unpaid payment reminder (Session N2): friendly nudge with a Pay now link back to the
 * registration's review page, the event date, the organiser's reply-to and a one-click opt-out.
 * Subject and opening message come from the event's reminder settings.
 */
class PaymentReminderMail extends BrandedMail
{
    public function type(): string
    {
        return 'payment_reminder';
    }

    protected function subjectLine(Registration $registration, EmailBrand $brand): string
    {
        return ReminderSettings::fill(ReminderSettings::for($registration->event)->subject, self::values($registration, $brand));
    }

    protected function template(): string
    {
        return 'payment-reminder';
    }

    public function content(): Content
    {
        $registration = $this->registration();
        $brand = EmailBrand::for($registration);

        return new Content(
            view: 'emails.payment-reminder',
            text: 'emails.payment-reminder-text',
            with: [
                'registration' => $registration,
                'brand' => $brand,
                'intro' => ReminderSettings::fill(ReminderSettings::for($registration->event)->message, self::values($registration, $brand)),
                'payUrl' => ReminderLinks::pay($registration),
                'stopUrl' => ReminderLinks::stop($registration),
            ],
        );
    }

    /** @return array<string, string> */
    public static function values(Registration $registration, EmailBrand $brand): array
    {
        return [
            'name' => (string) $registration->participant_name,
            'event' => $brand->eventName,
            'date' => (string) $brand->dateLine(),
            'org' => $brand->orgName,
        ];
    }
}
