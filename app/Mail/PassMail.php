<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PassMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public int $registrationId) {}

    public function envelope(): Envelope
    {
        $registration = $this->registration();

        return new Envelope(
            subject: 'Registration confirmed: '.($registration->event?->name ?? 'your event').' — '.$registration->pass_no,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.pass',
            with: [
                'registration' => $this->registration(),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $registration = $this->registration();
        $disk = (string) config('eventflow.pass.disk', 'local');
        $code = (string) $registration->pass_no;

        return [
            Attachment::fromStorageDisk($disk, (string) $registration->pass_png_path)
                ->as('EventFlow-Participant-Pass-'.$code.'.png')
                ->withMime('image/png'),
            Attachment::fromStorageDisk($disk, (string) $registration->pass_pdf_path)
                ->as('EventFlow-Participant-Pass-'.$code.'.pdf')
                ->withMime('application/pdf'),
        ];
    }

    public function registration(): Registration
    {
        return Registration::query()->with('event.org')->findOrFail($this->registrationId);
    }
}
