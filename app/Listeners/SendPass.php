<?php

namespace App\Listeners;

use App\Contracts\PassGenerator;
use App\Events\RegistrationPaid;
use App\Mail\PassMail;
use App\Models\Registration;
use App\Payments\PaymentStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Queued. Generates the EF- pass and queues the mailable after payment commits.
 */
class SendPass implements ShouldQueue
{
    public function handle(RegistrationPaid $event): void
    {
        $registration = Registration::query()
            ->with('event.org')
            ->find($event->registrationId);

        if (! $registration || $registration->status !== PaymentStatus::Paid) {
            return;
        }

        $this->ensurePassNo($registration);

        $files = app(PassGenerator::class)->generate($registration);

        $registration->forceFill([
            'pass_png_path' => $files->pngPath,
            'pass_pdf_path' => $files->pdfPath,
        ])->save();

        if (! filter_var($registration->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        Mail::to($registration->email)->send(new PassMail($registration->id));
    }

    private function ensurePassNo(Registration $registration): void
    {
        $pattern = (string) config('eventflow.pass.pattern');
        if (is_string($registration->pass_no) && preg_match($pattern, $registration->pass_no) === 1) {
            return;
        }

        do {
            $candidate = Registration::newPassNo();
        } while (Registration::query()->where('pass_no', $candidate)->exists());

        $registration->pass_no = $candidate;
        $registration->save();
    }
}
