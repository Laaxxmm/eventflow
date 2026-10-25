<?php

use App\Enums\Module;
use App\Models\Org;
use App\Models\SeasonPass;

beforeEach(function () {
    $this->org = Org::factory()->create(['slug' => 'demo-runners', 'name' => 'Demo Runners']);
});

test('grants a one-season pass with every module by default', function () {
    $this->travelTo('2027-01-10 10:00:00');

    $this->artisan('season-pass:grant demo-runners --plan="Season 2027" --amount=25000')
        ->expectsOutputToContain('Granted "Season 2027" to Demo Runners')
        ->assertSuccessful();

    $pass = SeasonPass::withoutGlobalScopes()->sole();
    expect($pass->org_id)->toBe($this->org->id)
        ->and($pass->status)->toBe('active')
        ->and($pass->starts_on->toDateString())->toBe('2027-01-10')
        ->and($pass->ends_on->toDateString())->toBe('2028-01-09')     // length_days 365, inclusive
        ->and($pass->modules->all())->toEqualCanonicalizing(Module::cases())
        ->and($pass->amount_paise)->toBe(2500000);
});

test('--module picks optional modules; core modules are always included', function () {
    $this->artisan('season-pass:grant demo-runners --module=checkin --module=certificates --from=2027-01-01 --until=2027-06-30')
        ->assertSuccessful();

    $pass = SeasonPass::withoutGlobalScopes()->sole();
    expect($pass->modules->map->value->sort()->values()->all())
        ->toBe(['certificates', 'checkin', 'passes', 'payments', 'registration'])
        ->and($pass->ends_on->toDateString())->toBe('2027-06-30');
});

test('rejects bad input', function (string $args, string $message) {
    $this->artisan("season-pass:grant {$args}")
        ->expectsOutputToContain($message)
        ->assertFailed();

    expect(SeasonPass::withoutGlobalScopes()->count())->toBe(0);
})->with([
    'unknown org' => ['nobody', 'No org with slug [nobody]'],
    'unknown module' => ['demo-runners --module=refunds', 'module'],
    'core module is not optional' => ['demo-runners --module=registration', 'module'],
    'bad date' => ['demo-runners --from=10-01-2027', 'from'],
    'ends before it starts' => ['demo-runners --from=2027-06-01 --until=2027-05-01', '--until must be on or after --from'],
]);
