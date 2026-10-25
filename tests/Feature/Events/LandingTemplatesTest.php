<?php

use App\Models\User;

use function Tests\Feature\Events\syncMarathon;

require_once __DIR__.'/helpers.php';

dataset('landing templates', ['aurora', 'midnight', 'clean', 'festival', 'heritage']);

it('ships exactly five landing templates', function () {
    expect(array_keys(config('eventflow.landing_templates')))->toBe(['aurora', 'midnight', 'clean', 'festival', 'heritage']);
});

it('renders each template with the event, prices and registration form', function (string $template) {
    $event = syncMarathon();
    $event->update(['config' => array_merge($event->config, ['template' => $template])]);

    $this->get('/demo-runners/bengaluru-city-marathon-2027')
        ->assertOk()
        ->assertSee('data-template="'.$template.'"', false)
        ->assertSee('Bengaluru City Marathon 2027')
        ->assertSee('Half Marathon (21.1K)')
        ->assertSee('name="dob"', false);
})->with('landing templates');

it('falls back to aurora for an unknown template', function () {
    $event = syncMarathon();
    $event->update(['config' => array_merge($event->config, ['template' => 'neon'])]);

    $this->get('/demo-runners/bengaluru-city-marathon-2027')->assertOk()->assertSee('data-template="aurora"', false);
});

it('previews each template with sample data for organisers', function (string $template) {
    $event = syncMarathon();
    $owner = User::factory()->create(['org_id' => $event->org_id]);
    $owner->forceFill(['role' => 'owner'])->save();

    $this->actingAs($owner)->get(route('organiser.templates.preview', [$template, 'primary' => '#1d3b8b']))
        ->assertOk()
        ->assertSee('data-template="'.$template.'"', false)
        ->assertSee('--brand-primary:#1d3b8b', false);
})->with('landing templates');
