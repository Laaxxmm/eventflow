<?php

namespace App\Saas;

use App\Contracts\Entitlements;
use App\Landing\Capacity;
use App\Models\Event;
use App\Models\Org;
use App\Models\ParticipantPack;
use App\Models\SeasonPass;
use App\Models\Tenancy\OrgScope;
use Illuminate\Support\Collection;

/**
 * Plan caps, read from the org's active Season Pass (written by PlanAssigner from
 * config/plans.php): races per term (max_events) and participants per race
 * (max_registrations, plus participant packs). null = no cap (Custom, staff passes).
 *
 * Without an active pass nothing is capped here: core modules stay on (product rule)
 * and Indefine decides what an org without a plan can do.
 */
final class PlanLimits
{
    public static function pass(Org $org): ?SeasonPass
    {
        if (app()->bound(Entitlements::class)) {
            return app(Entitlements::class)->activeSeasonPass($org);
        }

        $today = now('Asia/Kolkata')->toDateString();

        return SeasonPass::withoutGlobalScope(OrgScope::class)
            ->where('org_id', $org->id)->where('status', 'active')
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)
            ->latest('ends_on')->first();
    }

    /** Races the org may create this term, or null for no cap. */
    public static function racesAllowed(Org $org): ?int
    {
        return self::pass($org)?->max_events;
    }

    /** Races created in the active term. Slots from earlier terms do not roll over. */
    public static function racesUsed(Org $org): int
    {
        $pass = self::pass($org);
        if ($pass === null) {
            return 0;
        }

        return Event::withoutGlobalScope(OrgScope::class)
            ->where('org_id', $org->id)
            ->where('created_at', '>=', $pass->starts_on->startOfDay()->shiftTimezone('Asia/Kolkata')->utc())
            ->count();
    }

    public static function canCreateRace(Org $org): bool
    {
        $allowed = self::racesAllowed($org);

        return $allowed === null || self::racesUsed($org) < $allowed;
    }

    /** Participant cap for this race (plan cap + packs), or null for no cap. */
    public static function participantCap(Event $event): ?int
    {
        $planCap = self::pass($event->org)?->max_registrations;
        if ($planCap === null) {
            return null;
        }

        return $planCap + (int) ParticipantPack::withoutGlobalScope(OrgScope::class)
            ->where('event_id', $event->id)->sum('participants');
    }

    /** Places taken the same way distance capacity counts them (paid + pending in the hold). */
    public static function participantsTaken(Event $event): int
    {
        return Capacity::taken($event);
    }

    public static function atParticipantCap(Event $event): bool
    {
        $cap = self::participantCap($event);

        return $cap !== null && self::participantsTaken($event) >= $cap;
    }

    /**
     * Races at or near their cap (90%+), for the organiser banner.
     *
     * @return Collection<int, array{event: Event, taken: int, cap: int, full: bool}>
     */
    public static function pressure(Org $org): Collection
    {
        if (self::pass($org)?->max_registrations === null) {
            return collect();
        }

        return Event::withoutGlobalScope(OrgScope::class)->where('org_id', $org->id)
            ->where('date', '>=', now()->subDay())->with('org')->get()
            ->map(function (Event $event) {
                $cap = (int) self::participantCap($event);
                $taken = self::participantsTaken($event);

                return ['event' => $event, 'taken' => $taken, 'cap' => $cap, 'full' => $taken >= $cap];
            })
            ->filter(fn (array $row) => $row['cap'] > 0 && $row['taken'] >= (int) ceil($row['cap'] * 0.9))
            ->values();
    }

    /** Plan the org should move to when it runs out of room, if any. */
    public static function upgradeTarget(Org $org): ?string
    {
        return $org->plan ? (Plans::all()[$org->plan]['upgrade_to'] ?? null) : null;
    }
}
