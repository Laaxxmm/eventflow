{{-- Final CTA: one lilac card, one navy "Start your free trial". Nothing else competes with it. --}}
<section id="trial" class="mx-auto max-w-[1280px] px-4 pb-24 sm:px-10" data-band="cta">
    <div class="relative flex flex-wrap items-center justify-between gap-8 overflow-hidden rounded-[28px] bg-lilac px-6 py-12 sm:rounded-[36px] sm:px-14 sm:py-[72px]">
        <svg aria-hidden="true" width="620" height="620" viewBox="0 0 620 620" class="pointer-events-none absolute -right-[180px] -top-[140px] text-track">
            @foreach ([300, 260, 220, 180] as $r)
                <circle cx="310" cy="310" r="{{ $r }}" fill="none" stroke="currentColor" stroke-opacity=".14" stroke-width="2"/>
            @endforeach
        </svg>
        <div class="relative flex max-w-[36rem] flex-col gap-3.5">
            <h2 class="ri-headline text-[clamp(36px,4vw,60px)]">{{ __('Your race, live today.') }}</h2>
            <p class="text-[17px] leading-relaxed text-[#3B4463]">{{ __('A :days-day free trial. Publish one race and take up to :n test entries before you pay anything.', ['days' => config('plans.trial_days'), 'n' => \App\Saas\Trial::registrationLimit()]) }}</p>
        </div>
        <a href="{{ route('register') }}" class="relative inline-flex min-h-[60px] items-center rounded-full bg-ink px-8 text-[17px] font-semibold text-white hover:bg-ink/90">{{ __('Start your free trial') }}</a>
    </div>
</section>
