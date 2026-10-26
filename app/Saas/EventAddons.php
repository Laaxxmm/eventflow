<?php

namespace App\Saas;

use App\Contracts\Entitlements;
use App\Enums\Module;
use App\Models\Event;
use App\Models\EventAddon;
use App\Models\Tenancy\OrgScope;

/**
 * Per-event add-ons (config('plans.event_addons')). An event's race sheet or certificates
 * are unlocked when the org's plan includes the module (Club Season and up, Org Settings
 * toggle on) or the event has the add-on. Single Event includes neither by default.
 * The custom domain add-on has no module: Club Season and up include it (plans.*.custom_domain).
 */
final class EventAddons
{
    /** Add-on key that unlocks $module for one event, if any. */
    public static function keyFor(Module $module): ?string
    {
        foreach (Plans::eventAddons() as $key => $addon) {
            if (($addon['module'] ?? null) === $module->value) {
                return $key;
            }
        }

        return null;
    }

    public static function has(Event $event, string $addon): bool
    {
        return EventAddon::withoutGlobalScope(OrgScope::class)
            ->where('event_id', $event->id)->where('addon', $addon)->exists();
    }

    /** The plan covers $module for every event. */
    public static function inPlan(Event $event, Module $module): bool
    {
        return app()->bound(Entitlements::class)
            ? app(Entitlements::class)->enabled($event->org, $module)
            : ($event->org->modules[$module->value] ?? true) !== false;
    }

    public static function unlocked(Event $event, Module $module): bool
    {
        if (self::inPlan($event, $module)) {
            return true;
        }
        $key = self::keyFor($module);

        return $key !== null && self::has($event, $key);
    }

    /** The org's plan covers add-on $key for every event (module in plan, or e.g. custom domain on Club). */
    public static function keyInPlan(Event $event, string $key): bool
    {
        $addon = Plans::eventAddons()[$key] ?? null;
        if ($addon === null) {
            return false;
        }
        if (filled($addon['module'] ?? null)) {
            return self::inPlan($event, Module::from($addon['module']));
        }

        return ($addon['plan_flag'] ?? null) === 'custom_domain' && Plans::includesCustomDomain($event->org->plan);
    }

    /**
     * Status of each add-on for the event's Overview and locked pages.
     *
     * @return array<string, array{addon: array<string, mixed>, unlocked: bool, via: 'plan'|'addon'|null}>
     */
    public static function status(Event $event): array
    {
        $out = [];
        foreach (Plans::eventAddons() as $key => $addon) {
            $via = match (true) {
                self::keyInPlan($event, $key) => 'plan',
                self::has($event, $key) => 'addon',
                default => null,
            };
            $out[$key] = ['addon' => $addon, 'unlocked' => $via !== null, 'via' => $via];
        }

        return $out;
    }

    /**
     * Where "Unlock" / "Add" goes: the Buy page (online checkout, or a request to Indefine
     * while the add-on has no online price). Never a dead link.
     */
    public static function requestUrl(Event $event, string $key): string
    {
        return route('billing.show', ['sku' => $key, 'event' => $event->id]);
    }
}
