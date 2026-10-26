{{--
    Daylight Track button: always a pill. variant:
      primary (indigo, white) | ink (navy, white) | register (marigold, navy) |
      secondary (white, 1px #C5CBDB border) | quiet (chalk) | danger-text
    Race Ink names still work: heat → register, track → primary, light/ghost → secondary,
    ghost-light → on-photo outline. size: sm | md | lg | xl (13–18px vertical padding).
--}}
@props(['variant' => 'primary', 'href' => null, 'size' => 'lg', 'type' => 'button'])
@php
    $aliases = ['heat' => 'register', 'track' => 'primary', 'light' => 'secondary', 'ghost' => 'secondary'];
    $variant = $aliases[$variant] ?? $variant;
    $base = 'inline-flex min-h-[44px] items-center justify-center gap-2 rounded-full font-semibold transition duration-200 ease-track disabled:cursor-not-allowed disabled:opacity-50';
    $sizes = [
        'sm' => 'px-4 py-2 text-sm',
        'md' => 'px-5 py-[13px] text-[15px]',
        'lg' => 'px-7 py-[15px] text-base font-bold',
        'xl' => 'px-9 py-[18px] text-lg font-bold',
    ];
    $variants = [
        'primary' => 'bg-track text-on-track ri-glow hover:bg-track-deep',
        'ink' => 'bg-ink text-white hover:bg-ink/90',
        'register' => 'bg-heat text-on-heat hover:brightness-95',
        'secondary' => 'bg-white text-ink ring-1 ring-inset ring-[#C5CBDB] hover:ring-ink/40',
        'quiet' => 'bg-paper text-ink hover:bg-mist',
        'danger-text' => 'bg-transparent text-danger underline-offset-4 hover:underline',
        'ghost-light' => 'bg-white/10 text-white ring-1 ring-inset ring-white/50 backdrop-blur hover:bg-white hover:text-ink',
    ];
    $class = $base.' '.($sizes[$size] ?? $sizes['lg']).' '.($variants[$variant] ?? $variants['primary']);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif
