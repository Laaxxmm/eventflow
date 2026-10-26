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

it('serves the SaaS marketing home at / pricing-first, Single Event leading', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('EventFlow')
        ->assertSee('>by Indefine<', false)
        ->assertSee('your start')
        ->assertSee('Start with one race — ₹3,999') // Single Event drives the hero CTA
        ->assertSeeInOrder(['Single Event', '₹3,999', '1 race · 500 participants per race', 'Club Season', '₹12,999', 'Up to 5 races · 1,500 participants per race', 'Circuit Season', '₹29,999', 'Up to 25 races · 5,000 participants per race', 'Custom', 'Contact Indefine'])
        ->assertSeeInOrder(['id="pricing"', 'id="race-day"'], false) // pricing comes straight after the hero
        ->assertSee(route('pricing'))
        ->assertSee(route('login'));
});

it('shows the locked INR plans, terms and add-ons on /pricing', function () {
    $this->get('/pricing')
        ->assertOk()
        ->assertSeeInOrder(['Single Event', '₹3,999', '90-day window', 'One-off · does not auto-renew', 'Club Season', '₹12,999', '12 months', 'Renews yearly · unused races do not roll over', 'Circuit Season', '₹29,999', 'Custom'])
        ->assertSee('name="plan" value="single_event"', false)
        ->assertSee('name="plan" value="club_season"', false)
        ->assertSee('name="plan" value="circuit_season"', false)
        ->assertDontSee('name="plan" value="custom"', false) // contact-only, no fake price
        ->assertSee('mailto:hello@indefine.in', false)
        ->assertDontSee('on paid registrations');
});

it('never shows Platform mail or WhatsApp as included in the comparison', function () {
    $html = $this->get('/pricing')->assertOk()->getContent();

    foreach (['Email', 'Own SMTP'] as $row) {
        expect($html)->toMatch('#>'.$row.'</th>(\s*<td class="p-4">\s*<span class="inline-flex" aria-label="Included">(?:(?!</td>).)*</td>){4}\s*</tr>#s');
    }
    foreach (['Platform mail', 'WhatsApp'] as $row) {
        expect($html)->toMatch('#>'.$row.'</th>(\s*<td class="p-4">\s*Add-on\s*</td>){4}\s*</tr>#');
    }
});

it('locks plan prices, terms and caps in config', function () {
    expect(config('plans.plans.single_event'))->toMatchArray(['price_inr' => 3999, 'term' => ['days' => 90], 'renews' => false, 'max_events' => 1, 'max_participants_per_event' => 500])
        ->and(config('plans.plans.club_season'))->toMatchArray(['price_inr' => 12999, 'term' => ['months' => 12], 'renews' => true, 'max_events' => 5, 'max_participants_per_event' => 1500])
        ->and(config('plans.plans.circuit_season'))->toMatchArray(['price_inr' => 29999, 'term' => ['months' => 12], 'renews' => true, 'max_events' => 25, 'max_participants_per_event' => 5000])
        ->and(config('plans.plans.custom'))->toMatchArray(['price_inr' => null, 'self_serve' => false])
        ->and(array_key_first(config('plans.plans')))->toBe('single_event')
        ->and(config('plans.addons.platform_mail.module'))->toBe('email_branding')
        ->and(config('plans.addons.whatsapp.module'))->toBe('sms_whatsapp');
});

it('can hide the Indefine brand line', function () {
    config(['plans.brand.by' => null]);

    $this->get('/')->assertOk()->assertDontSee('>by Indefine<', false)->assertDontSee('A product by');
});

it('registers an organiser with an org on the chosen plan and a free trial', function () {
    $this->get('/register?plan=club_season&addons[]=whatsapp')
        ->assertOk()
        ->assertSee('value="club_season"', false)
        ->assertSee('Up to 5 races · 1,500 participants per race');

    $this->post('/register', signupForm(['plan' => 'club_season', 'addons' => ['whatsapp']]))
        ->assertRedirect(route('account.plan', ['welcome' => 1], absolute: false));

    $user = User::where('email', 'meera@sunrise.test')->sole();
    $org = $user->org;

    $this->assertAuthenticatedAs($user);
    expect($user->role)->toBe(Role::Owner)
        ->and($user->isPlatformAdmin())->toBeFalse()
        ->and($org->slug)->toBe('sunrise-sports-club')
        ->and($org->plan)->toBe('club_season')
        ->and($org->addons)->toBe(['whatsapp'])
        ->and($org->subscription_status)->toBe('trialing')
        ->and($org->trial_ends_at->toDateString())->toBe(now()->addDays(14)->toDateString())
        ->and($org->modules)->toMatchArray(['race_sheets' => true, 'certificates' => true, 'sms_whatsapp' => true, 'email_branding' => false]);

    $pass = SeasonPass::withoutGlobalScopes()->where('org_id', $org->id)->sole();
    expect($pass->status)->toBe('active')
        ->and($pass->amount_paise)->toBe(0)
        ->and($pass->max_events)->toBe(5)
        ->and($pass->max_registrations)->toBe(1500)
        ->and($pass->modules->contains(Module::SmsWhatsapp))->toBeTrue()
        ->and($pass->modules->contains(Module::EmailBranding))->toBeFalse();
});

it('defaults to the Single Event trial and rejects contact-only plans or unknown add-ons', function () {
    $this->post('/register', signupForm(['plan' => 'custom']))->assertSessionHasErrors('plan');
    $this->post('/register', signupForm(['addons' => ['free-money']]))->assertSessionHasErrors('addons.0');

    $this->post('/register', signupForm())->assertSessionHasNoErrors();
    expect(User::where('email', 'meera@sunrise.test')->sole()->org->plan)->toBe('single_event');
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
    $this->post('/register', signupForm(['plan' => 'single_event']));

    $this->get('/account/plan?welcome=1')
        ->assertOk()
        ->assertSee('Welcome to EventFlow, Sunrise Sports Club!')
        ->assertSee('We sent a verification link to meera@sunrise.test.')
        ->assertSee('Free trial until')
        ->assertSee('₹3,999')->assertSee('one-off, does not auto-renew')
        ->assertSee('0 of 1')
        ->assertSee('On hold: set up email')
        ->assertSee('Upgrade to Club Season (₹12,999)')
        ->assertSee('Buy a participant pack')
        ->assertSee('Online plan billing is being connected');
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
    $this->post('/register', signupForm(['plan' => 'club_season']));
    $this->post('/logout');
    organiserOf('Lake City Skaters');

    $this->actingAs(platformAdmin())->get('/platform/orgs')
        ->assertOk()
        ->assertSee('Sunrise Sports Club')->assertSee('Club Season')->assertSee('Trialing')
        ->assertSee('Lake City Skaters');

    $this->actingAs(platformAdmin())->get('/platform/orgs?plan=club_season')
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
        ->and($organiser->org->plan)->toBe('single_event')
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
