<?php

namespace App\Payments\Dto;

/**
 * Result of PaymentGateway::createOrder().
 */
final readonly class PaymentOrder
{
    /**
     * @param  array<string, mixed>  $checkout  Provider-specific data the checkout page needs
     *                                          (e.g. Razorpay key_id, Cashfree payment_session_id).
     */
    public function __construct(
        public string $provider,
        public string $orderId,
        public int $amountPaise,
        public string $currency = 'INR',
        public array $checkout = [],
        public ?string $redirectUrl = null,
    ) {}
}
