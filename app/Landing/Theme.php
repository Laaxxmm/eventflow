<?php

namespace App\Landing;

use Illuminate\Support\Facades\Storage;

/**
 * CSS variables for a branded page, from an event's (or org's) branding colours.
 */
final class Theme
{
    public const FALLBACK = ['primary' => '#4f46e5', 'secondary' => '#0f172a'];

    public function __construct(
        public readonly string $primary,
        public readonly string $secondary,
    ) {}

    public static function for(?Branding $branding, ?array $colours = null): self
    {
        $colours = $branding
            ? ['primary' => $branding->primary, 'secondary' => $branding->secondary]
            : array_merge(self::FALLBACK, array_filter($colours ?? [], fn ($c) => is_string($c) && preg_match('/^#[0-9a-fA-F]{6}$/', $c)));

        return new self($colours['primary'], $colours['secondary']);
    }

    /** Inline style for <body>: --brand-* plus the Race Ink tokens tinted by this event's colours. */
    public function style(): string
    {
        $vars = sprintf(
            '--brand-primary:%s;--brand-on-primary:%s;--brand-secondary:%s;--brand-on-secondary:%s;--brand-accent-dark:%s;',
            $this->primary, self::ink($this->primary), $this->secondary, self::ink($this->secondary), $this->accentOnDark(),
        );

        foreach ($this->raceInk() as $name => $hex) {
            $vars .= sprintf('--ri-%s:%s;', $name, implode(' ', self::rgb($hex)));
        }

        return $vars;
    }

    public const RI_INK = '#121a33'; // Daylight Track navy

    /** Daylight Track defaults: indigo track, marigold heat, chalk paper, dusk midnight. */
    public const RI_TRACK = '#4b3fe0';

    public const RI_HEAT = '#f2a93b';

    public const RI_PAPER = '#f3f5fa';

    public const RI_MIDNIGHT = '#1b2244';

    /**
     * Race Ink tokens tinted by the organiser's colours, contrast-checked:
     * - track (panels, headings): primary, with ink or white text at 4.5:1 or better;
     * - track-text: the same hue darkened to 4.5:1 on paper, for Track used as text;
     * - heat (Register / CTAs, always 20px bold = WCAG large text): secondary, text at 3:1 or better;
     * - volt (accent on dark fields): primary or secondary if bright enough, else the Race Ink volt.
     * A colour that cannot reach the ratio with either text colour is darkened towards ink.
     *
     * @return array{track: string, on-track: string, track-text: string, heat: string, on-heat: string, volt: string}
     */
    public function raceInk(): array
    {
        // Only vivid brand colours tint the tokens; greys and near-blacks keep Race Ink's own.
        [$track, $onTrack] = self::panel(self::vivid($this->primary, 0.25) ? $this->primary : self::RI_TRACK, 4.5);
        [$heat, $onHeat] = self::panel(self::vivid($this->secondary, 0.45) ? $this->secondary : self::RI_HEAT, 3.0);

        $volt = self::RI_HEAT; // volt is retired: falls back to marigold
        foreach ([$this->primary, $this->secondary] as $colour) {
            if (self::contrast($colour, self::RI_MIDNIGHT) >= 7.0) {
                $volt = strtolower($colour);
                break;
            }
        }

        // Track used as text (numerals, rules) on paper: darkened until it reads at 4.5:1.
        $trackText = self::vivid($this->primary, 0.25) ? $this->primary : self::RI_TRACK;
        for ($t = 0.0; self::contrast($trackText, self::RI_PAPER) < 4.5 && $t <= 1.0; $t += 0.05) {
            $trackText = self::mix(self::vivid($this->primary, 0.25) ? $this->primary : self::RI_TRACK, self::RI_INK, $t);
        }

        return ['track' => $track, 'on-track' => $onTrack, 'track-text' => $trackText, 'heat' => $heat, 'on-heat' => $onHeat, 'volt' => $volt];
    }

    /**
     * A fill colour and the text colour that reads on it at $ratio or better.
     *
     * @return array{0: string, 1: string}
     */
    public static function panel(string $hex, float $ratio): array
    {
        $fill = strtolower($hex);
        for ($t = 0.0; $t <= 1.0; $t += 0.05) {
            $fill = self::mix($hex, self::RI_INK, $t);
            $white = self::contrast($fill, '#ffffff');
            $ink = self::contrast($fill, self::RI_INK);
            if (max($white, $ink) >= $ratio) {
                return [$fill, $white >= $ink ? '#ffffff' : self::RI_INK];
            }
        }

        return [self::RI_INK, '#ffffff'];
    }

    /** HSL saturation at least $minSaturation and lightness 0.25-0.85: a colour, not a grey/black/white. */
    public static function vivid(string $hex, float $minSaturation): bool
    {
        [$r, $g, $b] = array_map(fn (int $c) => $c / 255, self::rgb($hex));
        [$max, $min] = [max($r, $g, $b), min($r, $g, $b)];
        $l = ($max + $min) / 2;
        $sat = $max === $min ? 0.0 : ($max - $min) / (1 - abs(2 * $l - 1));

        return $sat >= $minSaturation && $l >= 0.25 && $l <= 0.85;
    }

    public static function contrast(string $a, string $b): float
    {
        [$hi, $lo] = [max(self::luminance($a), self::luminance($b)), min(self::luminance($a), self::luminance($b))];

        return ($hi + 0.05) / ($lo + 0.05);
    }

    public static function mix(string $a, string $b, float $t): string
    {
        [$ar, $ag, $ab] = self::rgb($a);
        [$br, $bg, $bb] = self::rgb($b);

        return sprintf('#%02x%02x%02x', (int) round($ar + ($br - $ar) * $t), (int) round($ag + ($bg - $ag) * $t), (int) round($ab + ($bb - $ab) * $t));
    }

    /** @return array{0: int, 1: int, 2: int} */
    public static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    /** A brand colour that stays readable as text on near-black (dark templates), else white. */
    public function accentOnDark(): string
    {
        foreach ([$this->primary, $this->secondary] as $colour) {
            if (self::luminance($colour) >= 0.18) {
                return $colour;
            }
        }

        return '#ffffff';
    }

    /** WCAG relative luminance of #rrggbb. */
    public static function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(function (string $c) {
            $c = hexdec($c) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /** Near-black or white, whichever reads better on $hex (WCAG relative luminance). */
    public static function ink(string $hex): string
    {
        [$r, $g, $b] = array_map(function (string $c) {
            $c = hexdec($c) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

        // Contrast against #0f172a (L≈0.0099) vs white (L=1).
        return ($luminance + 0.05) / 0.0599 >= 1.05 / ($luminance + 0.05) ? self::RI_INK : '#ffffff';
    }

    /** Public URL for a logo/hero path from config: absolute URLs pass through, others live on the public disk. */
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return preg_match('#^https?://#', $path) ? $path : Storage::disk('public')->url($path);
    }
}
