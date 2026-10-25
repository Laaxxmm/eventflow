<?php

use App\Contracts\Entitlements;
use App\Enums\Module;
use App\Models\Org;
use App\Models\SeasonPass;
use App\Models\User;

function seasonPassFor(Org $org, array $attributes = []): SeasonPass
{
    return SeasonPass::withoutGlobalScopes()->create([
        'org_id' => $org->id,
        'plan' => 'Season 2027',
        'starts_on' => now('Asia/Kolkata')->subMonth()->toDateString(),
        'ends_on' => now('Asia/Kolkata')->addMonth()->toDateString(),
        'status' => 'active',
        'modules' => Module::cases(),
        ...$attributes,
    ]);
}

beforeEach(function () {
    $this->org = Org::factory()->create();
    $this->entitlements = app(Entitlements::class);
});

test('core modules are on without a Season Pass, optional ones are off', function () {
    expect($this->entitlements->activeSeasonPass($this->org))->toBeNull();

    foreach ([Module::Registration, Module::Payments, Module::Passes] as $core) {
        expect($this->entitlements->enabled($this->org, $core))->toBeTrue();
    }
    foreach ([Module::CheckIn, Module::RaceSheets, Module::Certificates] as $optional) {
        expect($this->entitlements->enabled($this->org, $optional))->toBeFalse();
    }
});

test('an active pass enables the optional modules it includes', function () {
    seasonPassFor($this->org, ['modules' => [Module::Registration, Module::CheckIn]]);

    expect($this->entitlements->enabled($this->org, Module::CheckIn))->toBeTrue()
        ->and($this->entitlements->enabled($this->org, Module::Certificates))->toBeFalse();
});

test('an owner can switch an included module off', function () {
    seasonPassFor($this->org);
    $this->org->update(['modules' => ['race_sheets' => false]]);

    expect($this->entitlements->enabled($this->org, Module::RaceSheets))->toBeFalse()
        ->and($this->entitlements->enabled($this->org, Module::Certificates))->toBeTrue();
});

test('core modules ignore the toggles', function () {
    $this->org->update(['modules' => ['registration' => false]]);

    expect($this->entitlements->enabled($this->org, Module::Registration))->toBeTrue();
});

test('expired, future and non-active passes do not count', function (array $attributes) {
    seasonPassFor($this->org, $attributes);

    expect($this->entitlements->activeSeasonPass($this->org))->toBeNull()
        ->and($this->entitlements->enabled($this->org, Module::CheckIn))->toBeFalse();
})->with([
    'expired' => [['starts_on' => '2020-01-01', 'ends_on' => '2020-12-31']],
    'future' => [['starts_on' => now()->addYear()->toDateString(), 'ends_on' => now()->addYears(2)->toDateString()]],
    'pending' => [['status' => 'pending']],
    'cancelled' => [['status' => 'cancelled']],
]);

test('a pass is valid on its first and last day, in IST', function () {
    $this->travelTo(now()->setTimezone('Asia/Kolkata')->setDate(2027, 3, 31)->setTime(23, 30)); // 18:00 UTC
    seasonPassFor($this->org, ['starts_on' => '2026-04-01', 'ends_on' => '2027-03-31']);
    expect(app(Entitlements::class)->activeSeasonPass($this->org))->not->toBeNull();

    $this->travelTo(now()->setTimezone('Asia/Kolkata')->setDate(2027, 4, 1)->setTime(0, 30)); // 19:00 UTC, still 31 Mar UTC
    $this->app->forgetScopedInstances();
    expect(app(Entitlements::class)->activeSeasonPass($this->org))->toBeNull();
});

test('with overlapping passes the one ending last wins', function () {
    seasonPassFor($this->org, ['plan' => 'Short', 'ends_on' => now()->addWeek()->toDateString()]);
    seasonPassFor($this->org, ['plan' => 'Long', 'ends_on' => now()->addYear()->toDateString()]);

    expect($this->entitlements->activeSeasonPass($this->org)->plan)->toBe('Long');
});

test('another org\'s pass does not count, even when signed in elsewhere', function () {
    $other = Org::factory()->create();
    seasonPassFor($other);
    $this->actingAs(User::factory()->for($other)->create());

    expect($this->entitlements->enabled($this->org, Module::Certificates))->toBeFalse()
        ->and($this->entitlements->enabled($other, Module::Certificates))->toBeTrue();
});
