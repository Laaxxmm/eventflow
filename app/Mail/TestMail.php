<?php

namespace App\Mail;

use App\Models\Org;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * "Send test email" from Org Settings -> Notifications. Sent synchronously on purpose:
 * the organiser is checking their SMTP credentials and needs the result now.
 */
class TestMail extends Mailable
{
    use Queueable;

    public function __construct(
        public Org $org,
        public Address $sender,
        public string $route,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->sender,
            subject: __('EventFlow test email for :org', ['org' => $this->org->name]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.test', with: ['org' => $this->org, 'route' => $this->route]);
    }
}
