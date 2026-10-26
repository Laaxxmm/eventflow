<?php

namespace App\Payments;

use App\Payments\Dto\PaymentOrder;

/**
 * A PaymentGateway that can also take organiser purchases (participant packs, add-ons) on the
 * platform account. Webhooks and fetchStatus() work the same as for registrations.
 */
interface SupportsPurchases
{
    public function createPurchaseOrder(PurchaseIntent $intent): PaymentOrder;
}
