<?php

namespace App\Saas;

use App\Enums\Module;
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
        return self::all()[self::resolve($key)] ?? throw new \InvalidArgumentException("Unknown plan [{$key}]");
    }

    /** A plan key, with pre-Single/Club/Circuit keys (starter, growth, scale) mapped to their successor. */
    public static function resolve(string $key): string
    {
        return config("plans.legacy.{$key}", $key);
    }

    /** @return array<string, mixed>|null The plan an org is on, or null when it has none. */
    public static function forOrg(?string $key): ?array
    {
        return $key === null ? null : (self::all()[self::resolve($key)] ?? null);
    }

    /** Total participants a pass covers: events × pax per event, or null for unlimited. */
    public static function maxRegistrations(array $plan): ?int
    {
        return $plan['max_events'] === null || $plan['pax_per_event'] === null
            ? null
            : $plan['max_events'] * $plan['pax_per_event'];
    }

    /**
     * Plans cheapest first, custom-priced ones last: the order the pricing strip shows
     * (Single → Club → Circuit → Custom).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function ordered(): array
    {
        $plans = self::all();
        uasort($plans, fn (array $a, array $b) => ($a['season_pass_inr'] ?? PHP_INT_MAX) <=> ($b['season_pass_inr'] ?? PHP_INT_MAX));

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
            if (($plan['self_serve'] ?? false) && $plan['season_pass_inr'] !== null) {
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

    public static function trialDays(): int
    {
        return (int) config('plans.trial_days');
    }

    public static function inr(?int $amount): ?string
    {
        return $amount === null ? null : Number::currency($amount, in: 'INR', locale: 'en_IN', precision: 0);
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
