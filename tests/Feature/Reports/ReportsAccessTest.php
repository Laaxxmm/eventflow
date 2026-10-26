<?php

use App\Enums\Module;
use App\Models\EventAddon;
use App\Models\Org;
use App\Models\User;
use Tests\Feature\Reports\ReportFixtures;

beforeEach(function () {
    $this->event = ReportFixtures::event();
});

dataset('report routes', [
    'race sheet' => ['reports.race-sheet'],
    'race sheet csv' => ['reports.race-sheet.csv'],
    'race sheet pdf' => ['reports.race-sheet.pdf'],
    'certificates' => ['reports.certificates'],
]);

test('guests are sent to login', function (string $route) {
    $this->get(route($route, $this->event))->assertRedirect(route('login'));
})->with('report routes');

test('volunteers only see check-in', function (string $route) {
    $volunteer = User::factory()->for($this->event->org)->volunteer()->create();

    $this->actingAs($volunteer)->get(route($route, $this->event))->assertRedirect('/checkin');
})->with('report routes');

test('volunteers cannot queue certificates', function () {
    $volunteer = User::factory()->for($this->event->org)->volunteer()->create();

    $this->actingAs($volunteer)->post(route('reports.certificates.generate', $this->event))->assertForbidden();
});

test('staff can use reports', function (string $route) {
    $staff = User::factory()->for($this->event->org)->staff()->create();

    $this->actingAs($staff)->get(route($route, $this->event))->assertOk();
})->with('report routes');

test('another org cannot reach this event', function (string $route) {
    $otherOwner = User::factory()->create(); // owner of a different org

    $this->actingAs($otherOwner)->get(route($route, $this->event))->assertNotFound();
})->with('report routes');

test('without the plan module or the event add-on, pages explain the lock and downloads stay locked', function () {
    $event = ReportFixtures::event(Org::factory()->create()); // no pass, no add-ons
    $owner = ReportFixtures::owner($event);

    $this->actingAs($owner)->get(route('reports.race-sheet', $event))
        ->assertOk()
        ->assertSee('Race sheet download is locked for this event.')
        ->assertSee('Unlock for ₹599')
        ->assertDontSee('action="'.route('reports.race-sheet.csv', $event).'"', false);
    $this->actingAs($owner)->get(route('reports.certificates', $event))
        ->assertOk()
        ->assertSee('Certificates is locked for this event.')
        ->assertSee('Unlock for ₹799');

    foreach (['reports.race-sheet.csv' => 'reports.race-sheet', 'reports.race-sheet.pdf' => 'reports.race-sheet', 'reports.certificates.download' => 'reports.certificates'] as $locked => $page) {
        $this->actingAs($owner)->get(route($locked, $event))->assertRedirect(route($page, $event));
    }
    $this->actingAs($owner)->post(route('reports.certificates.generate', $event))->assertRedirect(route('reports.certificates', $event));
    $this->actingAs($owner)->followingRedirects()->get(route('reports.race-sheet.csv', $event))
        ->assertSee('That needs the Race sheet download add-on for this event.');
});

test('a per-event add-on unlocks only that event', function () {
    $org = Org::factory()->create();
    $event = ReportFixtures::event($org);
    $other = ReportFixtures::event($org);
    $owner = ReportFixtures::owner($event);

    $this->artisan('plans:event-addon', ['org' => $org->slug, 'event' => $event->slug, 'addon' => 'race_sheet', '--note' => 'Invoice 9'])
        ->expectsOutputToContain('Race sheet download unlocked for')->assertSuccessful();

    $this->actingAs($owner)->get(route('reports.race-sheet.csv', $event))->assertOk();
    $this->actingAs($owner)->get(route('reports.race-sheet.csv', $other))->assertRedirect(route('reports.race-sheet', $other));
    $this->actingAs($owner)->get(route('reports.certificates.download', $event))->assertRedirect(route('reports.certificates', $event)); // certificates not bought
    expect(EventAddon::withoutGlobalScopes()->sole()->amount_paise)->toBe(59900);

    $this->artisan('plans:event-addon', ['org' => $org->slug, 'event' => $event->slug, 'addon' => 'race_sheet', '--revoke' => true])->assertSuccessful();
    $this->actingAs($owner)->get(route('reports.race-sheet.csv', $event))->assertRedirect(route('reports.race-sheet', $event));
    $this->artisan('plans:event-addon', ['org' => $org->slug, 'event' => $event->slug, 'addon' => 'medals'])->expectsOutputToContain('Unknown add-on')->assertFailed();
});

test('a module outside the pass, or switched off, stays locked unless the event has the add-on', function () {
    $org = Org::factory()->create(['modules' => ['certificates' => false]]);
    ReportFixtures::grantPass($org, [Module::Registration, Module::Payments, Module::Passes, Module::Certificates]);
    $event = ReportFixtures::event($org);
    $owner = ReportFixtures::owner($event);

    $this->actingAs($owner)->get(route('reports.race-sheet.pdf', $event))->assertRedirect(route('reports.race-sheet', $event));   // not in the pass
    $this->actingAs($owner)->get(route('reports.certificates.download', $event))->assertRedirect(route('reports.certificates', $event)); // switched off

    EventAddon::withoutGlobalScopes()->create(['org_id' => $org->id, 'event_id' => $event->id, 'addon' => 'certificates']);
    $this->actingAs($owner)->get(route('reports.certificates', $event))->assertOk()->assertDontSee('is locked for this event');
});
