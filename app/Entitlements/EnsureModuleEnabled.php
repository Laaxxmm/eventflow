<?php

namespace App\Entitlements;

use App\Contracts\Entitlements;
use App\Enums\Module;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware: ->middleware(EnsureModuleEnabled::class.':certificates').
 * Checks the signed-in user's org; place it after `auth`.
 */
final class EnsureModuleEnabled
{
    public function __construct(private Entitlements $entitlements) {}

    public function handle(Request $request, Closure $next, string $module): Response
    {
        $module = Module::from($module);
        $org = $request->user()?->org;

        abort_unless($org, 403);

        if (! $this->entitlements->enabled($org, $module)) {
            abort(403, __('This needs the :module module. Check your Season Pass and module settings in Org Settings.', [
                'module' => self::label($module),
            ]));
        }

        return $next($request);
    }

    public static function label(Module $module): string
    {
        return match ($module) {
            Module::Registration => __('Registration'),
            Module::Payments => __('Payments'),
            Module::Passes => __('Passes'),
            Module::CheckIn => __('Check-in'),
            Module::RaceSheets => __('Race sheets'),
            Module::Certificates => __('Certificates'),
        };
    }
}
