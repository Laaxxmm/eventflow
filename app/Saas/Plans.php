<?php

namespace App\Saas;

use App\Enums\Module;
use Carbon\CarbonImmutable;
use Illuminate\Support\Number;

/**
 * Read access to config/plans.php for the pricing page, signup and the platform console.
 */
final class Plans
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return config('plans.plans');
    }

    /** @return array<string, mixed> */
    public static function get(string $key): array
    {
        return self::all()[$key] ?? throw new \InvalidArgumentException("Unknown plan [{$key}]");
    }

    /**
     * Plans cheapest first, custom-priced ones last: the order the pricing strip shows
     * (Single → Club → Circuit → Custom once those plans are configured).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function ordered(): array
    {
        $plans = self::all();
        uasort($plans, fn (array $a, array $b) => ($a['price_inr'] ?? PHP_INT_MAX) <=> ($b['price_inr'] ?? PHP_INT_MAX));

        return $plans;
    }

    /**
     * The cheapest self-serve plan, for "Start with … ₹X" calls to action.
     *
     * @return array{key: string, plan: array<string, mixed>}|null
     */
    public static function entry(): ?array
    {
        foreach (self::ordered() as $key => $plan) {
            if (($plan['self_serve'] ?? false) && $plan['price_inr'] !== null) {
                return ['key' => $key, 'plan' => $plan];
            }
        }

        return null;
    }

    /** @return list<string> Plans a visitor can start a trial on (others are "contact us"). */
    public static function selfServe(): array
    {
        return array_keys(array_filter(self::all(), fn (array $p) => $p['self_serve']));
    }

    /** @return array<string, array<string, mixed>> */
    public static function addons(): array
    {
        return config('plans.addons');
    }

    /** @return array<string, array<string, mixed>> Per-event add-ons (race sheet, certificates, custom domain). */
    public static function eventAddons(): array
    {
        $addons = config('plans.event_addons', []);
        if (isset($addons['custom_domain'])) {
            $addons['custom_domain']['price_inr'] = self::customDomainPrice();
        }

        return $addons;
    }

    /** Custom domain add-on price in INR (Single Event; Club and Circuit include it). */
    public static function customDomainPrice(): int
    {
        return (int) config('plans.custom_domain_inr', 599);
    }

    /** Whether a plan includes a custom domain for free. */
    public static function includesCustomDomain(?string $plan): bool
    {
        return $plan !== null && (bool) (self::all()[$plan]['custom_domain'] ?? false);
    }

    public static function trialDays(): int
    {
        return (int) config('plans.trial_days');
    }

    public static function inr(?int $amount): ?string
    {
        return $amount === null ? null : Number::currency($amount, in: 'INR', locale: 'en_IN', precision: 0);
    }

    /** "₹3,999", or the quote wording when Indefine prices it. */
    public static function priceLabel(?int $amount): string
    {
        return self::inr($amount) ?? __('Contact Indefine');
    }

    /** "1 race · 500 participants" for a plan card or table. */
    public static function limitsLabel(array $plan): string
    {
        if ($plan['max_events'] === null) {
            return __('Races and field size to suit you');
        }

        return trans_choice('{1} 1 race|[2,*] Up to :count races', $plan['max_events'], ['count' => $plan['max_events']])
            .' · '.__(':pax participants per race', ['pax' => number_format($plan['max_participants_per_event'])]);
    }

    /** Last day (inclusive) of a paid term that starts on $from. */
    public static function termEnd(string $plan, \DateTimeInterface $from): ?CarbonImmutable
    {
        $term = self::get($plan)['term'];
        if ($term === null) {
            return null;
        }
        $start = CarbonImmutable::instance($from)->startOfDay();
        $end = isset($term['days']) ? $start->addDays($term['days']) : $start->addMonthsNoOverflow($term['months']);

        return $end->subDay();
    }

    /**
     * Optional modules a plan + add-ons switch on.
     *
     * @param  list<string>  $addons
     * @return list<Module>
     */
    public static function modules(string $plan, array $addons = []): array
    {
        $keys = self::get($plan)['modules'];

        foreach ($addons as $addon) {
            if ($module = self::addons()[$addon]['module'] ?? null) {
                $keys[] = $module;
            }
        }

        return array_values(array_map(fn (string $k) => Module::from($k), array_unique($keys)));
    }

    public static function brandName(): string
    {
        $by = config('plans.brand.by');

        return config('plans.brand.name').($by ? ' by '.$by : '');
    }
}
