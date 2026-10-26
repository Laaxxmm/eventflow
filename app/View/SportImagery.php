<?php

namespace App\View;

/**
 * Per-sport placeholder photography for Race Ink pages, used until an organiser
 * uploads a hero photo. Files: public/images/race-ink/<set>/<n>.jpg (sets: running,
 * cycling, skating, generic, marketing). Credits: public/images/race-ink/CREDITS.md.
 * Missing files are fine: <x-ri.photo> draws a graphic fallback instead.
 */
final class SportImagery
{
    /** @var array<string, list<string>> */
    private static array $cache = [];

    /** @return list<string> Public URLs for a set, in file-name order. */
    public static function all(string $set): array
    {
        return self::$cache[$set] ??= (function () use ($set) {
            $dir = public_path('images/race-ink/'.basename($set));
            $files = is_dir($dir) ? glob($dir.'/*.{jpg,jpeg,webp}', GLOB_BRACE) : [];
            sort($files);

            return array_map(fn (string $f) => asset('images/race-ink/'.basename($set).'/'.basename($f)), $files ?: []);
        })();
    }

    /** The $n-th photo for a sport (wrapping), falling back to the generic set, or null. */
    public static function pick(?string $sport, int $n = 0): ?string
    {
        foreach ([$sport ?: 'generic', 'generic'] as $set) {
            $photos = self::all($set);
            if ($photos) {
                return $photos[$n % count($photos)];
            }
        }

        return null;
    }
}
