<?php

namespace App\Http\Controllers;

use App\Certificates\BuildCertificateZip;
use App\Certificates\CertificateBatch;
use App\Certificates\DomPdfCertificateRenderer;
use App\Contracts\CertificateRenderer;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Organiser certificates: one click queues a ZIP of every checked-in runner's
 * A4 PDF; single certificates download directly. Org-scoped via route binding.
 */
class CertificateController extends Controller
{
    public function __construct(private DomPdfCertificateRenderer $templates) {}

    public function index(Event $event): View
    {
        $batch = CertificateBatch::get($event->id);

        return view('certificates.index', [
            'event' => $event,
            'settings' => $this->templates->settings($event),
            'eligibleCount' => BuildCertificateZip::eligible($event)->count(),
            'batch' => $batch,
            'zipReady' => ($batch['state'] ?? null) === 'ready'
                && Storage::disk($this->templates->disk())->exists($batch['path'] ?? ''),
            'contact' => config('eventflow.certificates.custom_contact'),
        ]);
    }

    public function store(Event $event): RedirectResponse
    {
        $route = redirect()->route('reports.certificates', $event);

        if (BuildCertificateZip::eligible($event)->doesntExist()) {
            return $route->with('status', __('No checked-in runners yet, so there are no certificates to generate.'));
        }

        if (CertificateBatch::inProgress($event->id)) {
            return $route->with('status', __('Certificates are already being generated.'));
        }

        CertificateBatch::put($event->id, 'queued');
        BuildCertificateZip::dispatch($event->id);

        return $route->with('status', __('Generating certificates. Refresh this page in a minute to download the ZIP.'));
    }

    public function download(Event $event): StreamedResponse
    {
        $batch = CertificateBatch::get($event->id);
        $disk = Storage::disk($this->templates->disk());

        abort_unless(($batch['state'] ?? null) === 'ready' && $disk->exists($batch['path'] ?? ''), 404);

        return $disk->download($batch['path'], basename($batch['path']));
    }

    public function show(Event $event, Registration $registration, CertificateRenderer $renderer): StreamedResponse
    {
        abort_unless(
            BuildCertificateZip::eligible($event)->whereKey($registration->id)->exists(),
            404,
        );

        $file = $renderer->render($registration);

        return Storage::disk($this->templates->disk())
            ->download($file->pdfPath, $registration->pass_no.'-certificate.pdf');
    }
}
