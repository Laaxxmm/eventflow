{{--
    Registration form generated from fields + distances + age_bands. $form: App\Landing\RegistrationForm
    Race Ink styling, phone first: numbered sections, distance cards with the price on the right,
    16px inputs (no iOS zoom), uppercase micro-labels and one full-width pay button.
--}}
@php
    $preview = $preview ?? false;
    $control = 'mt-2 block h-12 w-full rounded-none border-0 bg-paper px-4 text-base text-ink ring-1 ring-inset ring-ink/15 placeholder:text-ink/35 transition focus:bg-white focus:ring-2 focus:ring-ink';
    $label = 'block text-[0.7rem] font-semibold uppercase tracking-[0.16em] text-ink/60';
    $optional = fn (bool $required) => $required ? '' : ' · '.__('optional');
    $step = fn (string $n, string $title) => '<p class="flex items-baseline gap-3 font-display text-xl font-bold uppercase tracking-[0.04em]"><span class="text-heat">'.$n.'</span><span>'.e($title).'</span></p>';
    $custom = $form->fields();
@endphp
<form method="POST" action="{{ $preview ? '#' : route('registrations.store', [$config->org, $config->slug]) }}" @if ($preview) onsubmit="return false" @endif
      class="mt-6 space-y-10 bg-white p-5 text-left text-ink ring-1 ring-ink/10 shadow-[0_30px_60px_-30px_rgb(var(--ri-ink)/0.35)] sm:p-8" data-registration-form>
    @csrf

    @if ($errors->any())
        <div class="border-l-4 border-red-600 bg-red-50 p-4 text-sm font-medium text-red-800" role="alert">
            {{ __('Please fix the highlighted fields.') }}
        </div>
    @endif

    {{-- 01 Distance --}}
    <fieldset>
        <legend class="w-full">{!! $step('01', __('Distance')) !!}</legend>
        <div class="mt-4 grid gap-2.5 sm:grid-cols-2">
            @foreach ($config->distances as $distance)
                @php($full = ($remaining[$distance->code] ?? null) === 0)
                @php($left = $remaining[$distance->code] ?? null)
                <label @class([
                    'group relative flex items-center gap-4 p-4 ring-1 ring-inset ring-ink/15 transition',
                    'has-[:checked]:bg-ink has-[:checked]:text-paper has-[:checked]:ring-ink has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-heat',
                    'cursor-pointer hover:ring-ink/40' => ! $full,
                    'opacity-45' => $full,
                ])>
                    <input type="radio" name="distance" value="{{ $distance->code }}" class="peer sr-only" @checked(old('distance') === $distance->code) @disabled($full) required>
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full ring-2 ring-ink/30 group-has-[:checked]:ring-heat" aria-hidden="true">
                        <span class="h-2.5 w-2.5 rounded-full bg-heat opacity-0 transition group-has-[:checked]:opacity-100"></span>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-display text-lg font-bold uppercase leading-tight">{{ $distance->label }}</span>
                        <span class="mt-0.5 block text-xs text-ink/55 group-has-[:checked]:text-paper/65">
                            @if ($full)
                                {{ __('Full') }}
                            @elseif ($left !== null && $left <= 50)
                                {{ trans_choice(':count place left|:count places left', $left, ['count' => number_format($left)]) }}
                            @else
                                {{ __('Places available') }}
                            @endif
                        </span>
                    </span>
                    <span class="font-display text-xl font-extrabold">₹{{ number_format($distance->price_inr) }}</span>
                </label>
            @endforeach
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('distance')" />
    </fieldset>

    {{-- 02 You --}}
    <fieldset>
        <legend class="w-full">{!! $step('02', __('About you')) !!}</legend>
        <div class="mt-4 grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="participant_name" class="{{ $label }}">{{ __('Full name') }}</label>
                <input id="participant_name" name="participant_name" type="text" class="{{ $control }}" value="{{ old('participant_name') }}" required autocomplete="name" placeholder="{{ __('As on your ID') }}">
                <x-input-error class="mt-2" :messages="$errors->get('participant_name')" />
            </div>

            <div>
                <label for="email" class="{{ $label }}">{{ __('Email') }}</label>
                <input id="email" name="email" type="email" class="{{ $control }}" value="{{ old('email') }}" required autocomplete="email" inputmode="email" placeholder="{{ __('Your pass is sent here') }}">
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>

            <div>
                <label for="phone" class="{{ $label }}">{{ __('Mobile number') }}</label>
                <input id="phone" name="phone" type="tel" class="{{ $control }}" value="{{ old('phone') }}" required autocomplete="tel" inputmode="tel" placeholder="98450 12345">
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>

            @if ($form->asksDob())
                <div class="sm:col-span-2">
                    <label for="dob" class="{{ $label }}">{{ __('Date of birth') }}{{ $optional($form->dobRequired()) }}</label>
                    <input id="dob" name="dob" type="date" class="{{ $control }}" value="{{ old('dob') }}" @required($form->dobRequired()) autocomplete="bday" max="{{ now()->toDateString() }}">
                    @if ($config->ageBands)
                        <p class="mt-2 text-xs text-ink/55">{{ __('Your category is set by your age on race day (:range).', ['range' => $form->ageRange()]) }}</p>
                    @endif
                    <x-input-error class="mt-2" :messages="$errors->get('dob')" />
                </div>
            @endif
        </div>
    </fieldset>

    {{-- 03 Race details (the organiser's questions) --}}
    @if (count($custom))
        <fieldset>
            <legend class="w-full">{!! $step('03', __('Race details')) !!}</legend>
            <div class="mt-4 grid gap-5 sm:grid-cols-2">
                @foreach ($custom as $field)
                    @php($name = "answers[{$field->key}]")
                    @php($id = "answers_{$field->key}")
                    @php($old = old("answers.{$field->key}"))
                    <div @class(['sm:col-span-2' => in_array($field->type, ['textarea', 'checkbox', 'radio'], true)])>
                        @if ($field->type === 'checkbox' && $field->options === [])
                            <label for="{{ $id }}" class="flex items-start gap-3">
                                <input type="hidden" name="{{ $name }}" value="0">
                                <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="1" class="mt-0.5 h-5 w-5 rounded-none border-ink/30 text-heat focus:ring-heat" @checked($old) @required($field->required)>
                                <span class="text-sm text-ink/80">{{ $field->label }}</span>
                            </label>
                        @elseif (in_array($field->type, ['radio', 'checkbox'], true))
                            <fieldset>
                                <legend class="{{ $label }}">{{ $field->label }}{{ $optional($field->required) }}</legend>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($field->options as $option)
                                        <label class="cursor-pointer">
                                            @if ($field->type === 'radio')
                                                <input type="radio" name="{{ $name }}" value="{{ $option }}" class="peer sr-only" @checked($old === $option) @required($field->required)>
                                            @else
                                                <input type="checkbox" name="{{ $name }}[]" value="{{ $option }}" class="peer sr-only" @checked(in_array($option, (array) $old, true))>
                                            @endif
                                            <span class="inline-flex h-11 items-center px-4 text-sm font-medium ring-1 ring-inset ring-ink/15 transition peer-checked:bg-ink peer-checked:text-paper peer-checked:ring-ink peer-focus-visible:ring-2 peer-focus-visible:ring-heat">{{ $option }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @else
                            <label for="{{ $id }}" class="{{ $label }}">{{ $field->label }}{{ $optional($field->required) }}</label>
                            @if ($field->type === 'select')
                                <select id="{{ $id }}" name="{{ $name }}" class="{{ $control }} pe-10" @required($field->required)>
                                    <option value="">{{ __('Choose…') }}</option>
                                    @foreach ($field->options as $option)
                                        <option value="{{ $option }}" @selected($old === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            @elseif ($field->type === 'textarea')
                                <textarea id="{{ $id }}" name="{{ $name }}" rows="3" class="{{ str_replace('h-12', 'min-h-24 py-3', $control) }}" @required($field->required)>{{ $old }}</textarea>
                            @else
                                <input id="{{ $id }}" name="{{ $name }}" type="{{ $field->type }}" class="{{ $control }}" value="{{ $old }}" @required($field->required) @if ($field->type === 'tel') inputmode="tel" @endif>
                            @endif
                        @endif
                        <x-input-error class="mt-2" :messages="collect([$errors->get('answers.'.$field->key), $errors->get('answers.'.$field->key.'.*')])->flatten()->all()" />
                    </div>
                @endforeach
            </div>
        </fieldset>
    @endif

    <div class="space-y-3 border-t border-ink/10 pt-6">
        <button type="submit" class="inline-flex w-full items-center justify-between gap-3 bg-heat px-6 py-4 font-display text-xl font-bold uppercase tracking-[0.06em] text-on-heat transition hover:brightness-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-heat focus-visible:ring-offset-2">
            <span>{{ __('Continue to payment') }}</span>
            <span aria-hidden="true">→</span>
        </button>
        <p class="flex items-center gap-2 text-xs text-ink/55">
            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 2a4 4 0 0 0-4 4v2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-1V6a4 4 0 0 0-4-4Zm2 6V6a2 2 0 1 0-4 0v2h4Z" clip-rule="evenodd"/></svg>
            {{ __('Your place is held for :minutes minutes while you pay.', ['minutes' => \App\Landing\Capacity::PENDING_HOLD_MINUTES]) }}
        </p>
    </div>
</form>
