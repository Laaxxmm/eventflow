<?php

namespace App\Notifications\Listeners;

use App\Mail\CheckInConfirmedMail;
use App\Models\Registration;
use App\Notifications\NotificationSettings;
use Illuminate\Support\Facades\Mail;

/** Optional check-in confirmation email (Org Settings -> Notifications). */
class CheckInObserver
{
    public function updated(Registration $registration): void
    {
        if (! $registration->wasChanged('checked_in_at') || $registration->getOriginal('checked_in_at') !== null
            || $registration->checked_in_at === null) {
            return;
        }

        $org = $registration->event()->withoutGlobalScopes()->first()?->org;
        // Hand-added participants may have no email (Session P1).
        if ($org && filter_var($registration->email, FILTER_VALIDATE_EMAIL) && NotificationSettings::for($org)->send_checkin_confirmation) {
            Mail::to($registration->email)->queue(new CheckInConfirmedMail($registration->id));
        }
    }
}
