<?php

namespace App\Domains;

use App\Models\Event;
use App\Models\Org;
use App\Saas\EventAddons;
use App\Saas\Plans;

/**
 * Who may connect a custom domain (Session P1):
 *   Club Season, Circuit Season, Custom  included: any event page, and the workspace page
 *   Single Event                          only an event that has the Custom domain add-on (₹599)
 */
final class DomainAccess
{
    public const ADDON = 'custom_domain';

    public static function planIncludes(Org $org): bool
    {
        return Plans::includesCustomDomain($org->plan);
    }

    public static function eventHasAddon(Event $event): bool
    {
        return EventAddons::has($event, self::ADDON);
    }

    /** May the org point a host at $event (or, with null, at its workspace page)? */
    public static function canConnect(Org $org, ?Event $event): bool
    {
        if (self::planIncludes($org)) {
            return true;
        }

        return $event !== null && self::eventHasAddon($event);
    }
}
