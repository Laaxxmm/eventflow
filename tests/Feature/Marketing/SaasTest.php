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

it('serves the homepage bands: hero, sports, how it works, templates, pricing, one CTA', function () {
    $html = $this->get('/')
        ->assertOk()
        ->assertSee('>by Indefine<', false)
        ->assertSee('Put your race online in an hour.')
        ->assertSee('14 days free. No card, no call, and we never take a cut of your entry fees.') // the one proof line
        ->assertSee('href="'.route('register').'"', false)
        ->assertSee('See the templates')
        ->assertSee('data-track-stage', false) // the 3D track hero
        ->assertSee('What happens after you press Publish')
        ->assertSeeInOrder(['Single Event', '₹3,999', 'Club Season', '₹12,999', 'Circuit Season', '₹29,999'])
        ->assertSee('Race sheet ₹599')->assertSee('Certificates ₹799')->assertSee('platform mail')->assertSee('WhatsApp')
        ->assertSee('Custom plans for federations and series')
        ->assertDontSee('Talk to Indefine') // Contact is never a primary CTA
        ->getContent();

    // Six bands below the nav, in order.
    preg_match_all('/data-band="([a-z-]+)"/', $html, $bands);
    expect($bands[1])->toBe(['hero', 'sports', 'how-it-works', 'templates', 'pricing', 'cta']);
    // The final band has exactly one button and no contact link.
    $cta = (string) str($html)->after('data-band="cta"')->before('</section>');
    expect(substr_count($cta, '</a>'))->toBe(1)->and($cta)->toContain('Start your free trial')->not->toContain('mailto:');
    // Single Event is the highlighted bib.
    expect((string) str($html)->after('data-band="pricing"')->before('data-plan="single_event"'))->toContain('bg-track text-white');
});

it('shows the templates as a 3D fan on / and keeps the full gallery on /templates', function () {
    $html = $this->get('/')->getContent();
    $band = (string) str($html)->after('data-band="templates"')->before('</section>');

    foreach (config('eventflow.landing_templates') as $key => $tpl) {
        expect($band)->toContain($tpl['label'])->toContain(route('templates.show', $key));
    }
    expect($band)->toContain('aria-pressed')->toContain(route('templates.index'))
        ->and($html)->not->toContain('data-template-card'); // the gallery cards live on /templates
});

it('keeps the nav to Templates, Pricing, Log in and Start free trial', function () {
    $nav = str($this->get('/')->getContent())->between('<header', '</header>');
    expect((string) $nav)->not->toContain('>Product<');
    $this->get('/')->assertSeeInOrder(['Templates', 'Pricing', 'Log in', 'Start free trial']);
});

it('stops every homepage animation under prefers-reduced-motion', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain('@media (prefers-reduced-motion: reduce)')
        ->toContain('.ef-spin, [data-motion] { animation: none !important;')
        ->toContain('.ef-float, .ef-runner, .ef-medal, .ef-sway, .ef-lane, .ef-seal, .ef-scan { animation: none !important; }');
    $this->get('/')->assertSee('ef-spin', false)->assertSee('data-motion', false);
});

it('keeps the full template gallery on /templates', function () {
    $html = $this->get('/templates')->assertOk()->getContent();

    foreach (array_keys(config('eventflow.landing_templates')) as $key) {
        expect($html)->toContain('data-template-card="'.$key.'"')
            ->toContain(route('register', ['template' => $key]));
    }
});

it('prices the race sheet and certificate add-ons on /pricing', function () {
    $this->get('/pricing')->assertOk()
        ->assertSeeInOrder(['Race sheet download', '₹599 per event', 'Certificates', '₹799 per event', 'Platform mail', 'WhatsApp'])
        ->assertSee('Add-on, ₹599 per event') // Single Event row in the comparison
        ->assertSee('Included in Club Season, Circuit Season');
});

it('reads every price on the homepage from config', function () {
    config(['plans.plans.single_event.price_inr' => 4499]);

    $this->get('/')->assertOk()->assertSee('₹4,499')->assertDontSee('₹3,999');
});

it('lists Single, Club, Circuit and a contact-only Custom on /pricing', function () {
    $this->get('/pricing')
        ->assertOk()
        ->assertSeeInOrder(['Single Event', '₹3,999', '90-day window', 'Club Season', '₹12,999', '12 months', 'Circuit Season', '₹29,999', 'Custom'])
        ->assertSee('1 race · 500 participants per race')
        ->assertSee('Up to 5 races · 1,500 participants per race')
        ->assertSee('Up to 25 races · 5,000 participants per race')
        ->assertSee(route('register', ['plan' => 'single_event']), false)
        ->assertSee(route('register', ['plan' => 'club_season']), false)
        ->assertSee(route('register', ['plan' => 'circuit_season']), false)
        ->assertDontSee(route('register', ['plan' => 'custom']), false) // contact-only
        ->assertSee('mailto:hello@indefine.in', false)
        ->assertDontSee('on paid registrations');
});

it('compares races, duration, pax, mail, WhatsApp and support, and explains overage', function () {
    $html = $this->get('/pricing')
        ->assertSee('data-compare', false)
        ->assertSeeInOrder(['Races', 'Duration', 'Participants per race', 'Ticket percentage', 'Email with your own SMTP', 'Platform mail and WhatsApp', 'Support'])
        ->assertSee('data-overage', false)
        ->assertSee('Need more places?')
        ->assertSee('Buy a participant pack')
        ->getContent();

    // Platform mail and WhatsApp are never shown as included on any plan.
    expect($html)->toMatch('#>Platform mail and WhatsApp</th>(\s*<td class="[^"]*">\s*Add-ons\s*</td>){4}\s*</tr>#');
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

it('registers an organiser on a free trial of the chosen plan', function () {
    $this->get('/register?plan=club_season')
        ->assertOk()
        ->assertSee('Start your free trial')
        ->assertSee('Your 14-day trial includes 1 event you can publish and up to 25 test registrations.')
        ->assertSee('value="club_season"', false)
        ->assertDontSee('% on paid registrations');

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
        ->and($pass->modules->contains(Module::EmailBranding))->toBeFalse(); // Platform mail is a paid add-on
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

it('shows the organiser their plan, trial use and billing note', function () {
    $this->post('/register', signupForm(['plan' => 'single_event']));

    $this->get('/account/plan?welcome=1')
        ->assertOk()
        ->assertSee('Welcome to EventFlow, Sunrise Sports Club!')
        ->assertSee('We sent a verification link to meera@sunrise.test.')
        ->assertSee('Free trial until')
        ->assertSee('0 of 1 live events · 0 of 25 registrations')
        ->assertSee('₹3,999')->assertSee('one-off, does not auto-renew')
        ->assertSee('On hold: set up email')
        ->assertSee('Upgrade to Club Season (₹12,999)')
        ->assertSee('Buy a participant pack')
        ->assertSee('Nothing is charged automatically')
        ->assertSee('Add-on · ₹599 per event'); // Session P1: custom domain on Single Event
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
