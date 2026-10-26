<?php

namespace App\Reminders\Jobs;

use App\Mail\PaymentReminderMail;
use App\Models\Registration;
use App\Models\RegistrationReminderLog;
use App\Notifications\EmailBrand;
use App\Notifications\NotificationSettings;
use App\Reminders\ReminderLinks;
use App\Reminders\ReminderSchedule;
use App\Services\WhatsApp\PhoneNumber;
use App\Services\WhatsApp\WhatsAppGatewayFactory;
use App\Services\WhatsApp\WhatsAppMessage;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends one payment reminder (email, plus WhatsApp when the org has the add-on, an approved
 * payment template and the participant a mobile number) and records it on the registration.
 *
 * Idempotent: each channel has one log row per dispatch key ("auto-<n>" or "manual-<uuid>"),
 * and a row already marked sent is never sent again. Everything is re-checked at send time,
 * so a payment, an opt-out, or the event starting stops the reminder. Quiet hours re-queue it
 * for 08:00. WhatsApp problems never fail the email.
 *
 * WhatsApp template (approve in WhatsApp Manager, category Utility):
 *   body {{1}} name, {{2}} event, {{3}} date, {{4}} payment link
 */
class SendPaymentReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [60, 300, 900, 1800];

    public function __construct(
        public int $registrationId,
        public string $dispatchKey,
        public string $kind = 'auto',
        public ?int $sequence = null,
    ) {}

    public static function autoKey(int $sequence): string
    {
        return 'auto-'.$sequence;
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [new RateLimited('payment-reminders')];
    }

    public function handle(WhatsAppGatewayFactory $gateways): void
    {
        $registration = Registration::withoutGlobalScopes()->with('event.org')->find($this->registrationId);
        if ($registration === null) {
            return;
        }

        $mailLog = $this->log($registration, 'mail');
        if ($mailLog->status === RegistrationReminderLog::SENT) {
            return; // already delivered (repeated dispatch or retry after a WhatsApp error)
        }

        if ($reason = ReminderSchedule::blocker($registration)) {
            $mailLog->forceFill(['status' => RegistrationReminderLog::SKIPPED, 'detail' => $reason])->save();

            return;
        }

        if (ReminderSchedule::isQuiet(CarbonImmutable::now())) {
            $this->release(max(60, (int) CarbonImmutable::now()->diffInSeconds(ReminderSchedule::outsideQuietHours(CarbonImmutable::now()))));

            return;
        }

        $this->sendEmail($registration, $mailLog);
        $this->sendWhatsApp($registration, $gateways);
    }

    private function sendEmail(Registration $registration, RegistrationReminderLog $log): void
    {
        if (! filter_var($registration->email, FILTER_VALIDATE_EMAIL)) {
            $log->forceFill(['status' => RegistrationReminderLog::SKIPPED, 'detail' => 'No valid email address.'])->save();

            return;
        }

        try {
            $sent = Mail::to($registration->email)->sendNow(new PaymentReminderMail($registration->id));
        } catch (Throwable $e) {
            $log->forceFill([
                'status' => RegistrationReminderLog::FAILED,
                'detail' => mb_substr($e::class.': '.$e->getMessage(), 0, 255),
            ])->save();

            throw $e; // retried by the queue
        }

        // null: the org has neither its own SMTP nor Platform mail, so BrandedMail held it.
        $log->forceFill($sent === null
            ? ['status' => RegistrationReminderLog::SKIPPED, 'detail' => 'Held: connect your SMTP or add Platform mail.']
            : ['status' => RegistrationReminderLog::SENT, 'detail' => null, 'provider_id' => $sent->getMessageId(), 'sent_at' => now()]
        )->save();
    }

    private function sendWhatsApp(Registration $registration, WhatsAppGatewayFactory $gateways): void
    {
        $org = $registration->event->org;
        $settings = NotificationSettings::for($org);

        // No add-on (or WhatsApp off / no payment template): skip silently, nothing to log.
        if (! $settings->usesWhatsApp($org) || blank($settings->whatsapp_template_payment)) {
            return;
        }

        $log = $this->log($registration, 'whatsapp');
        if ($log->status === RegistrationReminderLog::SENT) {
            return;
        }

        $to = PhoneNumber::toWhatsApp($registration->phone);
        if ($to === null) {
            $log->forceFill(['status' => RegistrationReminderLog::SKIPPED, 'detail' => 'No valid mobile number.'])->save();

            return;
        }

        $brand = EmailBrand::for($registration);
        try {
            $result = $gateways->for($settings)->sendTemplate(new WhatsAppMessage($to, $settings->whatsapp_template_payment, $settings->whatsapp_language, [
                (string) $registration->participant_name,
                $brand->eventName,
                $brand->date?->format('D j M Y, g:i A') ?? '',
                ReminderLinks::pay($registration),
            ]));
        } catch (Throwable $e) {
            // Logged, not rethrown: a WhatsApp outage must not resend the email.
            $log->forceFill(['status' => RegistrationReminderLog::FAILED, 'detail' => mb_substr($e->getMessage(), 0, 255)])->save();
            report($e);

            return;
        }

        $log->forceFill(['status' => RegistrationReminderLog::SENT, 'detail' => null, 'provider_id' => $result->messageId, 'sent_at' => now()])->save();
    }

    /** This dispatch's row for $channel, created on first use (the scheduler pre-creates the mail row). */
    private function log(Registration $registration, string $channel): RegistrationReminderLog
    {
        $match = ['registration_id' => $registration->id, 'channel' => $channel, 'dispatch_key' => $this->dispatchKey];
        try {
            return RegistrationReminderLog::withoutGlobalScopes()->firstOrCreate($match, [
                'org_id' => $registration->org_id,
                'kind' => $this->kind,
                'sequence' => $this->sequence,
                'status' => RegistrationReminderLog::QUEUED,
            ]);
        } catch (UniqueConstraintViolationException) {
            return RegistrationReminderLog::withoutGlobalScopes()->where($match)->firstOrFail();
        }
    }

    public function failed(Throwable $e): void
    {
        RegistrationReminderLog::withoutGlobalScopes()
            ->where('registration_id', $this->registrationId)
            ->where('dispatch_key', $this->dispatchKey)
            ->where('channel', 'mail')
            ->where('status', '!=', RegistrationReminderLog::SENT)
            ->update(['status' => RegistrationReminderLog::FAILED]);
    }
}
