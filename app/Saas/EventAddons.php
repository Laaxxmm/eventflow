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
 */
final class EventAddons
{
    /** Add-on key that unlocks $module for one event, if any. */
    public static function keyFor(Module $module): ?string
    {
        foreach (Plans::eventAddons() as $key => $addon) {
            if ($addon['module'] === $module->value) {
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

    /**
     * Status of each add-on for the event's Overview and locked pages.
     *
     * @return array<string, array{addon: array<string, mixed>, unlocked: bool, via: 'plan'|'addon'|null}>
     */
    public static function status(Event $event): array
    {
        $out = [];
        foreach (Plans::eventAddons() as $key => $addon) {
            $module = Module::from($addon['module']);
            $via = match (true) {
                self::inPlan($event, $module) => 'plan',
                self::has($event, $key) => 'addon',
                default => null,
            };
            $out[$key] = ['addon' => $addon, 'unlocked' => $via !== null, 'via' => $via];
        }

        return $out;
    }

    /** mailto: that asks Indefine for the add-on (online checkout is not live yet). */
    public static function requestUrl(Event $event, string $key): string
    {
        $addon = Plans::eventAddons()[$key];

        return 'mailto:'.config('plans.brand.contact_email').'?subject='.rawurlencode(__(':addon (:price) for :event (:org)', [
            'addon' => __($addon['name']),
            'price' => Plans::inr($addon['price_inr']),
            'event' => $event->name,
            'org' => $event->org->name,
        ]));
    }
}
