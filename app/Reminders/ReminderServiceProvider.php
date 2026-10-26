<?php

namespace App\Reminders;

use App\Events\RegistrationPaid;
use App\Models\Registration;
use App\Reminders\Console\SendPaymentReminders;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/** Unpaid payment reminders (Session N2): scheduler, rate limit, stop-on-payment. */
class ReminderServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(RegistrationPaid::class, StopRemindersOnPayment::class);

        RateLimiter::for('payment-reminders', fn ($job) => Limit::perMinute((int) config('reminders.per_org_per_minute'))
            ->by('payment-reminders:'.(Registration::withoutGlobalScopes()->whereKey($job->registrationId)->value('org_id') ?? 0)));

        if ($this->app->runningInConsole()) {
            $this->commands([SendPaymentReminders::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('reminders:payments')->everyTenMinutes()->withoutOverlapping();
        });
    }
}
