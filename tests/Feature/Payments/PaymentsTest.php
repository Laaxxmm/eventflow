<?php

use App\Events\RegistrationPaid;
use App\Models\Event;
use App\Models\Org;
use App\Models\Registration;
use App\Payments\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->org = Org::create([
        'slug' => 'test-org',
        'name' => 'Test Org',
    ]);

    $this->event = Event::create([
        'org_id' => $this->org->id,
        'slug' => 'test-event',
        'name' => 'Test Marathon',
        'sport' => 'running',
        'date' => now()->addMonth(),
        'venue' => 'Test Venue',
        'price_paise' => 50000,
        'capacity' => 100,
    ]);

    $this->registration = Registration::create([
        'event_id' => $this->event->id,
        'pass_no' => Registration::newPassNo(),
        'status' => PaymentStatus::Pending,
        'participant_name' => 'Test Runner',
        'email' => 'runner@example.com',
        'phone' => '9999999999',
        'amount_paise' => 50000,
    ]);
});

it('marks registration paid exactly once on valid webhook (razorpay)', function () {
    EventFacade::fake([RegistrationPaid::class]);

    config(['eventflow.payment.providers.razorpay.webhook_secret' => 'test_secret']);

    $payload = [
        'id' => 'evt_test_123',
        'event' => 'payment.captured',
        'payload' => [
            'payment' => [
                'entity' => [
                    'id' => 'pay_test',
                    'order_id' => 'order_test_rzp',
                    'amount' => 50000,
                    'status' => 'captured',
                ],
            ],
        ],
    ];

    $this->registration->update(['gateway' => 'razorpay', 'gateway_order_id' => 'order_test_rzp']);

    $body = json_encode($payload);
    $signature = hash_hmac('sha256', $body, 'test_secret');

    $response = $this->postJson('/webhooks/razorpay', $payload, [
        'X-Razorpay-Signature' => $signature,
    ]);

    $response->assertOk();
    expect($this->registration->fresh()->status)->toBe(PaymentStatus::Paid);
    EventFacade::assertDispatched(RegistrationPaid::class);

    // Replay is harmless
    $this->postJson('/webhooks/razorpay', $payload, [
        'X-Razorpay-Signature' => $signature,
    ])->assertOk();

    expect($this->registration->fresh()->status)->toBe(PaymentStatus::Paid);
    EventFacade::assertDispatchedTimes(RegistrationPaid::class, 1);
});

it('rejects webhook with bad signature', function () {
    config(['eventflow.payment.providers.razorpay.webhook_secret' => 'test_secret']);

    $payload = ['id' => 'evt_bad', 'event' => 'payment.captured'];

    $response = $this->postJson('/webhooks/razorpay', $payload, [
        'X-Razorpay-Signature' => 'invalid_signature',
    ]);

    $response->assertStatus(400);
    expect($this->registration->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('respects org payment_provider override', function () {
    $this->org->update(['payment_provider' => 'razorpay']);

    $manager = app(\App\Payments\GatewayManager::class);
    $gateway = $manager->for($this->registration);

    expect($gateway)->toBeInstanceOf(\App\Payments\RazorpayGateway::class);
});

it('uses default provider when org has no override', function () {
    config(['eventflow.payment.provider' => 'cashfree']);

    $manager = app(\App\Payments\GatewayManager::class);
    $gateway = $manager->for($this->registration);

    expect($gateway)->toBeInstanceOf(\App\Payments\CashfreeGateway::class);
});
