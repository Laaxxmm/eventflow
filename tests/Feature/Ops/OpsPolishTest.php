<?php

use App\Billing\Catalog;
use App\Billing\Fulfilment;
use App\Contracts\PaymentGateway;
use App\Domains\DnsResolver;
use App\Domains\DomainAccess;
use App\Enums\Role;
use App\Events\RegistrationPaid;
use App\Landing\ConfigCompiler;
use App\Landing\EventForm;
use App\Models\CustomDomain;
use App\Models\Event;
use App\Models\EventAddon;
use App\Models\Org;
use App\Models\ParticipantPack;
use App\Models\Purchase;
use App\Models\Registration;
use App\Models\User;
use App\Payments\Dto\PaymentEvent;
use App\Payments\Dto\PaymentOrder;
use App\Payments\Dto\RefundResult;
use App\Payments\GatewayManager;
use App\Payments\PaymentStatus;
use App\Payments\PurchaseIntent;
use App\Payments\SupportsPurchases;
use App\Reminders\ReminderSchedule;
use App\Saas\PlanAssigner;
use App\Saas\PlanLimits;
use App\Support\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event as Events;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use function Tests\Feature\Events\syncMarathon;

require_once __DIR__.'/../Events/helpers.php';

const OPS_LANDING = '/demo-runners/bengaluru-city-marathon-2027';

function opsEvent(?string $plan = 'single_event'): Event
{
    $event = syncMarathon();
    if ($plan) {
        PlanAssigner::activate($event->org, $plan, now('Asia/Kolkata'));
    }

    return $event->fresh();
}

function opsUser(Org $org, Role $role = Role::Owner): User
{
    return User::factory()->create(['org_id' => $org->id, 'role' => $role, 'email_verified_at' => now(), 'email' => $role->value.'-'.$org->id.'-'.Str::lower(Str::random(6)).'@club.test']);
}

/** Platform gateway double: orders, status and webhooks without the network. */
function fakePlatformGateway(PaymentStatus $status = PaymentStatus::Paid): object
{
    $gateway = new class($status) implements PaymentGateway, SupportsPurchases
    {
        public array $intents = [];

        public function __construct(public PaymentStatus $status) {}

        public function createPurchaseOrder(PurchaseIntent $intent): PaymentOrder
        {
            $this->intents[] = $intent;

            return new PaymentOrder('razorpay', 'order_'.$intent->reference, $intent->amountPaise, checkout: ['key_id' => 'rzp_test', 'order_id' => 'order_'.$intent->reference]);
        }

        public function createOrder(Registration $r): PaymentOrder
        {
            throw new LogicException('not used');
        }

        public function verifyWebhook(Request $req): bool
        {
            return true;
        }

        public function parseEvent(Request $req): PaymentEvent
        {
            return new PaymentEvent('razorpay', $req->input('id'), 'payment.captured', $req->input('order'), PaymentStatus::Paid, 'pay_1');
        }

        public function fetchStatus(string $orderId): PaymentStatus
        {
            return $this->status;
        }

        public function refund(string $paymentId, int $amountPaise): RefundResult
        {
            throw new LogicException('not used');
        }
    };

    config([
        'eventflow.payment.provider' => 'razorpay',
        'eventflow.payment.providers.razorpay' => ['key_id' => 'rzp_test', 'key_secret' => 's', 'webhook_secret' => 'w'],
    ]);
    $manager = Mockery::mock(GatewayManager::class)->makePartial();
    $manager->shouldReceive('platform')->andReturn($gateway);
    app()->instance(GatewayManager::class, $manager);

    return $gateway;
}

function fakeDns(array $cnames = [], array $a = []): void
{
    app()->instance(DnsResolver::class, new class($cnames, $a) implements DnsResolver
    {
        public function __construct(private array $cnames, private array $ips) {}

        public function cname(string $host): array
        {
            return $this->cnames[$host] ?? [];
        }

        public function a(string $host): array
        {
            return $this->ips[$host] ?? [];
        }
    });
}

// --- 1. Manual participants ----------------------------------------------------

