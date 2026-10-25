<?php

namespace App\Http\Controllers\PlatformAdmin;

use App\Http\Controllers\Controller;
use App\Models\Org;
use App\Saas\Plans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Indefine platform console: every organisation, its plan and trial/subscription
 * status, and suspension. Reads across tenants on purpose (Org has no org scope;
 * counts bypass it explicitly).
 */
class OrganisationController extends Controller
{
    public const STATUSES = ['trialing', 'active', 'past_due', 'cancelled'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', [...self::STATUSES, 'suspended', 'trial_expired'])],
            'plan' => ['nullable', 'in:'.implode(',', array_keys(Plans::all()))],
        ]);

        $orgs = Org::query()
            ->withCount([
                'users' => fn ($q) => $q->withoutGlobalScopes(),
                'events' => fn ($q) => $q->withoutGlobalScopes(),
                'registrations' => fn ($q) => $q->withoutGlobalScopes(),
            ])
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")->orWhere('slug', 'like', "%{$term}%")))
            ->when($filters['plan'] ?? null, fn ($q, $plan) => $q->where('plan', $plan))
            ->when($filters['status'] ?? null, fn ($q, $status) => match ($status) {
                'suspended' => $q->whereNotNull('suspended_at'),
                'trial_expired' => $q->where('subscription_status', 'trialing')->where('trial_ends_at', '<', now()),
                default => $q->where('subscription_status', $status)->whereNull('suspended_at'),
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('platform.orgs.index', [
            'orgs' => $orgs,
            'filters' => $filters,
            'plans' => Plans::all(),
            'totals' => [
                'orgs' => Org::count(),
                'trialing' => Org::where('subscription_status', 'trialing')->whereNull('suspended_at')->count(),
                'active' => Org::where('subscription_status', 'active')->whereNull('suspended_at')->count(),
                'suspended' => Org::whereNotNull('suspended_at')->count(),
            ],
        ]);
    }

    public function suspend(Org $org): RedirectResponse
    {
        $org->forceFill(['suspended_at' => now()])->save();

        return back()->with('status', __(':org suspended. Its members are signed out.', ['org' => $org->name]));
    }

    public function reactivate(Org $org): RedirectResponse
    {
        $org->forceFill(['suspended_at' => null])->save();

        return back()->with('status', __(':org reactivated.', ['org' => $org->name]));
    }

    public function extendTrial(Org $org): RedirectResponse
    {
        $from = $org->trial_ends_at?->isFuture() ? $org->trial_ends_at : now();
        $org->forceFill([
            'subscription_status' => 'trialing',
            'trial_ends_at' => $from->addDays(Plans::trialDays()),
        ])->save();

        return back()->with('status', __('Trial for :org extended to :date.', [
            'org' => $org->name,
            'date' => $org->trial_ends_at->setTimezone('Asia/Kolkata')->format('j M Y'),
        ]));
    }
}
