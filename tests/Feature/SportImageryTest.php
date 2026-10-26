<?php

use App\View\SportImagery;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->dir = public_path('images/race-ink/test-sport');
    File::ensureDirectoryExists($this->dir);
    foreach (['02.jpg', '01.webp', 'notes.txt', '03.JPEG'] as $file) {
        File::put($this->dir.'/'.$file, 'x');
    }
});

afterEach(fn () => File::deleteDirectory($this->dir));

it('lists a set\'s photos in file-name order, ignoring other files', function () {
    expect(SportImagery::all('test-sport'))->toBe([
        asset('images/race-ink/test-sport/01.webp'),
        asset('images/race-ink/test-sport/02.jpg'),
        asset('images/race-ink/test-sport/03.JPEG'),
    ]);
});

it('wraps around a set and falls back to generic or null', function () {
    expect(SportImagery::pick('test-sport', 4))->toBe(asset('images/race-ink/test-sport/02.jpg'))
        ->and(SportImagery::pick('no-such-sport'))->toBe(SportImagery::all('generic')[0] ?? null);
});
