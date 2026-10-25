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

    /** Inline style for <body>: --brand-primary, --brand-secondary and readable text on each. */
    public function style(): string
    {
        return sprintf(
            '--brand-primary:%s;--brand-on-primary:%s;--brand-secondary:%s;--brand-on-secondary:%s;--brand-accent-dark:%s;',
            $this->primary, self::ink($this->primary), $this->secondary, self::ink($this->secondary), $this->accentOnDark(),
        );
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
        return ($luminance + 0.05) / 0.0599 >= 1.05 / ($luminance + 0.05) ? '#0f172a' : '#ffffff';
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
