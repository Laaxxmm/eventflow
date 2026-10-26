{{-- Graphic stand-in when no photo exists: a light tinted gradient (lilac → indigo, a marigold glow) with sport line work. --}}
<div class="absolute inset-0" aria-hidden="true"
     style="background: radial-gradient(110% 90% at 85% 10%, rgb(var(--ri-track) / .45), transparent 62%), radial-gradient(80% 70% at 5% 100%, rgb(var(--ri-heat) / .35), transparent 60%), linear-gradient(160deg, rgb(var(--ri-lilac)), rgb(var(--ri-paper)));">
    <svg class="absolute inset-0 h-full w-full text-track/[0.14]" viewBox="0 0 800 600" preserveAspectRatio="xMidYMid slice" fill="none" stroke="currentColor">
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
                    <text x="{{ 40 + $i * 70 }}" y="{{ 560 - $i * 6 }}" fill="currentColor" stroke="none" font-family="Archivo Variable, Archivo, sans-serif" font-size="28" font-weight="700">{{ $i }}</text>
                @endforeach
        @endswitch
    </svg>
</div>
