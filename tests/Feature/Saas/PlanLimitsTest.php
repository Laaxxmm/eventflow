<?php

use App\Enums\Role;
use App\Http\Controllers\RegistrationController;
use App\Models\Event;
use App\Models\Org;
use App\Models\Registration;
use App\Models\SeasonPass;
use App\Models\User;
use App\Payments\PaymentStatus;
use App\Saas\PlanAssigner;
use App\Saas\PlanLimits;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;

use function Tests\Feature\Events\registrationInput;
use function Tests\Feature\Events\syncMarathon;

require_once __DIR__.'/../Events/helpers.php';

const PLAN_REGISTER = '/demo-runners/bengaluru-city-marathon-2027/register';
const PLAN_LANDING = '/demo-runners/bengaluru-city-marathon-2027';

/** Demo marathon on $plan, with its participant cap lowered to $cap for the test. */
function marathonOnPlan(string $plan = 'single_event', ?int $cap = null): Event
{
    $event = syncMarathon();
    $pass = PlanAssigner::activate($event->org, $plan, now('Asia/Kolkata'));
    if ($cap !== null) {
        $pass->update(['max_registrations' => $cap]);
    }

    return $event;
}

function planOwner(Org $org): User
{
    return User::factory()->create(['org_id' => $org->id, 'role' => Role::Owner, 'email_verified_at' => now()]);
}

it('activates Single Event as a 90-day window that does not renew', function () {
    $org = syncMarathon()->org;

    $pass = PlanAssigner::activate($org, 'single_event', CarbonImmutable::parse('2026-11-01'));

    expect($pass->starts_on->toDateString())->toBe('2026-11-01')
        ->and($pass->ends_on->toDateString())->toBe('2027-01-29') // 90 days, inclusive
        ->and($pass->max_events)->toBe(1)
        ->and($pass->max_registrations)->toBe(500)
        ->and($pass->amount_paise)->toBe(399900)
        ->and($org->fresh()->plan)->toBe('single_event')
        ->and($org->fresh()->subscription_status)->toBe('active');
});

it('activates Club and Circuit Season for 12 months, replacing the previous term', function () {
    $org = syncMarathon()->org;
    PlanAssigner::activate($org, 'single_event', CarbonImmutable::parse('2026-11-01'));

    $club = PlanAssigner::activate($org, 'club_season', CarbonImmutable::parse('2026-11-01'));
    expect($club->ends_on->toDateString())->toBe('2027-10-31')
        ->and([$club->max_events, $club->max_registrations, $club->amount_paise])->toBe([5, 1500, 1299900])
        ->and(SeasonPass::withoutGlobalScopes()->where('org_id', $org->id)->where('status', 'active')->count())->toBe(1);

    $circuit = PlanAssigner::activate($org, 'circuit_season', CarbonImmutable::parse('2027-11-01'));
    expect([$circuit->max_events, $circuit->max_registrations, $circuit->amount_paise])->toBe([25, 5000, 2999900]);
});

it('counts races per term so unused slots do not roll over', function () {
    $event = syncMarathon();
    $org = $event->org;
    $event->forceFill(['created_at' => now()->subYear()])->save(); // created in an earlier term

    PlanAssigner::activate($org, 'single_event', now('Asia/Kolkata'));
    expect(PlanLimits::racesUsed($org))->toBe(0)->and(PlanLimits::canCreateRace($org))->toBeTrue();

    $event->forceFill(['created_at' => now()])->save();
    expect(PlanLimits::racesUsed($org))->toBe(1)->and(PlanLimits::canCreateRace($org))->toBeFalse();
});

it('sends organisers who used every race to the plan page with upgrade options', function () {
    $event = marathonOnPlan('single_event');
    $owner = planOwner($event->org);

    $this->actingAs($owner)->get('/organiser/events/create')->assertRedirect(route('account.plan'));
    $this->actingAs($owner)->post('/organiser/events', ['name' => 'Second race'])->assertRedirect(route('account.plan'));
    expect(Event::withoutGlobalScopes()->where('org_id', $event->org_id)->count())->toBe(1);

    $this->actingAs($owner)->withSession(['plan_limit' => 'races'])->get('/account/plan')
        ->assertOk()
        ->assertSee('You have used all 1 races on your plan for this term.')
        ->assertSee('Upgrade to Club Season (₹12,999)')
        ->assertSee('Buy a participant pack')
        ->assertSee('Contact Indefine');
});