it('adds a paid participant by hand as an ordinary paid registration with a pass', function () {
    Events::fake([RegistrationPaid::class]);
    $event = opsEvent();
    $owner = opsUser($event->org);

    $this->actingAs($owner)->get(route('dashboard.participants'))->assertOk()->assertSee('data-add-participant', false);
    $this->get(route('dashboard.participants.create', ['event' => $event->id]))->assertOk()
        ->assertSee('Half Marathon (21.1K)')->assertSee('Complimentary');

    $this->post(route('dashboard.participants.store'), [
        'event' => $event->id, 'distance' => '10K', 'participant_name' => 'Meera Iyer',
        'email' => 'Meera@Example.com', 'phone' => '98450 12345', 'payment' => 'paid', 'notes' => 'Paid cash at expo',
    ])->assertRedirect(route('dashboard.participants', ['event' => $event->id]));

    $r = Registration::where('participant_name', 'Meera Iyer')->firstOrFail();
    expect($r->status)->toBe(PaymentStatus::Paid)
        ->and($r->source)->toBe('manual')
        ->and($r->payment_method)->toBe('manual')
        ->and($r->amount_paise)->toBe(89900)
        ->and($r->email)->toBe('meera@example.com')
        ->and($r->paid_at)->not->toBeNull()
        ->and($r->added_by)->toBe($owner->id)
        ->and($r->pass_no)->toMatch('/^EF-[A-F0-9]{12}$/');
    Events::assertDispatched(RegistrationPaid::class, fn ($e) => $e->registrationId === $r->id);

    $this->get(route('dashboard.participants', ['event' => $event->id, 'status' => 'paid']))
        ->assertSee('Meera Iyer')->assertSee('data-manual-badge', false)->assertSee('data-participant-added', false);
});

it('adds unpaid participants who can be reminded, and complimentary places at ₹0', function () {
    Events::fake([RegistrationPaid::class]);
    $event = opsEvent();
    $this->actingAs(opsUser($event->org));

    // Unpaid needs a way to reach them.
    $this->post(route('dashboard.participants.store'), ['event' => $event->id, 'distance' => '5K', 'participant_name' => 'No Contact', 'payment' => 'unpaid'])
        ->assertSessionHasErrors('email');

    $this->post(route('dashboard.participants.store'), ['event' => $event->id, 'distance' => '5K', 'participant_name' => 'Ravi K', 'phone' => '9876543210', 'payment' => 'unpaid', 'amount_inr' => 450]);
    $unpaid = Registration::where('participant_name', 'Ravi K')->firstOrFail();
    expect($unpaid->status)->toBe(PaymentStatus::Pending)->and($unpaid->amount_paise)->toBe(45000)->and($unpaid->email)->toBeNull()
        ->and(ReminderSchedule::blocker($unpaid))->toBeNull();
    $this->get(route('dashboard.participants', ['status' => 'unpaid']))->assertSee('Ravi K');

    $this->post(route('dashboard.participants.store'), ['event' => $event->id, 'distance' => 'FM', 'participant_name' => 'Guest Elite', 'payment' => 'complimentary', 'amount_inr' => 1999]);
    $comp = Registration::where('participant_name', 'Guest Elite')->firstOrFail();
    expect($comp->status)->toBe(PaymentStatus::Paid)->and($comp->amount_paise)->toBe(0)->and($comp->isComplimentary())->toBeTrue();
    $this->get(route('dashboard.participants', ['status' => 'paid']))->assertSee('Guest Elite')->assertSee('Complimentary');
    Events::assertDispatchedTimes(RegistrationPaid::class, 1);
});

it('keeps manual entries inside the org, the distances and the plan cap', function () {
    $event = opsEvent();
    $other = Org::create(['slug' => 'other-club', 'name' => 'Other Club']);

    $this->actingAs(opsUser($other))->post(route('dashboard.participants.store'), ['event' => $event->id, 'distance' => '5K', 'participant_name' => 'X', 'payment' => 'paid'])->assertNotFound();
    $this->actingAs(opsUser($event->org, Role::Volunteer))->get(route('dashboard.participants.create'))->assertRedirect('/checkin');

    $this->actingAs(opsUser($event->org))->post(route('dashboard.participants.store'), ['event' => $event->id, 'distance' => '3K', 'participant_name' => 'X', 'payment' => 'paid'])
        ->assertSessionHasErrors('distance');

    PlanLimits::pass($event->org)->update(['max_registrations' => 0]);
    app()->forgetScopedInstances();
    $this->post(route('dashboard.participants.store'), ['event' => $event->id, 'distance' => '5K', 'participant_name' => 'Over Cap', 'payment' => 'complimentary'])
        ->assertSessionHasErrors('distance');
    expect(Registration::count())->toBe(0);
});

