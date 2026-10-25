<?php

use App\Models\Event;
use App\Models\Org;
use App\Models\Registration;
use App\Models\User;
use App\Payments\PaymentStatus;
use App\Support\EventSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function organiser(string $role = 'owner'): User
{
    $org = Org::create(['slug' => 'city-runners', 'name' => 'City Runners']);

    return User::factory()->create(['org_id' => $org->id, 'role' => $role]);
}

/** What the create wizard posts. */
function wizardInput(array $overrides = []): array
{
    return array_replace([
        'name' => 'Monsoon 10K',
        'date' => '2027-07-04T06:00',
        'venue' => 'Cubbon Park',
        'tagline' => 'Run in the rain',
        'sport' => 'running',
        'distances' => [
            ['code' => '5K', 'label' => '5K', 'price_inr' => '499', 'capacity' => '100'],
            ['code' => '10K', 'label' => '10K', 'price_inr' => '799', 'capacity' => ''],
        ],
        'template' => 'midnight',
        'primary' => '#D9FF45',
        'secondary' => '#ff6e61',
        'publish' => '1',
    ], $overrides);
}

/** What the full event editor posts. */
function eventInput(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => 'Monsoon 10K',
        'sport' => 'running',
        'template' => 'midnight',
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

/** A solid-colour PNG (alpha 0 = opaque, 127 = transparent). */
function pngLogo(string $hex, int $alpha = 0, int $w = 400, int $h = 160): UploadedFile
{
    $img = imagecreatetruecolor($w, $h);
    imagesavealpha($img, true);
    imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
    [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
    imagefilledrectangle($img, (int) ($w / 4), (int) ($h / 4), (int) ($w * 3 / 4), (int) ($h * 3 / 4), imagecolorallocatealpha($img, $r, $g, $b, $alpha));
    ob_start();
    imagepng($img);

    return UploadedFile::fake()->createWithContent('logo.png', ob_get_clean());
}

beforeEach(fn () => Storage::fake('public'));

it('shows the five-step wizard with sport packs and template previews', function () {
    $this->actingAs(organiser())->get('/organiser/events/create')
        ->assertOk()
        ->assertSeeInOrder(['1. Basics', '2. Sport pack', '3. Page design', '4. Branding', '5. Publish'])
        ->assertSee('Aurora')->assertSee('Midnight')->assertSee('Clean')->assertSee('Festival')->assertSee('Heritage')
        ->assertSee('Need a custom template?')
        ->assertSee('transparent background');
});

it('publishes the event with its template from the wizard, leaving the rest to the sport pack', function () {
    $user = organiser();

    $response = $this->actingAs($user)->post('/organiser/events', wizardInput());

    $event = Event::sole();
    $response->assertSessionHasNoErrors()->assertRedirect(route('organiser.events.published', $event));
    expect($event->slug)->toBe('monsoon-10k')
        ->and($event->status)->toBe('published')
        ->and($event->template_id)->toBe('midnight')
        ->and($event->certificate_template_id)->toBe('velocity') // running pack
        ->and($event->config)->toMatchArray([
            'org' => 'city-runners',
            'template' => 'midnight',
            'date' => '2027-07-04T06:00:00+05:30',
            'branding' => ['primary' => '#d9ff45', 'secondary' => '#ff6e61'],
            'hero' => ['title' => 'Monsoon 10K', 'subtitle' => 'Run in the rain'],
            'distances' => [
                ['code' => '5K', 'label' => '5K', 'price_inr' => 499, 'capacity' => 100],
                ['code' => '10K', 'label' => '10K', 'price_inr' => 799],
            ],
        ])
        ->and($event->config)->not->toHaveKeys(['age_bands', 'fields', 'faq', 'certificate'])
        ->and(EventSchema::errors($event->config))->toBe([]);

    $this->actingAs($user)->get(route('organiser.events.published', $event))
        ->assertOk()->assertSee('Your event is live')->assertSee(route('landing.show', ['city-runners', 'monsoon-10k']));

    // The landing renders the chosen template, with the running pack's FAQ.
    $this->get('/city-runners/monsoon-10k')->assertOk()->assertSee('Choose your lane')->assertSee('Is the race chip timed?');
});

it('saves a draft that is not public', function () {
    $this->actingAs(organiser())->post('/organiser/events', wizardInput(['publish' => '0']))->assertSessionHasNoErrors();

    expect(Event::sole()->status)->toBe('draft');
    $this->get('/city-runners/monsoon-10k')->assertNotFound();
});

it('reopens the wizard on the step with the error', function () {
    $user = organiser();

    $this->actingAs($user)->from('/organiser/events/create')
        ->post('/organiser/events', wizardInput(['distances' => [['code' => '5K', 'label' => '5K', 'price_inr' => '']]]))
        ->assertRedirect('/organiser/events/create')
        ->assertSessionHasErrors('distances.0.price_inr')
        ->assertSessionHas('wizard_step', 2);

    $this->actingAs($user)->post('/organiser/events', wizardInput(['template' => 'neon']))->assertSessionHas('wizard_step', 3);
    $this->actingAs($user)->post('/organiser/events', wizardInput(['date' => '2020-01-01T06:00']))->assertSessionHas('wizard_step', 1);
    expect(Event::count())->toBe(0);
});

it('stores the logo and photo on the public disk and places them in the template', function () {
    $user = organiser();
    $hero = UploadedFile::fake()->image('hero.jpg', 1600, 900);

    $this->actingAs($user)->post('/organiser/events', wizardInput(['logo' => pngLogo('#f8fafc', 0), 'hero' => $hero]))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('logo_dark', false);

    $config = Event::sole()->config;
    Storage::disk('public')->assertExists($config['branding']['logo']);
    Storage::disk('public')->assertExists($config['hero']['image']);

    $this->get('/city-runners/monsoon-10k')->assertSee(Storage::disk('public')->url($config['branding']['logo']), false);
});

it('warns about dark logos and logos without transparency', function () {
    $user = organiser();

    $this->actingAs($user)->post('/organiser/events', wizardInput(['logo' => pngLogo('#111827')]))
        ->assertSessionHas('logo_dark', true)
        ->assertSessionHas('logo_opaque', false); // transparent around the mark

    $event = Event::sole();
    $this->actingAs($user)->withSession(['logo_dark' => true])->get(route('organiser.events.published', $event))
        ->assertSee('Dark logo — pick a light template (Aurora/Clean) or a light hero background so it stays classy.');

    $solid = UploadedFile::fake()->image('logo.jpg', 400, 160);
    $this->actingAs($user)->post('/organiser/events', wizardInput(['name' => 'Another run', 'logo' => $solid]))
        ->assertSessionHas('logo_opaque', true);
});

it('rejects small photos, SVG logos and oversized files', function () {
    $user = organiser();

    $this->actingAs($user)->post('/organiser/events', wizardInput(['hero' => UploadedFile::fake()->image('hero.jpg', 800, 400)]))
        ->assertSessionHasErrors(['hero' => 'The photo must be at least 1200 × 600 pixels so it stays sharp.'])
        ->assertSessionHas('wizard_step', 4);
    $this->actingAs($user)->post('/organiser/events', wizardInput(['logo' => UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml')]))
        ->assertSessionHasErrors('logo');
    $this->actingAs($user)->post('/organiser/events', wizardInput(['logo' => UploadedFile::fake()->image('logo.png', 400, 160)->size(3000)]))
        ->assertSessionHasErrors('logo');

    expect(Event::count())->toBe(0);
});

it('edits an event in the full editor, keeping its template and slug', function () {
    $user = organiser();
    $this->actingAs($user)->post('/organiser/events', wizardInput());
    $this->actingAs($user)->post('/organiser/events', wizardInput());

    expect(Event::pluck('slug')->all())->toBe(['monsoon-10k', 'monsoon-10k-2']);

    $event = Event::first();
    $this->actingAs($user)->get("/organiser/events/{$event->id}/edit")->assertOk()->assertSee('Race categories');

    $this->actingAs($user)->put("/organiser/events/{$event->id}", eventInput(['name' => 'Monsoon 10K 2027', 'template' => 'heritage']))
        ->assertSessionHasNoErrors();

    $event->refresh();
    expect($event->name)->toBe('Monsoon 10K 2027')
        ->and($event->slug)->toBe('monsoon-10k')
        ->and($event->template_id)->toBe('heritage')
        ->and(collect($event->config['fields'])->pluck('key')->all())->toBe(['gender', 'tshirt_size', 'running_club'])
        ->and(EventSchema::errors($event->config))->toBe([]);

    $this->get('/city-runners/monsoon-10k')->assertOk()->assertSee('Order of events')->assertSee('Running club');
});

it('maps schema errors back onto the editor form', function () {
    $user = organiser();
    $this->actingAs($user)->post('/organiser/events', wizardInput());
    $event = Event::sole();

    $this->actingAs($user)
        ->put("/organiser/events/{$event->id}", ['distances' => [['code' => '', 'label' => '', 'price_inr' => '']]] + eventInput())
        ->assertSessionHasErrors('distances');
});

it('scopes events to the organiser org and blocks volunteers', function () {
    $user = organiser();
    $this->actingAs($user)->post('/organiser/events', wizardInput());
    $event = Event::sole();

    $otherOrg = Org::create(['slug' => 'others', 'name' => 'Others']);
    $stranger = User::factory()->create(['org_id' => $otherOrg->id, 'role' => 'owner']);
    $this->actingAs($stranger)->get("/organiser/events/{$event->id}/edit")->assertNotFound();
    $this->actingAs($stranger)->get("/organiser/events/{$event->id}/published")->assertNotFound();

    $volunteer = User::factory()->create(['org_id' => $user->org_id, 'role' => 'volunteer']);
    $this->actingAs($volunteer)->get('/organiser/events')->assertRedirect('/checkin');
    $this->actingAs($volunteer)->post('/organiser/events', wizardInput())->assertForbidden();

    $staff = User::factory()->create(['org_id' => $user->org_id, 'role' => 'staff']);
    $this->actingAs($staff)->get('/organiser/events')->assertOk();

    $this->post('/logout');
    $this->get('/organiser/events')->assertRedirect('/login');
});

it('refuses to delete an event with registrations', function () {
    $user = organiser();
    $this->actingAs($user)->post('/organiser/events', wizardInput());
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
