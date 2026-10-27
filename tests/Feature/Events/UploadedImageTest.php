<?php

use App\Landing\UploadedImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('shrinks a camera-size hero photo to a 2400 px JPEG', function () {
    Storage::fake('public');

    $path = UploadedImage::photo(UploadedFile::fake()->image('hero.png', 4800, 2400), 'branding/1');

    [$width, $height] = getimagesize(Storage::disk('public')->path($path));

    expect($path)->toEndWith('.jpg')->and($width)->toBe(2400)->and($height)->toBe(1200);
});

it('keeps logos as PNG and never upscales them', function () {
    Storage::fake('public');

    $path = UploadedImage::logo(UploadedFile::fake()->image('logo.png', 600, 200), 'branding/1');

    expect($path)->toEndWith('.png')
        ->and(getimagesize(Storage::disk('public')->path($path))[0])->toBe(600);
});
