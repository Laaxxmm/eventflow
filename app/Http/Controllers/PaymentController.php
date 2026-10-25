<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Payments\GatewayManager;
use App\Payments\PaymentStatus;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private GatewayManager $gateways) {}

    public function create(Registration $registration)
    {
        abort_unless($registration->status === PaymentStatus::Pending, 422, 'Registration is not payable');

        $gateway = $this->gateways->for($registration);
        $order = $gateway->createOrder($registration);

        $registration->update([
            'gateway' => $order->provider,
            'gateway_order_id' => $order->orderId,
        ]);

        return view('payments.checkout', [
            'registration' => $registration,
            'order' => $order,
        ]);
    }

    public function return(Request $request, Registration $registration)
    {
        if ($registration->gateway && $registration->gateway_order_id) {
            $gateway = $this->gateways->for($registration);
            $status = $gateway->fetchStatus($registration->gateway_order_id);

            if ($status === PaymentStatus::Paid && $registration->status === PaymentStatus::Pending) {
                $registration->update([
                    'status' => PaymentStatus::Paid,
                    'paid_at' => now(),
                ]);
                event(new \App\Events\RegistrationPaid($registration->id));
            }
        }

        return view('payments.return', [
            'registration' => $registration->fresh(),
        ]);
    }
}
