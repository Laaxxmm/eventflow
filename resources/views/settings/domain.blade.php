{{--
    Settings → Domain (Session P1). Default public URLs always work. Club Season / Circuit Season
    include custom domains; Single Event needs the Custom domain add-on per event (₹599).
    $org, $events, $domains, $included, $withAddon (event ids), $focus, $price, $plan, $target, $aRecords
--}}
@php
    $input = 'mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900';
    $flash = session('domain_status');
    $flashId = session('domain_id');
    $usedEvents = $domains->pluck('event_id')->filter()->all();
    $workspaceTaken = $domains->contains(fn ($d) => $d->event_id === null);
    $targets = collect();
    if ($included && ! $workspaceTaken) {
        $targets->put('workspace', __('Workspace page (all your events)'));
    }
    foreach ($events as $e) {
        if (! in_array($e->id, $usedEvents, true) && ($included || in_array($e->id, $withAddon, true))) {
            $targets->put((string) $e->id, $e->name);
        }
    }
    $lockedEvents = $included ? collect() : $events->reject(fn ($e) => in_array($e->id, $withAddon, true));
    $planName = $plan['name'] ?? __('your plan');
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('Domain') }}</x-slot>
    <x-slot name="header">
        <div>
            <h2>{{ __('Domain') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Where runners find your race. Your EventFlow address always works; your own domain looks more established.') }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-8 px-4 py-8 sm:px-6 lg:px-10" data-domain-settings>
        @if ($flash)
            <p @class(['rounded-xl p-4 text-sm font-medium', 'bg-green-50 text-green-800' => in_array($flash, ['added', 'verified', 'removed'], true), 'bg-amber-50 text-amber-900' => $flash === 'not-yet']) role="status" data-domain-flash="{{ $flash }}">
                {{ [
                    'added' => __('Domain added. Set up the DNS record below, then press Verify.'),
                    'verified' => __('Verified. Your domain is live; HTTPS is issued on the first visit (a few seconds).'),
                    'not-yet' => __('Not pointing at EventFlow yet. Check the record below; DNS can take up to an hour.'),
                    'removed' => __('Domain removed. Your EventFlow address keeps working.'),
                ][$flash] }}
            </p>
        @endif

        {{-- Default URLs --}}
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 sm:p-8">
            <h3 class="text-base font-semibold">{{ __('Your EventFlow addresses') }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ __('Always on, with HTTPS. Share these any time.') }}</p>
            <ul class="mt-4 divide-y divide-slate-100 text-sm" data-default-urls>
                <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                    <span class="text-slate-500">{{ __('Workspace page') }}</span>
                    <a href="{{ route('landing.org', $org->slug) }}" target="_blank" rel="noopener" class="font-mono text-slate-900 underline decoration-slate-300 hover:decoration-slate-900">{{ route('landing.org', $org->slug) }}</a>
                </li>
                @foreach ($events as $e)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                        <span class="text-slate-500">{{ $e->name }}</span>
                        <a href="{{ route('landing.show', [$org->slug, $e->slug]) }}" target="_blank" rel="noopener" class="font-mono text-slate-900 underline decoration-slate-300 hover:decoration-slate-900">{{ route('landing.show', [$org->slug, $e->slug]) }}</a>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Connected domains --}}
        @foreach ($domains as $domain)
            <section @class(['rounded-3xl bg-white p-6 shadow-sm ring-1 sm:p-8', 'ring-slate-900' => $flashId === $domain->id, 'ring-slate-200/70' => $flashId !== $domain->id]) data-domain="{{ $domain->host }}">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-2">
                            <span class="truncate font-mono text-lg font-semibold">{{ $domain->host }}</span>
                            <span @class(['rounded-full px-2.5 py-0.5 text-xs font-semibold', 'bg-emerald-100 text-emerald-800' => $domain->isActive(), 'bg-amber-100 text-amber-800' => ! $domain->isActive()]) data-domain-status>{{ $domain->isActive() ? __('Live') : __('Waiting for DNS') }}</span>
                        </p>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Shows') }}: {{ $domain->event?->name ?? __('your workspace page') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($domain->isActive())
                            <a href="https://{{ $domain->host }}" target="_blank" rel="noopener" class="rounded-xl bg-[#0b1020] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1f2a4a]">{{ __('Open') }} ↗</a>
                        @endif
                        <form method="POST" action="{{ route('settings.domain.verify', $domain) }}">
                            @csrf
                            <button type="submit" class="rounded-xl px-4 py-2 text-sm font-semibold ring-1 ring-slate-300 hover:bg-slate-50" data-verify>{{ $domain->isActive() ? __('Re-check') : __('Verify') }}</button>
                        </form>
                        <form method="POST" action="{{ route('settings.domain.destroy', $domain) }}" onsubmit="return confirm(@js(__('Disconnect :host?', ['host' => $domain->host])))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-xl px-3 py-2 text-sm font-medium text-slate-500 hover:text-red-700">{{ __('Remove') }}</button>
                        </form>
                    </div>
                </div>

                @unless ($domain->isActive())
                    <div class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm" data-dns-instructions>
                        <p class="font-semibold">{{ __('Add this record at your domain provider (GoDaddy, Namecheap, Cloudflare…):') }}</p>
                        <dl class="mt-3 grid gap-2 font-mono text-xs sm:grid-cols-[6rem_1fr]">
                            <dt class="text-slate-500">{{ __('Type') }}</dt><dd>CNAME</dd>
                            <dt class="text-slate-500">{{ __('Name') }}</dt><dd>{{ \Illuminate\Support\Str::before($domain->host, '.') }} <span class="text-slate-400">({{ $domain->host }})</span></dd>
                            <dt class="text-slate-500">{{ __('Value') }}</dt><dd class="select-all">{{ $target }}</dd>
                        </dl>
                        @if ($aRecords)
                            <p class="mt-3 text-xs text-slate-500">{{ __('Root domain (no www)? Use an A record to :ips instead.', ['ips' => implode(', ', $aRecords)]) }}</p>
                        @else
                            <p class="mt-3 text-xs text-slate-500">{{ __('Use a subdomain such as register. or www.; a bare root domain cannot take a CNAME at most providers.') }}</p>
                        @endif
                        @if ($domain->last_error)
                            <p class="mt-3 text-xs text-amber-800" data-domain-error>{{ __('Last check :when:', ['when' => $domain->last_checked_at?->diffForHumans()]) }} {{ $domain->last_error }}</p>
                        @endif
                    </div>
                @endunless
            </section>
        @endforeach

        {{-- Connect --}}
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 sm:p-8" id="connect" data-connect-domain>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold">{{ __('Connect custom domain') }}</h3>
                    <p class="mt-1 max-w-xl text-sm text-slate-500">{{ __('Put your race on your own address, like register.yourrace.in. Runners see your brand end to end, and the link reads like an official race site.') }}</p>
                </div>
                <span @class(['rounded-full px-3 py-1 text-xs font-semibold', 'bg-emerald-100 text-emerald-800' => $included, 'bg-slate-100 text-slate-700' => ! $included]) data-domain-entitlement="{{ $included ? 'included' : 'addon' }}">
                    {{ $included ? __('Included in :plan', ['plan' => $planName]) : __('Add-on · :price per event', ['price' => $price]) }}
                </span>
            </div>

            @if ($targets->isNotEmpty())
                <form method="POST" action="{{ route('settings.domain.store') }}" class="mt-6 grid gap-4 sm:grid-cols-[1fr_16rem_auto] sm:items-end">
                    @csrf
                    <div>
                        <label for="host" class="text-sm font-medium text-slate-700">{{ __('Your domain') }}</label>
                        <input id="host" name="host" type="text" value="{{ old('host') }}" placeholder="register.yourrace.in" class="{{ $input }} font-mono" required maxlength="253" autocapitalize="off" spellcheck="false">
                        <x-input-error class="mt-2" :messages="$errors->get('host')" />
                    </div>
                    <div>
                        <label for="target" class="text-sm font-medium text-slate-700">{{ __('Shows') }}</label>
                        <select id="target" name="target" class="{{ $input }}">
                            @foreach ($targets as $value => $label)
                                <option value="{{ $value }}" @selected((string) old('target', $focus) === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('target')" />
                    </div>
                    <button type="submit" class="rounded-xl bg-[#0b1020] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#1f2a4a]">{{ __('Connect') }}</button>
                </form>
            @elseif ($included || $withAddon)
                <p class="mt-6 text-sm text-slate-600">{{ __('Every page you can connect already has a domain.') }}</p>
            @endif

            @if ($lockedEvents->isNotEmpty())
                <div class="mt-6 rounded-2xl bg-amber-50 p-5 text-sm text-amber-950 ring-1 ring-amber-200" data-domain-locked>
                    <p class="font-semibold">{{ __('Custom domain is not included in :plan.', ['plan' => $planName]) }}</p>
                    <p class="mt-1">{{ __('Add it to an event for :price (per event, for your 90-day window), or move to Club Season or Circuit Season, which include it.', ['price' => $price]) }}</p>
                    <ul class="mt-4 space-y-2">
                        @foreach ($lockedEvents as $e)
                            <li class="flex flex-wrap items-center justify-between gap-3">
                                <span class="font-medium">{{ $e->name }}</span>
                                <a href="{{ route('billing.show', ['sku' => 'custom_domain', 'event' => $e->id]) }}" class="inline-flex items-center rounded-lg bg-[#0b1020] px-4 py-2 font-semibold text-white hover:bg-[#1f2a4a]" data-add-custom-domain>{{ __('Add Custom domain — :price', ['price' => $price]) }}</a>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('pricing') }}#add-ons" class="mt-4 inline-block font-semibold underline">{{ __('Compare plans') }}</a>
                </div>
            @endif

            @if ($events->isEmpty())
                <p class="mt-6 text-sm text-slate-600">{{ __('Create an event first; then you can give it its own domain.') }} <a href="{{ route('organiser.events.create') }}" class="font-semibold underline">{{ __('New event') }}</a></p>
            @endif

            <details class="mt-6 text-sm text-slate-600">
                <summary class="cursor-pointer font-medium text-slate-800">{{ __('How it works') }}</summary>
                <ol class="mt-3 list-decimal space-y-1 ps-5">
                    <li>{{ __('Enter the domain and choose which page it shows.') }}</li>
                    <li>{{ __('Add a CNAME record pointing to :target at your domain provider.', ['target' => $target]) }}</li>
                    <li>{{ __('Press Verify. Once DNS points at us, the domain goes live and HTTPS is issued automatically.') }}</li>
                </ol>
                <p class="mt-3">{{ __('Your EventFlow address keeps working, so links you already shared never break.') }}</p>
            </details>
        </section>
    </div>
</x-app-layout>
