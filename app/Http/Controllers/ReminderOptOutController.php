<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Notifications\EmailBrand;
use Illuminate\View\View;

/** Signed link in payment reminders: no more reminders for this registration (Session N2). */
class ReminderOptOutController extends Controller
{
    public function __invoke(int $registration): View
    {
        $registration = Registration::withoutGlobalScopes()->with('event.org')->findOrFail($registration);
        if ($registration->reminders_opted_out_at === null) {
            $registration->forceFill(['reminders_opted_out_at' => now()])->save();
        }

        return view('reminders.stopped', ['brand' => EmailBrand::for($registration)]);
    }
}