// --- 2. Dead CTAs ----------------------------------------------------------------

it('never renders an empty or # Contact Indefine link', function () {
    config(['brand.contact_email' => '', 'brand.contact_url' => null]);
    expect(Contact::url('Hi'))->toBe('mailto:hello@indefine.in?subject=Hi');

    config(['brand.contact_url' => 'https://indefine.in/contact']);
    expect(Contact::url('Hi'))->toBe('https://indefine.in/contact');

    config(['brand.contact_url' => '#']);
    expect(Contact::url())->toStartWith('mailto:hello@indefine.in');

    config(['eventflow.certificates.custom_contact' => 'design@indefine.in']);
    expect(Contact::customDesign('Layout'))->toBe('mailto:design@indefine.in?subject=Layout');

    $html = $this->get('/pricing')->assertOk()->getContent();
    preg_match_all('#<a [^>]*data-contact-indefine[^>]*>#', $html, $links);
    expect($links[0])->not->toBeEmpty();
    foreach ($links[0] as $a) {
        expect($a)->not->toContain('href="#"')->not->toContain('href=""')->not->toContain('href="mailto:?');
    }
});

it('sends Buy participant pack to a real Buy page that records a request when packs have no price', function () {
    $event = opsEvent();
    PlanLimits::pass($event->org)->update(['max_registrations' => 1]);
    Registration::factory()->create(['event_id' => $event->id, 'status' => PaymentStatus::Paid]);
    $owner = opsUser($event->org);

    $buy = route('billing.show', ['sku' => 'participant_pack', 'event' => $event->id]);
    $this->actingAs($owner)->get('/dashboard')->assertSee($buy, false);

    $this->get($buy)->assertOk()->assertSee('+250')->assertSee('Send request to Indefine')->assertSee('data-billing-submit="request"', false);
    $this->post(route('billing.store', 'participant_pack'), ['event' => $event->id, 'participants' => 500, 'note' => 'Expo weekend'])
        ->assertRedirect(route('billing.show', ['sku' => 'participant_pack', 'event' => $event->id, 'participants' => 500]));

    $purchase = Purchase::firstOrFail();
    expect($purchase->status)->toBe('requested')->and($purchase->quantity)->toBe(500)->and($purchase->amount_paise)->toBeNull();
    $this->get($buy)->assertSee('Request sent to Indefine')->assertSee('data-purchase="'.$purchase->id.'"', false);
    expect(ParticipantPack::count())->toBe(0); // nothing granted until Indefine prices it
});

it('checks out a priced participant pack on the platform gateway and raises the cap once', function () {
    config(['plans.overage.participant_pack.sizes' => [['participants' => 250, 'price_inr' => 2499]]]);
    $gateway = fakePlatformGateway();
    $event = opsEvent();
    $capBefore = PlanLimits::participantCap($event);

    $this->actingAs(opsUser($event->org))->get(route('billing.show', ['sku' => 'participant_pack', 'event' => $event->id]))
        ->assertSee('Pay ₹2,499')->assertSee('data-billing-submit="pay"', false);
    $this->post(route('billing.store', 'participant_pack'), ['event' => $event->id, 'participants' => 250])
        ->assertOk()->assertSee('data-billing-checkout', false)->assertSee('order_pur_', false);

    $purchase = Purchase::firstOrFail();
    expect($purchase->status)->toBe('pending')->and($purchase->amount_paise)->toBe(249900)->and($purchase->gateway_order_id)->toBe('order_pur_'.$purchase->id)
        ->and($gateway->intents[0]->amountPaise)->toBe(249900)
        ->and($gateway->intents[0]->returnUrl)->toBe(route('billing.return', $purchase));

    // The provider's webhook (platform account) fulfils it; the return page does not double it.
    $this->postJson('/webhooks/razorpay', ['id' => 'evt_pack_1', 'order' => $purchase->gateway_order_id])->assertOk();
    $this->get(route('billing.return', $purchase))->assertOk()->assertSee('250 more places');
    Fulfilment::paid($purchase->fresh());

    expect($purchase->fresh()->status)->toBe('paid')
        ->and((int) ParticipantPack::where('event_id', $event->id)->sum('participants'))->toBe(250) // MySQL SUM is a string
        ->and(PlanLimits::participantCap($event))->toBe($capBefore + 250);
});

