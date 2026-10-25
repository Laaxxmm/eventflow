<?php

namespace App\Http\Controllers;

use App\Events\RegistrationPaid;
use App\Models\PaymentEvent as PaymentEventModel;
use App\Models\Registration;
use App\Payments\GatewayManager;
use App\Payments\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    public function __construct(private GatewayManager $gateways) {}

    public function handle(Request $request, string $provider)
    {
        $gateway = match ($provider) {
            'razorpay' => app(\App\Payments\RazorpayGateway::class),
            'cashfree' => app(\App\Payments\CashfreeGateway::class),
            default => abort(404, 'Unknown payment provider'),
        };

        if (!$gateway->verifyWebhook($request)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = $gateway->parseEvent($request);

        $stored = PaymentEventModel::firstOrCreate(
            ['provider' => $event->provider, 'event_id' => $event->eventId],
            [
                'type' => $event->type,
                'payload' => $event->payload,
            ]
        );

        if ($stored->wasRecentlyCreated === false) {
            return response()->json(['status' => 'duplicate']);
        }

        if ($event->status === PaymentStatus::Paid && $event->orderId) {
            DB::transaction(function () use ($event, $stored) {
                $registration = Registration::where('gateway_order_id', $event->orderId)
                    ->lockForUpdate()
                    ->first();

                if ($registration && $registration->status === PaymentStatus::Pending) {
                    $registration->update([
                        'status' => PaymentStatus::Paid,
                        'gateway_payment_id' => $event->paymentId,
                        'paid_at' => now(),
                    ]);

                    $stored->update([
                        'registration_id' => $registration->id,
                        'processed_at' => now(),
                    ]);

                    event(new RegistrationPaid($registration->id));
                }
            });
        } else {
            $stored->update(['processed_at' => now()]);
        }

        return response()->json(['status' => 'ok']);
    }
}
