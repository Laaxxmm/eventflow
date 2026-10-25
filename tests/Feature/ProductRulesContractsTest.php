<?php

use App\Contracts\CertificateRenderer;
use App\Contracts\Entitlements;
use App\Contracts\RaceSheetBuilder;
use App\Contracts\SportPackRepository;
use App\Enums\Module;
use App\Enums\Sport;
use App\Landing\EventConfig;
use App\Models\Event;
use App\Models\Org;
use App\Models\SeasonPass;
use App\Sports\SportPack;
use App\Support\EventSchema;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Yaml\Yaml;

function yamlFile(string $path): array
{
    return Yaml::parseFile(base_path($path));
}

function sportPack(Sport $sport): SportPack
{
    return SportPack::fromArray(yamlFile("events/sports/{$sport->value}.yaml"));
}

it('ships a valid YAML pack for every sport', function (Sport $sport) {
    $data = yamlFile("events/sports/{$sport->value}.yaml");

    expect(EventSchema::errors($data, EventSchema::SPORT_PACK))->toBe([])
        ->and($data['sport'])->toBe($sport->value);
})->with(Sport::cases());

it('validates the skating demo after applying the skating pack', function () {
    $merged = sportPack(Sport::Skating)->applyTo(yamlFile('events/demo/example-skating.yaml'));
    $config = EventConfig::fromArray($merged);

    expect(EventSchema::errors($merged))->toBe([])
        ->and($config->sport)->toBe(Sport::Skating)
        ->and($config->raceSheet?->heatSize)->toBe(6)
        ->and($config->fields[2]->key)->toBe('skate_type') // inherited from the pack
        ->and($config->certificate?->isUpload())->toBeTrue();
});

it('lets the event override pack keys wholesale', function () {
    $merged = sportPack(Sport::Skating)->applyTo(yamlFile('events/demo/example-skating.yaml'));

    expect($merged['distances'])->toHaveCount(3)
        ->and($merged['race_sheet']['seeding'])->toBe('random');
});

it('rejects an event that inherits unpriced pack distances', function () {
    $event = yamlFile('events/demo/example-skating.yaml');
    unset($event['distances']);

    expect(EventSchema::errors(sportPack(Sport::Skating)->applyTo($event)))->not->toBe([]);
});

it('accepts heat_size presets and any N, rejects below 2', function (int $size, bool $valid) {
    $event = yamlFile('events/demo/example-skating.yaml');
    $event['race_sheet']['heat_size'] = $size;

    expect(EventSchema::errors($event) === [])->toBe($valid);
})->with([[6, true], [8, true], [10, true], [12, true], [1, false]]);

it('requires a background for uploaded certificates and gallery keys otherwise', function () {
    $event = yamlFile('events/demo/example-marathon.yaml');

    $event['certificate'] = ['template' => 'upload'];
    expect(EventSchema::errors($event))->not->toBe([]);

    $event['certificate'] = ['template' => 'classic'];
    expect(EventSchema::errors($event))->toBe([])
        ->and(config('eventflow.certificates.gallery'))->toHaveKey('classic');
});

it('fixes certificate size at A4 300 dpi', function () {
    expect(config('eventflow.certificates.px.landscape'))->toBe([3508, 2480])
        ->and(config('eventflow.certificates.px.portrait'))->toBe([2480, 3508]);
});

it('covers every module exactly once in core or optional', function () {
    $listed = [...config('eventflow.modules.core'), ...config('eventflow.modules.optional')];

    expect($listed)->toEqualCanonicalizing(array_column(Module::cases(), 'value'));
});

it('declares the product rule contracts', function () {
    foreach ([Entitlements::class, SportPackRepository::class, RaceSheetBuilder::class, CertificateRenderer::class] as $contract) {
        expect(interface_exists($contract))->toBeTrue();
    }
});

it('stores season passes, module toggles and event sport', function () {
    expect(Schema::hasColumns('season_passes', ['org_id', 'plan', 'starts_on', 'ends_on', 'status', 'modules']))->toBeTrue();

    $org = Org::create(['slug' => 'demo-skaters', 'name' => 'Demo Skaters', 'modules' => ['certificates' => false]]);
    $pass = SeasonPass::create([
        'org_id' => $org->id,
        'plan' => 'Season 2027',
        'starts_on' => '2027-01-01',
        'ends_on' => '2027-12-31',
        'status' => 'active',
        'modules' => [Module::Registration, Module::Payments, Module::Passes, Module::RaceSheets],
    ]);
    $event = Event::create(['org_id' => $org->id, 'slug' => 'open', 'name' => 'Open', 'sport' => Sport::Skating, 'date' => '2027-02-07', 'venue' => 'Chennai']);

    expect($pass->fresh()->modules->contains(Module::RaceSheets))->toBeTrue()
        ->and($org->fresh()->modules)->toBe(['certificates' => false])
        ->and($event->fresh()->sport)->toBe(Sport::Skating);
});
