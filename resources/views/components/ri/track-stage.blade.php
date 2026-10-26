{{--
    Homepage hero stage (docs/design/daylight-track/Main.dc.html): a 3D running track built from stacked
    layers (pure CSS transforms), three runners on the lanes, floating places-left and payment chips and a
    sample race pass. Tilts towards the pointer on desktop; static on touch; still under reduced motion.
--}}
@php
    $qr = 'data:image/png;base64,'.base64_encode(app(\App\Passes\LocalQr::class)->png('EF-7K2Q9M4XB3TA', 200));
    $lanes = [
        ['M 170 18 H 430 A 152 152 0 0 1 430 322 H 170 A 152 152 0 0 1 170 18 Z', '#F2A93B', '13s', '0s'],
        ['M 170 30 H 430 A 140 140 0 0 1 430 310 H 170 A 140 140 0 0 1 170 30 Z', '#FFFFFF', '15s', '-5s'],
        ['M 170 54 H 430 A 116 116 0 0 1 430 286 H 170 A 116 116 0 0 1 170 54 Z', '#F2A93B', '11s', '-8s'],
    ];
@endphp
<div {{ $attributes->merge(['class' => 'relative flex h-[380px] min-w-0 items-center justify-center overflow-hidden sm:h-[620px] sm:overflow-visible']) }}
     x-data="{ rx: 0, ry: 0, on: window.matchMedia('(hover: hover) and (prefers-reduced-motion: no-preference)').matches }"
     @mousemove="if (on) { const r = $el.getBoundingClientRect(); rx = Math.round(-((($event.clientY - r.top) / r.height) - .5) * 120) / 10; ry = Math.round(((($event.clientX - r.left) / r.width) - .5) * 160) / 10 }"
     @mouseleave="rx = 0; ry = 0" data-track-stage>
    <div class="h-[340px] w-[600px] flex-none scale-[.56] [perspective:1600px] sm:scale-100">
        <div class="relative h-[340px] w-[600px] transition-transform duration-500 ease-track [transform-style:preserve-3d]" :style="`transform: rotateX(${rx}deg) rotateY(${ry}deg)`">
            <div class="ef-spin absolute inset-0 [transform-style:preserve-3d]" style="transform: rotateX(58deg) rotateZ(-20deg)" data-motion>
                <div aria-hidden="true" class="absolute inset-2.5 rounded-[170px] bg-[rgb(43_35_144/.35)] blur-[28px]" style="transform: translateZ(-70px)"></div>
                @foreach (['#1E1870' => -18, '#221B7E' => -15, '#261F8C' => -12, '#2B2390' => -9, '#3027A0' => -6, '#352CB4' => -3] as $shade => $z)
                    <div aria-hidden="true" class="absolute inset-0 rounded-[170px]" style="background: {{ $shade }}; transform: translateZ({{ $z }}px)"></div>
                @endforeach
                <svg role="img" aria-label="{{ __('A running track with runners moving round the lanes') }}" width="600" height="340" viewBox="0 0 600 340" class="absolute inset-0 block">
                    <rect x="0" y="0" width="600" height="340" rx="170" fill="#4B3FE0"/>
                    @foreach ([12, 24, 36, 48, 60] as $o)
                        <rect x="{{ $o }}" y="{{ $o }}" width="{{ 600 - 2 * $o }}" height="{{ 340 - 2 * $o }}" rx="{{ 170 - $o }}" fill="none" stroke="#fff" stroke-opacity=".55" stroke-width="1.4"/>
                    @endforeach
                    <rect x="72" y="72" width="456" height="196" rx="98" fill="#F7F8FC"/>
                    <rect x="150" y="104" width="300" height="132" rx="6" fill="none" stroke="#4B3FE0" stroke-opacity=".18" stroke-width="2"/>
                    <line x1="300" y1="104" x2="300" y2="236" stroke="#4B3FE0" stroke-opacity=".18" stroke-width="2"/>
                    <circle cx="300" cy="170" r="26" fill="none" stroke="#4B3FE0" stroke-opacity=".18" stroke-width="2"/>
                    <rect x="398" y="268" width="6" height="72" fill="#fff"/>
                    <rect x="405" y="268" width="4" height="72" fill="#F2A93B"/>
                </svg>
                @foreach ($lanes as [$path, $colour, $duration, $delay])
                    <div class="ef-runner absolute left-0 top-0 h-3.5 w-3.5 rounded-full" aria-hidden="true"
                         style="background: {{ $colour }}; box-shadow: 0 0 0 4px {{ $colour }}4d; offset-path: path('{{ $path }}'); offset-rotate: 0deg; animation-duration: {{ $duration }}; animation-delay: {{ $delay }}; transform: translateZ(2px)"></div>
                @endforeach
            </div>

            {{-- Floating chips --}}
            <div class="absolute -left-9 -top-10 [transform-style:preserve-3d]" style="transform: translateZ(90px)">
                <div class="flex flex-col gap-0.5 rounded-2xl bg-white px-4 py-3 shadow-[0_20px_40px_-24px_rgb(18_26_51/.45)] ring-1 ring-mist">
                    <span class="text-[13px] text-muted">{{ __('Half marathon 21.1K') }}</span>
                    <span class="text-[15px] font-bold">{{ __('9 places left') }}</span>
                </div>
            </div>
            <div class="absolute left-[30px] top-[290px] [transform-style:preserve-3d]" style="transform: translateZ(70px)">
                <div class="flex items-center gap-2.5 rounded-full bg-ink py-2.5 pe-4 ps-2.5 text-sm font-semibold text-white shadow-[0_20px_40px_-20px_rgb(18_26_51/.6)]">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-heat"><svg aria-hidden="true" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#121A33" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg></span>
                    {{ __('₹1,499 paid by UPI') }}
                </div>
            </div>

            {{-- Floating pass --}}
            <div class="absolute left-[340px] -top-[150px] [transform-style:preserve-3d]" style="transform: translateZ(150px) rotateY(-16deg) rotateX(10deg) rotateZ(4deg)">
                <div class="ef-float" data-motion>
                    <div class="w-[236px] overflow-hidden rounded-[22px] bg-white shadow-[0_50px_80px_-30px_rgb(18_26_51/.5),0_0_0_1px_rgb(18_26_51/.06)]">
                        <div class="flex flex-col gap-1 bg-track px-[18px] py-4 text-white">
                            <span class="text-xs opacity-80">{{ __('Race pass') }}</span>
                            <span class="ri-headline text-[17px] leading-tight">Bengaluru City Marathon 2027</span>
                        </div>
                        <div class="flex flex-col gap-3.5 px-[18px] pb-[18px] pt-4">
                            <div class="flex justify-between gap-2">
                                <span class="flex flex-col gap-0.5"><span class="text-xs text-muted">{{ __('Runner') }}</span><span class="text-[15px] font-bold">Ananya Rao</span></span>
                                <span class="flex flex-col gap-0.5 text-right"><span class="text-xs text-muted">{{ __('Distance') }}</span><span class="text-[15px] font-bold">21.1K</span></span>
                            </div>
                            <img src="{{ $qr }}" alt="{{ __('Sample QR code') }}" width="140" height="140" class="self-center rounded-xl p-1.5 ring-1 ring-mist">
                            <div class="flex items-center justify-between border-t border-dashed border-mist pt-3">
                                <span class="text-xs font-semibold tracking-[.02em] text-muted">EF-7K2Q9M4XB3TA</span>
                                <span class="text-xs font-bold text-track-deep">{{ __('Sat 26 Dec') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
