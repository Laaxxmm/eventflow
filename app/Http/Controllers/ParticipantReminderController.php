<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\RegistrationReminderLog;
use App\Reminders\Jobs\SendPaymentReminder;
use App\Reminders\ReminderSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Participants → "Send reminder now" (Session N2): a manual payment reminder for the selected
 * unpaid registrations. Manual reminders are flagged "manual" and do not count toward the
 * automatic cap, but they follow every other rule: unpaid only, no opt-outs, not after the
 * start, quiet hours (queued for 08:00), and no second reminder within the cooldown.
 */
class ParticipantReminderController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'registrations' => ['required', 'array', 'min:1', 'max:500'],
            'registrations.*' => ['integer'],
        ]);

        $now = CarbonImmutable::now();
        $cooldown = $now->subHours((int) config('reminders.manual_cooldown_hours'));
        $deliverAt = ReminderSchedule::outsideQuietHours($now);
        $queued = 0;
        $skipped = [];

        // Org-scoped query: another org's ids simply do not match.
        Registration::query()->whereKey($data['registrations'])->with('event', 'reminderLogs')->get()
            ->each(function (Registration $registration) use ($cooldown, $deliverAt, $now, &$queued, &$skipped) {
                if ($reason = ReminderSchedule::blocker($registration, $now)) {
                    $skipped[] = $registration->participant_name.': '.$reason;

                    return;
                }
                $last = ReminderSchedule::last($registration);
                if ($last && ($last->sent_at ?? $last->created_at)->greaterThan($cooldown)) {
                    $skipped[] = $registration->participant_name.': '.__('reminded in the last :h hours.', ['h' => config('reminders.manual_cooldown_hours')]);

                    return;
                }

                $key = 'manual-'.Str::uuid();
                RegistrationReminderLog::withoutGlobalScopes()->create([
                    'org_id' => $registration->org_id,
                    'registration_id' => $registration->id,
                    'kind' => 'manual',
                    'dispatch_key' => $key,
                    'channel' => 'mail',
                    'status' => RegistrationReminderLog::QUEUED,
                ]);
                SendPaymentReminder::dispatch($registration->id, $key, 'manual')->delay($deliverAt->greaterThan($now) ? $deliverAt : null);
                $queued++;
            });

        return back()->with([
            'reminders_queued' => $queued,
            'reminders_deferred' => $queued > 0 && $deliverAt->greaterThan($now),
            'reminders_skipped' => $skipped,
        ]);
    }
}