// --- 5. Custom domain ------------------------------------------------------------

it('gates custom domains: a ₹599 add-on on Single Event, included on Club and Circuit', function () {
    $event = opsEvent('single_event');
    $owner = opsUser($event->org);

    expect(DomainAccess::canConnect($event->org, $event))->toBeFalse();
    $this->actingAs($owner)->get(route('settings.domain.show'))->assertOk()
        ->assertSee(route('landing.show', ['demo-runners', 'bengaluru-city-marathon-2027']))
        ->assertSee('Add Custom domain — ₹599')
        ->assertSee('data-domain-entitlement="addon"', false)
        ->assertDontSee('name="host"', false);

    // Connecting without the add-on goes to the Buy page, never silently free.
    $this->post(route('settings.domain.store'), ['host' => 'register.bcm.in', 'target' => (string) $event->id])
        ->assertRedirect(route('billing.show', ['sku' => 'custom_domain', 'event' => $event->id]));
    expect(CustomDomain::count())->toBe(0);

    // Buying it (₹599 on the platform gateway) unlocks the connect form for that event.
    fakePlatformGateway();
    $this->get(route('billing.show', ['sku' => 'custom_domain', 'event' => $event->id]))->assertSee('₹599')->assertSee('Pay ₹599');
    $this->post(route('billing.store', 'custom_domain'), ['event' => $event->id])->assertOk();
    $this->get(route('billing.return', Purchase::firstOrFail()))->assertSee('Connect your domain');
    expect(EventAddon::where('event_id', $event->id)->where('addon', 'custom_domain')->value('amount_paise'))->toBe(59900)
        ->and(DomainAccess::canConnect($event->org, $event))->toBeTrue()
        ->and(DomainAccess::canConnect($event->org, null))->toBeFalse(); // workspace page stays Club+
    $this->get(route('settings.domain.show'))->assertSee('name="host"', false)->assertDontSee('Workspace page (all your events)');

    foreach (['club_season', 'circuit_season'] as $plan) {
        PlanAssigner::activate($event->org, $plan, now('Asia/Kolkata'));
        expect(DomainAccess::canConnect($event->org->fresh(), null))->toBeTrue();
    }
    $this->actingAs($owner->fresh())->get(route('settings.domain.show'))->assertSee('Included in Circuit Season')->assertSee('Workspace page (all your events)');
});

