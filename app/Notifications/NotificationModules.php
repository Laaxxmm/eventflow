<?php

namespace App\Notifications;

use App\Contracts\Entitlements;
use App\Enums\Module;
use App\Models\Org;

/**
 * Season Pass gate for the notification add-ons (email_branding, sms_whatsapp).
 *
 * With an Entitlements implementation bound, it decides (active pass includes the
 * module and the org has not switched it off). Until one is bound, the org toggle
 * in orgs.modules decides and a module is available unless switched off.
 */
final class NotificationModules
{
    public static function enabled(Org $org, Module $module): bool
    {
        if (app()->bound(Entitlements::class)) {
            return app(Entitlements::class)->enabled($org, $module);
        }

        return ($org->modules[$module->value] ?? true) !== false;
    }
}
