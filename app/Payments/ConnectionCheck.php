<?php

namespace App\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * "Test connection" for an organiser's gateway keys: one authenticated, read-only
 * API call. Nothing is charged or created.
 */
final class ConnectionCheck
{
    public const CASHFREE_API_VERSION = '2023-08-01';

    /**
     * @param  array<string, mixed>  $keys
     * @return array{ok: bool, message: string}
     */
    public static function run(string $provider, array $keys): array
    {
        try {
            return match ($provider) {
                'razorpay' => self::razorpay($keys),
                'cashfree' => self::cashfree($keys),
                default => ['ok' => false, 'message' => __('Unknown provider.')],
            };
        } catch (ConnectionException) {
            return ['ok' => false, 'message' => __('Could not reach :provider. Try again in a minute.', ['provider' => ucfirst($provider)])];
        }
    }

    /** Lists at most one order: 200 means the key pair is valid. */
    private static function razorpay(array $keys): array
    {
        $response = Http::withBasicAuth((string) $keys['key_id'], (string) $keys['key_secret'])
            ->timeout(10)
            ->get('https://api.razorpay.com/v1/orders', ['count' => 1]);

        return match (true) {
            $response->successful() => ['ok' => true, 'message' => __('Connected to Razorpay (:mode mode).', ['mode' => self::razorpayMode((string) $keys['key_id'])])],
            in_array($response->status(), [401, 403], true) => ['ok' => false, 'message' => __('Razorpay rejected the Key ID or Key secret.')],
            default => ['ok' => false, 'message' => __('Razorpay answered with an error (:status).', ['status' => $response->status()])],
        };
    }

    /** Fetches an order id that never exists: 404 means authenticated, 401 means bad keys. */
    private static function cashfree(array $keys): array
    {
        $live = ($keys['mode'] ?? 'test') === 'live';
        $response = Http::withHeaders([
            'x-client-id' => (string) $keys['app_id'],
            'x-client-secret' => (string) $keys['secret_key'],
            'x-api-version' => self::CASHFREE_API_VERSION,
        ])->timeout(10)->get(($live ? 'https://api.cashfree.com/pg' : 'https://sandbox.cashfree.com/pg').'/orders/eventflow-connection-check');

        return match (true) {
            $response->successful(), $response->status() === 404 => ['ok' => true, 'message' => __('Connected to Cashfree (:mode mode).', ['mode' => $live ? __('live') : __('test')])],
            in_array($response->status(), [401, 403], true) => ['ok' => false, 'message' => __('Cashfree rejected the App ID or Secret key for :mode mode.', ['mode' => $live ? __('live') : __('test')])],
            default => ['ok' => false, 'message' => __('Cashfree answered with an error (:status).', ['status' => $response->status()])],
        };
    }

    public static function razorpayMode(string $keyId): string
    {
        return str_starts_with($keyId, 'rzp_live_') ? 'live' : 'test';
    }
}
