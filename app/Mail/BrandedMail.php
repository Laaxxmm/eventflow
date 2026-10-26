<?php

namespace App\Mail;

use App\Models\Registration;
use App\Notifications\EmailBrand;
use App\Notifications\NotificationDelivery;
use App\Notifications\NotificationSettings;
use App\Notifications\OrgMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\SentMessage;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Base for participant emails: always queued, branded with the event/org, sent through
 * the org's mailer (own SMTP or the Platform mail add-on) and logged in notification_deliveries.
 * With neither, the mail is held and logged as skipped.
 */
abstract class BrandedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const HELD_REASON = 'Held: connect your own SMTP (free) or add Platform mail in Settings -> Email.';

    /** Queue retries: 5 tries over about 40 minutes. */
    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [60, 300, 900, 1800];

    public function __construct(public int $registrationId) {}

    /** notification_deliveries.type */
    abstract public function type(): string;

    abstract protected function subjectLine(Registration $registration, EmailBrand $brand): string;

    /** Blade view under resources/views/emails. */
    abstract protected function template(): string;

    public function registration(): Registration
    {
        return Registration::withoutGlobalScopes()->with('event.org')->findOrFail($this->registrationId);
    }

    public function envelope(): Envelope
    {
        $registration = $this->registration();
        $brand = EmailBrand::for($registration);
        $org = $registration->event->org;
        $from = OrgMailer::from($org, NotificationSettings::for($org));

        return new Envelope(
            from: new Address($from->getAddress(), $from->getName()),
            replyTo: $brand->replyTo ? [new Address($brand->replyTo, $org->name)] : [],
            subject: $this->subjectLine($registration, $brand),
        );
    }

    public function content(): Content
    {
        $registration = $this->registration();

        return new Content(
            view: 'emails.'.$this->template(),
            text: 'emails.'.$this->template().'-text',
            with: [
                'registration' => $registration,
                'brand' => EmailBrand::for($registration),
            ],
        );
    }

    /**
     * Sends through the org's mailer and records the outcome. Exceptions propagate so
     * the queue retries; failed() marks the delivery failed after the last try.
     */
    public function send($mailer): ?SentMessage
    {
        $registration = $this->registration();
        $org = $registration->event->org;
        $delivery = NotificationDelivery::track($org->id, $registration->id, 'mail', $this->type(), $registration->email);

        $mailerName = OrgMailer::mailerName($org, NotificationSettings::for($org));
        if ($mailerName === null) {
            // No own SMTP and no Platform mail add-on: hold, and tell the organiser why.
            $delivery->markSkipped(self::HELD_REASON);

            return null;
        }
        $this->mailer($mailerName);
        if ($mailer instanceof MailerContract && ! $mailer instanceof MailFactory) {
            $mailer = app(MailFactory::class);
        }

        try {
            $sent = parent::send($mailer);
        } catch (Throwable $e) {
            $delivery->markFailed($e);
            throw $e;
        }

        $delivery->markSent($sent?->getMessageId());

        return $sent;
    }

    public function failed(Throwable $e): void
    {
        NotificationDelivery::withoutGlobalScopes()
            ->where('registration_id', $this->registrationId)
            ->where('channel', 'mail')
            ->where('type', $this->type())
            ->first()
            ?->markFailed($e, final: true);

        report($e);
    }
}
