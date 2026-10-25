<?php

use App\Models\Event;
use App\Models\Org;
use App\Models\Registration;
use App\Models\Tenancy\CurrentOrg;
use App\Models\User;

beforeEach(function () {
    $this->orgA = Org::factory()->create();
    $this->orgB = Org::factory()->create();
    $this->ownerA = User::factory()->for($this->orgA)->create();

    $this->eventA = Event::factory()->for($this->orgA)->create(['name' => 'Alpha Run']);
    $this->eventB = Event::factory()->for($this->orgB)->create(['name' => 'Bravo Ride']);

    Registration::factory()->count(2)->for($this->eventA)->paid()->create(['participant_name' => 'Alpha Runner']);
    Registration::factory()->count(3)->for($this->eventB)->paid()->create(['participant_name' => 'Bravo Rider']);
});

test('registrations take their org from the event', function () {
    expect(Registration::withoutGlobalScopes()->where('org_id', $this->orgB->id)->count())->toBe(3);
});

test('queries as org A only return org A rows', function () {
    $this->actingAs($this->ownerA);

    expect(Event::pluck('name')->all())->toBe(['Alpha Run'])
        ->and(Registration::count())->toBe(2)
        ->and(User::count())->toBe(1)
        ->and(Event::find($this->eventB->id))->toBeNull();
});

test('org A dashboard does not show org B events', function () {
    $this->actingAs($this->ownerA)->get('/dashboard')
        ->assertOk()
        ->assertSee('Alpha Run')
        ->assertDontSee('Bravo Ride');
});

test('org A cannot open org B participants by event id', function () {
    $this->actingAs($this->ownerA)
        ->get(route('dashboard.participants', ['event' => $this->eventB->id]))
        ->assertNotFound();

    $this->actingAs($this->ownerA)
        ->get(route('dashboard.participants.export', ['event' => $this->eventB->id]))
        ->assertNotFound();
});

test('org A participants list and CSV only contain org A people', function () {
    $this->actingAs($this->ownerA)->get(route('dashboard.participants'))
        ->assertOk()
        ->assertSee('Alpha Runner')
        ->assertDontSee('Bravo Rider');

    $csv = $this->actingAs($this->ownerA)->get(route('dashboard.participants.export'))->streamedContent();

    expect($csv)->toContain('Alpha Runner')->not->toContain('Bravo Rider');
});

test('an explicit current org scopes queries without a logged-in user', function () {
    $names = app(CurrentOrg::class)->run($this->orgB, fn () => Event::pluck('name')->all());

    expect($names)->toBe(['Bravo Ride'])
        ->and(Event::count())->toBe(2); // restored: no org, no filter
});

test('new org rows get the current org automatically', function () {
    $this->actingAs($this->ownerA);

    $event = Event::create(['name' => 'Alpha Run', 'date' => '2027-03-01', 'venue' => 'Pune']);

    expect($event->org_id)->toBe($this->orgA->id)
        ->and($event->slug)->toBe('alpha-run-2'); // auto slug, unique per org
});
