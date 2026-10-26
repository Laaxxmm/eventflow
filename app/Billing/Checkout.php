<?php

namespace App\Billing;

use App\Models\Purchase;
use App\Payments\Dto\PaymentOrder;
use App\Payments\GatewayManager;
use App\Payments\PaymentStatus;
use App\Payments\PurchaseIntent;
use App\Payments\SupportsPurchases;
use App\Support\Contact;
use RuntimeException;

/**
 * Online checkout for organiser purchases, on the platform (Indefine) gateway account from
 * config('eventflow.payment'), never an organiser's own keys. Off (request-only) when
 * FEATURE_ONLINE_BILLING=false or the platform keys for the provider are not set.
 */
final class Checkout
{
    public function __construct(private GatewayManager $gateways) {}

    public static function provider(): string
    {
        return (string) config('eventflow.payment.provider', 'cashfree');
    }

    /** Platform keys are present for the configured provider and online billing is on. */
    public static function ready(): bool
    {
        if (! config('features.online_billing')) {
            return false;
        }
        $keys = (array) config('eventflow.payment.providers.'.self::provider(), []);
        $required = match (self::provider()) {
            'cashfree' => ['app_id', 'secret_key'],
            'razorpay' => ['key_id', 'key_secret', 'webhook_secret'],
            default => ['__unsupported__'],
        };

        foreach ($required as $key) {
            if (blank($keys[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    public function start(Purchase $purchase, string $description): PaymentOrder
    {
        $gateway = $this->gateways->platform(self::provider());
        if (! $gateway instanceof SupportsPurchases) {
            throw new RuntimeException('The payment provider cannot take purchases.');
        }

        $user = $purchase->user;
        $order = $gateway->createPurchaseOrder(new PurchaseIntent(
            reference: 'pur_'.$purchase->id,
            amountPaise: (int) $purchase->amount_paise,
            description: $description,
            customerName: $user?->name ?? $purchase->org->name,
            customerEmail: $user?->email ?? Contact::email(),
            customerPhone: $purchase->org->phone,
            returnUrl: route('billing.return', $purchase),
        ));

        $purchase->forceFill(['gateway' => $order->provider, 'gateway_order_id' => $order->orderId])->save();

        return $order;
    }

    /** Ask the provider whether the purchase is paid (return page; the webhook is the source of truth). */
    public function refresh(Purchase $purchase): void
    {
        if ($purchase->status !== Purchase::PENDING || ! $purchase->gateway || ! $purchase->gateway_order_id) {
            return;
        }

        $status = $this->gateways->platform($purchase->gateway)->fetchStatus($purchase->gateway_order_id);
        if ($status === PaymentStatus::Paid) {
            Fulfilment::paid($purchase);
        } elseif ($status === PaymentStatus::Failed) {
            $purchase->forceFill(['status' => Purchase::FAILED])->save();
        }
    }
}
