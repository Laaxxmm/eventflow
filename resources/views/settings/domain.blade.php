{{--
    Settings → Domain (Session P1). Default public URLs always work. Club Season / Circuit Season
    include custom domains; Single Event needs the Custom domain add-on per event (₹599).
    $org, $events, $domains, $included, $withAddon (event ids), $focus, $price, $plan, $target, $aRecords
--}}
@php
    $input = 'ri-input px-3.5 py-3 text-[15px]';
    $copyBtn = 'inline-flex min-h-[44px] items-center rounded-full px-3 text-sm font-semibold text-track hover:text-track-deep';
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
            <p class="mt-1.5 text-[15px] text-muted">{{ $included ? __('A custom domain is included in your :plan.', ['plan' => $planName]) : __('Where runners find your race. Your EventFlow address always works; your own domain looks more established.') }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-[1180px] space-y-5 px-4 pb-12 pt-4 sm:px-6 lg:px-10" data-domain-settings>
        <div class="max-w-[920px] space-y-5">
        @if ($flash)
            <p @class(['rounded-[18px] px-4 py-3 text-sm', 'bg-[#E3F4EE] text-[#0B4A38]' => in_array($flash, ['added', 'verified', 'removed'], true), 'bg-[#FDF0DA] text-[#6B4A12]' => $flash === 'not-yet']) role="status" data-domain-flash="{{ $flash }}">
                {{ [
                    'added' => __('Domain added. Set up the DNS record below, then press Verify.'),
                    'verified' => __('Verified. Your domain is live; HTTPS is issued on the first visit (a few seconds).'),
                    'not-yet' => __('Not pointing at EventFlow yet. Check the record below; DNS can take up to an hour.'),
                    'removed' => __('Domain removed. Your EventFlow address keeps working.'),
                ][$flash] }}
            </p>
        @endif

        {{-- Default URLs --}}
        <section class="flex flex-col gap-3.5 rounded-[28px] bg-white p-[22px] sm:p-[26px]">
            <div>
                <h3 class="text-lg font-bold text-ink">{{ __('Your EventFlow addresses') }}</h3>
                <p class="mt-1 text-sm text-muted">{{ __('Always on, with HTTPS. Share these any time.') }}</p>
            </div>
            <ul class="space-y-2.5" data-default-urls>
                @foreach (collect([[__('Workspace page'), route('landing.org', $org->slug)]])->concat($events->map(fn ($e) => [$e->name, route('landing.show', [$org->slug, $e->slug])])) as [$name, $url])
                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-[18px] bg-paper px-4 py-3.5" x-data="{ copied: false }">
                        <span class="flex min-w-0 flex-col gap-0.5">
                            <span class="text-[13px] text-muted">{{ $name }}</span>
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="break-all font-bold text-ink hover:text-track">{{ $url }}</a>
                        </span>
                        <span class="flex items-center gap-1">
                            <x-ri.chip tone="paid">{{ __('Live') }}</x-ri.chip>
                            <button type="button" class="{{ $copyBtn }}" @click="navigator.clipboard.writeText(@js($url)); copied = true; setTimeout(() => copied = false, 2000)" x-text="copied ? @js(__('Copied')) : @js(__('Copy'))">{{ __('Copy') }}</button>
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Connected domains --}}
        @foreach ($domains as $domain)
            @php
                $live = $domain->isActive();
                $steps = [__('Add your domain'), __('Add the DNS record'), __('We verify and add HTTPS')];
                $at = $live ? 3 : 1; // index of the current step (3 = all done)
            @endphp
            <section @class(['flex flex-col gap-5 rounded-[28px] bg-white p-[22px] sm:p-[26px]', 'ring-2 ring-track' => $flashId === $domain->id]) data-domain="{{ $domain->host }}">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex min-w-0 flex-col gap-1">
                        <h2 class="break-all text-lg font-bold text-ink">{{ $domain->host }}</h2>
                        <span class="text-sm text-muted">{{ __('Shows') }}: {{ $domain->event?->name ?? __('your workspace page') }}</span>
                    </div>
                    <x-ri.chip :tone="$live ? 'paid' : 'unpaid'" data-domain-status>{{ $live ? __('Live') : __('Waiting for DNS') }}</x-ri.chip>
                </div>

                <ol aria-label="{{ __('Setup progress') }}" class="grid grid-cols-3 gap-2 text-sm font-semibold">
                    @foreach ($steps as $i => $step)
                        <li @if ($i === $at) aria-current="step" @endif @class(['flex flex-col gap-2', 'text-track-deep' => $i < $at, 'text-ink' => $i === $at, 'text-muted' => $i > $at])>
                            <span aria-hidden="true" @class(['h-1.5 rounded-full', 'bg-track' => $i < $at, 'bg-heat' => $i === $at, 'bg-mist' => $i > $at])></span>
                            {{ $i + 1 }} {{ $step }}
                        </li>
                    @endforeach
                </ol>

                @unless ($live)
                    <div class="flex flex-col gap-3" data-dns-instructions>
                        <p class="text-[15px] leading-relaxed">{{ __('Add this record at your domain provider (GoDaddy, Namecheap, Cloudflare…):') }}</p>
                        <div class="relative overflow-x-auto rounded-[18px] ring-1 ring-inset ring-[#EEF0F6]">
                            <table class="w-full min-w-[560px] text-[15px]">
                                <thead>
                                    <tr class="bg-paper text-left text-[13px] text-muted">
                                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('Type') }}</th>
                                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('Name') }}</th>
                                        <th scope="col" class="px-4 py-3 font-semibold">{{ __('Value') }}</th>
                                        <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Copy') }}</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="px-4 py-3 font-bold">CNAME</td>
                                        <td class="px-4 py-3">{{ \Illuminate\Support\Str::before($domain->host, '.') }} <span class="text-[13px] text-muted">({{ $domain->host }})</span></td>
                                        <td class="select-all px-4 py-3 font-mono text-sm">{{ $target }}</td>
                                        <td class="px-2 py-1 text-right" x-data="{ copied: false }">
                                            <button type="button" class="{{ $copyBtn }}" @click="navigator.clipboard.writeText(@js($target)); copied = true; setTimeout(() => copied = false, 2000)" x-text="copied ? @js(__('Copied')) : @js(__('Copy'))">{{ __('Copy') }}</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-[13px] text-muted">
                            @if ($aRecords)
                                {{ __('Root domain (no www)? Use an A record to :ips instead.', ['ips' => implode(', ', $aRecords)]) }}
                            @else
                                {{ __('Use a subdomain such as register. or www.; a bare root domain cannot take a CNAME at most providers.') }}
                            @endif
                        </p>
                        @if ($domain->last_error)
                            <p class="text-[13px] text-[#6B4A12]" data-domain-error>{{ __('Last check :when:', ['when' => $domain->last_checked_at?->diffForHumans()]) }} {{ $domain->last_error }}</p>
                        @endif
                    </div>
                @endunless

                <div class="flex flex-wrap items-center justify-between gap-2.5 border-t border-[#EEF0F6] pt-[18px]">
                    <form method="POST" action="{{ route('settings.domain.destroy', $domain) }}" onsubmit="return confirm(@js(__('Disconnect :host?', ['host' => $domain->host])))">
                        @csrf
                        @method('DELETE')
                        <x-ri.button variant="danger-text" size="sm" type="submit" class="!px-1">{{ __('Remove domain') }}</x-ri.button>
                    </form>
                    <span class="flex flex-wrap gap-2.5">
                        @if ($live)
                            <x-ri.button variant="secondary" size="md" :href="'https://'.$domain->host" target="_blank" rel="noopener">{{ __('Open') }}</x-ri.button>
                        @endif
                        <form method="POST" action="{{ route('settings.domain.verify', $domain) }}">
                            @csrf
                            <x-ri.button :variant="$live ? 'secondary' : 'primary'" size="md" type="submit" data-verify>{{ $live ? __('Re-check') : __('Verify now') }}</x-ri.button>
                        </form>
                    </span>
                </div>
            </section>
        @endforeach

        {{-- Connect --}}
        <section class="flex flex-col gap-5 rounded-[28px] bg-white p-[22px] sm:p-[26px]" id="connect" data-connect-domain>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-ink">{{ __('Connect custom domain') }}</h3>
                    <p class="mt-1 max-w-xl text-sm text-muted">{{ __('Put your race on your own address, like register.yourrace.in. Runners see your brand end to end, and the link reads like an official race site.') }}</p>
                </div>
                <x-ri.chip :tone="$included ? 'paid' : 'trial'" data-domain-entitlement="{{ $included ? 'included' : 'addon' }}">
                    {{ $included ? __('Included in :plan', ['plan' => $planName]) : __('Add-on · :price per event', ['price' => $price]) }}
                </x-ri.chip>
            </div>

            @if ($targets->isNotEmpty())
                <form method="POST" action="{{ route('settings.domain.store') }}" class="grid gap-4 sm:grid-cols-[1fr_16rem_auto] sm:items-end">
                    @csrf
                    <div>
                        <label for="host" class="ri-label">{{ __('Your domain') }}</label>
                        <input id="host" name="host" type="text" value="{{ old('host') }}" placeholder="register.yourrace.in" class="{{ $input }}" required maxlength="253" autocapitalize="off" spellcheck="false">
                        <x-input-error class="mt-2" :messages="$errors->get('host')" />
                    </div>
                    <div>
                        <label for="target" class="ri-label">{{ __('Shows') }}</label>
                        <select id="target" name="target" class="{{ $input }}">
                            @foreach ($targets as $value => $label)
                                <option value="{{ $value }}" @selected((string) old('target', $focus) === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('target')" />
                    </div>
                    <x-ri.button variant="primary" size="md" type="submit" class="font-bold">{{ __('Connect') }}</x-ri.button>
                </form>
            @elseif ($included || $withAddon)
                <p class="text-sm text-muted">{{ __('Every page you can connect already has a domain.') }}</p>
            @endif

            @if ($lockedEvents->isNotEmpty())
                <div class="rounded-[22px] bg-[#FDF0DA] p-5 text-sm text-[#6B4A12]" data-domain-locked>
                    <p class="font-bold">{{ __('Custom domain is not included in :plan.', ['plan' => $planName]) }}</p>
                    <p class="mt-1">{{ __('Add it to an event for :price (per event, for your 90-day window), or move to Club Season or Circuit Season, which include it.', ['price' => $price]) }}</p>
                    <ul class="mt-4 space-y-2">
                        @foreach ($lockedEvents as $e)
                            <li class="flex flex-wrap items-center justify-between gap-3">
                                <span class="font-semibold text-ink">{{ $e->name }}</span>
                                <x-ri.button variant="ink" size="sm" :href="route('billing.show', ['sku' => 'custom_domain', 'event' => $e->id])" data-add-custom-domain>{{ __('Add Custom domain — :price', ['price' => $price]) }}</x-ri.button>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('pricing') }}#add-ons" class="mt-3 inline-flex min-h-[44px] items-center font-bold underline underline-offset-4">{{ __('Compare plans') }}</a>
                </div>
            @endif

            @if ($events->isEmpty())
                <p class="text-sm text-muted">{{ __('Create an event first; then you can give it its own domain.') }} <a href="{{ route('organiser.events.create') }}" class="font-semibold text-track underline">{{ __('New event') }}</a></p>
            @endif

            <details class="rounded-[18px] bg-paper px-4 py-3 text-sm text-muted">
                <summary class="flex min-h-[32px] cursor-pointer items-center font-semibold text-ink">{{ __('How it works') }}</summary>
                <ol class="mt-3 list-decimal space-y-1 ps-5">
                    <li>{{ __('Enter the domain and choose which page it shows.') }}</li>
                    <li>{{ __('Add a CNAME record pointing to :target at your domain provider.', ['target' => $target]) }}</li>
                    <li>{{ __('Press Verify. Once DNS points at us, the domain goes live and HTTPS is issued automatically.') }}</li>
                </ol>
                <p class="mt-3">{{ __('Your EventFlow address keeps working, so links you already shared never break.') }}</p>
            </details>
        </section>
        </div>
    </div>
</x-app-layout>
