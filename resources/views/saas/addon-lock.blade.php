{{-- Soft-lock banner for a per-event add-on. $event, $addonKey. Renders nothing when unlocked. --}}
@php
    $addon = \App\Saas\Plans::eventAddons()[$addonKey];
    $unlocked = \App\Saas\EventAddons::unlocked($event, \App\Enums\Module::from($addon['module']));
@endphp
@unless ($unlocked)
    <div class="rounded-2xl bg-amber-50 p-5 text-sm text-amber-950 ring-1 ring-amber-200" role="status" data-addon-lock="{{ $addonKey }}">
        <p class="font-semibold">
            @if (session('addon_locked') === $addonKey)
                {{ __('That needs the :addon add-on for this event.', ['addon' => __($addon['name'])]) }}
            @else
                {{ __(':addon is locked for this event.', ['addon' => __($addon['name'])]) }}
            @endif
        </p>
        <p class="mt-1">{{ __(':what Add it to this event for :price, or move to Club Season, which includes it for every event.', ['what' => __($addon['description']), 'price' => \App\Saas\Plans::inr($addon['price_inr'])]) }}</p>
        <div class="mt-3 flex flex-wrap items-center gap-4">
            <a href="{{ \App\Saas\EventAddons::requestUrl($event, $addonKey) }}" class="inline-flex items-center rounded-lg bg-[#0b1020] px-4 py-2 font-semibold text-white hover:bg-[#1f2a4a]">{{ __('Unlock for :price', ['price' => \App\Saas\Plans::inr($addon['price_inr'])]) }}</a>
            <a href="{{ route('account.plan') }}" class="font-semibold underline">{{ __('Upgrade plan') }}</a>
        </div>
        <p class="mt-2 text-xs text-amber-900/70">{{ __('Online checkout is rolling out; until then your request goes to Indefine and the add-on is switched on for you.') }}</p>
    </div>
@endunless
