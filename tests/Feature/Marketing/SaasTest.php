<?php

use App\Enums\Module;
use App\Enums\Role;
use App\Models\Org;
use App\Models\SeasonPass;
use App\Models\User;
use App\Saas\Plans;
use Database\Seeders\PlatformSeeder;
use Illuminate\Support\Str;

function signupForm(array $overrides = []): array
{
    return [
        'organization_name' => 'Sunrise Sports Club',
        'name' => 'Meera Iyer',
        'email' => 'meera@sunrise.test',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
        ...$overrides,
    ];
}

function platformAdmin(): User
{
    $admin = User::factory()->create(['org_id' => null, 'email_verified_at' => now()]);
    $admin->forceFill(['is_platform_admin' => true])->save();

    return $admin;
}

function organiserOf(string $name = 'Trail Runners'): User
{
    $org = Org::create(['name' => $name, 'slug' => Str::slug($name)]);

    return User::factory()->create(['org_id' => $org->id, 'role' => Role::Owner, 'email_verified_at' => now()]);
}

it('serves the self-serve SaaS homepage with Single-first pricing from config', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('>by Indefine<', false)
        ->assertSee('Publish a race site you’re proud of')
        ->assertSee('— in under an hour.')
        ->assertSee('No ticket % required')->assertSee('Own SMTP free')->assertSee('India Razorpay-ready')
        ->assertSee('Start free trial')
        ->assertSee('href="'.route('register').'"', false)
        ->assertSee('See live templates')
        ->assertSee('href="'.route('templates.index').'"', false)
        ->assertSeeInOrder(['Single Event', '₹3,999', 'Club Season', '₹12,999', 'Circuit Season', '₹29,999']) // price line under the hero
        ->assertSee('Your runners land on a sports page, not a form.')
        ->assertSee('Soft pax caps')
        ->assertSee('Email ✓')->assertSee('Platform mail add-on')->assertSee('WhatsApp add-on')
        ->assertSee('Upgrade, participant packs, and SMTP — all in settings. No waiting on a quote to go live.')
        ->assertSee('Talk to Indefine')
        ->assertSee('Managed race ops by Indefine')
        ->assertDontSee('Book a demo');
});

it('keeps the nav to Product, Templates, Pricing, Login and Start free trial', function () {
    $this->get('/')->assertSeeInOrder(['Product', 'Templates', 'Pricing', 'Login', 'Start free trial']);
});

it('reads every price on the homepage from config', function () {
    config(['plans.plans.single.season_pass_inr' => 4499]);

    $this->get('/')->assertOk()->assertSee('₹4,499')->assertDontSee('₹3,999');
});

it('lists Single, Club, Circuit and a contact-only Custom on /pricing', function () {
    $this->get('/pricing')
        ->assertOk()
        ->assertSeeInOrder(['Single Event', '₹3,999', '90-day window', 'Club Season', '₹12,999', 'Circuit Season', '₹29,999', 'Custom'])
        ->assertSee('1 race')->assertSee('500 pax')
        ->assertSee('5 races')->assertSee('1,500 pax/event')
        ->assertSee('25 races')->assertSee('5,000 pax/event')
        ->assertSee(route('register', ['plan' => 'single']), false)
        ->assertSee(route('register', ['plan' => 'club']), false)
        ->assertSee(route('register', ['plan' => 'circuit']), false)
        ->assertDontSee(route('register', ['plan' => 'custom']), false) // contact-only
        ->assertSee('mailto:hello@indefine.in', false);
});

it('compares races, duration, pax, mail, WhatsApp and support, and explains overage', function () {
    $this->get('/pricing')
        ->assertSee('data-compare', false)
        ->assertSeeInOrder(['Races', 'Duration', 'Participants', 'Ticket percentage', 'Email confirmations · own SMTP', 'Platform mail', 'WhatsApp', 'Support'])
        ->assertSee('data-overage', false)
        ->assertSeeInOrder(['Upgrade', 'Buy a participant pack', 'Contact Indefine']);
});

