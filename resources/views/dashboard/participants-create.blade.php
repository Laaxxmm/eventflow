{{--
    Registrations → Add participant (Session P1). $events, $distances [event id => [{code,label,price_inr}]], $selected.
    Same registration as an online entry; payment Paid (offline) / Unpaid / Complimentary.
--}}
@php
    $control = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $oldEvent = (int) old('event', $selected->id);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Add participant') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('dashboard.participants.store') }}" class="space-y-6 bg-white p-4 shadow sm:rounded-lg sm:p-8"
                  x-data="{ event: @js((string) $oldEvent), payment: @js(old('payment', 'paid')) }"
                  data-manual-registration>
                @csrf
                <header>
                    <p class="text-sm text-gray-600">{{ __('For entries taken by phone, at the desk, or free places. They appear in Participants with the same paid / unpaid filters, and paid or complimentary entries get their pass by email.') }}</p>
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
                        <p class="mt-1 text-xs text-gray-500">{{ __('The pass is emailed here.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>
                    <div>
                        <x-input-label for="phone" :value="__('Mobile number')" />
                        <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone')" maxlength="20" inputmode="tel" autocomplete="off" />
                        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    </div>
                </div>

                <fieldset>
                    <legend class="text-sm font-medium text-gray-700">{{ __('Payment') }}</legend>
                    <div class="mt-2 grid gap-2 sm:grid-cols-3">
                        @foreach ([
                            'paid' => [__('Paid'), __('Paid outside EventFlow (cash, UPI, bank).')],
                            'unpaid' => [__('Unpaid'), __('Owes the entry fee. Can get payment reminders.')],
                            'complimentary' => [__('Complimentary'), __('A free place: ₹0, pass issued.')],
                        ] as $value => [$label, $hint])
                            <label class="cursor-pointer rounded-lg p-3 ring-1 ring-gray-200 has-[:checked]:bg-gray-900 has-[:checked]:text-white has-[:checked]:ring-gray-900">
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
                        <p class="mt-1 text-xs text-gray-500">{{ __('Blank = the distance price.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('amount_inr')" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="notes" :value="__('Notes (only you see these)')" />
                        <textarea id="notes" name="notes" rows="2" maxlength="2000" class="{{ $control }}">{{ old('notes') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('dashboard.participants') }}" class="text-center text-sm text-gray-600 underline hover:text-gray-900">{{ __('Cancel') }}</a>
                    <x-primary-button class="w-full justify-center py-3 sm:w-auto">{{ __('Add participant') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
