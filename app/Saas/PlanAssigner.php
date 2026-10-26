<?php

namespace App\Saas;

use App\Contracts\Entitlements;
use App\Entitlements\SeasonPassEntitlements;
use App\Enums\Module;
use App\Models\Org;
use App\Models\SeasonPass;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Puts an org on a plan. Until SaaS billing (Cashfree/Razorpay subscriptions) is
 * live, every plan starts as a free trial: no charge, subscription_status=trialing.
 *
 * The trial is also recorded as a zero-amount Season Pass covering the plan's
 * modules, so App\Contracts\Entitlements implementations see the trial as an
 * active pass; optional modules outside the plan are switched off in orgs.modules.
 */
final class PlanAssigner
{
    /** @param  list<string>  $addons */
    public static function startTrial(Org $org, string $plan, array $addons = []): Org
    {
        $definition = Plans::get($plan);
        $addons = array_values(array_intersect($addons, array_keys(Plans::addons())));
        $modules = Plans::modules($plan, $addons);
        $endsAt = now()->addDays(Plans::trialDays());

        $optional = array_fill_keys(config('eventflow.modules.optional'), false);
        foreach ($modules as $module) {
            $optional[$module->value] = true;
        }

        $org = DB::transaction(function () use ($org, $plan, $definition, $addons, $modules, $endsAt, $optional) {
            $org->forceFill([
                'plan' => $plan,
                'addons' => $addons,
                'subscription_status' => 'trialing',
                'trial_ends_at' => $endsAt,
                'modules' => $optional,
            ])->save();

            SeasonPass::withoutGlobalScopes()->create([
                'org_id' => $org->id,
                'plan' => $definition['name'].' (trial)',
                'starts_on' => today(),
                'ends_on' => $endsAt->toDateString(),
                'status' => 'active',
                'modules' => [
                    ...array_map(fn (string $m) => Module::from($m), config('eventflow.modules.core')),
                    ...$modules,
                ],
                'max_events' => $definition['max_events'],
                'max_registrations' => $definition['max_participants_per_event'],
                'amount_paise' => 0,
            ]);

            return $org;
        });

        self::forgetCachedPass();

        return $org;
    }

    /**
     * Activates a paid plan from $from (Indefine staff, until online billing is live):
     * Single Event = 90-day window that does not renew; season plans = 12 months.
     * A new term replaces the old one, so unused race slots do not roll over.
     *
     * @param  list<string>|null  $addons  null keeps the org's current add-ons
     */
    public static function activate(Org $org, string $plan, \DateTimeInterface $from, ?array $addons = null): SeasonPass
    {
        $definition = Plans::get($plan);
        $addons = array_values(array_intersect($addons ?? ($org->addons ?? []), array_keys(Plans::addons())));
        $modules = Plans::modules($plan, $addons);
        $start = CarbonImmutable::instance($from)->startOfDay();
        $end = Plans::termEnd($plan, $start) ?? $start->addYear()->subDay();

        $optional = array_fill_keys(config('eventflow.modules.optional'), false);
        foreach ($modules as $module) {
            $optional[$module->value] = ($org->modules[$module->value] ?? true) !== false;
        }

        $pass = DB::transaction(function () use ($org, $plan, $definition, $addons, $modules, $start, $end, $optional) {
            $org->forceFill([
                'plan' => $plan,
                'addons' => $addons,
                'subscription_status' => 'active',
                'trial_ends_at' => null,
                'modules' => $optional,
            ])->save();

            // The new term supersedes trials and earlier terms that overlap it.
            SeasonPass::withoutGlobalScopes()->where('org_id', $org->id)->where('status', 'active')
                ->whereDate('ends_on', '>=', $start->toDateString())
                ->update(['status' => 'cancelled']);

            return SeasonPass::withoutGlobalScopes()->create([
                'org_id' => $org->id,
                'plan' => $definition['name'],
                'starts_on' => $start->toDateString(),
                'ends_on' => $end->toDateString(),
                'status' => 'active',
                'modules' => [
                    ...array_map(fn (string $m) => Module::from($m), config('eventflow.modules.core')),
                    ...$modules,
                ],
                'max_events' => $definition['max_events'],
                'max_registrations' => $definition['max_participants_per_event'],
                'amount_paise' => ($definition['price_inr'] ?? 0) * 100,
            ]);
        });

        self::forgetCachedPass();

        return $pass;
    }

    /** SeasonPassEntitlements memoises the active pass per org; drop it after a change. */
    private static function forgetCachedPass(): void
    {
        app()->forgetInstance(Entitlements::class);
        app()->forgetInstance(SeasonPassEntitlements::class);
    }
}
