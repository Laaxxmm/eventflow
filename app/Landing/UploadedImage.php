<?php

namespace App\Landing;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Organiser uploads (hero, share image, logo) resized before they are stored, so race pages never
 * serve a 6 MB camera original. Photos become JPEG (2400 px long side), logos PNG (keeps
 * transparency, 1000 px). A file GD cannot read is stored as uploaded.
 */
final class UploadedImage
{
    public const PHOTO_MAX = 2400;

    public const LOGO_MAX = 1000;

    public static function photo(UploadedFile $file, string $dir): string
    {
        return self::store($file, $dir, self::PHOTO_MAX, false);
    }

    public static function logo(UploadedFile $file, string $dir): string
    {
        return self::store($file, $dir, self::LOGO_MAX, true);
    }

    private static function store(UploadedFile $file, string $dir, int $max, bool $png): string
    {
        try {
            $image = ImageManager::gd()->read($file->getRealPath())->scaleDown($max, $max);
            $path = $dir.'/'.Str::random(40).($png ? '.png' : '.jpg');
            Storage::disk('public')->put($path, (string) ($png ? $image->toPng() : $image->toJpeg(82)), 'public');

            return $path;
        } catch (Throwable) {
            return $file->storePublicly($dir, 'public');
        }
    }
}
