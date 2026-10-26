{{--
    Mobile register bar (every template). A slim dark bar, price on the left and one Heat button,
    that slides in only once the hero's own Register button has scrolled away, and steps aside
    again over the registration form, so there are never two Register buttons on screen.
    Without JavaScript it simply stays visible.
--}}
@if ($page->open)
    <div data-sticky-register class="ri-sticky-register fixed inset-x-0 bottom-0 z-40 px-3 sm:hidden" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))">
        <div class="flex items-center gap-3 bg-ink py-2 pe-2 ps-4 text-paper shadow-[0_-10px_40px_-12px_rgb(0_0_0/0.55)] ring-1 ring-paper/10">
            <div class="min-w-0 flex-1 leading-tight">
                <p class="truncate text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-paper/55">{{ $page->date->format('j M') }} · {{ __('Entries from') }}</p>
                <p class="font-display text-2xl font-bold">{{ $page->fromPrice() }}</p>
            </div>
            <a href="{{ $page->registerUrl() }}" class="inline-flex items-center gap-2 bg-heat px-5 py-3 font-display text-lg font-bold uppercase tracking-[0.06em] text-on-heat">{{ __('Register') }} <span aria-hidden="true">→</span></a>
        </div>
    </div>
    <style>
        .js .ri-sticky-register { transition: transform .35s cubic-bezier(.2, .8, .2, 1), opacity .25s; }
        .js .ri-sticky-register:not(.is-on) { transform: translateY(115%); opacity: 0; pointer-events: none; }
    </style>
    <script>
        (() => {
            const bar = document.querySelector('[data-sticky-register]');
            if (!bar || !('IntersectionObserver' in window)) { bar?.classList.add('is-on'); return; }
            const watched = [...document.querySelectorAll('[data-hero-cta], #register')];
            const visible = new Set();
            const update = () => bar.classList.toggle('is-on', visible.size === 0 && window.scrollY > window.innerHeight * 0.35);
            const io = new IntersectionObserver((entries) => {
                entries.forEach((e) => e.isIntersecting ? visible.add(e.target) : visible.delete(e.target));
                update();
            });
            watched.forEach((el) => io.observe(el));
            window.addEventListener('scroll', update, { passive: true });
            update();
        })();
    </script>
@endif
