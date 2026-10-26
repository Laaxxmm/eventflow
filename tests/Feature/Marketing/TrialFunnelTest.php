<?php

use App\Enums\Role;
use App\Landing\EventStatus;
use App\Models\Event;
use App\Models\Org;
use App\Models\Registration;
use App\Models\User;
use App\Saas\PlanAssigner;
use App\Saas\Trial;

use function Tests\Feature\Events\registrationInput;
use function Tests\Feature\Events\syncMarathon;

require_once __DIR__.'/../Events/helpers.php';

function trialOwner(): User
{
    $org = Org::create(['name' => 'Sunrise Sports Club', 'slug' => 'sunrise-sports-club']);
    PlanAssigner::startTrial($org, 'club_season'); // 5 races, so trial-only limits are what bite

    return User::factory()->create(['org_id' => $org->id, 'role' => Role::Owner, 'email_verified_at' => now()]);
}

function trialWizard(array $overrides = []): array
{
    return array_replace([
        'name' => 'Monsoon 10K', 'date' => '2027-07-04T06:00', 'venue' => 'Cubbon Park', 'tagline' => 'Run in the rain',
        'sport' => 'running', 'template' => 'stadium-split', 'primary' => '#D9FF45', 'secondary' => '#ff6e61', 'publish' => '1',
        'distances' => [['code' => '5K', 'label' => '5K', 'price_inr' => '499', 'capacity' => '100']],
    ], $overrides);
}

it('ships the trial rules as plans.trial_* config with TRIAL_STUB on by default', function () {
    expect(config('plans.trial_days'))->toBe(14)
        ->and(config('plans.trial_events'))->toBe(1)
        ->and(config('plans.trial_registrations'))->toBe(25)
        ->and(config('plans.trial_watermark'))->toBeTrue()
        ->and(Trial::stubbed())->toBeTrue();
});

it('lets a trial org publish without a human gate', function () {
    $owner = trialOwner();

    $this->actingAs($owner)->post(route('organiser.events.store'), trialWizard())->assertSessionHasNoErrors();

    expect(Event::withoutGlobalScopes()->where('org_id', $owner->org_id)->sole()->status)->toBe(EventStatus::Published);
});

it('never blocks a second publish while TRIAL_STUB is on', function () {
    $owner = trialOwner();
    $this->actingAs($owner)->post(route('organiser.events.store'), trialWizard());
    $this->actingAs($owner)->post(route('organiser.events.store'), trialWizard(['name' => 'Winter 5K']))->assertSessionHasNoErrors();

    expect(Trial::publishedEvents($owner->org))->toBe(2);
});

it('holds a trial to one live event once the paywall is on', function () {
    config(['plans.trial_stub' => false]);
    $owner = trialOwner();

    $this->actingAs($owner)->post(route('organiser.events.store'), trialWizard())->assertSessionHasNoErrors();
    $this->actingAs($owner)->post(route('organiser.events.store'), trialWizard(['name' => 'Winter 5K']))
        ->assertSessionHasErrors(['publish' => 'Your free trial covers 1 live event. Pick Single Event, Club Season or Circuit Season in Plan & billing to publish more.']);

    // A draft is still fine.
    $this->actingAs($owner)->post(route('organiser.events.store'), trialWizard(['name' => 'Winter 5K', 'publish' => '0']))->assertSessionHasNoErrors();
    expect(Trial::publishedEvents($owner->org))->toBe(1);
});

it('caps a trial at 25 test registrations once the paywall is on', function () {
    $event = syncMarathon();
    $event->org->forceFill(['plan' => 'single_event', 'subscription_status' => 'trialing', 'trial_ends_at' => now()->addDays(10)])->save();
    Registration::factory()->count(25)->create(['event_id' => $event->id]);

    $this->post('/demo-runners/bengaluru-city-marathon-2027/register', registrationInput())->assertSessionHasNoErrors(); // stubbed

    config(['plans.trial_stub' => false]);
    $this->post('/demo-runners/bengaluru-city-marathon-2027/register', registrationInput(['email' => 'b@example.com']))
        ->assertSessionHasErrors('distance');
});

