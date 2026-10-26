<?php

namespace App\Http\Controllers;

use App\Domains\DomainAccess;
use App\Domains\DomainRouter;
use App\Domains\DomainVerifier;
use App\Domains\HostName;
use App\Models\CustomDomain;
use App\Models\Event;
use App\Models\Tenancy\OrgScope;
use App\Saas\Plans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Settings → Domain (Session P1): the default public URLs, and connecting the org's own domain.
 * Club Season and Circuit Season include custom domains; Single Event needs the ₹599 Custom
 * domain add-on for the event first (Add Custom domain → the Buy page).
 */
class DomainSettingsController extends Controller
{
    public function show(Request $request): View
    {
        $org = $request->user()->org ?? abort(404);
        $events = Event::query()->orderByDesc('date')->get();

        return view('settings.domain', [
            'org' => $org,
            'events' => $events,
            'domains' => CustomDomain::query()->with('event')->orderBy('id')->get(),
            'included' => DomainAccess::planIncludes($org),
            'withAddon' => $events->filter(fn (Event $e) => DomainAccess::eventHasAddon($e))->pluck('id')->all(),
            'focus' => $request->integer('event') ?: null,
            'price' => Plans::inr(Plans::customDomainPrice()),
            'plan' => $org->plan ? (Plans::all()[$org->plan] ?? null) : null,
            'target' => config('domains.cname_target'),
            'aRecords' => config('domains.a_records'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $org = $request->user()->org ?? abort(404);
        $data = $request->validate([
            'host' => ['required', 'string', 'max:253'],
            'target' => ['required', 'string', 'max:20'], // "workspace" or an event id
        ]);

        $host = HostName::normalise($data['host']);
        if ($problem = HostName::problem($host)) {
            throw ValidationException::withMessages(['host' => $problem]);
        }
        if (CustomDomain::withoutGlobalScope(OrgScope::class)->where('host', $host)->exists()) {
            throw ValidationException::withMessages(['host' => __('That domain is already connected.')]);
        }

        $event = $data['target'] === 'workspace' ? null : Event::findOrFail((int) $data['target']);
        if (! DomainAccess::canConnect($org, $event)) {
            return redirect()->route('billing.show', ['sku' => DomainAccess::ADDON, 'event' => $event?->id ?? Event::query()->orderByDesc('date')->value('id')]);
        }
        if (CustomDomain::query()->where('event_id', $event?->id)->exists()) {
            throw ValidationException::withMessages(['target' => $event
                ? __(':event already has a custom domain. Remove it first.', ['event' => $event->name])
                : __('Your workspace page already has a custom domain. Remove it first.')]);
        }

        $domain = CustomDomain::create(['event_id' => $event?->id, 'host' => $host, 'status' => CustomDomain::PENDING]);

        return redirect()->route('settings.domain.show')->with('domain_status', 'added')->with('domain_id', $domain->id);
    }

    public function verify(CustomDomain $domain, DomainVerifier $verifier): RedirectResponse
    {
        $verifier->verify($domain);

        return redirect()->route('settings.domain.show')->with('domain_status', $domain->isActive() ? 'verified' : 'not-yet')->with('domain_id', $domain->id);
    }

    public function destroy(CustomDomain $domain): RedirectResponse
    {
        $host = $domain->host;
        $domain->delete();
        cache()->forget(DomainRouter::cacheKey($host));

        return redirect()->route('settings.domain.show')->with('domain_status', 'removed');
    }

    /**
     * On-demand TLS "ask" endpoint for the edge proxy (Caddy on_demand_tls / Traefik): 200 when
     * the host is a connected, verified custom domain, else 404 (no certificate is issued).
     */
    public function tlsCheck(Request $request): Response
    {
        $host = HostName::normalise((string) $request->query('domain'));
        $ok = $host !== '' && CustomDomain::withoutGlobalScopes()->where('host', $host)->where('status', CustomDomain::ACTIVE)->exists();

        return response($ok ? 'ok' : 'unknown domain', $ok ? 200 : 404)->header('Content-Type', 'text/plain');
    }
}
