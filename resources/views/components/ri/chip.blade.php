{{--
    Daylight Track status badge (pill). tone:
      live (marigold/navy) | paid | unpaid | trial | ended | suspended | brand | neutral
    Race Ink names still work: heat → live, track → brand, ink → navy, paper/outline → neutral,
    volt → live, outline-light → on-photo.
--}}
@props(['tone' => 'neutral'])
@php
    $aliases = ['heat' => 'live', 'volt' => 'live', 'track' => 'brand', 'paper' => 'neutral', 'outline' => 'neutral'];
    $tone = $aliases[$tone] ?? $tone;
    $tones = [
        'live' => 'bg-heat text-on-heat',
        'paid' => 'bg-[#E3F4EE] text-[#0B4A38]',
        'unpaid' => 'bg-[#FDF0DA] text-[#6B4A12]',
        'trial' => 'bg-lilac text-track-deep',
        'ended' => 'bg-mist text-[#3B4463]',
        'suspended' => 'bg-[#FBE4E4] text-[#6E1414]',
        'brand' => 'bg-track text-on-track',
        'ink' => 'bg-ink text-white',
        'neutral' => 'bg-white text-ink ring-1 ring-inset ring-mist',
        'outline-light' => 'bg-white/10 text-white ring-1 ring-inset ring-white/40 backdrop-blur',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold '.($tones[$tone] ?? $tones['neutral'])]) }}>{{ $slot }}</span>
