<?php

use App\Landing\Capacity;
use App\Models\Org;
use App\Models\Registration;
use App\Models\User;
use App\Payments\PaymentStatus;

use function Tests\Feature\Events\registrationInput;
use function Tests\Feature\Events\syncMarathon;

require_once __DIR__.'/helpers.php';

const REGISTER = '/demo-runners/bengaluru-city-marathon-2027/register';

it('creates a pending registration and hands off to /pay', function () {
    syncMarathon();

    $response = $this->post(REGISTER, registrationInput());

    $registration = Registration::sole();
    expect($registration->status)->toBe(PaymentStatus::Pending)
        ->and($registration->pass_no)->toMatch(config('eventflow.pass.pattern'))
        ->and($registration->distance_code)->toBe('5K')
        ->and($registration->amount_paise)->toBe(59900)
        ->and($registration->answers)->toMatchArray(['age' => 36, 'age_band' => 'Open', 'tshirt_size' => 'M']);

    $response->assertRedirect();
    $this->get($response->headers->get('Location'))
        ->assertOk()
        ->assertSee($registration->pass_no)
        ->assertSee('action="'.route('payments.create', $registration).'"', false);
});

it('requires a valid signature on the review link', function () {
    syncMarathon();
    $this->post(REGISTER, registrationInput());

    $this->get(route('registrations.review', Registration::sole()))->assertForbidden();
});

it('validates fields from the same config', function () {
    syncMarathon();

    $this->post(REGISTER, registrationInput([
        'distance' => '3K',
        'email' => 'not-an-email',
        'answers' => ['tshirt_size' => 'XXXL', 'gender' => ''],
    ]))->assertSessionHasErrors(['distance', 'email', 'answers.tshirt_size', 'answers.gender']);

    expect(Registration::count())->toBe(0);
});

it('rejects an age outside every band, computed from DOB on race day', function () {
    syncMarathon();

    // Turns 12 on 2027-01-18, the day after the race: 11 on race day, below Junior (12-17).
    $this->post(REGISTER, registrationInput(['dob' => '2015-01-18']))
        ->assertSessionHasErrors(['dob' => 'You will be 11 on race day. This event is open to ages 12–99.']);

    $this->post(REGISTER, registrationInput(['dob' => '2015-01-17']))->assertSessionHasNoErrors();

    expect(Registration::sole()->answers)->toMatchArray(['age' => 12, 'age_band' => 'Junior']);
});

it('enforces capacity per distance', function () {
    $event = syncMarathon();
    $config = $event->config;
    $config['distances'][3]['capacity'] = 2; // FM
    $event->update(['config' => $config]);

    $this->post(REGISTER, registrationInput(['distance' => 'FM']))->assertSessionHasNoErrors();
    $this->post(REGISTER, registrationInput(['distance' => 'FM', 'email' => 'b@example.com']))->assertSessionHasNoErrors();

    $this->post(REGISTER, registrationInput(['distance' => 'FM', 'email' => 'c@example.com']))
        ->assertSessionHasErrors(['distance' => 'Full Marathon (42.2K) is full.']);

    // Other distances stay open, and the landing shows FM as full.
    $this->post(REGISTER, registrationInput(['distance' => 'HM', 'email' => 'd@example.com']))->assertSessionHasNoErrors();
    $this->get('/demo-runners/bengaluru-city-marathon-2027')->assertSee('Full');

    expect(Registration::where('distance_code', 'FM')->count())->toBe(2);
});

it('frees a place when a pending checkout is abandoned past the hold', function () {
    $event = syncMarathon();
    $config = $event->config;
    $config['distances'][3]['capacity'] = 1;
    $event->update(['config' => $config]);

    $this->post(REGISTER, registrationInput(['distance' => 'FM']));
    $this->travel(Capacity::PENDING_HOLD_MINUTES + 1)->minutes();

    $this->post(REGISTER, registrationInput(['distance' => 'FM', 'email' => 'b@example.com']))->assertSessionHasNoErrors();
});

it('closes registration once the event has started', function () {
    syncMarathon();
    $this->travelTo('2027-01-17 06:00:00');

    $this->post(REGISTER, registrationInput())->assertSessionHasErrors('distance');
    expect(Registration::count())->toBe(0);
});

it('serves public pages as the event org even when another org is signed in', function () {
    $event = syncMarathon();
    $config = $event->config;
    $config['distances'][3]['capacity'] = 1;
    $event->update(['config' => $config]);
    $this->post(REGISTER, registrationInput(['distance' => 'FM']));

    $other = Org::create(['slug' => 'other-org', 'name' => 'Other']);
    $this->actingAs(User::factory()->create(['org_id' => $other->id, 'role' => 'owner']));

    $this->get('/demo-runners')->assertOk()->assertSee('Bengaluru City Marathon 2027');
    $this->post(REGISTER, registrationInput(['distance' => 'FM', 'email' => 'b@example.com']))
        ->assertSessionHasErrors(['distance' => 'Full Marathon (42.2K) is full.']);

    $response = $this->post(REGISTER, registrationInput(['distance' => 'HM', 'email' => 'c@example.com']));
    $this->get($response->headers->get('Location'))->assertOk()->assertSee('Half Marathon (21.1K)');

    $registration = Registration::withoutGlobalScopes()->where('email', 'c@example.com')->sole();
    expect($registration->org_id)->toBe($event->org_id);
});
