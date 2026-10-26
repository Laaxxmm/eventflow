<?php

namespace App\Payments;

/**
 * A platform order that is not a race registration (Session P1): an organiser paying
 * Indefine for a participant pack or an add-on. Amounts are integer paise.
 */
final readonly class PurchaseIntent
{
    public function __construct(
        public string $reference, // e.g. "pur_12": receipt / customer id at the provider
        public int $amountPaise,
        public string $description,
        public string $customerName,
        public string $customerEmail,
        public ?string $customerPhone,
        public string $returnUrl,
    ) {}
}
