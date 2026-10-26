{{-- Race Ink chip. tone: heat | track | ink | volt | paper | outline | outline-light --}}
@props(['tone' => 'ink'])
@php
    $tones = [
        'heat' => 'bg-heat text-on-heat',
        'track' => 'bg-track text-on-track',
        'ink' => 'bg-ink text-paper',
        'volt' => 'bg-volt text-midnight',
        'paper' => 'bg-paper text-ink',
        'outline' => 'ring-1 ring-inset ring-ink/25 text-ink',
        'outline-light' => 'ring-1 ring-inset ring-paper/40 text-paper',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] '.($tones[$tone] ?? $tones['ink'])]) }}>{{ $slot }}</span>
