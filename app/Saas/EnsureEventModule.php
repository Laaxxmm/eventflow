<?php

namespace App\Saas;

use App\Enums\Module;
use App\Models\Event;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Soft-lock for per-event add-ons: `EnsureEventModule::class.':race_sheets,reports.race-sheet'`.
 * Without the plan module or the event's add-on, downloads and generation send the organiser
 * back to the module's page, which explains the lock and offers the add-on.
 */
final class EnsureEventModule
{
    public function handle(Request $request, Closure $next, string $module, string $lockedRoute): Response
    {
        $event = $request->route('event');
        if ($event instanceof Event && ! EventAddons::unlocked($event, Module::from($module))) {
            if ($request->expectsJson()) {
                abort(402, __('This needs the :addon add-on for this event.', ['addon' => EventAddons::keyFor(Module::from($module))]));
            }

            return redirect()->route($lockedRoute, $event)->with('addon_locked', EventAddons::keyFor(Module::from($module)));
        }

        return $next($request);
    }
}
