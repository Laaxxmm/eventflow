<?php

namespace App\Certificates;

/**
 * Colours for one certificate: the event's brand colours made print-safe, or a
 * neutral gold / slate / ink set (config('eventflow.certificates.palettes')).
 *
 * accent / accent2 are safe for text on light paper (WCAG 4.5:1 on white);
 * accentFill / accent2Fill are the raw brand colours for panels and bars, with
 * onAccent the readable text colour on top of accentFill.
 */
final readonly class Palette
{
    public function __construct(
        public string $key,
        public string $accent,
        public string $accent2,
        public string $accentFill,
        public string $accent2Fill,
        public string $onAccent,
        public string $ink,
        public string $muted,
        public string $paper,
        public string $foil,
    ) {}

    /**
     * @param  array{primary?: string|null, secondary?: string|null}  $brand
     */
    public static function resolve(?string $choice, array $brand): self
    {
        $palettes = (array) config('eventflow.certificates.palettes', []);
        $primary = self::hex($brand['primary'] ?? null);
        $secondary = self::hex($brand['secondary'] ?? null);

        $key = $choice !== null && isset($palettes[$choice]) ? $choice : ($primary ? 'brand' : 'gold');
        if ($key === 'brand' && ! $primary) {
            $key = 'gold';
        }

        if ($key !== 'brand') {
            $p = $palettes[$key];

            return new self(
                key: $key,
                accent: $p['accent'],
                accent2: $p['accent2'],
                accentFill: $p['accent'],
                accent2Fill: $p['accent2'],
                onAccent: '#ffffff',
                ink: $p['ink'],
                muted: self::mix($p['ink'], '#ffffff', 0.45),
                paper: $key === 'slate' ? '#f7f8fa' : '#fbf8f1',
                foil: $p['foil'],
            );
        }

        $secondary ??= '#1f2a44';

        return new self(
            key: 'brand',
            accent: self::readable($primary),
            accent2: self::readable($secondary),
            accentFill: $primary,
            accent2Fill: $secondary,
            onAccent: self::contrast($primary, '#ffffff') >= 4.5 ? '#ffffff' : '#141a2a',
            ink: '#1c2333',
            muted: '#5d6577',
            paper: '#fbf9f4',
            foil: 'gold',
        );
    }

    /** Darken a colour (mix towards near-black) until it reads at 4.5:1 on white. */
    public static function readable(string $hex): string
    {
        $c = $hex;
        for ($t = 0.0; self::contrast($c, '#ffffff') < 4.5 && $t <= 1.0; $t += 0.05) {
            $c = self::mix($hex, '#10131c', $t);
        }

        return $c;
    }

    /** Linear mix: t = 0 gives $a, t = 1 gives $b. */
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

    public static function contrast(string $a, string $b): float
    {
        $lum = function (string $hex): float {
            $c = array_map(function (int $v) {
                $v /= 255;

                return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
            }, self::rgb($hex));

            return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
        };
        [$hi, $lo] = [max($lum($a), $lum($b)), min($lum($a), $lum($b))];

        return ($hi + 0.05) / ($lo + 0.05);
    }

    private static function hex(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? strtolower($value) : null;
    }
}
