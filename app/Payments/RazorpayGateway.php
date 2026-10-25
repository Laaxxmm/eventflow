<?php

namespace App\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Registration;
use App\Payments\Dto\PaymentEvent;
use App\Payments\Dto\PaymentOrder;
use App\Payments\Dto\RefundResult;
use App\Payments\PaymentStatus;
use Illuminate\Http\Request;
use Razorpay\Api\Api;

class RazorpayGateway implements PaymentGateway
{
    private Api $api;
    private string $keyId;
    private string $keySecret;
    private string $webhookSecret;

    public function __construct()
    {
        $cfg = config('eventflow.payment.providers.razorpay');
        $this->keyId = $cfg['key_id'] ?? '';
        $this->keySecret = $cfg['key_secret'] ?? '';
        $this->webhookSecret = $cfg['webhook_secret'] ?? '';
        $this->api = new Api($this->keyId, $this->keySecret);
    }

    public function createOrder(Registration $r): PaymentOrder
    {
        $order = $this->api->order->create([
            'amount' => $r->amount_paise,
            'currency' => 'INR',
            'receipt' => $r->pass_no,
            'notes' => [
                'registration_id' => (string) $r->id,
                'pass_no' => $r->pass_no,
            ],
        ]);

        return new PaymentOrder(
            provider: 'razorpay',
            orderId: $order['id'],
            amountPaise: $r->amount_paise,
            currency: 'INR',
            checkout: [
                'key_id' => $this->keyId,
                'order_id' => $order['id'],
                'amount' => $r->amount_paise,
                'currency' => 'INR',
                'name' => config('app.name'),
                'description' => $r->event->name ?? 'Event registration',
                'prefill' => [
                    'name' => $r->participant_name,
                    'email' => $r->email,
                    'contact' => $r->phone,
                ],
            ],
        );
    }

    public function verifyWebhook(Request $req): bool
    {
        $signature = $req->header('X-Razorpay-Signature');
        if (!$signature) {
            return false;
        }
        $payload = $req->getContent();
        $expected = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expected, $signature);
    }

    public function parseEvent(Request $req): PaymentEvent
    {
        $data = $req->json()->all();
        $event = $data['event'] ?? 'unknown';
        $payload = $data['payload'] ?? [];
        $payment = $payload['payment']['entity'] ?? [];
        $order = $payload['order']['entity'] ?? [];

        $orderId = $payment['order_id'] ?? $order['id'] ?? '';
        $paymentId = $payment['id'] ?? null;
        $amount = isset($payment['amount']) ? (int) $payment['amount'] : null;

        $status = match ($event) {
            'payment.captured', 'order.paid' => PaymentStatus::Paid,
            'payment.failed' => PaymentStatus::Failed,
            'refund.processed' => PaymentStatus::Refunded,
            default => PaymentStatus::Pending,
        };

        return new PaymentEvent(
            provider: 'razorpay',
            eventId: $data['id'] ?? ($paymentId ?? uniqid('rzp_')),
            type: $event,
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
            $order = $this->api->order->fetch($orderId);
            $status = $order['status'] ?? 'created';

            return match ($status) {
                'paid' => PaymentStatus::Paid,
                'attempted' => PaymentStatus::Pending,
                default => PaymentStatus::Pending,
            };
        } catch (\Throwable $e) {
            return PaymentStatus::Pending;
        }
    }

    public function refund(string $paymentId, int $amountPaise): RefundResult
    {
        try {
            $refund = $this->api->payment->fetch($paymentId)->refund([
                'amount' => $amountPaise,
            ]);

            return new RefundResult(
                provider: 'razorpay',
                success: true,
                paymentId: $paymentId,
                amountPaise: $amountPaise,
                refundId: $refund['id'] ?? null,
                message: 'Refund initiated',
                raw: $refund->toArray(),
            );
        } catch (\Throwable $e) {
            return new RefundResult(
                provider: 'razorpay',
                success: false,
                paymentId: $paymentId,
                amountPaise: $amountPaise,
                message: $e->getMessage(),
            );
        }
    }
}
