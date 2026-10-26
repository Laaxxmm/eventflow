<?php

namespace App\Reminders;

use App\Events\RegistrationPaid;
use App\Models\RegistrationReminderLog;

/**
 * Payment arrived (webhook, return page or reconcile): cancel reminders still queued for the
 * registration. The job re-checks the status anyway; this keeps the timeline honest.
 */
class StopRemindersOnPayment
{
    public function handle(RegistrationPaid $event): void
    {
        RegistrationReminderLog::withoutGlobalScopes()
            ->where('registration_id', $event->registrationId)
            ->where('status', RegistrationReminderLog::QUEUED)
            ->update(['status' => RegistrationReminderLog::SKIPPED, 'detail' => 'Cancelled: payment received.']);
    }
}
