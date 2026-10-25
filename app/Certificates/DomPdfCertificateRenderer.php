<?php

namespace App\Certificates;

use App\Contracts\CertificateRenderer;
use App\Landing\Certificate;
use App\Models\Event;
use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * A4 certificate PDFs from the event's YAML `certificate` block: a gallery
 * template (resources/views/certificates/templates/<key>) or "upload", which
 * prints the text over the organiser's A4 background.
 */
final class DomPdfCertificateRenderer implements CertificateRenderer
{
    public function render(Registration $r): CertificateFile
    {
        $r->loadMissing('event.org');
        $settings = $this->settings($r->event);

        $pdf = Pdf::loadHTML($this->html($r))
            ->setPaper('a4', $settings->orientation)
            ->output();

        $path = 'certificates/'.$r->event_id.'/'.$r->pass_no.'.pdf';
        Storage::disk($this->disk())->put($path, $pdf);

        return new CertificateFile($path);
    }

    public function html(Registration $r): string
    {
        $r->loadMissing('event.org');
        $event = $r->event;
        $settings = $this->settings($event);

        return view('certificates.templates.'.($settings->isUpload() ? 'upload' : $settings->template), [
            'orientation' => $settings->orientation,
            'background' => $settings->isUpload() ? $this->backgroundDataUri($settings) : null,
            'colours' => $this->colours($event),
            'participant' => $r->participant_name,
            'distance' => $this->distanceLabel($event, $r->distance_code),
            'eventName' => $event->name,
            'date' => $event->date?->timezone('Asia/Kolkata')->format('d F Y'),
            'venue' => $event->venue,
            'orgName' => $event->org?->name,
            'passNo' => $r->pass_no,
        ])->render();
    }

    /**
     * The event's certificate settings. Unknown gallery keys fall back to
     * "classic"; an orientation the template does not offer falls back to its first.
     */
    public function settings(Event $event): Certificate
    {
        $data = is_array($event->config['certificate'] ?? null) ? $event->config['certificate'] : [];
        $gallery = (array) config('eventflow.certificates.gallery', []);

        $template = (string) ($data['template'] ?? 'classic');
        if ($template !== 'upload' && ! isset($gallery[$template])) {
            $template = 'classic';
        }

        $orientation = (string) ($data['orientation'] ?? 'landscape');
        $allowed = $template === 'upload' ? ['landscape', 'portrait'] : ($gallery[$template]['orientations'] ?? ['landscape']);
        if (! in_array($orientation, $allowed, true)) {
            $orientation = $allowed[0];
        }

        return new Certificate(template: $template, background: $data['background'] ?? null, orientation: $orientation);
    }

    public function disk(): string
    {
        return (string) config('eventflow.certificates.disk', 'local');
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

    private function distanceLabel(Event $event, ?string $code): string
    {
        foreach ($event->config['distances'] ?? $event->distances ?? [] as $distance) {
            if (($distance['code'] ?? null) === $code) {
                return (string) ($distance['label'] ?? $code);
            }
        }

        return (string) $code;
    }

    /**
     * Org colours overridden by event colours, validated as #rrggbb, plus
     * readable variants: brand colours are often light (e.g. lime), so text
     * on white only uses them at 4.5:1 contrast or better.
     *
     * @return array{primary: string, secondary: string, primaryText: string, secondaryText: string, onPrimary: string}
     */
    private function colours(Event $event): array
    {
        $defaults = ['primary' => '#111a31', 'secondary' => '#5148d9'];
        $merged = array_merge($defaults, (array) ($event->org?->colours ?? []), (array) ($event->colours ?? []));

        foreach ($defaults as $key => $fallback) {
            $value = $merged[$key] ?? null;
            $merged[$key] = is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? $value : $fallback;
        }

        $ink = '#17213a';

        return [
            'primary' => $merged['primary'],
            'secondary' => $merged['secondary'],
            'primaryText' => self::contrast($merged['primary'], '#ffffff') >= 4.5 ? $merged['primary'] : $ink,
            'secondaryText' => self::contrast($merged['secondary'], '#ffffff') >= 4.5 ? $merged['secondary'] : $ink,
            'onPrimary' => self::contrast($merged['primary'], '#ffffff') >= 4.5 ? '#ffffff' : $ink,
        ];
    }

    /** WCAG contrast ratio between two #rrggbb colours. */
    public static function contrast(string $a, string $b): float
    {
        $luminance = function (string $hex): float {
            $channels = array_map(function (string $pair) {
                $c = hexdec($pair) / 255;

                return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            }, str_split(ltrim($hex, '#'), 2));

            return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
        };

        [$hi, $lo] = [max($luminance($a), $luminance($b)), min($luminance($a), $luminance($b))];

        return ($hi + 0.05) / ($lo + 0.05);
    }
}
