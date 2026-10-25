<?php

namespace App\Certificates;

use App\Contracts\CertificateRenderer;
use App\Landing\Certificate;
use App\Models\Event;
use App\Models\IssuedCertificate;
use App\Models\Registration;
use App\Passes\LocalQr;
use App\RaceSheets\ResolvedConfig;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Print-ready A4 certificate PDFs (DomPDF) from the event's `certificate`
 * block: a premium gallery template (resources/views/certificates/templates/<key>)
 * or "upload", which stamps the text over the organiser's A4 artwork.
 *
 * Each registration gets one IssuedCertificate (EF-CERT number, verify QR).
 * The stored PDF is reused until anything printed on it changes: the
 * fingerprint is a hash of the rendered HTML, so a new template, palette,
 * name or logo re-issues the file.
 */
final class DomPdfCertificateRenderer implements CertificateRenderer
{
    /** Bump when templates change in a way that should re-issue stored PDFs. */
    public const VERSION = 'premium-1';

    public function __construct(private LocalQr $qr) {}

    public function render(Registration $r): CertificateFile
    {
        return $this->issue($r);
    }

    /** Store (or reuse) the participant's certificate PDF. $force re-renders even when nothing changed. */
    public function issue(Registration $r, bool $force = false): CertificateFile
    {
        $r->loadMissing('event.org');
        $certificate = IssuedCertificate::for($r);
        $disk = Storage::disk($this->disk());

        $html = $this->html($r, $certificate->cert_no);
        $fingerprint = hash('sha256', self::VERSION.$html);

        if (! $force && $certificate->fingerprint === $fingerprint && $certificate->pdf_path && $disk->exists($certificate->pdf_path)) {
            return new CertificateFile($certificate->pdf_path);
        }

        $path = 'certificates/'.$r->event_id.'/'.$certificate->cert_no.'.pdf';
        $disk->put($path, $this->pdf($html, $this->settings($r->event)->orientation));
        if ($certificate->pdf_path && $certificate->pdf_path !== $path) {
            $disk->delete($certificate->pdf_path);
        }

        $certificate->forceFill([
            'template' => $this->settings($r->event)->template,
            'fingerprint' => $fingerprint,
            'pdf_path' => $path,
            'issued_at' => now(),
        ])->save();

        return new CertificateFile($path);
    }

    /** Whether the stored PDF still matches what would be printed now. */
    public function isCurrent(Registration $r, ?IssuedCertificate $certificate): bool
    {
        return $certificate?->fingerprint !== null
            && $certificate->fingerprint === hash('sha256', self::VERSION.$this->html($r, $certificate->cert_no));
    }

    public function html(Registration $r, ?string $certNo = null): string
    {
        $r->loadMissing('event.org');
        $certNo ??= $r->exists ? IssuedCertificate::for($r)->cert_no : 'EF-CERT-SAMPLE00';

        return $this->view($this->data($r, $certNo));
    }

    /** A placeholder certificate for the gallery ("Download sample"). */
    public function sample(Event $event, string $template, ?string $orientation = null, array $overrides = [], string $participant = 'Ananya Krishnan'): CertificateData
    {
        $event->loadMissing('org');
        $copy = clone $event;
        $config = is_array($copy->config) ? $copy->config : [];
        $current = is_array($config['certificate'] ?? null) ? $config['certificate'] : [];
        unset($current['background']);
        $config['certificate'] = array_merge($current, $overrides, ['template' => $template] + ($orientation ? ['orientation' => $orientation] : []));
        $copy->config = $config;

        $distances = ResolvedConfig::for($copy)['distances'] ?? $event->distances ?? [];
        $registration = (new Registration)->forceFill([
            'pass_no' => 'EF-SAMPLE',
            'participant_name' => $participant,
            'distance_code' => $distances[0]['code'] ?? null,
            'answers' => ['bib' => '1024', 'finish_time' => '01:42:18', 'rank' => '12', 'age' => 29],
        ])->setRelation('event', $copy);

        return $this->data($registration, 'EF-CERT-SAMPLE00', sample: true);
    }

