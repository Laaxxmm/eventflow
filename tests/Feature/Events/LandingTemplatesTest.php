<?php

use App\Landing\EventConfig;
use App\Landing\Theme;
use App\Models\User;

use function Tests\Feature\Events\syncMarathon;

require_once __DIR__.'/helpers.php';

dataset('landing templates', ['gun-lap', 'stadium-split', 'equipment-close', 'night-circuit', 'heritage-crest']);

it('ships exactly five Race Ink landing templates', function () {
    expect(array_keys(config('eventflow.landing_templates')))->toBe(['gun-lap', 'stadium-split', 'equipment-close', 'night-circuit', 'heritage-crest']);
});

it('renders each template as a full event page with Register, prices, schedule and sponsors', function (string $template) {
    $event = syncMarathon();
    $event->update(['config' => array_merge($event->config, ['template' => $template])]);

    $this->get('/demo-runners/bengaluru-city-marathon-2027')
        ->assertOk()
        ->assertSee('data-template="'.$template.'"', false)
        ->assertSee('Bengaluru City Marathon 2027')
        ->assertSee('Half Marathon')
        ->assertSee('₹1,499')
        ->assertSee('href="#register"', false)          // Register CTA in the page
        ->assertSee('id="register"', false)             // ... and the form it points at
        ->assertSee('name="dob"', false)
        ->assertSee('Full Marathon flag-off')           // schedule from YAML
        ->assertSee('Garden City Sports')               // sponsors from YAML
        ->assertSee('--ri-heat:', false);               // brand-tinted Race Ink tokens
})->with('landing templates');

it('keeps Register one tap away on mobile', function () {
    syncMarathon();

    $this->get('/demo-runners/bengaluru-city-marathon-2027')
        ->assertSee('fixed inset-x-0 bottom-0', false)
        ->assertSee('From ₹599');
});

it('renders old template keys as their Race Ink successor', function (string $old, string $new) {
    $event = syncMarathon();
    $event->update(['config' => array_merge($event->config, ['template' => $old])]);

    $this->get('/demo-runners/bengaluru-city-marathon-2027')->assertOk()->assertSee('data-template="'.$new.'"', false);
})->with([
    ['aurora', 'gun-lap'], ['midnight', 'night-circuit'], ['clean', 'stadium-split'], ['festival', 'equipment-close'], ['heritage', 'heritage-crest'],
]);

it('falls back to gun-lap for an unknown template', function () {
    expect(EventConfig::resolveTemplate('neon'))->toBe('gun-lap');

    $event = syncMarathon();
    $event->update(['config' => array_merge($event->config, ['template' => 'neon'])]);

    $this->get('/demo-runners/bengaluru-city-marathon-2027')->assertOk()->assertSee('data-template="gun-lap"', false);
});

it('tints Track and Heat with brand colours only at safe contrast', function (string $primary, string $secondary) {
    $tokens = (new Theme($primary, $secondary))->raceInk();

    expect(Theme::contrast($tokens['track'], $tokens['on-track']))->toBeGreaterThanOrEqual(4.5)
        ->and(Theme::contrast($tokens['heat'], $tokens['on-heat']))->toBeGreaterThanOrEqual(3.0)   // Heat buttons are 20px bold
        ->and(Theme::contrast($tokens['track-text'], Theme::RI_PAPER))->toBeGreaterThanOrEqual(4.5)
        ->and(Theme::contrast($tokens['volt'], Theme::RI_MIDNIGHT))->toBeGreaterThanOrEqual(7.0);
})->with([
    'lime / coral (demo)' => ['#d9ff45', '#ff6e61'],
    'indigo / slate' => ['#4f46e5', '#0f172a'],
    'yellow / orange' => ['#ffd400', '#ff8a00'],
    'grey / black' => ['#888888', '#111111'],
    'navy / red' => ['#1d3b8b', '#e8553f'],
    'Daylight indigo / marigold' => ['#4b3fe0', '#f2a93b'],
    'pale lilac / pale chalk' => ['#eceaff', '#f3f5fa'],
]);

it('keeps the Daylight Track defaults when brand colours are greys or near-black', function () {
    $tokens = (new Theme('#888888', '#0f172a'))->raceInk();

    expect($tokens['track'])->toBe('#4b3fe0')->and($tokens['on-track'])->toBe('#ffffff')
        ->and($tokens['heat'])->toBe('#f2a93b')->and($tokens['on-heat'])->toBe('#121a33')
        ->and($tokens['track-text'])->toBe('#4b3fe0')
        ->and($tokens['volt'])->toBe('#f2a93b');
});

it('meets the Daylight Track contrast pairs', function (string $fg, string $bg, float $min) {
    expect(Theme::contrast($fg, $bg))->toBeGreaterThanOrEqual($min);
})->with([
    'navy on chalk' => ['#121a33', '#f3f5fa', 4.5],
    'muted on chalk' => ['#4a5372', '#f3f5fa', 4.5],
    'indigo on white' => ['#4b3fe0', '#ffffff', 4.5],
    'white on indigo' => ['#ffffff', '#4b3fe0', 4.5],
    'navy on marigold' => ['#121a33', '#f2a93b', 4.5],
    'deep indigo on lilac' => ['#2b2390', '#eceaff', 4.5],
    'paid' => ['#0b4a38', '#e3f4ee', 4.5],
    'unpaid' => ['#6b4a12', '#fdf0da', 4.5],
    'suspended' => ['#6e1414', '#fbe4e4', 4.5],
    'ended' => ['#3b4463', '#dde2ee', 4.5],
]);

it('stops motion for visitors who prefer reduced motion', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain('@media (prefers-reduced-motion: reduce)')
        ->toContain('.ef-spin, [data-motion] { animation: none !important;');
});

it('previews each template with sample data for organisers', function (string $template) {
    $event = syncMarathon();
    $owner = User::factory()->create(['org_id' => $event->org_id]);
    $owner->forceFill(['role' => 'owner'])->save();

    $this->actingAs($owner)->get(route('organiser.templates.preview', [$template, 'primary' => '#1d3b8b']))
        ->assertOk()
        ->assertSee('data-template="'.$template.'"', false)
        ->assertSee('--brand-primary:#1d3b8b', false)
        ->assertSee('--ri-track:29 59 139', false);
})->with('landing templates');
