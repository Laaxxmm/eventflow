{{-- Graphic stand-in when no photo exists: midnight field, Track/Heat glow, sport line work. --}}
<div class="absolute inset-0" aria-hidden="true"
     style="background: radial-gradient(120% 90% at 85% 15%, rgb(var(--ri-track) / .55), transparent 60%), radial-gradient(90% 70% at 10% 100%, rgb(var(--ri-heat) / .45), transparent 60%), rgb(var(--ri-midnight));">
    <svg class="absolute inset-0 h-full w-full text-paper/[0.08] sm:text-paper/15" viewBox="0 0 800 600" preserveAspectRatio="xMidYMid slice" fill="none" stroke="currentColor">
        @switch($sport)
            @case('cycling')
                @foreach (range(0, 9) as $i)
                    <path d="M-50 {{ 520 - $i * 34 }} C 200 {{ 380 - $i * 40 }}, 520 {{ 640 - $i * 48 }}, 860 {{ 300 - $i * 30 }}" stroke-width="{{ $i % 3 === 0 ? 2 : 1 }}"/>
                @endforeach
                @break
            @case('skating')
                @foreach (range(0, 7) as $i)
                    <rect x="{{ 140 - $i * 30 }}" y="{{ 170 - $i * 22 }}" width="{{ 520 + $i * 60 }}" height="{{ 260 + $i * 44 }}" rx="{{ 130 + $i * 22 }}" stroke-width="{{ $i === 3 ? 2.5 : 1 }}"/>
                @endforeach
                @break
            @default
                @foreach (range(0, 8) as $i)
                    <path d="M{{ -100 + $i * 60 }} 640 L{{ 260 + $i * 70 }} -40" stroke-width="{{ $i % 4 === 0 ? 2.5 : 1 }}"/>
                @endforeach
                @foreach (range(1, 8) as $i)
                    <text x="{{ 40 + $i * 70 }}" y="{{ 560 - $i * 6 }}" fill="currentColor" stroke="none" font-family="Barlow Condensed, sans-serif" font-size="28" font-weight="700">{{ $i }}</text>
                @endforeach
        @endswitch
    </svg>
</div>
