<?php

use Illuminate\Support\Facades\Http;

it('needs a stock photo key', function () {
    config(['services.unsplash.key' => null, 'services.pexels.key' => null]);

    $this->artisan('imagery:fetch')->assertFailed();
});

it('downloads a slot, keeps it under 400 KB and credits it', function () {
    $public = sys_get_temp_dir().'/imagery-'.uniqid();
    mkdir($public.'/images/race-ink', 0755, true);
    file_put_contents($public.'/images/race-ink/CREDITS.md', "| File |\n");
    app()->usePublicPath($public);
    config(['services.unsplash.key' => 'test-key']);

    $img = imagecreatetruecolor(3000, 1500);
    ob_start();
    imagejpeg($img, null, 95);
    $jpeg = ob_get_clean();

    Http::fake([
        'api.unsplash.com/search/*' => Http::response(['results' => [[
            'urls' => ['raw' => 'https://images.unsplash.com/photo-1?ixid=x'],
            'links' => ['html' => 'https://unsplash.com/photos/abc', 'download_location' => 'https://api.unsplash.com/photos/abc/download'],
            'user' => ['name' => 'Asha Photographer'],
        ]]]),
        'api.unsplash.com/photos/*' => Http::response([]),
        'images.unsplash.com/*' => Http::response($jpeg),
    ]);

    $this->artisan('imagery:fetch', ['--only' => 'running/01'])->assertSuccessful();

    $file = $public.'/images/race-ink/running/01.jpg';
    expect(getimagesize($file)[0])->toBe(2400)
        ->and(filesize($file))->toBeLessThan(400 * 1024)
        ->and(file_get_contents($public.'/images/race-ink/CREDITS.md'))
        ->toContain('| `running/01.jpg` | https://unsplash.com/photos/abc | Asha Photographer | Unsplash License |');
});
