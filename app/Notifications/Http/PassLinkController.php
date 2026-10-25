<?php

namespace App\Notifications\Http;

use App\Contracts\PassGenerator;
use App\Models\Registration;
use App\Payments\PaymentStatus;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * GET /passes/{registration}/pass.pdf (signed): the "View pass" link in emails and
 * the WhatsApp document header. Generates the pass on first open if needed.
 */
class PassLinkController extends Controller
{
    public function __invoke(int $registration): StreamedResponse
    {
        $registration = Registration::withoutGlobalScopes()->with('event.org')->findOrFail($registration);
        abort_unless($registration->status === PaymentStatus::Paid, 404);

        $disk = Storage::disk((string) config('eventflow.pass.disk', 'local'));
        if (! $registration->pass_pdf_path || ! $disk->exists($registration->pass_pdf_path)) {
            $files = app(PassGenerator::class)->generate($registration);
            $registration->forceFill(['pass_png_path' => $files->pngPath, 'pass_pdf_path' => $files->pdfPath])->save();
        }

        return $disk->response($registration->pass_pdf_path, 'Pass-'.$registration->pass_no.'.pdf', [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, max-age=3600',
        ], 'inline');
    }
}