it('can hide the Indefine brand line', function () {
    config(['plans.brand.by' => null]);

    $this->get('/')->assertOk()->assertDontSee('>by Indefine<', false)->assertDontSee('A product by');
});

it('registers an organiser on a free trial of the chosen plan', function () {
    $this->get('/register?plan=club')
        ->assertOk()
        ->assertSee('Start your free trial')
        ->assertSee('14 days · 1 event you can publish · up to 25 test registrations · no card.')
        ->assertSee('value="club"', false)
        ->assertDontSee('% on paid registrations');

    $this->post('/register', signupForm(['plan' => 'club', 'addons' => ['whatsapp']]))
        ->assertRedirect(route('account.plan', ['welcome' => 1], absolute: false));

    $user = User::where('email', 'meera@sunrise.test')->sole();
    $org = $user->org;

    $this->assertAuthenticatedAs($user);
    expect($user->role)->toBe(Role::Owner)
        ->and($user->isPlatformAdmin())->toBeFalse()
        ->and($org->slug)->toBe('sunrise-sports-club')
        ->and($org->plan)->toBe('club')
        ->and($org->addons)->toBe(['whatsapp'])
        ->and($org->subscription_status)->toBe('trialing')
        ->and($org->trial_ends_at->toDateString())->toBe(now()->addDays(14)->toDateString())
        ->and($org->modules)->toMatchArray(['race_sheets' => true, 'certificates' => true, 'sms_whatsapp' => true, 'email_branding' => true]);

    $pass = SeasonPass::withoutGlobalScopes()->where('org_id', $org->id)->sole();
    expect($pass->status)->toBe('active')
        ->and($pass->amount_paise)->toBe(0)
        ->and($pass->max_events)->toBe(1)          // the trial's limits, not Club's
        ->and($pass->max_registrations)->toBe(25)
        ->and($pass->modules->contains(Module::SmsWhatsapp))->toBeTrue()
        ->and($pass->modules->contains(Module::EmailBranding))->toBeTrue(); // own SMTP is free
});

it('defaults to the Single Event trial and rejects contact-only plans or unknown add-ons', function () {
    $this->post('/register', signupForm(['plan' => 'custom']))->assertSessionHasErrors('plan');
    $this->post('/register', signupForm(['addons' => ['free-money']]))->assertSessionHasErrors('addons.0');

    $this->post('/register', signupForm())->assertSessionHasNoErrors();
    expect(User::where('email', 'meera@sunrise.test')->sole()->org->plan)->toBe('single');
});

it('resolves pre-M2 plan keys to their successors', function () {
    expect(Plans::resolve('starter'))->toBe('club')
        ->and(Plans::resolve('growth'))->toBe('circuit')
        ->and(Plans::resolve('scale'))->toBe('custom')
        ->and(Plans::get('growth')['name'])->toBe('Circuit Season')
        ->and(Plans::forOrg('starter')['name'])->toBe('Club Season');
});

it('never lets a signup make itself a platform admin', function () {
    $this->post('/register', signupForm(['is_platform_admin' => '1']));

    expect(User::where('email', 'meera@sunrise.test')->sole()->isPlatformAdmin())->toBeFalse();
});

it('keeps org slugs off reserved site paths', function () {
    $this->post('/register', signupForm(['organization_name' => 'Pricing']))->assertSessionHasNoErrors();

    expect(User::where('email', 'meera@sunrise.test')->sole()->org->slug)->toBe('pricing-events');
});

it('shows the organiser their plan, trial use and billing note', function () {
    $this->post('/register', signupForm(['plan' => 'single']));

    $this->get('/account/plan?welcome=1')
        ->assertOk()
        ->assertSee('Welcome to EventFlow, Sunrise Sports Club!')
        ->assertSee('We sent a verification link to meera@sunrise.test.')
        ->assertSee('Free trial until')
        ->assertSee('0 of 1 live events · 0 of 25 registrations')
        ->assertSee('Self-serve checkout for Single Event, Club and Circuit is coming to this page.');
});