it('connects, verifies and serves a custom domain, with TLS only for verified hosts', function () {
    $event = opsEvent('club_season');
    $this->actingAs(opsUser($event->org));
    config(['domains.cname_target' => 'domains.eventflow.test', 'domains.app_hosts' => ['localhost', 'eventflow.test']]);

    $this->post(route('settings.domain.store'), ['host' => 'app.eventflow.test', 'target' => 'workspace'])->assertSessionHasErrors('host');
    $this->post(route('settings.domain.store'), ['host' => 'not a domain', 'target' => 'workspace'])->assertSessionHasErrors('host');

    $this->post(route('settings.domain.store'), ['host' => 'https://Register.BCM.in/', 'target' => (string) $event->id])->assertRedirect(route('settings.domain.show'));
    $domain = CustomDomain::firstOrFail();
    expect($domain->host)->toBe('register.bcm.in')->and($domain->status)->toBe('pending');
    $this->get(route('settings.domain.show'))->assertSee('data-dns-instructions', false)->assertSee('domains.eventflow.test');
    $this->get('/domains/tls-check?domain=register.bcm.in')->assertNotFound();
    $this->get('http://register.bcm.in/')->assertOk()->assertSee('EventFlow'); // pending: not routed yet

    fakeDns();
    $this->post(route('settings.domain.verify', $domain))->assertSessionHas('domain_status', 'not-yet');
    expect($domain->fresh()->last_error)->toContain('No CNAME');

    fakeDns(['register.bcm.in' => ['domains.eventflow.test']]);
    $this->post(route('settings.domain.verify', $domain))->assertSessionHas('domain_status', 'verified');
    expect($domain->fresh()->status)->toBe('active');

    $this->get('/domains/tls-check?domain=register.bcm.in')->assertOk();
    $this->get('/domains/tls-check?domain=evil.example.com')->assertNotFound();

    auth()->logout();
    $this->get('http://register.bcm.in/')->assertOk()->assertSee('Bengaluru City Marathon 2027')->assertSee('name="participant_name"', false);
    $this->get('http://register.bcm.in/dashboard')->assertRedirect(rtrim(config('app.url'), '/').'/dashboard');
    $this->get(OPS_LANDING)->assertOk(); // the default URL keeps working

    // Back on the app host (route() would now build URLs on the custom host).
    $this->actingAs(opsUser($event->org))->post(config('app.url').'/dashboard/settings/domain', ['host' => 'register.bcm.in', 'target' => 'workspace'])->assertSessionHasErrors('host');
    $this->delete(config('app.url').'/dashboard/settings/domain/'.$domain->id)->assertRedirect();
    $this->get(config('app.url').'/domains/tls-check?domain=register.bcm.in')->assertNotFound();
});

it('shows the custom domain rules on pricing and Plan & billing', function () {
    $this->get('/pricing')->assertOk()->assertSee('Custom domain')->assertSee('Add-on ₹599 / event')->assertSee('Included')
        ->assertSee('Included in Club Season, Circuit Season');

    $event = opsEvent('club_season');
    $this->actingAs(opsUser($event->org))->get(route('account.plan'))->assertSee('data-plan-custom-domain="included"', false);
});

// --- 3/4/6. Banner, footer, countdown ---------------------------------------------

it('uploads the hero banner and share image from Edit event and uses them on the page and list', function () {
    Storage::fake('public');
    $event = opsEvent();
    $owner = opsUser($event->org);
    $input = EventForm::toInput(app(ConfigCompiler::class)->resolve($event->config), 'published');

    $this->actingAs($owner)->put(route('organiser.events.update', $event), $input + [
        'hero_upload' => UploadedFile::fake()->image('banner.jpg', 1600, 900),
        'og_upload' => UploadedFile::fake()->image('share.png', 1200, 630),
    ])->assertRedirect(route('organiser.events.edit', $event))->assertSessionHasNoErrors();

    $config = $event->fresh()->config;
    Storage::disk('public')->assertExists($config['hero']['image']);
    Storage::disk('public')->assertExists($config['hero']['og_image']);

    $html = $this->get(OPS_LANDING)->assertOk()->getContent();
    expect($html)->toContain(Storage::disk('public')->url($config['hero']['image']))
        ->toContain('property="og:image" content="'.url(Storage::disk('public')->url($config['hero']['og_image'])));
    $this->get(route('organiser.events.index'))->assertSee('data-event-thumb', false)->assertSee(Storage::disk('public')->url($config['hero']['image']));

    // Remove: back to the sport pack artwork.
    $this->put(route('organiser.events.update', $event), EventForm::toInput(app(ConfigCompiler::class)->resolve($event->fresh()->config), 'published') + ['remove' => ['hero_image' => '1']]);
    expect($event->fresh()->config['hero'])->not->toHaveKey('image');
});

