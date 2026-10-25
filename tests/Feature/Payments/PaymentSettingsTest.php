<?php

use App\Enums\Role;
use App\Events\RegistrationPaid;
use App\Models\Event;
use App\Models\Org;
use App\Models\Registration;
use App\Models\User;
use App\Payments\CashfreeGateway;
use App\Payments\GatewayManager;
use App\Payments\PaymentStatus;
use App\Payments\RazorpayGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->org = Org::create(['slug' => 'river-runners', 'name' => 'River Runners']);
    $this->owner = User::factory()->for($this->org)->create(['role' => Role::Owner]);
    $this->event = Event::create([
        'org_id' => $this->org->id, 'slug' => 'river-10k', 'name' => 'River 10K', 'sport' => 'running',
        'date' => now()->addMonth(), 'venue' => 'Riverside', 'price_paise' => 50000, 'capacity' => 100,
    ]);
});

function razorpayInput(array $overrides = []): array
{
    return array_merge(['provider' => 'razorpay', 'key_id' => 'rzp_test_AbC123', 'key_secret' => 'rp-secret-1234', 'webhook_secret' => 'rp-webhook-9876'], $overrides);
}

function pendingRegistration(Event $event, string $orderId): Registration
{
    return Registration::create([
        'event_id' => $event->id, 'pass_no' => Registration::newPassNo(), 'status' => PaymentStatus::Pending,
        'participant_name' => 'Asha Rao', 'email' => 'asha@example.com', 'phone' => '9999999999',
        'amount_paise' => 50000, 'gateway' => 'razorpay', 'gateway_order_id' => $orderId,
    ]);
}

function capturedPayload(string $orderId, string $eventId = 'evt_1'): array
{
    return ['id' => $eventId, 'event' => 'payment.captured', 'payload' => ['payment' => ['entity' => [
        'id' => 'pay_1', 'order_id' => $orderId, 'amount' => 50000, 'status' => 'captured',
    ]]]];
}

test('owners see the payments page; staff do not', function () {
    $this->actingAs($this->owner)->get(route('settings.payments.show'))
        ->assertOk()
        ->assertSee('Cashfree')
        ->assertSee('Razorpay')
        ->assertSee(route('payments.webhook.org', ['provider' => 'razorpay', 'org' => 'river-runners']));

    $staff = User::factory()->for($this->org)->create(['role' => Role::Staff]);
    $this->actingAs($staff)->get(route('settings.payments.show'))->assertForbidden();
});

test('the dashboard nav links to Payments for owners', function () {
    $this->actingAs($this->owner)->get(route('dashboard'))->assertSee(route('settings.payments.show'));
});

test('saving Razorpay keys stores them encrypted and makes Razorpay the org gateway', function () {
    $this->actingAs($this->owner)->put(route('settings.payments.update'), razorpayInput())
        ->assertRedirect(route('settings.payments.show'))
        ->assertSessionHasNoErrors();

    $org = $this->org->fresh();
    expect($org->payment_provider)->toBe('razorpay')
        ->and($org->ownPaymentKeys('razorpay'))->toMatchArray(['key_id' => 'rzp_test_AbC123', 'key_secret' => 'rp-secret-1234', 'mode' => 'test'])
        ->and(DB::table('orgs')->where('id', $org->id)->value('payment_credentials'))->not->toContain('rp-secret-1234')
        ->and($org->toArray())->not->toHaveKey('payment_credentials')
        ->and(app(GatewayManager::class)->for($org))->toBeInstanceOf(RazorpayGateway::class);
});

test('secrets are never shown again and a blank secret keeps the saved one', function () {
    $this->actingAs($this->owner)->put(route('settings.payments.update'), razorpayInput());

    $this->actingAs($this->owner)->get(route('settings.payments.show'))
        ->assertDontSee('rp-secret-1234')
        ->assertSee('••••1234');

    $this->actingAs($this->owner)->put(route('settings.payments.update'), razorpayInput(['key_id' => 'rzp_live_XyZ789', 'key_secret' => '', 'webhook_secret' => '']))
        ->assertSessionHasNoErrors();

    expect($this->org->fresh()->ownPaymentKeys('razorpay'))->toMatchArray(['key_id' => 'rzp_live_XyZ789', 'key_secret' => 'rp-secret-1234', 'mode' => 'live']);
});

test('keys are validated per provider', function () {
    $this->actingAs($this->owner)->put(route('settings.payments.update'), razorpayInput(['key_id' => 'pk_live_123', 'key_secret' => '']))
        ->assertSessionHasErrors(['key_id', 'key_secret']);

    $this->actingAs($this->owner)->put(route('settings.payments.update'), ['provider' => 'cashfree', 'mode' => 'staging', 'app_id' => ''])
        ->assertSessionHasErrors(['mode', 'app_id', 'secret_key']);

    $this->actingAs($this->owner)->put(route('settings.payments.update'), ['provider' => 'stripe'])
        ->assertSessionHasErrors('provider');
});

