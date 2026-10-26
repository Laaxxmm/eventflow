{{--
    Race Ink button. variant: heat (primary CTA) | ink | track | light | ghost | ghost-light.
    Heat buttons use ink text (6:1); a brand-tinted Heat picks its text colour by contrast (App\Landing\Theme).
--}}
@props(['variant' => 'heat', 'href' => null, 'size' => 'lg', 'type' => 'button'])
@php
    $base = 'inline-flex items-center justify-center gap-2 font-display font-bold uppercase tracking-[0.06em] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50';
    $sizes = ['md' => 'px-5 py-2.5 text-lg', 'lg' => 'px-7 py-3.5 text-xl', 'xl' => 'px-9 py-4 text-2xl'];
    $variants = [
        'heat' => 'bg-heat text-on-heat hover:brightness-95 focus-visible:ring-heat',
        'ink' => 'bg-ink text-paper hover:bg-ink/90 focus-visible:ring-ink',
        'track' => 'bg-track text-on-track hover:brightness-110 focus-visible:ring-track',
        'light' => 'bg-paper text-ink hover:bg-white focus-visible:ring-paper',
        'ghost' => 'ring-2 ring-inset ring-ink text-ink hover:bg-ink hover:text-paper focus-visible:ring-ink',
        'ghost-light' => 'ring-2 ring-inset ring-paper/70 text-paper hover:bg-paper hover:text-ink focus-visible:ring-paper',
    ];
    $class = $base.' '.($sizes[$size] ?? $sizes['lg']).' '.($variants[$variant] ?? $variants['heat']);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif
