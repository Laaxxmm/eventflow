<?php

namespace App\Notifications\Listeners;

use App\Events\RegistrationPaid;
use App\Models\Registration;
use App\Notifications\Jobs\SendWhatsAppTemplate;
use App\Notifications\NotificationSettings;

/**
 * After payment: queue the WhatsApp confirmation when the org has the module on and
 * set up. The email (pass PDF attached) is queued by App\Listeners\SendPass once the
 * pass exists; WhatsApp links to the pass and generates it on first open if needed.
 */
class QueueWhatsAppConfirmation
{
    public function handle(RegistrationPaid $event): void
    {
        $registration = Registration::withoutGlobalScopes()->with('event.org')->find($event->registrationId);
        $org = $registration?->event?->org;

        if ($org && NotificationSettings::for($org)->usesWhatsApp($org)) {
            SendWhatsAppTemplate::dispatch($registration->id, SendWhatsAppTemplate::CONFIRMED);
        }
    }
}
