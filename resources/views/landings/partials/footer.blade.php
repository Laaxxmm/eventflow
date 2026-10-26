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
<footer class="bg-white pb-32 pt-14 text-ink sm:pb-10" data-event-footer>
    <div class="mx-auto grid max-w-[1280px] gap-10 px-4 sm:px-6 md:grid-cols-[1.2fr_1fr_1fr] lg:px-10">
        <div>
            @if ($page->logoUrl())
                <span class="inline-flex items-center rounded-[12px] bg-paper px-3 py-2">@include('landings.partials.logo', ['class' => 'h-10', 'textClass' => 'ri-headline text-xl text-ink'])</span>
            @else
                <p class="ri-headline text-2xl">{{ $page->orgName }}</p>
            @endif
            <p class="mt-4 max-w-xs text-sm text-muted">{{ __(':event is organised by :org.', ['event' => $page->config->name, 'org' => $page->orgName]) }}</p>
        </div>

        <div>
            <p class="text-sm font-bold">{{ __('Questions?') }}</p>
            <ul class="mt-3 space-y-2 text-sm">
                @if ($f['email'])
                    <li><a href="mailto:{{ $f['email'] }}?subject={{ rawurlencode($page->config->name) }}" class="font-medium hover:text-track" data-footer-email>{{ $f['email'] }}</a></li>
                @endif
                @if ($f['phone'])
                    <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $f['phone']) }}" class="hover:text-track">{{ $f['phone'] }}</a>@if ($wa) · <a href="https://wa.me/{{ $wa }}" class="hover:text-track" rel="noopener" target="_blank">WhatsApp</a>@endif</li>
                @endif
                @if ($f['website'])
                    <li><a href="{{ $f['website'] }}" rel="noopener" target="_blank" class="hover:text-track">{{ preg_replace('#^https?://(www\.)?#', '', rtrim($f['website'], '/')) }}</a></li>
                @endif
                @if (! $f['email'] && ! $f['phone'] && ! $f['website'])
                    <li class="text-muted">{{ __('Contact :org through your registration email.', ['org' => $page->orgName]) }}</li>
                @endif
            </ul>
            @if ($f['socials'])
                <ul class="mt-4 flex flex-wrap gap-2" aria-label="{{ __('Social media') }}">
                    @foreach ($f['socials'] as $label => $url)
                        <li><a href="{{ $url }}" rel="noopener" target="_blank" class="inline-flex min-h-[40px] items-center rounded-full bg-paper px-4 text-xs font-semibold hover:bg-lilac hover:text-track-deep">{{ $label }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div>
            <p class="text-sm font-bold">{{ __('Race') }}</p>
            <ul class="mt-3 space-y-2 text-sm text-ink">
                <li>{{ $page->date->format('D j M Y · g:i A') }}</li>
                <li><a href="{{ $page->mapUrl() }}" target="_blank" rel="noopener" class="hover:text-track">{{ $page->config->venue }}</a></li>
                @if ($page->open)
                    <li><a href="{{ $page->registerUrl() }}" class="font-semibold text-track">{{ __('Register') }}</a></li>
                @endif
            </ul>
        </div>
    </div>

    <div class="mx-auto mt-12 flex max-w-[1280px] flex-col gap-3 border-t border-mist px-4 pt-6 text-xs text-muted sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-10">
        <p class="flex flex-wrap gap-x-4 gap-y-1">
            <span>© {{ $year }} {{ $page->orgName }}</span>
            @if ($f['terms'])
                <a href="{{ $f['terms'] }}" rel="noopener" target="_blank" class="hover:text-ink">{{ __('Terms & refunds') }}</a>
            @endif
            @if ($f['privacy'])
                <a href="{{ $f['privacy'] }}" rel="noopener" target="_blank" class="hover:text-ink">{{ __('Privacy') }}</a>
            @endif
            @if (! $f['terms'] && ! $f['privacy'] && $f['email'])
                <a href="mailto:{{ $f['email'] }}?subject={{ rawurlencode(__('Refund policy: :event', ['event' => $page->config->name])) }}" class="hover:text-ink">{{ __('Refund policy: ask the organiser') }}</a>
            @endif
        </p>
        <a href="{{ route('home') }}" class="hover:text-ink" @if ($page->watermark) data-watermark @endif>{{ __('Powered by :app', ['app' => config('plans.brand.name', 'EventFlow')]) }}</a>
    </div>
</footer>
