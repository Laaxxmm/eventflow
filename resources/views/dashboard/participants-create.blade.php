{{--
    Registrations → Add participant (Session P1). $events, $distances [event id => [{code,label,price_inr}]], $selected.
    Same registration as an online entry; payment Paid (offline) / Unpaid / Complimentary.
--}}
@php
    $control = 'ri-input';
    $oldEvent = (int) old('event', $selected->id);
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('Add participant') }}</x-slot>
    <x-slot name="header">
        <div class="space-y-2">
            <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm text-muted"><a href="{{ route('dashboard.participants') }}" class="hover:text-ink">{{ __('Participants') }}</a> / {{ __('Add') }}</nav>
            <h2>{{ __('Add participant') }}</h2>
        </div>
    </x-slot>

    <div class="pb-12 pt-4">
        <div class="mx-auto max-w-[1180px] space-y-6 px-4 sm:px-6 lg:px-10">
            <form method="POST" action="{{ route('dashboard.participants.store') }}" class="max-w-3xl space-y-6 rounded-[28px] bg-white p-5 sm:p-8"
                  x-data="{ event: @js((string) $oldEvent), payment: @js(old('payment', 'paid')) }"
                  data-manual-registration>
                @csrf
                <header>
                    <p class="text-sm text-muted">{{ __('For entries taken by phone, at the desk, or free places. They appear in Participants with the same paid / unpaid filters, and paid or complimentary entries get their pass by email.') }}</p>
                </header>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <x-input-label for="event" :value="__('Event')" />
                        <select id="event" name="event" class="{{ $control }}" x-model="event" @change="$refs.distance.value = ''" required>
                            @foreach ($events as $option)
                                <option value="{{ $option->id }}" @selected($oldEvent === $option->id)>{{ $option->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('event')" />
                    </div>
                    <div>
                        <x-input-label for="distance" :value="__('Category / distance *')" />
                        <select id="distance" name="distance" class="{{ $control }}" x-ref="distance" required>
                            <option value="">{{ __('Choose…') }}</option>
                            {{-- One group per event; with JS only the chosen event's group is shown. --}}
                            @foreach ($events as $option)
                                <optgroup label="{{ $option->name }}" :hidden="event !== '{{ $option->id }}'" :disabled="event !== '{{ $option->id }}'">
                                    @foreach ($distances[$option->id] ?? [] as $d)
                                        <option value="{{ $d['code'] }}" @selected($oldEvent === $option->id && old('distance') === $d['code'])>{{ $d['label'] }} · ₹{{ number_format($d['price_inr']) }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('distance')" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="participant_name" :value="__('Full name *')" />
                        <x-text-input id="participant_name" name="participant_name" type="text" class="mt-1 block w-full" :value="old('participant_name')" required maxlength="255" autocomplete="off" />
                        <x-input-error class="mt-2" :messages="$errors->get('participant_name')" />
                    </div>
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" maxlength="255" autocomplete="off" />
                        <p class="mt-1 text-xs text-muted">{{ __('The pass is emailed here.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>
                    <div>
                        <x-input-label for="phone" :value="__('Mobile number')" />
                        <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone')" maxlength="20" inputmode="tel" autocomplete="off" />
                        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    </div>
                </div>

                <fieldset>
                    <legend class="text-sm font-semibold text-ink">{{ __('Payment') }}</legend>
                    <div class="mt-2 grid gap-2 sm:grid-cols-3">
                        @foreach ([
                            'paid' => [__('Paid'), __('Paid outside EventFlow (cash, UPI, bank).')],
                            'unpaid' => [__('Unpaid'), __('Owes the entry fee. Can get payment reminders.')],
                            'complimentary' => [__('Complimentary'), __('A free place: ₹0, pass issued.')],
                        ] as $value => [$label, $hint])
                            <label class="cursor-pointer rounded-[18px] border-2 border-[#EEF0F6] p-3 has-[:checked]:border-track has-[:checked]:bg-lilac has-[:checked]:text-track-deep has-[:focus-visible]:outline has-[:focus-visible]:outline-[3px] has-[:focus-visible]:outline-[#F2A93B]">
                                <input type="radio" name="payment" value="{{ $value }}" x-model="payment" class="sr-only" @checked(old('payment', 'paid') === $value)>
                                <span class="block text-sm font-semibold">{{ $label }}</span>
                                <span class="mt-0.5 block text-xs opacity-75">{{ $hint }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('payment')" />
                </fieldset>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div x-show="payment !== 'complimentary'">
                        <x-input-label for="amount_inr" :value="__('Amount (₹, optional)')" />
                        <x-text-input id="amount_inr" name="amount_inr" type="number" min="0" step="1" class="mt-1 block w-full" :value="old('amount_inr')" />
                        <p class="mt-1 text-xs text-muted">{{ __('Blank = the distance price.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('amount_inr')" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="notes" :value="__('Notes (only you see these)')" />
                        <textarea id="notes" name="notes" rows="2" maxlength="2000" class="{{ $control }}">{{ old('notes') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-[#EEF0F6] pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('dashboard.participants') }}" class="text-center text-sm text-muted underline hover:text-ink">{{ __('Cancel') }}</a>
                    <x-primary-button class="w-full justify-center py-3 sm:w-auto">{{ __('Add participant') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
