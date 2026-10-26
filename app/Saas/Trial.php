<?php

namespace App\Saas;

use App\Landing\EventStatus;
use App\Models\Org;
use App\Models\Registration;
use App\Models\Tenancy\OrgScope;
use Carbon\CarbonImmutable;

/**
 * The free trial (config/plans.php trial_*): 14 days, one publishable event, 25 test
 * registrations, a subtle "Powered by EventFlow" on landings until the org pays.
 *
 * Converting (publish beyond the trial, more than 25 registrations, drop the watermark)
 * means checking out Single, Club or Circuit. SaaS checkout is not live yet, so with
 * TRIAL_STUB=true (default) the limits are shown but never block anything.
 */
final class Trial
{
    public static function days(): int
    {
        return (int) config('plans.trial_days');
    }

    public static function eventLimit(): int
    {
        return (int) config('plans.trial_events');
    }

    public static function registrationLimit(): int
    {
        return (int) config('plans.trial_registrations');
    }

    /** True while paywall enforcement is stubbed out (no checkout to send people to). */
    public static function stubbed(): bool
    {
        return (bool) config('plans.trial_stub');
    }

    /** Self-serve signups on a plan's trial. Orgs set up from YAML or by Indefine (no plan) are not. */
    public static function onTrial(?Org $org): bool
    {
        return $org !== null && $org->plan !== null && $org->subscription_status === 'trialing';
    }

    /** "Powered by EventFlow" on the org's landings: trial orgs only. */
    public static function watermark(?Org $org): bool
    {
        return (bool) config('plans.trial_watermark') && self::onTrial($org);
    }

    public static function publishedEvents(Org $org): int
    {
        return $org->events()->withoutGlobalScope(OrgScope::class)->where('status', EventStatus::Published)->count();
    }

    public static function registrations(Org $org): int
    {
        return Registration::withoutGlobalScope(OrgScope::class)->where('org_id', $org->id)->count();
    }

    /** Whether one more event may go live. $except: the event being re-saved (already counted). */
    public static function canPublish(Org $org, ?int $except = null): bool
    {
        if (! self::onTrial($org) || self::stubbed()) {
            return true;
        }

        $published = $org->events()->withoutGlobalScope(OrgScope::class)
            ->where('status', EventStatus::Published)
            ->when($except, fn ($q) => $q->whereKeyNot($except))
            ->count();

        return $published < self::eventLimit() && ! self::expired($org);
    }

    public static function canRegister(Org $org): bool
    {
        if (! self::onTrial($org) || self::stubbed()) {
            return true;
        }

        return self::registrations($org) < self::registrationLimit() && ! self::expired($org);
    }

    public static function expired(Org $org): bool
    {
        return self::onTrial($org) && $org->trial_ends_at?->isPast();
    }

    /**
     * Numbers for the dashboard trial banner.
     *
     * @return array{days_left: int, ends_at: ?CarbonImmutable, events: int, event_limit: int, registrations: int, registration_limit: int, stubbed: bool, expired: bool}
     */
    public static function status(Org $org): array
    {
        $ends = $org->trial_ends_at;

        return [
            'days_left' => $ends ? max(0, (int) ceil(now()->diffInDays($ends, false))) : 0,
            'ends_at' => $ends,
            'events' => self::publishedEvents($org),
            'event_limit' => self::eventLimit(),
            'registrations' => self::registrations($org),
            'registration_limit' => self::registrationLimit(),
            'stubbed' => self::stubbed(),
            'expired' => self::expired($org),
        ];
    }
}