it('logs organisers in to the dashboard and platform admins in to the console', function () {
    $organiser = organiserOf();
    $this->post('/login', ['email' => $organiser->email, 'password' => 'password'])->assertRedirect(route('dashboard', absolute: false));
    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();

    $admin = platformAdmin();
    $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect(route('platform.orgs.index', absolute: false));
    $this->get('/app')->assertRedirect(route('platform.orgs.index'));
});

it('keeps /platform to platform admins', function () {
    $this->get('/platform/orgs')->assertRedirect('/login');

    $organiser = organiserOf();
    $this->actingAs($organiser)->get('/platform/orgs')->assertForbidden();
    $this->actingAs($organiser)->post("/platform/orgs/{$organiser->org_id}/suspend")->assertForbidden();
    expect($organiser->org->fresh()->isSuspended())->toBeFalse();
});

it('lists every organisation with plan and status for platform admins', function () {
    $this->post('/register', signupForm(['plan' => 'circuit']));
    $this->post('/logout');
    organiserOf('Lake City Skaters');

    $this->actingAs(platformAdmin())->get('/platform/orgs')
        ->assertOk()
        ->assertSee('Sunrise Sports Club')->assertSee('Circuit Season')->assertSee('Trialing')
        ->assertSee('Lake City Skaters');

    $this->actingAs(platformAdmin())->get('/platform/orgs?plan=circuit')
        ->assertSee('Sunrise Sports Club')->assertDontSee('Lake City Skaters');
});

it('suspends an organisation and signs its members out', function () {
    $organiser = organiserOf();
    $admin = platformAdmin();

    $this->actingAs($admin)->post("/platform/orgs/{$organiser->org_id}/suspend")->assertRedirect();
    expect($organiser->org->fresh()->isSuspended())->toBeTrue();

    $this->actingAs($organiser->fresh())->get('/dashboard')->assertRedirect(route('login'));
    $this->assertGuest();

    $this->actingAs($admin)->post("/platform/orgs/{$organiser->org_id}/reactivate");
    $this->actingAs($organiser->fresh())->get('/dashboard')->assertOk();
});

it('extends a trial from its current end date', function () {
    $this->post('/register', signupForm());
    $org = User::where('email', 'meera@sunrise.test')->sole()->org;
    $this->post('/logout');

    $this->actingAs(platformAdmin())->post("/platform/orgs/{$org->id}/extend-trial");

    expect($org->fresh()->trial_ends_at->toDateString())->toBe(now()->addDays(28)->toDateString());
});

it('seeds the demo platform admin and organiser from env passwords', function () {
    config(['plans.demo.admin_password' => 'admin-secret-1', 'plans.demo.organiser_password' => 'org-secret-1']);

    $this->seed(PlatformSeeder::class);
    $this->seed(PlatformSeeder::class); // idempotent

    $admin = User::withoutGlobalScopes()->where('email', 'admin@eventflow.local')->sole();
    $organiser = User::withoutGlobalScopes()->where('email', 'organiser@eventflow.local')->sole();

    expect($admin->isPlatformAdmin())->toBeTrue()->and($admin->org_id)->toBeNull()
        ->and(Hash::check('admin-secret-1', $admin->password))->toBeTrue()
        ->and($organiser->isPlatformAdmin())->toBeFalse()
        ->and($organiser->org->plan)->toBe('single')
        ->and(SeasonPass::withoutGlobalScopes()->where('org_id', $organiser->org_id)->count())->toBe(1);
});

it('refuses to seed without passwords, or with the example ones in production', function () {
    config(['plans.demo.admin_password' => null]);
    expect(fn () => (new PlatformSeeder)->run())->toThrow(RuntimeException::class, 'PLATFORM_ADMIN_PASSWORD');

    config(['plans.demo.admin_password' => 'ChangeMe-EventFlow-Admin!', 'plans.demo.organiser_password' => 'x']);
    app()->detectEnvironment(fn () => 'production');
    expect(fn () => (new PlatformSeeder)->run())->toThrow(RuntimeException::class, '.env.example value');
    expect(User::withoutGlobalScopes()->where('email', 'admin@eventflow.local')->exists())->toBeFalse();
});
