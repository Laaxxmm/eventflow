<?php

namespace App\Mail;

use App\Models\Registration;
use App\Notifications\EmailBrand;

/** Optional (org setting): sent when a volunteer checks the participant in. */
class CheckInConfirmedMail extends BrandedMail
{
    public function type(): string
    {
        return 'checkin_confirmed';
    }

    protected function subjectLine(Registration $registration, EmailBrand $brand): string
    {
        return __('Checked in at :event', ['event' => $brand->eventName]);
    }

    protected function template(): string
    {
        return 'checkin-confirmed';
    }
}