test('test connection checks the saved keys against the provider', function () {
    Http::fake(['api.razorpay.com/*' => Http::response(['items' => []], 200)]);
    $this->actingAs($this->owner)->put(route('settings.payments.update'), razorpayInput());

    $this->actingAs($this->owner)->post(route('settings.payments.test'))
        ->assertSessionHas('status', fn ($s) => str_contains($s, 'Connected to Razorpay (test mode)'));

    Http::assertSent(fn ($request) => $request->url() === 'https://api.razorpay.com/v1/orders?count=1'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('rzp_test_AbC123:rp-secret-1234')));
    expect($this->org->fresh()->ownPaymentKeys('razorpay')['verified_at'])->not->toBeNull();
});

test('test connection reports rejected keys', function () {
    Http::fake(['sandbox.cashfree.com/*' => Http::response(['message' => 'authentication Failed'], 401)]);
    $this->actingAs($this->owner)->put(route('settings.payments.update'), ['provider' => 'cashfree', 'mode' => 'test', 'app_id' => 'TEST123', 'secret_key' => 'cf-secret']);

    $this->actingAs($this->owner)->post(route('settings.payments.test'))
        ->assertSessionHasErrors(['test' => 'Cashfree rejected the App ID or Secret key for test mode.']);

    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://sandbox.cashfree.com/pg/orders/')
        && $request->hasHeader('x-client-id', 'TEST123'));
});

test('a Cashfree org gateway uses the org keys, live mode and its own webhook URL', function () {
    $this->actingAs($this->owner)->put(route('settings.payments.update'), ['provider' => 'cashfree', 'mode' => 'live', 'app_id' => 'LIVE123', 'secret_key' => 'cf-live-secret']);

    $gateway = app(GatewayManager::class)->for(pendingRegistration($this->event, 'order_cf_1'));

    expect($gateway)->toBeInstanceOf(CashfreeGateway::class);
    $read = fn (string $prop) => (fn () => $this->{$prop})->call($gateway);
    expect($read('appId'))->toBe('LIVE123')
        ->and($read('env'))->toBe('production')
        ->and($read('webhookSecret'))->toBe('cf-live-secret')
        ->and($read('notifyUrl'))->toBe(route('payments.webhook.org', ['provider' => 'cashfree', 'org' => 'river-runners']));
});

test('org webhooks are verified with the org secret and only touch that org', function () {
    EventFacade::fake([RegistrationPaid::class]);
    config(['eventflow.payment.providers.razorpay.webhook_secret' => 'platform-secret']);
    $this->actingAs($this->owner)->put(route('settings.payments.update'), razorpayInput());
    $registration = pendingRegistration($this->event, 'order_river');

    $other = Org::create(['slug' => 'hill-club', 'name' => 'Hill Club']);
    $otherEvent = Event::create(['org_id' => $other->id, 'slug' => 'hill-run', 'name' => 'Hill Run', 'sport' => 'running', 'date' => now()->addMonth(), 'venue' => 'Hills', 'price_paise' => 50000, 'capacity' => 10]);
    $foreign = pendingRegistration($otherEvent, 'order_hill');

    $url = route('payments.webhook.org', ['provider' => 'razorpay', 'org' => 'river-runners']);
    $sign = fn (array $payload, string $secret) => ['X-Razorpay-Signature' => hash_hmac('sha256', json_encode($payload), $secret)];

    $own = capturedPayload('order_river', 'evt_river');
    $this->postJson($url, $own, $sign($own, 'platform-secret'))->assertStatus(400); // the platform secret is not this org's
    $this->postJson($url, $own, $sign($own, 'rp-webhook-9876'))->assertOk();

    // This org's secret cannot settle another org's order.
    $cross = capturedPayload('order_hill', 'evt_cross');
    $this->postJson($url, $cross, $sign($cross, 'rp-webhook-9876'))->assertOk();

    expect($registration->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($foreign->fresh()->status)->toBe(PaymentStatus::Pending);
    EventFacade::assertDispatchedTimes(RegistrationPaid::class, 1);
});

test('an org without keys for a provider has no webhook endpoint', function () {
    $this->postJson(route('payments.webhook.org', ['provider' => 'razorpay', 'org' => 'river-runners']), capturedPayload('x'))->assertNotFound();
    $this->postJson('/webhooks/stripe/river-runners', [])->assertNotFound();
});

test('disconnecting falls back to the platform account', function () {
    $this->actingAs($this->owner)->put(route('settings.payments.update'), razorpayInput());

    $this->actingAs($this->owner)->delete(route('settings.payments.disconnect', 'razorpay'))
        ->assertRedirect(route('settings.payments.show'));

    $org = $this->org->fresh();
    expect($org->ownPaymentKeys('razorpay'))->toBeNull()
        ->and($org->payment_provider)->toBeNull()
        ->and(app(GatewayManager::class)->forOrg($org, 'razorpay'))->toBeNull();
});
