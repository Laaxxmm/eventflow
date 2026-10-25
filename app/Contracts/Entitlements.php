<?php

namespace App\Contracts;

use App\Enums\Module;
use App\Models\Org;
use App\Models\SeasonPass;

/**
 * Season Pass + module toggles. Billing is per season, not per-event invoices.
 *
 * - Core modules (config('eventflow.modules.core'): registration, payments,
 *   passes) are always enabled, with or without a pass, so an org can take
 *   entries before buying a season.
 * - Optional modules (config('eventflow.modules.optional'): check-in, race
 *   sheets, certificates, email branding, SMS/WhatsApp) are enabled only when
 *   ALL hold:
 *    - the org has an active Season Pass (status active, today within
 *      starts_on..ends_on, Asia/Kolkata);
 *    - that pass includes the module (season_passes.modules), and
 *    - the org has not switched it off in Org Settings
 *      (orgs.modules[<module>] !== false).
 */
interface Entitlements
{
    public function activeSeasonPass(Org $org): ?SeasonPass;

    public function enabled(Org $org, Module $module): bool;
}
