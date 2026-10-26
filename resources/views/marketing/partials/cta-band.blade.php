{{-- Final CTA band: Heat "Start free trial", ghost "Talk to Indefine". --}}
<section class="border-t-8 border-heat bg-ink text-paper">
    <div class="mx-auto flex max-w-7xl flex-col gap-10 px-4 py-20 sm:px-6 lg:flex-row lg:items-end lg:justify-between lg:px-8">
        <div>
            <h2 class="ri-headline text-7xl sm:text-9xl">{{ __('Your race,') }}<br><span class="text-heat">{{ __('live today.') }}</span></h2>
            <p class="mt-6 max-w-md text-lg text-paper/75">{{ __(':days-day free trial. No card, no call, no ticket percentage.', ['days' => config('plans.trial_days')]) }}</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            <x-ri.button variant="heat" size="lg" :href="route('register')">{{ __('Start free trial') }}</x-ri.button>
            <x-ri.button variant="ghost-light" size="lg" href="mailto:{{ config('plans.brand.contact_email') }}">{{ __('Talk to Indefine') }}</x-ri.button>
        </div>
    </div>
</section>
