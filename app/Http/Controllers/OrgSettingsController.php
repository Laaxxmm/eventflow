<?php

namespace App\Http\Controllers;

use App\Entitlements\EnsureModuleEnabled;
use App\Entitlements\SeasonPassEntitlements;
use App\Enums\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Org Settings (owners only): the org's Season Pass and the optional module
 * toggles. Core modules are always on; modules outside the pass can't be enabled.
 */
class OrgSettingsController extends Controller
{
    public function __construct(private SeasonPassEntitlements $entitlements) {}

    public function edit(Request $request): View
    {
        $org = $request->user()->org;

        return view('settings.edit', [
            'org' => $org,
            'pass' => $this->entitlements->activeSeasonPass($org),
            'core' => array_values(array_filter(Module::cases(), fn (Module $m) => SeasonPassEntitlements::isCore($m))),
            'optional' => collect(SeasonPassEntitlements::optional())->map(fn (Module $m) => [
                'module' => $m,
                'label' => EnsureModuleEnabled::label($m),
                'included' => $this->entitlements->included($org, $m),
                'on' => $this->entitlements->enabled($org, $m),
            ])->all(),
            'contact' => config('eventflow.certificates.custom_contact'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $org = $request->user()->org;
        $request->validate(['modules' => ['array'], 'modules.*' => ['string']]);
        $checked = (array) $request->input('modules', []);

        $toggles = (array) ($org->modules ?? []);
        foreach (SeasonPassEntitlements::optional() as $module) {
            // Only modules the pass includes can be switched; others keep their value.
            if ($this->entitlements->included($org, $module)) {
                $toggles[$module->value] = in_array($module->value, $checked, true);
            }
        }

        $org->update(['modules' => $toggles]);

        return redirect()->route('settings.edit')->with('status', 'modules-updated');
    }
}
