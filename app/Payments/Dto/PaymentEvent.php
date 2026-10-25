<?php

namespace App\Payments\Dto;

use App\Payments\PaymentStatus;

/**
 * Normalised webhook event from PaymentGateway::parseEvent().
 *
 * (provider, eventId) is the idempotency key stored in the payment_events table.
 */
final readonly class PaymentEvent
{
    /**
     * @param  array<string, mixed>  $payload  Raw decoded webhook body
     */
    public function __construct(
        public string $provider,
        public string $eventId,
        public string $type,
        public string $orderId,
        public PaymentStatus $status,
        public ?string $paymentId = null,
        public ?int $amountPaise = null,
        public array $payload = [],
    ) {}
}