    public function view(CertificateData $c): string
    {
        $legacy = [ // variables the "upload" template stamps
            'orientation' => $c->orientation,
            'participant' => $c->participant,
            'eventName' => $c->eventName,
            'distance' => $c->distance,
            'category' => $c->category,
            'time' => $c->time,
            'date' => $c->date,
            'bib' => $c->bib ?? $c->certNo,
            'logo' => $c->logo,
            'layout' => config("eventflow.certificates.upload_layout.{$c->orientation}"),
        ];

        return view('certificates.templates.'.$c->template, ['c' => $c, 'p' => $c->palette, 'art' => CertificateArt::class] + $legacy + ['background' => $c->background])->render();
    }

    /** Render certificate HTML to an A4 PDF. */
    public function pdf(string $html, string $orientation): string
    {
        File::ensureDirectoryExists((string) config('dompdf.options.font_cache', storage_path('fonts')));

        return Pdf::loadHTML($html)
            ->setPaper('a4', $orientation)
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('chroot', base_path())
            ->output();
    }

    public function data(Registration $r, string $certNo, bool $sample = false): CertificateData
    {
        $event = $r->event;
        $settings = $this->settings($event);
        $resolved = ResolvedConfig::for($event);
        $answers = is_array($r->answers) ? $r->answers : [];

        $brand = array_merge((array) ($event->org?->colours ?? []), array_filter((array) ($event->colours ?? [])));
        $credentialKey = $settings->credential ?? 'completion';
        $signatories = array_values(array_filter($settings->signatories, fn ($s) => is_array($s))) ?: (array) config('eventflow.certificates.default_signatories');
        $signatories = array_map(fn ($s) => ['name' => filled($s['name'] ?? null) ? (string) $s['name'] : null, 'role' => (string) ($s['role'] ?? '')], array_slice(array_pad($signatories, 2, ['name' => null, 'role' => '']), 0, 2));
        $verifyUrl = Route::has('certificates.verify') ? route('certificates.verify', $certNo) : null;
        $venue = $event->venue ? (string) $event->venue : null;

        return new CertificateData(
            template: $settings->template,
            orientation: $settings->orientation,
            variant: $settings->variant === 'print' ? 'print' : 'screen',
            palette: Palette::resolve($settings->palette, $brand),
            credential: __((string) config("eventflow.certificates.credentials.{$credentialKey}", 'Certificate of Completion')),
            participant: (string) $r->participant_name,
            eventName: (string) $event->name,
            date: $event->date?->timezone('Asia/Kolkata')->format('d F Y'),
            venue: $venue,
            city: $venue && str_contains($venue, ',') ? trim((string) last(explode(',', $venue))) : $venue,
            distance: $this->distanceLabel($resolved, $event, $r->distance_code),
            category: self::filled($answers['category'] ?? null) ?? $this->ageBand($resolved, $answers['age'] ?? null),
            sport: $event->sport ? ucfirst(is_object($event->sport) ? $event->sport->value : (string) $event->sport) : null,
            bib: self::filled($answers['bib'] ?? null),
            rank: self::filled($answers['rank'] ?? $answers['position'] ?? null),
            time: self::filled($answers['finish_time'] ?? $answers['time'] ?? null),
            orgName: (string) ($event->org?->name ?? ''),
            logo: $this->logoDataUri($event),
            certNo: $certNo,
            verifyUrl: $verifyUrl,
            qr: $verifyUrl ? 'data:image/png;base64,'.base64_encode($this->qr->png($verifyUrl, 300)) : null,
            signatories: $signatories,
            stamp: $settings->stamp ?? true,
            sample: $sample,
            background: $settings->isUpload() ? $this->backgroundDataUri($settings) : null,
        );
    }

