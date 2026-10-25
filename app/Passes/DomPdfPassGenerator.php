<?php

namespace App\Passes;

use App\Contracts\PassGenerator;
use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Geometry\Factories\RectangleFactory;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\FontFactory;

/**
 * PNG (Intervention) + PDF (DomPDF) following the prototype participant pass:
 * navy strip, org, event, participant, EF- number, locally signed QR.
 */
final class DomPdfPassGenerator implements PassGenerator
{
    public function __construct(private LocalQr $qr) {}

    public function generate(Registration $r): PassFiles
    {
        $r->loadMissing('event.org');

        $colours = $this->colours($r);
        $payload = SignedPassQr::payload((string) $r->pass_no);
        $qrPng = $this->qr->png($payload, 360);

        $disk = (string) config('eventflow.pass.disk', 'local');
        $base = 'passes/'.$r->id.'/'.$r->pass_no;
        $pngPath = $base.'.png';
        $pdfPath = $base.'.pdf';

        Storage::disk($disk)->put($pngPath, $this->renderPng($r, $colours, $qrPng));
        Storage::disk($disk)->put($pdfPath, $this->renderPdf($r, $colours, $qrPng));

        return new PassFiles($pngPath, $pdfPath);
    }

    /**
     * @param  array{primary: string, secondary: string, accent: string, background: string, onPrimary: string, secondaryText: string}  $colours
     */
    private function renderPng(Registration $r, array $colours, string $qrPng): string
    {
        $font = __DIR__.'/fonts/DejaVuSans.ttf';
        $bold = __DIR__.'/fonts/DejaVuSans-Bold.ttf';
        $manager = new ImageManager(new Driver);
        $image = $manager->create(760, 1040);
        $image->fill('#f3f5fb');

        $image->drawRectangle(24, 24, function (RectangleFactory $rect) use ($colours) {
            $rect->size(712, 992);
            $rect->background($colours['background']);
            $rect->border('#e4e9f2', 1);
        });

        $image->drawRectangle(24, 24, function (RectangleFactory $rect) use ($colours) {
            $rect->size(712, 56);
            $rect->background($colours['primary']);
        });

        $image->text('OFFICIAL PARTICIPANT PASS', 44, 60, function (FontFactory $f) use ($bold, $colours) {
            $f->filename($bold);
            $f->size(16);
            $f->color($colours['onPrimary']);
            $f->valign('middle');
        });

        $image->text('REGISTRATION PASS', 520, 60, function (FontFactory $f) use ($bold, $colours) {
            $f->filename($bold);
            $f->size(14);
            $f->color($colours['onPrimary']);
            $f->align('right');
            $f->valign('middle');
        });

        $org = (string) ($r->event?->org?->name ?? 'EventFlow');
        $image->text($this->clip($org, 42), 44, 130, function (FontFactory $f) use ($bold, $colours) {
            $f->filename($bold);
            $f->size(16);
            $f->color($colours['secondaryText']);
        });

        $image->text($this->clip((string) ($r->event?->name ?? 'Event'), 32), 44, 180, function (FontFactory $f) use ($bold) {
            $f->filename($bold);
            $f->size(32);
            $f->color('#17213a');
            $f->wrap(680);
            $f->lineHeight(1.25);
        });

        $when = $r->event?->date?->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?? 'Date to be announced';
        $venue = (string) ($r->event?->venue ?? '');
        $image->text($this->clip($when.'  ·  '.$venue, 70), 44, 250, function (FontFactory $f) use ($font) {
            $f->filename($font);
            $f->size(16);
            $f->color('#79849a');
        });

        $this->label($image, 'PARTICIPANT NAME', 44, 310, $font);
        $image->text($this->clip((string) $r->participant_name, 28), 44, 348, function (FontFactory $f) use ($bold) {
            $f->filename($bold);
            $f->size(28);
            $f->color('#17213a');
        });

        $this->label($image, 'MOBILE NUMBER', 44, 410, $font);
        $image->text((string) $r->phone, 44, 446, function (FontFactory $f) use ($bold) {
            $f->filename($bold);
            $f->size(18);
            $f->color('#17213a');
        });

        $this->label($image, 'RACE CATEGORY', 380, 410, $font);
        $image->text($this->clip((string) ($r->distance_code ?: 'Open'), 18), 380, 446, function (FontFactory $f) use ($bold) {
            $f->filename($bold);
            $f->size(18);
            $f->color('#17213a');
        });

        $image->drawRectangle(44, 500, function (RectangleFactory $rect) {
            $rect->size(400, 92);
            $rect->background('#f2f1ff');
            $rect->border('#aaa4ff', 2);
        });
        $this->label($image, 'UNIQUE REGISTRATION NUMBER', 60, 528, $font);
        $image->text((string) $r->pass_no, 60, 568, function (FontFactory $f) use ($bold) {
            $f->filename($bold);
            $f->size(22);
            $f->color('#4037bf');
        });

        $qr = $manager->read($qrPng)->resize(220, 220);
        $image->place($qr, 'top-left', 490, 500);

        $image->text('SCAN AT CHECK-IN', 510, 740, function (FontFactory $f) use ($bold) {
            $f->filename($bold);
            $f->size(12);
            $f->color('#17213a');
        });

        $image->text('Powered by EventFlow', 44, 980, function (FontFactory $f) use ($font) {
            $f->filename($font);
            $f->size(14);
            $f->color('#79849a');
        });

        return (string) $image->toPng();
    }

