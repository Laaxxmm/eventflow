<?php

namespace App\Reminders;

use App\Models\Event;
use App\Models\Registration;
use App\Models\RegistrationReminderLog;
use App\Payments\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * When a registration's next automatic payment reminder is due, and whether it may get one.
 *
 * Audience: status pending or failed (never paid or refunded), the participant has not opted out,
 * and the event has not started. Cadence (ReminderSettings): #1 delay after the registration was
 * created, each later one delay after the previous reminder; from #3 on, no later than
 * before_event_hours before the start, and skipped once that point is already behind the previous
 * reminder. At most `max` automatic reminders; manual ones do not count, but the next automatic
 * one waits at least the manual cooldown after a manual reminder. Quiet hours
 * (21:00–08:00 Asia/Kolkata) push a due time to 08:00.
 */
final class ReminderSchedule
{
    public const UNPAID = [PaymentStatus::Pending, PaymentStatus::Failed];

    public static function featureOn(): bool
    {
        return (bool) config('features.payment_reminders');
    }

    /** Why this registration cannot be reminded right now, or null if it can (toggle aside). */
    public static function blocker(Registration $registration, ?CarbonImmutable $now = null): ?string
    {
        $now ??= CarbonImmutable::now();
        $event = $registration->event;

        return match (true) {
            ! self::featureOn() => 'Payment reminders are switched off for this installation.',
            ! in_array($registration->status, self::UNPAID, true) => 'Not unpaid any more ('.$registration->status->value.').',
            $registration->reminders_opted_out_at !== null => 'The participant opted out of reminders.',
            $event === null || $event->date === null || $event->date->lessThanOrEqualTo($now) => 'The event has started.',
            ! filter_var($registration->email, FILTER_VALIDATE_EMAIL) && blank($registration->phone) => 'No email or phone.',
            default => null,
        };
    }

    /** Automatic reminders already dispatched, keyed by sequence (one row per sequence, mail channel). */
    public static function autoLogs(Registration $registration): Collection
    {
        $logs = $registration->relationLoaded('reminderLogs')
            ? $registration->reminderLogs
            : $registration->reminderLogs()->get();

        return $logs->where('kind', 'auto')->where('channel', 'mail')->keyBy('sequence');
    }

    /** The next automatic reminder: [sequence, due time], or null when none is left. */
    public static function next(Registration $registration, ?CarbonImmutable $now = null): ?array
    {
        $now ??= CarbonImmutable::now();
        $event = $registration->event;
        if ($event === null || self::blocker($registration, $now) !== null) {
            return null;
        }

        $settings = ReminderSettings::for($event);
        if (! $settings->enabled) {
            return null;
        }

        $logs = self::autoLogs($registration);
        $n = $logs->count() + 1;
        if ($n > $settings->max) {
            return null;
        }

        $previous = $n === 1
            ? CarbonImmutable::instance($registration->created_at)
            : CarbonImmutable::instance($logs->sortByDesc('sequence')->first()->sent_at ?? $logs->sortByDesc('sequence')->first()->created_at);

        $due = $previous->addHours($settings->delayFor($n));
        if ($n >= 3) {
            $cutoff = CarbonImmutable::instance($event->date)->subHours($settings->beforeEventHours);
            if ($cutoff->lessThanOrEqualTo($previous)) {
                return null; // the event is too close: skip the rest
            }
            $due = $due->min($cutoff);
        }

        // A manual "Send reminder now" pushes the next automatic one past the manual cooldown,
        // so a participant never gets two reminders within a few hours.
        $manual = self::lastManual($registration);
        if ($manual !== null) {
            $due = $due->max(CarbonImmutable::instance($manual->sent_at ?? $manual->created_at)->addHours((int) config('reminders.manual_cooldown_hours')));
        }

        $due = self::outsideQuietHours($due);

        return $due->lessThan($event->date) ? [$n, $due] : null;
    }

    public static function isQuiet(CarbonImmutable $at): bool
    {
        $hour = $at->setTimezone(config('reminders.timezone'))->hour;

        return $hour >= (int) config('reminders.quiet_from') || $hour < (int) config('reminders.quiet_until');
    }

    /** The same moment, or the next 08:00 local when it falls in quiet hours. */
    public static function outsideQuietHours(CarbonImmutable $at): CarbonImmutable
    {
        if (! self::isQuiet($at)) {
            return $at;
        }
        $local = $at->setTimezone(config('reminders.timezone'));
        $morning = $local->setTime((int) config('reminders.quiet_until'), 0);
        if ($local->hour >= (int) config('reminders.quiet_from')) {
            $morning = $morning->addDay();
        }

        return $morning->setTimezone($at->getTimezone());
    }

    /** Latest reminder of any kind (for the cooldown and the participants list). */
    public static function last(Registration $registration): ?RegistrationReminderLog
    {
        $logs = $registration->relationLoaded('reminderLogs') ? $registration->reminderLogs : $registration->reminderLogs()->get();

        return $logs->where('channel', 'mail')->sortByDesc('id')->first();
    }

    private static function lastManual(Registration $registration): ?RegistrationReminderLog
    {
        $logs = $registration->relationLoaded('reminderLogs') ? $registration->reminderLogs : $registration->reminderLogs()->get();

        return $logs->where('kind', 'manual')->where('channel', 'mail')->where('status', RegistrationReminderLog::SENT)->sortByDesc('id')->first();
    }

    /** Organiser-visible list of who is due, for an event. */
    public static function unpaidQuery(Event $event)
    {
        return Registration::withoutGlobalScopes()
            ->where('event_id', $event->id)
            ->whereIn('status', self::UNPAID)
            ->whereNull('reminders_opted_out_at');
    }
}
