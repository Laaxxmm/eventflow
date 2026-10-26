<?php

namespace App\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Registration;
use App\Payments\Dto\PaymentEvent;
use App\Payments\Dto\PaymentOrder;
use App\Payments\Dto\RefundResult;
use Cashfree\Cashfree;
use Cashfree\Model\CreateOrderRequest;
use Cashfree\Model\CustomerDetails;
use Cashfree\Model\OrderMeta;
use Illuminate\Http\Request;

class CashfreeGateway implements PaymentGateway, SupportsPurchases
{
    private string $appId;

    private string $secretKey;

    private string $env;

    private string $webhookSecret;

    private ?string $notifyUrl;

    /**
     * @param  array<string, mixed>|null  $cfg  An org's own keys (GatewayManager); null = platform keys from config
     */
    public function __construct(?array $cfg = null)
    {
        $cfg ??= config('eventflow.payment.providers.cashfree');
        $this->appId = $cfg['app_id'] ?? '';
        $this->secretKey = $cfg['secret_key'] ?? '';
        $this->env = $cfg['env'] ?? 'sandbox';
        $this->webhookSecret = $cfg['webhook_secret'] ?? '';
        $this->notifyUrl = $cfg['notify_url'] ?? null;
    }

    /**
     * cashfree-pg 4.x is configured through static properties, so set them
     * right before each API call.
     */
    private function client(): Cashfree
    {
        Cashfree::$XClientId = $this->appId;
        Cashfree::$XClientSecret = $this->secretKey;
        Cashfree::$XEnvironment = $this->env === 'production' ? Cashfree::$PRODUCTION : Cashfree::$SANDBOX;
        // Keep SDK errors in our own logs instead of reporting them to Cashfree's error tracker.
        Cashfree::$XEnableErrorAnalytics = false;

        return new Cashfree;
    }

    public function createOrder(Registration $r): PaymentOrder
    {
        $customer = new CustomerDetails;
        $customer->setCustomerId('reg_'.$r->id);
        $customer->setCustomerName($r->participant_name);
        $customer->setCustomerEmail($r->email);
        // Cashfree needs a phone; hand-added participants (Session P1) may only have an email.
        $customer->setCustomerPhone(filled($r->phone) ? $r->phone : '9999999999');

        $meta = new OrderMeta;
        $meta->setReturnUrl(url("/pay/return/{$r->id}"));
        $meta->setNotifyUrl($this->notifyUrl ?? url('/webhooks/cashfree'));

        $request = new CreateOrderRequest;
        $request->setOrderAmount($r->amount_paise / 100);
        $request->setOrderCurrency('INR');
        $request->setCustomerDetails($customer);
        $request->setOrderMeta($meta);
        $request->setOrderNote('Registration '.$r->pass_no);

        $response = $this->client()->PGCreateOrder(Cashfree::$XApiVersion, $request);
        $order = $response[0] ?? $response;

        $orderId = $order->getOrderId() ?? $order['order_id'] ?? '';
        $paymentSessionId = $order->getPaymentSessionId() ?? $order['payment_session_id'] ?? '';

        return new PaymentOrder(
            provider: 'cashfree',
            orderId: $orderId,
            amountPaise: $r->amount_paise,
            currency: 'INR',
            checkout: [
                'payment_session_id' => $paymentSessionId,
                'order_id' => $orderId,
                'env' => $this->env,
            ],
        );
    }

