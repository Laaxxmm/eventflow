<?php

namespace App\Payments\Dto;

/**
 * Result of PaymentGateway::refund().
 */
final readonly class RefundResult
{
    /**
     * @param  array<string, mixed>  $raw  Raw provider response
     */
    public function __construct(
        public string $provider,
        public bool $success,
        public string $paymentId,
        public int $amountPaise,
        public ?string $refundId = null,
        public ?string $message = null,
        public array $raw = [],
    ) {}
}
