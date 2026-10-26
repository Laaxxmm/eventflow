{{--
    Countdown block (Session P1). $page. Off unless the organiser switched it on in Edit event.
    A big Race Ink timer to registration close or the start; it flips to "Registration closed"
    and "We're live" by itself (server-rendered state, updated every second by the script), and
    is gone 12 hours after the start. Without JavaScript the numbers are as of page load.
--}}
@php
    $cd = $page->countdown();
@endphp
@if ($cd)
    @php
        $diff = max(0, now()->diffInSeconds($cd['ends'], false));
        $parts = ['d' => intdiv((int) $diff, 86400), 'h' => intdiv((int) $diff % 86400, 3600), 'm' => intdiv((int) $diff % 3600, 60), 's' => (int) $diff % 60];
        $units = ['d' => __('Days'), 'h' => __('Hours'), 'm' => __('Min'), 's' => __('Sec')];
    @endphp
    <section class="relative overflow-hidden bg-lilac text-track-deep" data-countdown data-state="{{ $cd['state'] }}" data-target="{{ $cd['target'] }}"
             data-close="{{ $cd['close']->toIso8601String() }}" data-start="{{ $cd['start']->toIso8601String() }}" data-live-until="{{ $cd['live_until']->toIso8601String() }}"
             aria-live="polite">
                <div class="relative mx-auto flex max-w-[1280px] flex-col gap-6 px-4 py-10 sm:px-6 sm:py-12 lg:flex-row lg:items-center lg:justify-between lg:px-10">
            <div data-countdown-counting @class(['hidden' => $cd['state'] !== 'counting'])>
                <p class="ri-headline text-2xl">{{ $cd['target'] === 'registration_close' ? __('Registration closes in') : __('Race starts in') }}</p>
                <p class="mt-2 text-sm text-[#3B4463]">{{ ($cd['target'] === 'registration_close' ? $cd['close'] : $cd['start'])->setTimezone(\App\Landing\ConfigCompiler::TIMEZONE)->format('D j M Y · g:i A') }} IST</p>
            </div>
            <dl data-countdown-counting @class(['grid grid-cols-4 gap-2 sm:gap-4', 'hidden' => $cd['state'] !== 'counting'])>
                @foreach ($units as $key => $label)
                    <div class="min-w-[4.25rem] rounded-[18px] bg-white px-2 py-3 text-center sm:min-w-[6.5rem] sm:px-4 sm:py-4">
                        <dd class="ri-headline text-4xl tabular-nums text-ink sm:text-6xl" data-unit="{{ $key }}">{{ str_pad((string) $parts[$key], 2, '0', STR_PAD_LEFT) }}</dd>
                        <dt class="mt-2 text-xs font-semibold text-muted">{{ $label }}</dt>
                    </div>
                @endforeach
            </dl>
            <div data-countdown-closed @class(['flex flex-wrap items-baseline gap-x-6 gap-y-2', 'hidden' => $cd['state'] !== 'closed'])>
                <p class="ri-headline text-4xl sm:text-6xl">{{ __('Registration closed') }}</p>
                <p class="text-sm text-[#3B4463]">{{ __('Race day :date. See you at the start line.', ['date' => $cd['start']->setTimezone(\App\Landing\ConfigCompiler::TIMEZONE)->format('j M')]) }}</p>
            </div>
            <div data-countdown-live @class(['flex items-center gap-4', 'hidden' => $cd['state'] !== 'live'])>
                <span class="relative flex h-4 w-4" aria-hidden="true"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-heat motion-reduce:animate-none opacity-75"></span><span class="relative inline-flex h-4 w-4 rounded-full bg-heat"></span></span>
                <p class="ri-headline text-4xl sm:text-6xl">{{ __('We’re live') }}</p>
            </div>
        </div>
    </section>
    <script>
        (() => {
            const el = document.currentScript.previousElementSibling;
            const t = (k) => Date.parse(el.dataset[k]);
            const pad = (n) => String(n).padStart(2, '0');
            const show = (state) => {
                el.dataset.state = state;
                el.querySelectorAll('[data-countdown-counting]').forEach((n) => n.classList.toggle('hidden', state !== 'counting'));
                el.querySelector('[data-countdown-closed]').classList.toggle('hidden', state !== 'closed');
                el.querySelector('[data-countdown-live]').classList.toggle('hidden', state !== 'live');
            };
            const tick = () => {
                const now = Date.now();
                if (now >= t('liveUntil')) { el.remove(); return; }
                if (now >= t('start')) { show('live'); return; }
                const ends = el.dataset.target === 'registration_close' ? t('close') : t('start');
                if (now >= ends) { show('closed'); return; }
                show('counting');
                let s = Math.floor((ends - now) / 1000);
                const parts = { d: Math.floor(s / 86400), h: Math.floor(s % 86400 / 3600), m: Math.floor(s % 3600 / 60), s: s % 60 };
                Object.entries(parts).forEach(([k, v]) => { el.querySelector(`[data-unit="${k}"]`).textContent = pad(v); });
                setTimeout(tick, 1000 - (now % 1000));
            };
            tick();
        })();
    </script>
@endif