it('soft-blocks registrations at the plan participant cap, for runners and organisers', function () {
    $event = marathonOnPlan('single_event', cap: 1);
    $this->post(PLAN_REGISTER, registrationInput())->assertSessionHasNoErrors();

    $this->post(PLAN_REGISTER, registrationInput(['email' => 'second@example.com']))
        ->assertSessionHasErrors(['distance' => __(RegistrationController::PAUSED_MESSAGE)]);
    expect(Registration::count())->toBe(1);

    $this->get(PLAN_LANDING)->assertOk()
        ->assertSee('Registrations for this race are paused for now')
        ->assertSee('Registrations paused')->assertDontSee('Registration closed')
        ->assertDontSee('name="participant_name"', false);

    $owner = planOwner($event->org);
    $this->actingAs($owner)->get('/dashboard')->assertOk()
        ->assertSee('has reached its plan limit (1 of 1 participants). New registrations are paused.')
        ->assertSee('Upgrade to Club Season (₹12,999)')
        ->assertSee(rawurlencode('Participant pack for Bengaluru City Marathon 2027'), false)
        ->assertSee('Contact Indefine');
});

it('lifts the soft-block when Indefine adds a participant pack', function () {
    $event = marathonOnPlan('single_event', cap: 1);
    $this->post(PLAN_REGISTER, registrationInput())->assertSessionHasNoErrors();

    Artisan::call('plans:pack', ['org' => 'demo-runners', 'event' => $event->slug, 'participants' => 1, '--note' => 'Invoice 7']);
    expect(Artisan::output())->toContain('Cap is now 2');

    $this->post(PLAN_REGISTER, registrationInput(['email' => 'second@example.com']))->assertSessionHasNoErrors();
    $this->post(PLAN_REGISTER, registrationInput(['email' => 'third@example.com']))->assertSessionHasErrors('distance');
    expect(Registration::count())->toBe(2);
});

it('warns organisers before the cap, and leaves orgs without a plan uncapped', function () {
    $event = syncMarathon();
    expect(PlanLimits::participantCap($event))->toBeNull()->and(PlanLimits::atParticipantCap($event))->toBeFalse();

    marathonOnPlan('club_season', cap: 10);
    foreach (range(1, 9) as $i) {
        Registration::create([
            'org_id' => $event->org_id, 'event_id' => $event->id, 'pass_no' => Registration::newPassNo(),
            'status' => PaymentStatus::Paid, 'participant_name' => "Runner {$i}", 'email' => "r{$i}@example.com",
            'phone' => '9876543210', 'distance_code' => '5K', 'amount_paise' => 59900,
        ]);
    }

    $this->actingAs(planOwner($event->org))->get('/dashboard')->assertOk()
        ->assertSee('is close to its plan limit: 9 of 10 participants.');
    $this->get(PLAN_LANDING)->assertOk()->assertSee('name="participant_name"', false); // still open
});

it('activates plans from the command line and rejects bad input', function () {
    syncMarathon();

    $this->artisan('plans:activate', ['org' => 'demo-runners', 'plan' => 'club_season', '--from' => '2026-11-01', '--addon' => ['whatsapp']])
        ->expectsOutputToContain('Club Season from 01 Nov 2026 to 31 Oct 2027 (12 months). Up to 5 races · 1,500 participants per race.')
        ->assertSuccessful();
    expect(Org::where('slug', 'demo-runners')->sole()->addons)->toBe(['whatsapp']);

    $this->artisan('plans:activate', ['org' => 'demo-runners', 'plan' => 'gold'])->expectsOutputToContain('Unknown plan')->assertFailed();
    $this->artisan('plans:activate', ['org' => 'nobody', 'plan' => 'single_event'])->assertFailed();
    $this->artisan('plans:activate', ['org' => 'demo-runners', 'plan' => 'single_event', '--addon' => ['custom_smtp']])->expectsOutputToContain('Unknown add-on')->assertFailed();
});
