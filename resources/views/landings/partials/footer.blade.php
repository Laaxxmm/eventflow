{{--
    Public event footer (Session P1), every template. Never empty: organiser logo or name,
    support email / phone (the event's, else the org owner's and org phone), socials, legal
    and a small "Powered by EventFlow". On trial orgs the credit carries data-watermark.
--}}
@php
    $f = $page->footer();
    $year = $page->date->format('Y');
    $wa = $f['phone'] ? \App\Services\WhatsApp\PhoneNumber::toWhatsApp($f['phone']) : null;
@endphp
<footer class="border-t border-paper/10 bg-ink pb-28 pt-14 text-paper sm:pb-10" data-event-footer>
    <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 md:grid-cols-[1.2fr_1fr_1fr] lg:px-8">
        <div>
            @if ($page->logoUrl())
                <span class="inline-flex items-center bg-paper px-3 py-2">@include('landings.partials.logo', ['class' => 'h-10', 'textClass' => 'font-display text-xl font-bold uppercase text-ink'])</span>
            @else
                <p class="font-display text-3xl font-extrabold uppercase tracking-wide">{{ $page->orgName }}</p>
            @endif
            <p class="mt-4 max-w-xs text-sm text-paper/60">{{ __(':event is organised by :org.', ['event' => $page->config->name, 'org' => $page->orgName]) }}</p>
        </div>

        <div>
            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-paper/45">{{ __('Questions?') }}</p>
            <ul class="mt-3 space-y-2 text-sm">
                @if ($f['email'])
                    <li><a href="mailto:{{ $f['email'] }}?subject={{ rawurlencode($page->config->name) }}" class="font-medium hover:text-heat" data-footer-email>{{ $f['email'] }}</a></li>
                @endif
                @if ($f['phone'])
                    <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $f['phone']) }}" class="hover:text-heat">{{ $f['phone'] }}</a>@if ($wa) · <a href="https://wa.me/{{ $wa }}" class="hover:text-heat" rel="noopener" target="_blank">WhatsApp</a>@endif</li>
                @endif
                @if ($f['website'])
                    <li><a href="{{ $f['website'] }}" rel="noopener" target="_blank" class="hover:text-heat">{{ preg_replace('#^https?://(www\.)?#', '', rtrim($f['website'], '/')) }} ↗</a></li>
                @endif
                @if (! $f['email'] && ! $f['phone'] && ! $f['website'])
                    <li class="text-paper/60">{{ __('Contact :org through your registration email.', ['org' => $page->orgName]) }}</li>
                @endif
            </ul>
            @if ($f['socials'])
                <ul class="mt-4 flex flex-wrap gap-2" aria-label="{{ __('Social media') }}">
                    @foreach ($f['socials'] as $label => $url)
                        <li><a href="{{ $url }}" rel="noopener" target="_blank" class="inline-flex px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em] ring-1 ring-inset ring-paper/20 hover:bg-paper hover:text-ink">{{ $label }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div>
            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-paper/45">{{ __('Race') }}</p>
            <ul class="mt-3 space-y-2 text-sm text-paper/75">
                <li>{{ $page->date->format('D j M Y · g:i A') }}</li>
                <li><a href="{{ $page->mapUrl() }}" target="_blank" rel="noopener" class="hover:text-heat">{{ $page->config->venue }} ↗</a></li>
                @if ($page->open)
                    <li><a href="{{ $page->registerUrl() }}" class="font-semibold text-heat">{{ __('Register') }} →</a></li>
                @endif
            </ul>
        </div>
    </div>

    <div class="mx-auto mt-12 flex max-w-7xl flex-col gap-3 border-t border-paper/10 px-4 pt-6 text-xs text-paper/50 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
        <p class="flex flex-wrap gap-x-4 gap-y-1">
            <span>© {{ $year }} {{ $page->orgName }}</span>
            @if ($f['terms'])
                <a href="{{ $f['terms'] }}" rel="noopener" target="_blank" class="hover:text-paper">{{ __('Terms & refunds') }}</a>
            @endif
            @if ($f['privacy'])
                <a href="{{ $f['privacy'] }}" rel="noopener" target="_blank" class="hover:text-paper">{{ __('Privacy') }}</a>
            @endif
            @if (! $f['terms'] && ! $f['privacy'] && $f['email'])
                <a href="mailto:{{ $f['email'] }}?subject={{ rawurlencode(__('Refund policy: :event', ['event' => $page->config->name])) }}" class="hover:text-paper">{{ __('Refund policy: ask the organiser') }}</a>
            @endif
        </p>
        <a href="{{ route('home') }}" class="hover:text-paper" @if ($page->watermark) data-watermark @endif>{{ __('Powered by :app', ['app' => config('plans.brand.name', 'EventFlow')]) }}</a>
    </div>
</footer>
