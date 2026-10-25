<?php

namespace App\Notifications;

use App\Events\RegistrationPaid;
use App\Models\Registration;
use App\Notifications\Console\SendEventReminders;
use App\Notifications\Listeners\CheckInObserver;
use App\Notifications\Listeners\QueueWhatsAppConfirmation;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Participant notifications. Email after payment is queued by App\Listeners\SendPass
 * (PassMail = RegistrationConfirmedMail) once the pass exists; this provider adds
 * WhatsApp, reminders, the optional check-in email, settings and the pass link.
 */
class NotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(RegistrationPaid::class, QueueWhatsAppConfirmation::class);
        Registration::observe(CheckInObserver::class);

        if ($this->app->runningInConsole()) {
            $this->commands([SendEventReminders::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('notifications:send-reminders')->hourly()->withoutOverlapping();
        });

        if (! $this->app->routesAreCached()) {
            Route::middleware('web')->group(base_path('routes/notifications.php'));
        }
    }
}
