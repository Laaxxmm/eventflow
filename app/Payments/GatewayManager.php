<?php

namespace App\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Org;
use App\Models\Registration;
use InvalidArgumentException;

/**
 * Picks the gateway for a registration or org: the org's own Cashfree / Razorpay
 * account when the organiser connected one (Settings → Payments), otherwise the
 * platform account from config('eventflow.payment').
 */
class GatewayManager
{
    public const PROVIDERS = ['cashfree', 'razorpay'];

    public function for(Registration|Org|null $context = null): PaymentGateway
    {
        $provider = $this->resolveProvider($context);
        $org = $context instanceof Registration ? $context->event?->org : $context;

        if ($org instanceof Org && ($gateway = $this->forOrg($org, $provider))) {
            return $gateway;
        }

        return $this->platform($provider);
    }

    /** The org's own gateway for a provider, or null when it has no complete keys for it. */
    public function forOrg(Org $org, string $provider): ?PaymentGateway
    {
        $keys = $org->ownPaymentKeys($provider);
        if ($keys === null) {
            return null;
        }

        return match ($provider) {
            'cashfree' => new CashfreeGateway([
                'app_id' => $keys['app_id'],
                'secret_key' => $keys['secret_key'],
                'env' => ($keys['mode'] ?? 'test') === 'live' ? 'production' : 'sandbox',
                // Cashfree signs webhooks with the secret key unless a separate one is set.
                'webhook_secret' => filled($keys['webhook_secret'] ?? null) ? $keys['webhook_secret'] : $keys['secret_key'],
                'notify_url' => self::webhookUrl($org, 'cashfree'),
            ]),
            'razorpay' => new RazorpayGateway([
                'key_id' => $keys['key_id'],
                'key_secret' => $keys['key_secret'],
                'webhook_secret' => $keys['webhook_secret'],
            ]),
            default => null,
        };
    }

    public function platform(string $provider): PaymentGateway
    {
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

    /** Where the provider must send this org's payment webhooks. */
    public static function webhookUrl(Org $org, string $provider): string
    {
        return route('payments.webhook.org', ['provider' => $provider, 'org' => $org->slug]);
    }
}
