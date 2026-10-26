<?php

namespace App\Reminders\Console;

use App\Models\Event;
use App\Models\RegistrationReminderLog;
use App\Reminders\Jobs\SendPaymentReminder;
use App\Reminders\ReminderSchedule;
use App\Reminders\ReminderSettings;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Every 10 minutes: for events with payment reminders switched on, queue the automatic
 * reminder that is due for each unpaid registration. Nothing goes out in quiet hours
 * (21:00–08:00 Asia/Kolkata); due reminders wait for the 08:00 run.
 *
 * Each reminder is claimed with its log row (unique per registration, channel and
 * "auto-<n>") before the job is queued, so overlapping runs cannot send it twice.
 */
class SendPaymentReminders extends Command
{
    protected $signature = 'reminders:payments {--dry-run : List what is due without queuing}';

    protected $description = 'Queue due payment reminders for unpaid registrations';

    public function handle(): int
    {
        $now = CarbonImmutable::now();
        if (! ReminderSchedule::featureOn()) {
            $this->components->info('Payment reminders are switched off (features.payment_reminders).');

            return self::SUCCESS;
        }
        if (ReminderSchedule::isQuiet($now)) {
            $this->components->info('Quiet hours: nothing sent until 08:00 IST.');

            return self::SUCCESS;
        }

        $limit = (int) config('reminders.per_run');
        $queued = 0;

        $events = Event::withoutGlobalScopes()->with('org')
            ->whereNotNull('payment_reminders')
            ->where('date', '>', $now)
            ->get()
            ->filter(fn (Event $event) => ReminderSettings::for($event)->enabled);

        foreach ($events as $event) {
            ReminderSchedule::unpaidQuery($event)
                ->with('reminderLogs')
                ->orderBy('id')
                ->each(function ($registration) use ($event, $now, $limit, &$queued) {
                    if ($queued >= $limit) {
                        return false;
                    }
                    $registration->setRelation('event', $event);
                    $next = ReminderSchedule::next($registration, $now);
                    if ($next === null || $next[1]->greaterThan($now)) {
                        return true;
                    }

                    [$sequence] = $next;
                    if ($this->option('dry-run')) {
                        $this->line("#{$registration->id} {$registration->email}: reminder {$sequence}");
                        $queued++;

                        return true;
                    }

                    try {
                        RegistrationReminderLog::withoutGlobalScopes()->create([
                            'org_id' => $registration->org_id,
                            'registration_id' => $registration->id,
                            'kind' => 'auto',
                            'sequence' => $sequence,
                            'dispatch_key' => SendPaymentReminder::autoKey($sequence),
                            'channel' => 'mail',
                            'status' => RegistrationReminderLog::QUEUED,
                        ]);
                    } catch (UniqueConstraintViolationException) {
                        return true; // another run claimed it
                    }

                    SendPaymentReminder::dispatch($registration->id, SendPaymentReminder::autoKey($sequence), 'auto', $sequence);
                    $queued++;

                    return true;
                });
        }

        $this->components->info(($this->option('dry-run') ? 'Due' : 'Queued')." {$queued} payment reminder(s).");

        return self::SUCCESS;
    }
}
