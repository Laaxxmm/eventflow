<?php

use App\Events\RegistrationPaid;
use App\Models\Event;
use App\Models\Org;
use App\Models\Registration;
use App\Payments\PaymentStatus;
use App\Payments\RazorpayGateway;
use Illuminate\Support\Facades\Event as EventFacade;

// Regression: RegistrationPaid takes the registration id (int), not the model.

beforeEach(function () {
    EventFacade::fake([RegistrationPaid::class]);

    $org = Org::create(['slug' => 'dispatch-org', 'name' => 'Dispatch Org', 'payment_provider' => 'razorpay']);
    $event = Event::create([
        'org_id' => $org->id, 'slug' => 'dispatch-run', 'name' => 'Dispatch Run',
        'date' => now()->addMonth(), 'venue' => 'Pune',
    ]);

    $this->registration = Registration::create([
        'event_id' => $event->id,
        'pass_no' => Registration::newPassNo(),
        'status' => PaymentStatus::Pending,
        'participant_name' => 'Asha',
        'email' => 'asha@example.com',
        'phone' => '9999999999',
        'amount_paise' => 50000,
        'gateway' => 'razorpay',
        'gateway_order_id' => 'order_dispatch_1',
    ]);

    $this->mock(RazorpayGateway::class)
        ->shouldReceive('fetchStatus')->with('order_dispatch_1')->andReturn(PaymentStatus::Paid);
});

function assertPaidOnce(Registration $registration): void
{
    expect($registration->fresh()->status)->toBe(PaymentStatus::Paid);

    EventFacade::assertDispatchedTimes(RegistrationPaid::class, 1);
    EventFacade::assertDispatched(RegistrationPaid::class, fn (RegistrationPaid $e) => $e->registrationId === $registration->id);
}

it('dispatches RegistrationPaid with the id from the payment return page', function () {
    $this->get(route('payments.return', $this->registration))->assertOk();

    assertPaidOnce($this->registration);
});

it('dispatches RegistrationPaid with the id from the reconcile command', function () {
    $this->registration->forceFill(['created_at' => now()->subHour()])->save();

    $this->artisan('payments:reconcile')
        ->expectsOutputToContain('Reconciled 1 of 1')
        ->assertSuccessful();

    assertPaidOnce($this->registration);
});
