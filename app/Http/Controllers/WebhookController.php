<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Events\RegistrationPaid;
use App\Models\Org;
use App\Models\PaymentEvent as PaymentEventModel;
use App\Models\Registration;
use App\Payments\GatewayManager;
use App\Payments\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    public function __construct(private GatewayManager $gateways) {}

    /** Webhooks for the platform account (keys from config). */
    public function handle(Request $request, string $provider)
    {
        abort_unless(in_array($provider, GatewayManager::PROVIDERS, true), 404, 'Unknown payment provider');

        return $this->process($request, $this->gateways->platform($provider));
    }

    /**
     * Webhooks for an organiser's own gateway account (Settings → Payments): verified
     * with that org's secret, and only that org's registrations can be marked paid.
     */
    public function handleForOrg(Request $request, string $provider, Org $org)
    {
        abort_unless(in_array($provider, GatewayManager::PROVIDERS, true), 404, 'Unknown payment provider');
        $gateway = $this->gateways->forOrg($org, $provider);
        abort_if($gateway === null, 404, 'This organiser has no keys for that provider');

        return $this->process($request, $gateway, $org);
    }

    private function process(Request $request, PaymentGateway $gateway, ?Org $org = null)
    {
        if (! $gateway->verifyWebhook($request)) {
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
            DB::transaction(function () use ($event, $stored, $org) {
                $registration = Registration::where('gateway_order_id', $event->orderId)
                    ->when($org, fn ($q) => $q->where('org_id', $org->id))
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
