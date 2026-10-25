<?php

namespace App\Contracts;

use App\Enums\Module;
use App\Models\Org;
use App\Models\SeasonPass;

/**
 * Season Pass + module toggles. Billing is per season, not per-event invoices.
 *
 * enabled() is true only when ALL hold:
 *  - the org has an active Season Pass (status active, today within starts_on..ends_on);
 *  - that pass includes the module (season_passes.modules), and
 *  - the module is core, or the org has not switched it off (orgs.modules[<module>] !== false).
 * With no active pass, every module is disabled.
 */
interface Entitlements
{
    public function activeSeasonPass(Org $org): ?SeasonPass;

    public function enabled(Org $org, Module $module): bool;
}
