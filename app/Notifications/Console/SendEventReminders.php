<?php

namespace App\Notifications\Console;

use App\Mail\EventReminderMail;
use App\Models\Event;
use App\Models\Registration;
use App\Notifications\Jobs\SendWhatsAppTemplate;
use App\Notifications\NotificationDelivery;
use App\Notifications\NotificationSettings;
use App\Payments\PaymentStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Queues the 24-hour reminder (email, plus WhatsApp when on) for paid registrations of
 * events starting in the next 24 hours. Runs hourly; each registration gets one reminder.
 */
class SendEventReminders extends Command
{
    protected $signature = 'notifications:send-reminders {--hours=24 : Remind for events starting within this many hours}';

    protected $description = 'Queue event reminder emails and WhatsApp messages';

    public function handle(): int
    {
        $events = Event::withoutGlobalScopes()
            ->with('org')
            ->where('status', 'published')
            ->whereBetween('date', [now(), now()->addHours((int) $this->option('hours'))])
            ->get();

        $queued = 0;
        foreach ($events as $event) {
            $settings = NotificationSettings::for($event->org);
            if (! $settings->send_reminders) {
                continue;
            }
            $whatsapp = $settings->usesWhatsApp($event->org) && filled($settings->whatsapp_template_reminder);

            Registration::withoutGlobalScopes()
                ->where('event_id', $event->id)
                ->where('status', PaymentStatus::Paid)
                ->whereNotNull('email')->where('email', '!=', '') // hand-added participants may have none (Session P1)
                ->whereNotExists(fn ($q) => $q->from('notification_deliveries')
                    ->whereColumn('notification_deliveries.registration_id', 'registrations.id')
                    ->where('channel', 'mail')
                    ->where('type', 'event_reminder'))
                ->each(function (Registration $registration) use ($event, $whatsapp, &$queued) {
                    // Claim the reminder first so an overlapping run cannot queue it twice.
                    NotificationDelivery::track($event->org_id, $registration->id, 'mail', 'event_reminder', $registration->email)->save();
                    Mail::to($registration->email)->queue(new EventReminderMail($registration->id));

                    if ($whatsapp) {
                        SendWhatsAppTemplate::dispatch($registration->id, SendWhatsAppTemplate::REMINDER);
                    }
                    $queued++;
                });
        }

        $this->components->info("Queued {$queued} reminder(s).");

        return self::SUCCESS;
    }
}
