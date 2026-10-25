<?php

namespace App\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Org;
use App\Models\Registration;
use InvalidArgumentException;

class GatewayManager
{
    public function for(Registration|Org|null $context = null): PaymentGateway
    {
        $provider = $this->resolveProvider($context);

        return match ($provider) {
            'razorpay' => app(RazorpayGateway::class),
            'cashfree' => app(CashfreeGateway::class),
            default => throw new InvalidArgumentException("Unsupported payment provider [{$provider}]"),
        };
    }

    public function resolveProvider(Registration|Org|null $context = null): string
    {
        if ($context instanceof Registration) {
            $org = $context->event?->org;
            if ($org?->payment_provider) {
                return $org->payment_provider;
            }
        } elseif ($context instanceof Org && $context->payment_provider) {
            return $context->payment_provider;
        }

        return config('eventflow.payment.provider', 'cashfree');
    }
}
