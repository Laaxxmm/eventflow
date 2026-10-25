<?php

namespace App\Landing;

/**
 * Cheap checks on an uploaded logo (GD):
 *  - dark: the visible (non-transparent) pixels are mostly dark, so it disappears on dark
 *    heroes. Average relative luminance of opaque pixels below DARK_THRESHOLD.
 *  - transparent: the image has see-through pixels (a transparent PNG/WebP), which is what
 *    templates expect for their fixed logo slots.
 */
final class LogoInspector
{
    public const DARK_THRESHOLD = 0.3;

    private const SAMPLES = 48; // sample a 48x48 grid, enough for a logo

    /** @return array{dark: bool, transparent: bool, luminance: float|null} */
    public static function inspect(string $path): array
    {
        $image = @imagecreatefromstring((string) @file_get_contents($path));
        if ($image === false) {
            return ['dark' => false, 'transparent' => false, 'luminance' => null];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $sum = 0.0;
        $opaque = 0;
        $transparent = false;

        for ($i = 0; $i < self::SAMPLES; $i++) {
            for ($j = 0; $j < self::SAMPLES; $j++) {
                $x = (int) (($i + 0.5) * $width / self::SAMPLES);
                $y = (int) (($j + 0.5) * $height / self::SAMPLES);
                $rgba = imagecolorsforindex($image, imagecolorat($image, $x, $y));

                if ($rgba['alpha'] > 64) { // GD alpha: 0 opaque … 127 transparent
                    $transparent = true;

                    continue;
                }

                $sum += Theme::luminance(sprintf('#%02x%02x%02x', $rgba['red'], $rgba['green'], $rgba['blue']));
                $opaque++;
            }
        }
        imagedestroy($image);

        $luminance = $opaque > 0 ? $sum / $opaque : null;

        return [
            'dark' => $luminance !== null && $luminance < self::DARK_THRESHOLD,
            'transparent' => $transparent,
            'luminance' => $luminance,
        ];
    }
}