    /**
     * The event's certificate settings (sport pack defaults applied). Deprecated
     * keys map to their premium successor; unknown keys fall back to heritage-gold;
     * an orientation the template does not offer falls back to its first.
     */
    public function settings(Event $event): Certificate
    {
        $certificate = ResolvedConfig::for($event)['certificate'] ?? null;
        $data = is_array($certificate) ? $certificate : [];
        $gallery = (array) config('eventflow.certificates.gallery', []);

        $template = self::resolveTemplate((string) ($data['template'] ?? ''));

        $orientation = (string) ($data['orientation'] ?? 'landscape');
        $allowed = $template === 'upload' ? ['landscape', 'portrait'] : ($gallery[$template]['orientations'] ?? ['landscape']);
        if (! in_array($orientation, $allowed, true)) {
            $orientation = $allowed[0];
        }

        return new Certificate(
            template: $template,
            background: $data['background'] ?? null,
            orientation: $orientation,
            palette: is_string($data['palette'] ?? null) ? $data['palette'] : null,
            credential: in_array($data['credential'] ?? null, ['completion', 'participation', 'merit'], true) ? $data['credential'] : null,
            variant: ($data['variant'] ?? null) === 'print' ? 'print' : null,
            signatories: is_array($data['signatories'] ?? null) ? $data['signatories'] : [],
            stamp: isset($data['stamp']) ? (bool) $data['stamp'] : null,
        );
    }

    /** Gallery key for a stored template id: aliases resolved, unknown → heritage-gold. */
    public static function resolveTemplate(string $id): string
    {
        if ($id === 'upload') {
            return 'upload';
        }
        $id = config("eventflow.certificates.aliases.{$id}", $id);

        return array_key_exists($id, (array) config('eventflow.certificates.gallery', [])) ? $id : 'heritage-gold';
    }

    public function disk(): string
    {
        return (string) config('eventflow.certificates.disk', 'local');
    }

    /** WCAG contrast ratio between two #rrggbb colours. */
    public static function contrast(string $a, string $b): float
    {
        return Palette::contrast($a, $b);
    }

    private function backgroundDataUri(Certificate $settings): string
    {
        $disk = Storage::disk($this->disk());
        $path = (string) $settings->background;

        if ($path === '' || ! $disk->exists($path)) {
            throw new RuntimeException("Certificate background [{$path}] was not found on the [{$this->disk()}] disk.");
        }

        $mime = str_ends_with(strtolower($path), '.png') ? 'image/png' : 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($disk->get($path));
    }

    /** The event's logo (public disk path in branding.logo) as a data URI. DomPDF cannot draw WebP. */
    private function logoDataUri(Event $event): ?string
    {
        $path = $event->config['branding']['logo'] ?? null;
        if (! is_string($path) || $path === '' || preg_match('#^https?://#', $path)) {
            return null;
        }

        $disk = Storage::disk('public');
        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => null,
        };

        return $mime && $disk->exists($path) ? 'data:'.$mime.';base64,'.base64_encode($disk->get($path)) : null;
    }

    private function distanceLabel(array $resolved, Event $event, ?string $code): ?string
    {
        if ($code === null) {
            return null;
        }
        foreach ($resolved['distances'] ?? $event->distances ?? [] as $distance) {
            if (($distance['code'] ?? null) === $code) {
                return (string) ($distance['label'] ?? $code);
            }
        }

        return $code;
    }

    private function ageBand(array $resolved, mixed $age): ?string
    {
        if (! is_numeric($age)) {
            return null;
        }
        foreach ($resolved['age_bands'] ?? [] as $band) {
            if ((int) $age >= (int) ($band['min'] ?? 0) && (int) $age <= (int) ($band['max'] ?? 200)) {
                return (string) $band['label'];
            }
        }

        return null;
    }

    private static function filled(mixed $value): ?string
    {
        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
    }
}
