<?php

use App\Models\Org;

use function Tests\Feature\Events\syncMarathon;

require_once __DIR__.'/helpers.php';

it('renders the branded marathon landing', function () {
    syncMarathon();

    $this->get('/demo-runners/bengaluru-city-marathon-2027')
        ->assertOk()
        ->assertSee('--brand-primary:#4b3fe0', false)
        ->assertSee('Run the Garden City')
        ->assertSee('Half Marathon (21.1K)')
        ->assertSee('₹1,499')
        ->assertSee('When will I get my pass?')
        ->assertSee('name="dob"', false)
        ->assertSee('name="answers[tshirt_size]"', false)
        ->assertDontSee('name="answers[age]"', false);
});

it('404s for drafts and unknown events', function () {
    $event = syncMarathon();
    $event->update(['status' => 'draft']);

    $this->get('/demo-runners/bengaluru-city-marathon-2027')->assertNotFound();
    $this->get('/demo-runners/missing')->assertNotFound();
    $this->get('/nobody/missing')->assertNotFound();
});

it('lists published events on the org page', function () {
    $event = syncMarathon();
    $event->org->events()->create(['slug' => 'secret', 'name' => 'Secret Draft Run', 'date' => '2027-03-01', 'venue' => 'X', 'status' => 'draft']);

    $this->get('/demo-runners')
        ->assertOk()
        ->assertSee('Bengaluru City Marathon 2027')
        ->assertSee('From ₹599')
        ->assertDontSee('Secret Draft Run');
});

it('does not let org slugs shadow app routes', function () {
    Org::create(['slug' => 'login', 'name' => 'Login Org']);

    $this->get('/login')->assertOk()->assertDontSee('Login Org');
});
