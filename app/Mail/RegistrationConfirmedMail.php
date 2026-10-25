<?php

namespace App\Mail;

use App\Contracts\PassGenerator;
use App\Models\Registration;
use App\Notifications\EmailBrand;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Storage;

/**
 * Sent once payment is confirmed: summary, payment receipt, EF- number, QR note,
 * with the pass PDF (and PNG) attached. Queued by App\Listeners\SendPass after the
 * pass is generated (via PassMail).
 */
class RegistrationConfirmedMail extends BrandedMail
{
    public function type(): string
    {
        return 'registration_confirmed';
    }

    protected function subjectLine(Registration $registration, EmailBrand $brand): string
    {
        return __('You\'re in: :event — pass :pass', ['event' => $brand->eventName, 'pass' => $registration->pass_no]);
    }

    protected function template(): string
    {
        return 'registration-confirmed';
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        $registration = $this->registration();
        $disk = (string) config('eventflow.pass.disk', 'local');

        if (! $registration->pass_pdf_path || ! Storage::disk($disk)->exists($registration->pass_pdf_path)) {
            $files = app(PassGenerator::class)->generate($registration);
            $registration->forceFill(['pass_png_path' => $files->pngPath, 'pass_pdf_path' => $files->pdfPath])->save();
        }

        $name = 'Pass-'.$registration->pass_no;
        $attachments = [
            Attachment::fromStorageDisk($disk, $registration->pass_pdf_path)->as($name.'.pdf')->withMime('application/pdf'),
        ];

        if ($registration->pass_png_path && Storage::disk($disk)->exists($registration->pass_png_path)) {
            $attachments[] = Attachment::fromStorageDisk($disk, $registration->pass_png_path)->as($name.'.png')->withMime('image/png');
        }

        return $attachments;
    }
}
