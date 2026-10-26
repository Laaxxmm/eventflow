<?php

namespace App\Saas;

use App\Enums\Module;
use App\Models\Org;
use App\Models\SeasonPass;
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
        $plan = Plans::resolve($plan);
        $definition = Plans::get($plan);
        $addons = array_values(array_intersect($addons, array_keys(Plans::addons())));
        $modules = Plans::modules($plan, $addons);
        $endsAt = now()->addDays(Plans::trialDays());

        $optional = array_fill_keys(config('eventflow.modules.optional'), false);
        foreach ($modules as $module) {
            $optional[$module->value] = true;
        }

        return DB::transaction(function () use ($org, $plan, $definition, $addons, $modules, $endsAt, $optional) {
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
                // The trial's own limits (App\Saas\Trial), not the plan's.
                'max_events' => Trial::eventLimit(),
                'max_registrations' => Trial::registrationLimit(),
                'amount_paise' => 0,
            ]);

            return $org;
        });
    }
}
