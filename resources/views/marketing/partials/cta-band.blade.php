{{-- Final CTA band: one Heat "Start free trial". Nothing else competes with it. --}}
<section class="border-t-8 border-heat bg-ink text-paper" data-band="cta">
    <div class="mx-auto flex max-w-7xl flex-col gap-10 px-4 py-24 sm:px-6 lg:flex-row lg:items-end lg:justify-between lg:px-8 lg:py-28">
        <div>
            <h2 class="ri-headline text-6xl sm:text-8xl">{{ __('Your race,') }} <span class="text-heat">{{ __('live today.') }}</span></h2>
            <p class="mt-6 max-w-md text-lg text-paper/70">{{ __(':days-day free trial. No card, no call, no ticket percentage.', ['days' => config('plans.trial_days')]) }}</p>
        </div>
        <x-ri.button variant="heat" size="lg" :href="route('register')">{{ __('Start free trial') }}</x-ri.button>
    </div>
</section>
