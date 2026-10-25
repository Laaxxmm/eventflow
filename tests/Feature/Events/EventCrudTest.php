<?php

use App\Models\Event;
use App\Models\Org;
use App\Models\Registration;
use App\Models\User;
use App\Payments\PaymentStatus;
use App\Support\EventSchema;

function organiser(string $role = 'owner'): User
{
    $org = Org::create(['slug' => 'city-runners', 'name' => 'City Runners']);

    return User::factory()->create(['org_id' => $org->id, 'role' => $role]);
}

function eventInput(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => 'Monsoon 10K',
        'sport' => 'running',
        'date' => '2027-07-04T06:00',
        'venue' => 'Cubbon Park',
        'status' => 'published',
        'branding' => ['primary' => '#D9FF45', 'secondary' => '#ff6e61'],
        'hero' => ['title' => '', 'subtitle' => 'Run in the rain'],
        'distances' => [
            ['code' => '5K', 'label' => '5K', 'price_inr' => 499, 'capacity' => 100],
            ['code' => '10K', 'label' => '10K', 'price_inr' => 799, 'capacity' => ''],
            ['code' => '', 'label' => '', 'price_inr' => '', 'capacity' => ''],
        ],
        'age_bands' => [['label' => 'Open', 'min' => 18, 'max' => 60], ['label' => '', 'min' => '', 'max' => '']],
        'show' => ['gender' => '1', 'tshirt_size' => '1', 'city' => '0'],
        'custom' => [['key' => '', 'label' => 'Running club', 'type' => 'select', 'required' => '1', 'options' => 'None, Striders, Pacers']],
        'faq' => [['q' => 'Parking?', 'a' => 'Yes, at the gate.']],
        'certificate' => ['template' => 'classic', 'orientation' => 'landscape'],
        'race_sheet' => ['heat_size' => '', 'group_by' => '', 'seeding' => 'registration_order'],
    ], $overrides);
}

it('creates an event in the YAML config shape', function () {
    $user = organiser();

    $this->actingAs($user)->get('/organiser/events/create?sport=running')->assertOk()->assertSee('Race categories');
    $this->actingAs($user)->post('/organiser/events', eventInput())->assertSessionHasNoErrors()->assertRedirect();

    $event = Event::sole();
    expect($event->slug)->toBe('monsoon-10k')
        ->and($event->config)->toMatchArray([
            'org' => 'city-runners',
            'slug' => 'monsoon-10k',
            'date' => '2027-07-04T06:00:00+05:30',
            'branding' => ['primary' => '#d9ff45', 'secondary' => '#ff6e61'],
            'hero' => ['title' => 'Monsoon 10K', 'subtitle' => 'Run in the rain'],
            'distances' => [
                ['code' => '5K', 'label' => '5K', 'price_inr' => 499, 'capacity' => 100],
                ['code' => '10K', 'label' => '10K', 'price_inr' => 799],
            ],
            'age_bands' => [['label' => 'Open', 'min' => 18, 'max' => 60]],
        ])
        ->and(collect($event->config['fields'])->pluck('key')->all())->toBe(['gender', 'tshirt_size', 'running_club'])
        ->and(EventSchema::errors($event->config + ['sport' => 'running']))->toBe([]);

    $this->get('/city-runners/monsoon-10k')->assertOk()->assertSee('Running club');
});

it('round-trips the editor and keeps slugs unique per org', function () {
    $user = organiser();
    $this->actingAs($user)->post('/organiser/events', eventInput());
    $this->actingAs($user)->post('/organiser/events', eventInput());

    expect(Event::pluck('slug')->all())->toBe(['monsoon-10k', 'monsoon-10k-2']);

    $event = Event::first();
    $this->actingAs($user)->get("/organiser/events/{$event->id}/edit")
        ->assertOk()
        ->assertSee('value="Running club"', false)
        ->assertSee('value="None, Striders, Pacers"', false);

    $this->actingAs($user)->put("/organiser/events/{$event->id}", eventInput(['name' => 'Monsoon 10K 2027', 'custom' => [['key' => 'running_club']]]))
        ->assertSessionHasNoErrors();

    expect($event->fresh()->name)->toBe('Monsoon 10K 2027')
        ->and($event->fresh()->slug)->toBe('monsoon-10k');
});

it('maps schema errors back onto the form', function () {
    $this->actingAs(organiser())
        ->post('/organiser/events', ['distances' => [['code' => '', 'label' => '', 'price_inr' => '']]] + eventInput())
        ->assertSessionHasErrors('distances');

    expect(Event::count())->toBe(0);
});

it('scopes events to the organiser org and blocks volunteers', function () {
    $user = organiser();
    $this->actingAs($user)->post('/organiser/events', eventInput());
    $event = Event::sole();

    $otherOrg = Org::create(['slug' => 'others', 'name' => 'Others']);
    $stranger = User::factory()->create(['org_id' => $otherOrg->id, 'role' => 'owner']);
    $this->actingAs($stranger)->get("/organiser/events/{$event->id}/edit")->assertNotFound();

    $volunteer = User::factory()->create(['org_id' => $user->org_id, 'role' => 'volunteer']);
    $this->actingAs($volunteer)->get('/organiser/events')->assertForbidden();

    $this->post('/logout');
    $this->get('/organiser/events')->assertRedirect('/login');
});

it('refuses to delete an event with registrations', function () {
    $user = organiser();
    $this->actingAs($user)->post('/organiser/events', eventInput());
    $event = Event::sole();
    Registration::create([
        'event_id' => $event->id, 'pass_no' => Registration::newPassNo(), 'status' => PaymentStatus::Pending,
        'participant_name' => 'A', 'email' => 'a@example.com', 'phone' => '9876543210', 'distance_code' => '5K',
    ]);

    $this->actingAs($user)->delete("/organiser/events/{$event->id}")->assertSessionHasErrors('event');
    $this->actingAs($user)->put("/organiser/events/{$event->id}", ['distances' => [['code' => '10K', 'label' => '10K', 'price_inr' => 799]]] + eventInput())
        ->assertSessionHasErrors('distances');

    expect(Event::count())->toBe(1);

    $event->registrations()->delete();
    $this->actingAs($user)->delete("/organiser/events/{$event->id}")->assertRedirect('/organiser/events');
    expect(Event::count())->toBe(0);
});
