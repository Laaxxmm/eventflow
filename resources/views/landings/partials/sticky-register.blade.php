{{--
    Mobile register bar (every template): a floating navy card, days to go and the entry price on
    the left, one marigold Register button. It slides in only once the hero's own Register button
    has scrolled away, and steps aside again over the registration form, so there are never two
    Register buttons on screen. Without JavaScript it simply stays visible.
--}}
@if ($page->open)
    <div data-sticky-register class="ri-sticky-register fixed inset-x-0 bottom-0 z-40 px-3 sm:hidden" style="padding-bottom: max(1rem, env(safe-area-inset-bottom))">
        <div class="ri-float flex items-center justify-between gap-3 rounded-[22px] bg-ink py-3 pe-3 ps-[18px] text-white">
            <div class="min-w-0 leading-tight">
                <p class="truncate text-xs text-[#C9CDE0]">{{ trans_choice('{0} Race day today|{1} 1 day to go|[2,*] :count days to go', $page->daysToGo()) }}</p>
                <p class="text-[15px] font-bold">{{ __('From :price', ['price' => $page->fromPrice()]) }}</p>
            </div>
            <a href="{{ $page->registerUrl() }}" class="inline-flex min-h-[48px] items-center rounded-full bg-heat px-5 text-[15px] font-bold text-on-heat">{{ __('Register now') }}</a>
        </div>
    </div>
    <style>
        .js .ri-sticky-register { transition: transform .35s cubic-bezier(.2, .7, .2, 1), opacity .25s; }
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
