{{--
    Photo slot: organiser upload, else a per-sport stock photo (App\View\SportImagery), else a
    tinted gradient graphic, so the layout never shows an empty box or a broken image.
    $src, $alt, $sport, $kenburns, $focus (CSS object-position; alias $position),
    $scrim (bottom navy gradient for text on photos).
--}}
@props(['src' => null, 'alt' => '', 'sport' => 'running', 'kenburns' => false, 'position' => 'center', 'focus' => null, 'scrim' => false])
@php($position = $focus ?? $position)
@php($positioned = preg_match('/(^|\s)(absolute|fixed|sticky)(\s|$)/', (string) $attributes->get('class')))
<div {{ $attributes->merge(['class' => ($positioned ? '' : 'relative ').'overflow-hidden bg-lilac']) }}>
    {{-- The graphic sits underneath; a photo covers it, and a broken photo removes itself. --}}
    @include('components.ri.partials.fallback', ['sport' => $sport])
    @if ($src)
        <img src="{{ $src }}" alt="{{ $alt }}" loading="eager" decoding="async" onerror="this.remove()"
             class="ri-grade absolute inset-0 h-full w-full object-cover {{ $kenburns ? 'ri-kenburns' : '' }}" style="object-position: {{ $position }}">
    @endif
    @if ($scrim)
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-[rgb(18_26_51/.55)] to-transparent" aria-hidden="true"></div>
    @endif
    {{ $slot }}
</div>
