<?php

namespace App\Entitlements;

use App\Contracts\Entitlements;
use App\Enums\Module;
use App\Models\Org;
use App\Models\SeasonPass;
use App\Models\Tenancy\OrgScope;

/**
 * Season Pass + module toggles (rule in App\Contracts\Entitlements):
 * core modules are always on; optional modules need an active pass that
 * includes them and must not be switched off in Org Settings.
 */
final class SeasonPassEntitlements implements Entitlements
{
    /** Per-request memo of active passes, keyed by org id. */
    private array $active = [];

    public function activeSeasonPass(Org $org): ?SeasonPass
    {
        if (array_key_exists($org->id, $this->active)) {
            return $this->active[$org->id];
        }

        $today = now('Asia/Kolkata')->toDateString();

        return $this->active[$org->id] = SeasonPass::withoutGlobalScope(OrgScope::class)
            ->where('org_id', $org->id)
            ->where('status', 'active')
            ->whereDate('starts_on', '<=', $today)
            ->whereDate('ends_on', '>=', $today)
            ->latest('ends_on')
            ->first();
    }

    public function enabled(Org $org, Module $module): bool
    {
        if (self::isCore($module)) {
            return true;
        }

        $pass = $this->activeSeasonPass($org);

        return $pass !== null
            && $pass->modules->contains($module)
            && ($org->modules[$module->value] ?? true) !== false;
    }

    /** Whether the active pass includes an optional module, toggle aside. */
    public function included(Org $org, Module $module): bool
    {
        return self::isCore($module) || (bool) $this->activeSeasonPass($org)?->modules->contains($module);
    }

    public static function isCore(Module $module): bool
    {
        return in_array($module->value, (array) config('eventflow.modules.core', []), true);
    }

    /** @return list<Module> */
    public static function optional(): array
    {
        return array_values(array_filter(Module::cases(), fn (Module $m) => ! self::isCore($m)));
    }
}
