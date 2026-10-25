<?php

use App\Landing\ConfigCompiler;
use App\Landing\InvalidEventConfig;
use App\Landing\YamlLandingSource;
use App\Models\Event;
use App\Models\Registration;
use App\Payments\PaymentStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Tests\Feature\Events\marathonData;
use function Tests\Feature\Events\syncMarathon;
use function Tests\Feature\Events\yamlFile;

require_once __DIR__.'/helpers.php';

it('loads the demo marathon YAML as an EventConfig', function () {
    $config = app(YamlLandingSource::class)->load('demo-runners', 'bengaluru-city-marathon-2027');

    expect($config->name)->toBe('Bengaluru City Marathon 2027')
        ->and($config->distances)->toHaveCount(4)
        ->and($config->date->utcOffset())->toBe(330);
});

it('fills sport pack defaults for keys the YAML omits', function () {
    $config = app(YamlLandingSource::class)->load('demo-skaters', 'chennai-open-skating-2027');

    expect($config->ageBands)->toHaveCount(7)
        ->and(collect($config->fields)->pluck('key'))->toContain('skate_type')
        ->and($config->raceSheet?->heatSize)->toBe(6);
});

it('throws ModelNotFoundException for an unknown event', function () {
    app(YamlLandingSource::class)->load('demo-runners', 'nope');
})->throws(ModelNotFoundException::class);

it('passes events:validate for every YAML in the repo', function () {
    $this->artisan('events:validate')->assertSuccessful();
});

it('fails events:validate on a schema error', function () {
    $data = marathonData();
    $data['branding']['primary'] = 'red';
    unset($data['venue']);

    $this->artisan('events:validate', ['paths' => [yamlFile($data)]])
        ->expectsOutputToContain('branding.primary')
        ->assertFailed();
});

it('fails events:validate on unparseable YAML and on pack distances without prices', function () {
    $broken = yamlFile([], 'broken.yaml');
    file_put_contents($broken, "org: [unclosed\n");

    $data = marathonData();
    unset($data['distances']); // running pack distances carry no price_inr

    $this->artisan('events:validate', ['paths' => [$broken, yamlFile($data, 'unpriced.yaml')]])->assertFailed();
});

it('rejects a schema failure when compiling', function () {
    $data = marathonData();
    $data['distances'][0]['price_inr'] = -1;

    app(ConfigCompiler::class)->compile($data);
})->throws(InvalidEventConfig::class);

it('upserts by org + slug into events.config', function () {
    $event = syncMarathon();

    expect($event->org->slug)->toBe('demo-runners')
        ->and($event->status)->toBe('published')
        ->and($event->config['slug'])->toBe('bengaluru-city-marathon-2027')
        ->and($event->price_paise)->toBe(59900)
        ->and($event->capacity)->toBe(4000);

    $data = marathonData();
    $data['name'] = 'Renamed Marathon';
    $this->artisan('events:sync', ['paths' => [yamlFile($data)]])->assertSuccessful();

    expect(Event::count())->toBe(1)
        ->and($event->fresh()->name)->toBe('Renamed Marathon');
});

it('never deletes or breaks events that have registrations', function () {
    $event = syncMarathon();
    $other = $event->org->events()->create(['slug' => 'old-race', 'name' => 'Old', 'date' => '2026-01-01', 'venue' => 'X']);
    $kept = $event->org->events()->create(['slug' => 'old-paid-race', 'name' => 'Old paid', 'date' => '2026-01-01', 'venue' => 'X', 'status' => 'published']);
    Registration::create([
        'event_id' => $kept->id, 'pass_no' => Registration::newPassNo(), 'status' => PaymentStatus::Paid,
        'participant_name' => 'A', 'email' => 'a@example.com', 'phone' => '9876543210', 'distance_code' => '5K',
    ]);

    $this->artisan('events:sync', ['paths' => [base_path('events/demo/example-marathon.yaml')], '--prune' => true])->assertSuccessful();

    expect(Event::find($other->id))->toBeNull()
        ->and($kept->fresh()->status)->toBe('closed');

    // Dropping a distance that registrations use is refused.
    Registration::create([
        'event_id' => $event->id, 'pass_no' => Registration::newPassNo(), 'status' => PaymentStatus::Paid,
        'participant_name' => 'B', 'email' => 'b@example.com', 'phone' => '9876543210', 'distance_code' => 'FM',
    ]);
    $data = marathonData();
    array_pop($data['distances']);

    $this->artisan('events:sync', ['paths' => [yamlFile($data)]])->assertFailed();
    expect(collect($event->fresh()->distances)->pluck('code'))->toContain('FM');
});