    private function label(mixed $image, string $text, int $x, int $y, string $font): void
    {
        $image->text($text, $x, $y, function (FontFactory $f) use ($font) {
            $f->filename($font);
            $f->size(11);
            $f->color('#8791a5');
        });
    }

    /**
     * @param  array{primary: string, secondary: string, accent: string, background: string, onPrimary: string, secondaryText: string}  $colours
     */
    private function renderPdf(Registration $r, array $colours, string $qrPng): string
    {
        $html = view('passes.pdf', [
            'registration' => $r,
            'colours' => $colours,
            'qrDataUri' => 'data:image/png;base64,'.base64_encode($qrPng),
            'when' => $r->event?->date?->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?? 'Date to be announced',
        ])->render();

        return Pdf::loadHTML($html)->setPaper('a4', 'portrait')->output();
    }

    /**
     * Brand colours (org, then event) validated as #rrggbb, plus readable text
     * colours: brand colours are often light (the prototype's lime), so text
     * only uses them at 4.5:1 contrast against what it sits on.
     *
     * @return array{primary: string, secondary: string, accent: string, background: string, onPrimary: string, secondaryText: string}
     */
    public function colours(Registration $r): array
    {
        $event = is_array($r->event?->colours) ? $r->event->colours : [];
        $org = is_array($r->event?->org?->colours) ? $r->event->org->colours : [];
        $merged = array_merge([
            'primary' => '#111a31',
            'secondary' => '#5148d9',
            'accent' => '#d3f36b',
            'background' => '#ffffff',
        ], $org, $event);

        foreach (['primary', 'secondary', 'accent', 'background'] as $key) {
            $value = $merged[$key] ?? '';
            $merged[$key] = is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value)
                ? $value
                : ['primary' => '#111a31', 'secondary' => '#5148d9', 'accent' => '#d3f36b', 'background' => '#ffffff'][$key];
        }

        $merged['onPrimary'] = self::readable($merged['accent'], $merged['primary']);
        $merged['secondaryText'] = self::readable($merged['secondary'], $merged['background']);

        return $merged;
    }

    /** $preferred on $background if it reaches 4.5:1, else white or ink, whichever reads better. */
    public static function readable(string $preferred, string $background): string
    {
        if (self::contrast($preferred, $background) >= 4.5) {
            return $preferred;
        }

        return self::contrast('#ffffff', $background) >= self::contrast('#111a31', $background) ? '#ffffff' : '#111a31';
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

    private function clip(string $value, int $max): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return mb_strlen($value) > $max ? mb_substr($value, 0, $max - 1).'…' : $value;
    }
}
