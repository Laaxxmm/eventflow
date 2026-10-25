<?php

namespace App\Http\Controllers;

use App\Certificates\BuildCertificateZip;
use App\Certificates\CertificateBatch;
use App\Certificates\DomPdfCertificateRenderer;
use App\Certificates\IssueCertificate;
use App\Models\Event;
use App\Models\IssuedCertificate;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Organiser certificates: pick a design, issue / re-issue per participant,
 * or queue a ZIP of every checked-in runner's A4 PDF. Org-scoped via route binding.
 */
class CertificateController extends Controller
{
    public function __construct(private DomPdfCertificateRenderer $templates) {}

    public function index(Request $request, Event $event): View
    {
        $batch = CertificateBatch::get($event->id);
        $search = trim((string) $request->query('q', ''));

        $participants = BuildCertificateZip::eligible($event)
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('participant_name', 'like', "%{$search}%")
                ->orWhere('pass_no', 'like', "%{$search}%")))
            ->orderBy('participant_name')
            ->paginate(20)
            ->withQueryString();

        $issued = IssuedCertificate::query()
            ->whereIn('registration_id', $participants->pluck('id'))
            ->get()
            ->keyBy('registration_id');

        return view('certificates.index', [
            'event' => $event,
            'settings' => $this->templates->settings($event),
            'eligibleCount' => BuildCertificateZip::eligible($event)->count(),
            'issuedCount' => IssuedCertificate::query()->where('event_id', $event->id)->whereNotNull('issued_at')->count(),
            'participants' => $participants,
            'issued' => $issued,
            'search' => $search,
            'batch' => $batch,
            'zipReady' => ($batch['state'] ?? null) === 'ready'
                && Storage::disk($this->templates->disk())->exists($batch['path'] ?? ''),
            'contact' => config('eventflow.certificates.custom_contact') ?: config('plans.brand.contact_email'),
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

    /** Download one participant's certificate, issuing it first if needed. */
    public function show(Event $event, Registration $registration): StreamedResponse
    {
        $this->eligibleOr404($event, $registration);

        $file = $this->templates->issue($registration);
        $certificate = IssuedCertificate::for($registration);

        return Storage::disk($this->templates->disk())
            ->download($file->pdfPath, $certificate->cert_no.'.pdf');
    }

    /** Queue a fresh render of one participant's certificate (same EF-CERT number). */
    public function reissue(Event $event, Registration $registration): RedirectResponse
    {
        $this->eligibleOr404($event, $registration);

        IssueCertificate::dispatch($registration->id, force: true);

        return back()->with('status', __('Certificate for :name is being issued.', ['name' => $registration->participant_name]));
    }

    private function eligibleOr404(Event $event, Registration $registration): void
    {
        abort_unless(BuildCertificateZip::eligible($event)->whereKey($registration->id)->exists(), 404);
    }
}
