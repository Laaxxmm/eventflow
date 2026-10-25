<?php

namespace App\Mail;

use App\Models\Registration;
use App\Notifications\EmailBrand;

/** About 24 hours before the start: when, where, bring the pass. */
class EventReminderMail extends BrandedMail
{
    public function type(): string
    {
        return 'event_reminder';
    }

    protected function subjectLine(Registration $registration, EmailBrand $brand): string
    {
        return __('Tomorrow: :event', ['event' => $brand->eventName]);
    }

    protected function template(): string
    {
        return 'event-reminder';
    }
}
