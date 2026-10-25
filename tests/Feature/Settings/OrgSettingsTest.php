<?php

use App\Contracts\Entitlements;
use App\Enums\Module;
use App\Models\Org;
use App\Models\SeasonPass;
use App\Models\User;

beforeEach(function () {
    $this->org = Org::factory()->create();
    $this->owner = User::factory()->for($this->org)->create();
});

function grantSettingsPass(Org $org, array $modules): void
{
    SeasonPass::withoutGlobalScopes()->create([
        'org_id' => $org->id,
        'plan' => 'Season 2027',
        'starts_on' => now()->subDay()->toDateString(),
        'ends_on' => now()->addYear()->toDateString(),
        'status' => 'active',
        'modules' => $modules,
    ]);
}

test('the owner sees the pass and the module toggles', function () {
    grantSettingsPass($this->org, [Module::Registration, Module::Payments, Module::Passes, Module::CheckIn, Module::Certificates]);

    $this->actingAs($this->owner)->get(route('settings.edit'))
        ->assertOk()
        ->assertSee('Season 2027')
        ->assertSee('Always on')
        ->assertSee('Race sheets')
        ->assertSee('not in your Season Pass')
        ->assertSee(route('settings.notifications.show'), false);
});

test('without a pass the page says what still works', function () {
    $this->actingAs($this->owner)->get(route('settings.edit'))
        ->assertOk()
        ->assertSee('No active Season Pass')
        ->assertSee('contact Indefine');
});

test('the owner can switch an included module off and on again', function () {
    grantSettingsPass($this->org, Module::cases());

    $this->actingAs($this->owner)
        ->patch(route('settings.modules.update'), ['modules' => ['checkin', 'race_sheets']])
        ->assertRedirect(route('settings.edit'))
        ->assertSessionHas('status', 'modules-updated');

    expect($this->org->fresh()->modules)->toMatchArray(['checkin' => true, 'race_sheets' => true, 'certificates' => false])
        ->and(app(Entitlements::class)->enabled($this->org->fresh(), Module::Certificates))->toBeFalse();

    $this->actingAs($this->owner)
        ->patch(route('settings.modules.update'), ['modules' => ['checkin', 'race_sheets', 'certificates']]);

    $this->app->forgetScopedInstances();
    expect(app(Entitlements::class)->enabled($this->org->fresh(), Module::Certificates))->toBeTrue();
});

test('modules outside the pass cannot be switched on', function () {
    grantSettingsPass($this->org, [Module::Registration, Module::CheckIn]);

    $this->actingAs($this->owner)
        ->patch(route('settings.modules.update'), ['modules' => ['checkin', 'certificates', 'registration']]);

    expect($this->org->fresh()->modules)->toBe(['checkin' => true])
        ->and(app(Entitlements::class)->enabled($this->org->fresh(), Module::Certificates))->toBeFalse();
});

test('only owners can open Org Settings', function () {
    $staff = User::factory()->for($this->org)->staff()->create();
    $volunteer = User::factory()->for($this->org)->volunteer()->create();

    $this->get(route('settings.edit'))->assertRedirect(route('login')); // guest

    $this->actingAs($staff)->get(route('settings.edit'))->assertForbidden();
    $this->actingAs($staff)->patch(route('settings.modules.update'), ['modules' => []])->assertForbidden();
    $this->actingAs($volunteer)->get(route('settings.edit'))->assertRedirect('/checkin');
});

test('an owner only changes their own org', function () {
    $other = Org::factory()->create(['modules' => ['certificates' => true]]);
    grantSettingsPass($this->org, Module::cases());

    $this->actingAs($this->owner)->patch(route('settings.modules.update'), ['modules' => []]);

    expect($other->fresh()->modules)->toBe(['certificates' => true]);
});
