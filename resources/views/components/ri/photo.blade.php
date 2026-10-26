{{--
    Race Ink photo slot: organiser upload or a per-sport stock placeholder
    (App\View\SportImagery). With neither, a graded graphic fallback so the
    layout never shows an empty box. $src, $alt, $sport, $kenburns.
--}}
@props(['src' => null, 'alt' => '', 'sport' => 'running', 'kenburns' => false, 'position' => 'center'])
@php($positioned = preg_match('/(^|\s)(absolute|fixed|sticky)(\s|$)/', (string) $attributes->get('class')))
<div {{ $attributes->merge(['class' => ($positioned ? '' : 'relative ').'overflow-hidden bg-midnight']) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $alt }}" loading="eager" decoding="async"
             class="ri-grade absolute inset-0 h-full w-full object-cover {{ $kenburns ? 'ri-kenburns' : '' }}" style="object-position: {{ $position }}">
    @else
        @include('components.ri.partials.fallback', ['sport' => $sport])
    @endif
    {{ $slot }}
</div>