    /** An organiser purchase (participant pack, add-on) on this account (Session P1). */
    public function createPurchaseOrder(PurchaseIntent $intent): PaymentOrder
    {
        $customer = new CustomerDetails;
        $customer->setCustomerId($intent->reference);
        $customer->setCustomerName($intent->customerName);
        $customer->setCustomerEmail($intent->customerEmail);
        // Cashfree wants a 10-digit mobile: keep the last 10 digits ("+91 98450 12345" -> 9845012345).
        $digits = substr((string) preg_replace('/\D+/', '', (string) $intent->customerPhone), -10);
        $customer->setCustomerPhone(strlen($digits) === 10 ? $digits : '9999999999');

        $meta = new OrderMeta;
        $meta->setReturnUrl($intent->returnUrl);
        $meta->setNotifyUrl($this->notifyUrl ?? url('/webhooks/cashfree'));

        $request = new CreateOrderRequest;
        $request->setOrderAmount($intent->amountPaise / 100);
        $request->setOrderCurrency('INR');
        $request->setCustomerDetails($customer);
        $request->setOrderMeta($meta);
        $request->setOrderNote(mb_substr($intent->description, 0, 200));

        $response = $this->client()->PGCreateOrder(Cashfree::$XApiVersion, $request);
        $order = $response[0] ?? $response;
        $orderId = $order->getOrderId() ?? $order['order_id'] ?? '';

        return new PaymentOrder(
            provider: 'cashfree',
            orderId: $orderId,
            amountPaise: $intent->amountPaise,
            currency: 'INR',
            checkout: [
                'payment_session_id' => $order->getPaymentSessionId() ?? $order['payment_session_id'] ?? '',
                'order_id' => $orderId,
                'env' => $this->env,
            ],
        );
    }

    public function verifyWebhook(Request $req): bool
    {
        $signature = $req->header('x-webhook-signature') ?? $req->header('X-Webhook-Signature');
        $timestamp = $req->header('x-webhook-timestamp') ?? $req->header('X-Webhook-Timestamp');
        if (! $signature || ! $timestamp) {
            return false;
        }

        $payload = $req->getContent();
        $signedPayload = $timestamp.$payload;
        $expected = base64_encode(hash_hmac('sha256', $signedPayload, $this->webhookSecret, true));

        return hash_equals($expected, $signature);
    }

    public function parseEvent(Request $req): PaymentEvent
    {
        $data = $req->json()->all();
        $type = $data['type'] ?? $data['event_type'] ?? 'unknown';
        $order = $data['data']['order'] ?? $data['order'] ?? [];
        $payment = $data['data']['payment'] ?? $data['payment'] ?? [];

        $orderId = $order['order_id'] ?? '';
        $paymentId = $payment['cf_payment_id'] ?? $payment['payment_id'] ?? null;
        $amount = isset($payment['payment_amount']) ? (int) ($payment['payment_amount'] * 100) : null;

        $status = match (true) {
            str_contains($type, 'SUCCESS') || ($payment['payment_status'] ?? '') === 'SUCCESS' => PaymentStatus::Paid,
            str_contains($type, 'FAILED') => PaymentStatus::Failed,
            default => PaymentStatus::Pending,
        };

        return new PaymentEvent(
            provider: 'cashfree',
            eventId: $data['event_id'] ?? ($paymentId ?? uniqid('cf_')),
            type: $type,
            orderId: $orderId,
            status: $status,
            paymentId: $paymentId,
            amountPaise: $amount,
            payload: $data,
        );
    }

    public function fetchStatus(string $orderId): PaymentStatus
    {
        try {
            $response = $this->client()->PGFetchOrder(Cashfree::$XApiVersion, $orderId);
            $order = $response[0] ?? $response;
            $status = $order->getOrderStatus() ?? $order['order_status'] ?? 'ACTIVE';

            return match (strtoupper($status)) {
                'PAID' => PaymentStatus::Paid,
                'EXPIRED', 'TERMINATED' => PaymentStatus::Failed,
                default => PaymentStatus::Pending,
            };
        } catch (\Throwable $e) {
            return PaymentStatus::Pending;
        }
    }

    public function refund(string $paymentId, int $amountPaise): RefundResult
    {
        return new RefundResult(
            provider: 'cashfree',
            success: false,
            paymentId: $paymentId,
            amountPaise: $amountPaise,
            message: 'Refund not yet implemented for Cashfree in this session',
        );
    }
}