it('renders a branded footer on every landing, never empty', function (string $template) {
    $event = opsEvent();
    $owner = opsUser($event->org);
    $event->update(['config' => ['template' => $template] + $event->config]);

    $html = $this->get(OPS_LANDING)->assertOk()->getContent();
    preg_match('#<footer[^>]*data-event-footer.*?</footer>#s', $html, $footer);
    expect($footer[0])->toContain($event->org->name)->toContain($owner->email)->toContain('Powered by EventFlow')->toContain('©');

    $event->update(['config' => $event->config + ['contact' => ['email' => 'help@bcm.in', 'instagram' => 'https://instagram.com/bcm', 'terms_url' => 'https://bcm.in/terms']]]);
    $this->get(OPS_LANDING)->assertSee('help@bcm.in')->assertSee('https://instagram.com/bcm')->assertSee('Terms &amp; refunds', false);
})->with(['gun-lap', 'stadium-split', 'equipment-close', 'night-circuit', 'heritage-crest']);

it('shows the countdown when enabled and switches to closed, live, then hides', function () {
    $event = opsEvent();
    $start = now()->addDays(10)->startOfHour();
    $close = $start->copy()->subDays(2);
    $event->update(['config' => array_merge($event->config, [
        'date' => $start->toIso8601String(),
        'registration_closes' => $close->toIso8601String(),
    ])]);

    $this->get(OPS_LANDING)->assertDontSee('data-countdown', false); // off by default

    $event->update(['config' => $event->config + ['countdown' => ['enabled' => true, 'target' => 'registration_close']]]);
    $this->get(OPS_LANDING)->assertSee('data-state="counting"', false)->assertSee('Registration closes in')->assertSee('name="participant_name"', false);

    $this->travelTo($close->copy()->addMinute());
    $this->get(OPS_LANDING)->assertSee('data-state="closed"', false)->assertSee('Registration closed')->assertDontSee('name="participant_name"', false);
    $this->post(OPS_LANDING.'/register', \Tests\Feature\Events\registrationInput())->assertSessionHasErrors('distance');

    $this->travelTo($start->copy()->addHour());
    $this->get(OPS_LANDING)->assertSee('data-state="live"', false)->assertSee('We’re live');

    $this->travelTo($start->copy()->addHours(13));
    $this->get(OPS_LANDING)->assertDontSee('data-countdown', false);
    $this->travelBack();

    $event->update(['config' => array_merge($event->config, ['countdown' => ['enabled' => true, 'target' => 'event_start']])]);
    $this->get(OPS_LANDING)->assertSee('Race starts in')->assertSee('data-target="event_start"', false);
});

it('saves countdown, closing time and footer contact from Edit event', function () {
    $event = opsEvent();
    $input = EventForm::toInput(app(ConfigCompiler::class)->resolve($event->config), 'published');

    $this->actingAs(opsUser($event->org))->put(route('organiser.events.update', $event), array_replace_recursive($input, [
        'countdown' => ['enabled' => '1', 'target' => 'event_start'],
        'registration_closes' => '2027-01-10T23:59',
        'contact' => ['email' => 'help@bcm.in', 'instagram' => '@bcmruns', 'website' => 'https://bcm.in'],
    ]))->assertSessionHasNoErrors();

    $config = $event->fresh()->config;
    // MySQL JSON columns reorder object keys, so compare as maps.
    expect($config['countdown'])->toEqual(['enabled' => true, 'target' => 'event_start'])
        ->and($config['registration_closes'])->toStartWith('2027-01-10T23:59:00+05:30')
        ->and($config['contact'])->toEqual(['email' => 'help@bcm.in', 'website' => 'https://bcm.in', 'instagram' => 'https://instagram.com/bcmruns']);

    $this->get(route('organiser.events.edit', $event))->assertSee('data-countdown-settings', false)->assertSee('https://instagram.com/bcmruns');
});

it('lists every SKU on the catalog with the locked prices', function () {
    $event = opsEvent();
    expect(Catalog::skus())->toBe(['participant_pack', 'race_sheet', 'certificates', 'custom_domain'])
        ->and(Catalog::item('custom_domain', $event)['price_inr'])->toBe(599)
        ->and(Catalog::item('race_sheet', $event)['price_inr'])->toBe(599)
        ->and(Catalog::item('certificates', $event)['price_inr'])->toBe(799)
        ->and(config('plans.custom_domain_inr'))->toBe(599)
        ->and(Catalog::item('participant_pack', $event, 999))->toBeNull();
});
