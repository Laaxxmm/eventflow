<?php

use App\Enums\Module;
use App\Enums\Role;
use App\Models\Org;
use App\Models\SeasonPass;
use App\Models\User;
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

it('serves the SaaS marketing home at / with plans from config', function () {
    config(['plans.plans.starter.season_pass_inr' => 12345]);

    $this->get('/')
        ->assertOk()
        ->assertSee('EventFlow')
        ->assertSee('>by Indefine<', false)
        ->assertSee('your start')
        ->assertSee('Start your season — ₹12,345') // cheapest self-serve plan drives the hero CTA
        ->assertSee('Starter')->assertSee('Growth')->assertSee('Scale')
        ->assertSee('₹12,345')
        ->assertSee(route('pricing'))
        ->assertSee(route('login'));
});

it('leads with a single-race plan, cheapest first, when one is configured', function () {
    config(['plans.plans' => ['single' => [
        'name' => 'Single', 'tagline' => 'One race.', 'season_pass_inr' => 3999, 'participant_fee_percent' => null,
        'self_serve' => true, 'max_events' => 1, 'max_registrations' => 500, 'modules' => ['checkin'], 'features' => ['One event'],
    ]] + config('plans.plans')]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Start with one race — ₹3,999')
        ->assertSeeInOrder(['Single', 'Starter', 'Growth', 'Scale']);

    $this->get('/pricing')->assertOk()->assertSeeInOrder(['₹3,999', '₹19,999', '₹49,999', 'Custom']);
});

it('shows three selectable plans and add-ons on /pricing', function () {
    $this->get('/pricing')
        ->assertOk()
        ->assertSee('Starter')->assertSee('Growth')->assertSee('Scale')
        ->assertSee('₹19,999')->assertSee('₹49,999')
        ->assertSee('WhatsApp notifications')
        ->assertSee('name="plan" value="starter"', false)
        ->assertSee('name="plan" value="growth"', false)
        ->assertDontSee('name="plan" value="scale"', false) // contact-only
        ->assertSee('mailto:hello@indefine.in', false);
});

it('can hide the Indefine brand line', function () {
    config(['plans.brand.by' => null]);

    $this->get('/')->assertOk()->assertDontSee('>by Indefine<', false)->assertDontSee('A product by');
});

it('registers an organiser with an org on the chosen plan and a free trial', function () {
    $this->get('/register?plan=growth&addons[]=whatsapp')
        ->assertOk()
        ->assertSee('value="growth"', false)
        ->assertSee('Billing connects when Cashfree/Razorpay SaaS subscription is live');

    $this->post('/register', signupForm(['plan' => 'growth', 'addons' => ['whatsapp']]))
        ->assertRedirect(route('account.plan', ['welcome' => 1], absolute: false));

    $user = User::where('email', 'meera@sunrise.test')->sole();
    $org = $user->org;

    $this->assertAuthenticatedAs($user);
    expect($user->role)->toBe(Role::Owner)
        ->and($user->isPlatformAdmin())->toBeFalse()
        ->and($org->slug)->toBe('sunrise-sports-club')
        ->and($org->plan)->toBe('growth')
        ->and($org->addons)->toBe(['whatsapp'])
        ->and($org->subscription_status)->toBe('trialing')
        ->and($org->trial_ends_at->toDateString())->toBe(now()->addDays(14)->toDateString())
        ->and($org->modules)->toMatchArray(['race_sheets' => true, 'certificates' => true, 'sms_whatsapp' => true, 'email_branding' => false]);

    $pass = SeasonPass::withoutGlobalScopes()->where('org_id', $org->id)->sole();
    expect($pass->status)->toBe('active')
        ->and($pass->amount_paise)->toBe(0)
        ->and($pass->modules->contains(Module::SmsWhatsapp))->toBeTrue()
        ->and($pass->modules->contains(Module::EmailBranding))->toBeFalse();
});

it('defaults to the Starter trial and rejects contact-only plans or unknown add-ons', function () {
    $this->post('/register', signupForm(['plan' => 'scale']))->assertSessionHasErrors('plan');
    $this->post('/register', signupForm(['addons' => ['free-money']]))->assertSessionHasErrors('addons.0');

    $this->post('/register', signupForm())->assertSessionHasNoErrors();
    expect(User::where('email', 'meera@sunrise.test')->sole()->org->plan)->toBe('starter');
});

it('never lets a signup make itself a platform admin', function () {
    $this->post('/register', signupForm(['is_platform_admin' => '1']));

    expect(User::where('email', 'meera@sunrise.test')->sole()->isPlatformAdmin())->toBeFalse();
});

it('keeps org slugs off reserved site paths', function () {
    $this->post('/register', signupForm(['organization_name' => 'Pricing']))->assertSessionHasNoErrors();

    expect(User::where('email', 'meera@sunrise.test')->sole()->org->slug)->toBe('pricing-events');
});

it('shows the organiser their plan, trial and billing note', function () {
    $this->post('/register', signupForm(['plan' => 'starter']));

    $this->get('/account/plan?welcome=1')
        ->assertOk()
        ->assertSee('Welcome to EventFlow, Sunrise Sports Club!')
        ->assertSee('We sent a verification link to meera@sunrise.test.')
        ->assertSee('Free trial until')
        ->assertSee('Billing connects when Cashfree/Razorpay SaaS subscription is live');
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
    $this->post('/register', signupForm(['plan' => 'growth']));
    $this->post('/logout');
    organiserOf('Lake City Skaters');

    $this->actingAs(platformAdmin())->get('/platform/orgs')
        ->assertOk()
        ->assertSee('Sunrise Sports Club')->assertSee('Growth')->assertSee('Trialing')
        ->assertSee('Lake City Skaters');

    $this->actingAs(platformAdmin())->get('/platform/orgs?plan=growth')
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
        ->and($organiser->org->plan)->toBe('starter')
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
