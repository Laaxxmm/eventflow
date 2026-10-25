<?php

namespace App\Contracts;

use App\Models\Registration;
use App\Payments\Dto\PaymentEvent;
use App\Payments\Dto\PaymentOrder;
use App\Payments\Dto\RefundResult;
use App\Payments\PaymentStatus;
use Illuminate\Http\Request;

/**
 * Provider-agnostic payment gateway (Razorpay, Cashfree, ...).
 *
 * The active implementation is chosen from config('eventflow.payment.provider'),
 * optionally overridden per org via orgs.payment_provider. Never type-hint a
 * concrete provider outside app/Payments.
 *
 * All amounts are integer paise (INR x 100).
 */
interface PaymentGateway
{
    /** Create a provider-side order for the registration's amount_paise. */
    public function createOrder(Registration $r): PaymentOrder;

    /** Verify the webhook signature against the provider's webhook secret. */
    public function verifyWebhook(Request $req): bool;

    /** Normalise a (verified) webhook request into a PaymentEvent. */
    public function parseEvent(Request $req): PaymentEvent;

    /** Poll the provider for the current status of an order. */
    public function fetchStatus(string $orderId): PaymentStatus;

    /** Refund (fully or partially) a captured payment. */
    public function refund(string $paymentId, int $amountPaise): RefundResult;
}