it('shows "Powered by EventFlow" on trial landings only', function () {
    $event = syncMarathon();

    $this->get('/demo-runners/bengaluru-city-marathon-2027')->assertOk()->assertDontSee('data-watermark', false);

    $event->org->forceFill(['plan' => 'single_event', 'subscription_status' => 'trialing', 'trial_ends_at' => now()->addDays(10)])->save();
    $this->get('/demo-runners/bengaluru-city-marathon-2027')->assertSee('data-watermark', false)->assertSee('Powered by EventFlow');

    config(['plans.trial_watermark' => false]);
    $this->get('/demo-runners/bengaluru-city-marathon-2027')->assertDontSee('data-watermark', false);
});

it('puts a trial banner and the homepage promise on an empty dashboard', function () {
    $this->actingAs(trialOwner())->get('/dashboard')
        ->assertOk()
        ->assertSee('data-trial-banner', false)
        ->assertSee('Free trial · 14 days left')
        ->assertSee('0/1 live event · 0/25 test registrations')
        ->assertSee('Plans from ₹3,999')
        ->assertSee('Publish your race site in under an hour')
        ->assertSee('Your runners land on a sports page, not a form');
});

it('shows a public gallery of the five templates', function () {
    $this->get('/templates')
        ->assertOk()
        ->assertSee('Your runners land on a')
        ->assertSeeInOrder(['Gun Lap', 'Stadium Split', 'Equipment Close', 'Night Circuit', 'Heritage Crest'])
        ->assertSee(route('templates.show', 'night-circuit'), false)
        ->assertSee(route('register', ['template' => 'night-circuit']), false);
});

it('previews each template publicly with a Use this template CTA', function (string $template) {
    $this->get(route('templates.show', $template))
        ->assertOk()
        ->assertSee('data-template="'.$template.'"', false)
        ->assertSee('data-gallery-bar', false)
        ->assertSee('Use this template')
        ->assertSee(route('register', ['template' => $template]), false)
        ->assertSee('name="robots" content="noindex"', false)
        ->assertSee('onsubmit="return false"', false); // sample form never posts
})->with(['gun-lap', 'stadium-split', 'equipment-close', 'night-circuit', 'heritage-crest']);

it('404s an unknown template and sends signed-in organisers straight to the wizard', function () {
    $this->get('/templates/neon')->assertNotFound();

    $this->actingAs(trialOwner())->get(route('templates.show', 'heritage-crest'))
        ->assertSee(route('organiser.events.create', ['template' => 'heritage-crest']), false);
});

it('carries the chosen template from signup into the create wizard', function () {
    $this->get('/register?template=equipment-close')
        ->assertOk()
        ->assertSee('name="template" value="equipment-close"', false)
        ->assertSee('Equipment Close will be ready when you create your first event.');

    $this->post('/register', [
        'organization_name' => 'Sunrise Sports Club', 'name' => 'Meera Iyer', 'email' => 'meera@sunrise.test',
        'password' => 'correct-horse-battery', 'password_confirmation' => 'correct-horse-battery', 'template' => 'equipment-close',
    ])->assertRedirect(route('account.plan', ['welcome' => 1, 'template' => 'equipment-close'], absolute: false));

    $this->get(route('account.plan', ['welcome' => 1, 'template' => 'equipment-close']))
        ->assertSee('Create your event with Equipment Close')
        ->assertSee(route('organiser.events.create', ['template' => 'equipment-close']), false);

    $user = User::where('email', 'meera@sunrise.test')->sole();
    $user->forceFill(['email_verified_at' => now()])->save();
    $this->actingAs($user)->get(route('organiser.events.create', ['template' => 'equipment-close']))
        ->assertOk()
        ->assertViewHas('presetTemplate', 'equipment-close');
});

it('ignores a template key it does not ship', function () {
    $this->get('/register?template=neon')->assertOk()->assertDontSee('name="template"', false);
    $this->post('/register', [
        'organization_name' => 'Sunrise', 'name' => 'Meera', 'email' => 'meera@sunrise.test',
        'password' => 'correct-horse-battery', 'password_confirmation' => 'correct-horse-battery', 'template' => 'neon',
    ])->assertSessionHasErrors('template');
});
