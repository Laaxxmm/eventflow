<?php

namespace App\Billing;

use App\Models\Event;
use App\Saas\EventAddons;
use App\Saas\Plans;

/**
 * What an organiser can buy from Indefine (Session P1), priced from config/plans.php:
 *   participant_pack  extra places for one race (plans.overage.participant_pack.sizes)
 *   race_sheet, certificates, custom_domain  per-event add-ons (plans.event_addons)
 *
 * price_inr null = no online price yet: the Buy page sends a request to Indefine instead.
 */
final class Catalog
{
    public const PACK = 'participant_pack';

    /** @return list<string> */
    public static function skus(): array
    {
        return [self::PACK, ...array_keys(Plans::eventAddons())];
    }

    public static function exists(string $sku): bool
    {
        return in_array($sku, self::skus(), true);
    }

    /** @return list<array{participants: int, price_inr: int|null}> */
    public static function packSizes(): array
    {
        return array_values(array_map(fn (array $s) => [
            'participants' => (int) $s['participants'],
            'price_inr' => isset($s['price_inr']) ? (int) $s['price_inr'] : null,
        ], (array) config('plans.overage.participant_pack.sizes', [])));
    }

    /**
     * One line item, or null when the SKU / pack size is unknown.
     *
     * @return array{sku: string, name: string, description: string, price_inr: int|null, quantity: int}|null
     */
    public static function item(string $sku, Event $event, ?int $participants = null): ?array
    {
        if ($sku === self::PACK) {
            $pack = config('plans.overage.participant_pack');
            $sizes = self::packSizes();
            $size = collect($sizes)->firstWhere('participants', $participants ?? ($sizes[0]['participants'] ?? 0));
            if ($size === null) {
                return null;
            }

            return [
                'sku' => $sku,
                'name' => __(':n-place participant pack', ['n' => number_format($size['participants'])]),
                'description' => __($pack['description']),
                'price_inr' => $size['price_inr'],
                'quantity' => $size['participants'],
            ];
        }

        $addon = Plans::eventAddons()[$sku] ?? null;
        if ($addon === null) {
            return null;
        }

        return [
            'sku' => $sku,
            'name' => __($addon['name']),
            'description' => __($addon['description']),
            'price_inr' => $addon['price_inr'] !== null ? (int) $addon['price_inr'] : null,
            'quantity' => 1,
        ];
    }

    /** Already covered for this event (plan includes it, or bought before): nothing to buy. */
    public static function owned(string $sku, Event $event): bool
    {
        return $sku !== self::PACK && (EventAddons::keyInPlan($event, $sku) || EventAddons::has($event, $sku));
    }
}
