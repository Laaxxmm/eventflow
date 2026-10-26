<?php

namespace App\Reminders;

use App\Models\Registration;
use Illuminate\Support\Facades\URL;

/** Signed links in payment reminders. */
final class ReminderLinks
{
    /** The registration's signed review page, whose button POSTs to /pay/{registration}. */
    public static function pay(Registration $registration): string
    {
        return URL::temporarySignedRoute('registrations.review', now()->addDays((int) config('reminders.link_days')), ['registration' => $registration->id]);
    }

    /** One click: no more payment reminders for this registration. */
    public static function stop(Registration $registration): string
    {
        return URL::temporarySignedRoute('reminders.stop', now()->addDays(60), ['registration' => $registration->id]);
    }
}
