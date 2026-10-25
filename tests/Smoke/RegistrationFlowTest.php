<?php

/*
 * End-to-end smoke test of the paid registration flow, in-process:
 * seed demo -> register on the landing -> checkout -> signed Razorpay webhook
 * -> pass PNG + PDF mailed -> volunteer QR check-in.
 *
 * Only Razorpay's order API is stubbed (no network in CI); webhook signature
 * checks, pass generation, the queued listener and the mailer are real.
 * Run: php artisan test tests/Smoke
 */

use App\Models\Registration;
use App\Models\User;
use App\Passes\SignedPassQr;
use App\Payments\Dto\PaymentOrder;
use App\Payments\PaymentStatus;
use App\Payments\RazorpayGateway;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

const WEBHOOK_SECRET = 'smoke-webhook-secret';

beforeEach(function () {
    config([
        'eventflow.payment.provider' => 'razorpay',
        'eventflow.payment.providers.razorpay' => ['key_id' => 'rzp_test_smoke', 'key_secret' => 'smoke', 'webhook_secret' => WEBHOOK_SECRET],
        'eventflow.pass.signing_key' => str_repeat('cd', 32),
        'mail.default' => 'array',
        'queue.default' => 'sync',
    ]);
    Storage::fake(config('eventflow.pass.disk'));

    // Razorpay's order API is the only network call: answer it locally.
    app()->instance(RazorpayGateway::class, new class extends RazorpayGateway
    {
        public function createOrder(Registration $r): PaymentOrder
        {
            return new PaymentOrder('razorpay', 'order_smoke_'.$r->id, $r->amount_paise, checkout: ['key' => 'rzp_test_smoke']);
        }
    });
});

it('takes a participant from registration to check-in', function () {
    // 1. Seed the demo org, owner/volunteer users and the marathon.
    $this->seed(DemoSeeder::class);
    Artisan::call('events:sync', ['paths' => [base_path('events/demo/example-marathon.yaml')]]);

    $this->get('/demo-runners/bengaluru-city-marathon-2027')->assertOk()->assertSee('Run the Garden City');

    // 2. Register on the public landing.
    $response = $this->post('/demo-runners/bengaluru-city-marathon-2027/register', [
        'distance' => '10K',
        'participant_name' => 'Smoke Runner',
        'email' => 'smoke.runner@example.com',
        'phone' => '9876543210',
        'dob' => '1990-05-01',
        'answers' => [
            'gender' => 'Female',
            'emergency_contact_name' => 'Smoke Contact',
            'emergency_contact_phone' => '9876500000',
            'tshirt_size' => 'M',
        ],
    ])->assertSessionHasNoErrors()->assertRedirect();

    $registration = Registration::where('email', 'smoke.runner@example.com')->sole();
    expect($registration->status)->toBe(PaymentStatus::Pending)
        ->and($registration->amount_paise)->toBe(89900);

    // 3. Review page hands off to checkout, which creates the gateway order.
    $this->get($response->headers->get('Location'))->assertOk()->assertSee(route('payments.create', $registration), false);
    $this->post(route('payments.create', $registration))->assertOk()->assertSee('Pay with Razorpay');
    expect($registration->fresh()->gateway_order_id)->toBe('order_smoke_'.$registration->id);

    // 4. Razorpay calls the webhook (signed with the webhook secret).
    $body = json_encode([
        'id' => 'evt_smoke_1',
        'event' => 'payment.captured',
        'payload' => ['payment' => ['entity' => [
            'id' => 'pay_smoke_1',
            'order_id' => 'order_smoke_'.$registration->id,
            'amount' => 89900,
            'status' => 'captured',
        ]]],
    ]);
    $webhook = fn () => $this->call('POST', '/webhooks/razorpay', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_RAZORPAY_SIGNATURE' => hash_hmac('sha256', $body, WEBHOOK_SECRET),
    ], $body);

    $webhook()->assertOk()->assertJson(['status' => 'ok']);
    $webhook()->assertOk()->assertJson(['status' => 'duplicate']); // retries are idempotent

    $registration->refresh();
    expect($registration->status)->toBe(PaymentStatus::Paid)
        ->and($registration->pass_no)->toMatch(config('eventflow.pass.pattern'));

    // 5. The pass mail went out with the PNG + PDF pass attached.
    $messages = app('mailer')->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1);

    /** @var Email $mail */
    $mail = $messages[0]->getOriginalMessage();
    expect($mail->getTo()[0]->getAddress())->toBe('smoke.runner@example.com')
        ->and($mail->getSubject())->toContain($registration->pass_no)
        ->and(collect($mail->getAttachments())->map->getContentType()->sort()->values()->all())
        ->toBe(['application/pdf', 'image/png']);

    // 6. A volunteer scans the signed QR at the venue; a second scan is flagged.
    $volunteer = User::withoutGlobalScopes()->where('email', 'volunteer@demo.test')->sole();
    $scan = fn () => $this->actingAs($volunteer)->postJson('/checkin', ['payload' => SignedPassQr::payload($registration->pass_no)]);

    $scan()->assertOk()->assertJson(['ok' => true, 'duplicate' => false, 'pass_no' => $registration->pass_no]);
    $scan()->assertOk()->assertJson(['ok' => true, 'duplicate' => true]);

    expect($registration->fresh()->checked_in_at)->not->toBeNull();
});
