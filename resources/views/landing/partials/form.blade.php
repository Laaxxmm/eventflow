{{-- Registration form generated from fields + distances + age_bands. $form: App\Landing\RegistrationForm --}}
@php($control = 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full')

<form method="POST" action="{{ route('registrations.store', [$config->org, $config->slug]) }}" class="mt-6 rounded-lg bg-white p-4 sm:p-8 shadow-sm space-y-6">
    @csrf

    @if ($errors->any())
        <div class="rounded-md bg-red-50 p-4 text-sm text-red-700" role="alert">
            {{ __('Please fix the highlighted fields.') }}
        </div>
    @endif

    <fieldset>
        <legend class="block font-medium text-sm text-gray-700">{{ __('Distance') }}</legend>
        <div class="mt-2 grid gap-3 sm:grid-cols-2">
            @foreach ($config->distances as $distance)
                @php($full = ($remaining[$distance->code] ?? null) === 0)
                <label class="flex items-center gap-3 rounded-md border border-gray-300 p-3 has-[:checked]:border-[var(--brand-secondary)] has-[:checked]:ring-1 has-[:checked]:ring-[var(--brand-secondary)] {{ $full ? 'opacity-50' : 'cursor-pointer' }}">
                    <input type="radio" name="distance" value="{{ $distance->code }}" class="text-indigo-600 focus:ring-indigo-500" @checked(old('distance') === $distance->code) @disabled($full) required>
                    <span class="flex-1">
                        <span class="block font-semibold">{{ $distance->label }}</span>
                        <span class="block text-sm text-gray-600">₹{{ number_format($distance->price_inr) }}@if ($full) · {{ __('Full') }}@endif</span>
                    </span>
                </label>
            @endforeach
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('distance')" />
    </fieldset>

    <div class="grid gap-6 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <x-input-label for="participant_name" :value="__('Full name')" />
            <x-text-input id="participant_name" name="participant_name" type="text" class="mt-1 block w-full" :value="old('participant_name')" required autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('participant_name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required autocomplete="email" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('Mobile number')" />
            <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone')" required autocomplete="tel" inputmode="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        @if ($form->asksDob())
            <div>
                <x-input-label for="dob" :value="__('Date of birth').($form->dobRequired() ? '' : ' '.__('(optional)'))" />
                <x-text-input id="dob" name="dob" type="date" class="mt-1 block w-full" :value="old('dob')" :required="$form->dobRequired()" autocomplete="bday" max="{{ now()->toDateString() }}" />
                @if ($config->ageBands)
                    <p class="mt-1 text-xs text-gray-500">{{ __('Your category is set by your age on race day (:range).', ['range' => $form->ageRange()]) }}</p>
                @endif
                <x-input-error class="mt-2" :messages="$errors->get('dob')" />
            </div>
        @endif

        @foreach ($form->fields() as $field)
            @php($name = "answers[{$field->key}]")
            @php($id = "answers_{$field->key}")
            @php($old = old("answers.{$field->key}"))
            <div @class(['sm:col-span-2' => in_array($field->type, ['textarea', 'checkbox', 'radio'], true)])>
                @if ($field->type === 'checkbox' && $field->options === [])
                    <label for="{{ $id }}" class="inline-flex items-center gap-2">
                        <input type="hidden" name="{{ $name }}" value="0">
                        <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked($old) @required($field->required)>
                        <span class="text-sm text-gray-700">{{ $field->label }}</span>
                    </label>
                @elseif (in_array($field->type, ['radio', 'checkbox'], true))
                    <fieldset>
                        <legend class="block font-medium text-sm text-gray-700">{{ $field->label }}@unless ($field->required) {{ __('(optional)') }}@endunless</legend>
                        <div class="mt-2 flex flex-wrap gap-4">
                            @foreach ($field->options as $option)
                                <label class="inline-flex items-center gap-2 text-sm">
                                    @if ($field->type === 'radio')
                                        <input type="radio" name="{{ $name }}" value="{{ $option }}" class="text-indigo-600 focus:ring-indigo-500" @checked($old === $option) @required($field->required)>
                                    @else
                                        <input type="checkbox" name="{{ $name }}[]" value="{{ $option }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(in_array($option, (array) $old, true))>
                                    @endif
                                    {{ $option }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @else
                    <x-input-label :for="$id" :value="$field->label.($field->required ? '' : ' '.__('(optional)'))" />
                    @if ($field->type === 'select')
                        <select id="{{ $id }}" name="{{ $name }}" class="{{ $control }}" @required($field->required)>
                            <option value="">{{ __('Choose…') }}</option>
                            @foreach ($field->options as $option)
                                <option value="{{ $option }}" @selected($old === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    @elseif ($field->type === 'textarea')
                        <textarea id="{{ $id }}" name="{{ $name }}" rows="3" class="{{ $control }}" @required($field->required)>{{ $old }}</textarea>
                    @else
                        <x-text-input :id="$id" :name="$name" :type="$field->type" class="mt-1 block w-full" :value="$old" :required="$field->required" />
                    @endif
                @endif
                <x-input-error class="mt-2" :messages="collect([$errors->get('answers.'.$field->key), $errors->get('answers.'.$field->key.'.*')])->flatten()->all()" />
            </div>
        @endforeach
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
        <button type="submit" class="inline-flex justify-center rounded-full px-6 py-3 font-semibold bg-[var(--brand-primary)] text-[var(--brand-on-primary)] hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--brand-primary)]">
            {{ __('Continue to payment') }}
        </button>
        <p class="text-sm text-gray-600">{{ __('Your place is held for :minutes minutes while you pay.', ['minutes' => \App\Landing\Capacity::PENDING_HOLD_MINUTES]) }}</p>
    </div>
</form>
