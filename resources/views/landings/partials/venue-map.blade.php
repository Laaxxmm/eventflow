{{-- Venue card: a drawn map (not a live map) with the venue and an Open in Maps link. $page, $class --}}
@php
    $venueName = \Illuminate\Support\Str::before($page->config->venue, ',');
    $venueRest = trim(\Illuminate\Support\Str::after($page->config->venue, ','));
@endphp
<div class="relative min-h-[300px] overflow-hidden rounded-[28px] bg-[#E3E8F3] {{ $class ?? '' }}">
    <svg aria-hidden="true" class="absolute inset-0 h-full w-full" viewBox="0 0 600 380" preserveAspectRatio="xMidYMid slice"><path d="M0 120 H600 M0 260 H600 M160 0 V380 M420 0 V380" stroke="#FFFFFF" stroke-width="10"/><path d="M60 330 C 200 300, 300 200, 520 60" stroke="rgb(var(--ri-track))" stroke-width="4" fill="none" stroke-dasharray="10 8"/><circle cx="300" cy="200" r="14" fill="rgb(var(--ri-heat))" stroke="#FFFFFF" stroke-width="4"/></svg>
    <div class="absolute inset-x-4 bottom-4 flex items-center justify-between gap-3 rounded-[18px] bg-white px-[18px] py-4 text-ink sm:inset-x-5 sm:bottom-5">
        <span class="flex min-w-0 flex-col gap-0.5"><strong>{{ $venueName }}</strong>@if ($venueRest !== '' && $venueRest !== $page->config->venue)<span class="text-sm text-muted">{{ $venueRest }}</span>@endif</span>
        <a href="{{ $page->mapUrl() }}" target="_blank" rel="noopener" class="inline-flex min-h-[44px] shrink-0 items-center text-sm font-semibold text-track-text hover:underline">{{ __('Open in Maps') }}</a>
    </div>
</div>
