{{--
    Registration form generated from fields + distances + age_bands. $form: App\Landing\RegistrationForm
    Daylight Track (docs/design/daylight-track/Review.dc.html), phone first: one white card, distance
    cards with the price on the right, 16px inputs (no iOS zoom), sentence-case labels, and a pay bar
    that sticks to the bottom of the screen while the form is in view.
--}}
@php
    $preview = $preview ?? false;
    $control = 'mt-1.5 block h-12 w-full rounded-[14px] border-0 bg-white px-4 text-base text-ink ring-1 ring-inset ring-[#C5CBDB] placeholder:text-[#8A92AB] transition focus:ring-2 focus:ring-track';
    $label = 'block text-sm font-semibold text-ink';
    $optional = fn (bool $required) => $required ? '' : ' · '.__('optional');
    $step = fn (string $n, string $title) => '<p class="ri-headline text-[22px]">'.e($title).'</p>';
    $prices = collect($config->distances)->mapWithKeys(fn ($d) => [$d->code => \App\View\LandingPage::inr($d->price_inr)]);
    $custom = $form->fields();
@endphp
<form method="POST" action="{{ $preview ? '#' : route('registrations.store', [$config->org, $config->slug]) }}" @if ($preview) onsubmit="return false" @endif
      class="mt-6 space-y-8 rounded-[24px] bg-white p-[18px] pb-0 text-left text-ink sm:p-8 sm:pb-0" data-registration-form
      x-data="{ prices: @js($prices), picked: @js(old('distance')) }">
    @csrf

    @if ($errors->any())
        <div class="rounded-[14px] bg-[#FBE4E4] p-4 text-sm font-medium text-[#6E1414]" role="alert">
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
                    'group relative flex items-center gap-3.5 rounded-[20px] p-4 ring-2 ring-inset transition duration-200 ease-track',
                    'bg-paper ring-transparent has-[:checked]:bg-lilac has-[:checked]:ring-track has-[:focus-visible]:outline has-[:focus-visible]:outline-[3px] has-[:focus-visible]:outline-[#F2A93B]',
                    'cursor-pointer hover:ring-mist' => ! $full,
                    'opacity-55' => $full,
                ])>
                    <input type="radio" name="distance" value="{{ $distance->code }}" x-model="picked" class="peer sr-only" @checked(old('distance') === $distance->code) @disabled($full) required>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-[13px] font-bold text-track-deep group-has-[:checked]:bg-track group-has-[:checked]:text-on-track" aria-hidden="true">{{ \Illuminate\Support\Str::limit(\App\View\LandingPage::numeral($distance), 5, '') }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-bold leading-tight">{{ $distance->label }}</span>
                        <span @class(['mt-0.5 block text-[13px]', 'font-semibold text-warning' => ! $full && $left !== null && $left <= 10, 'text-muted' => $full || $left === null || $left > 10])>
                            @if ($full)
                                {{ __('Full') }}
                            @elseif ($left !== null && $left <= 50)
                                {{ trans_choice(':count place left|:count places left', $left, ['count' => number_format($left)]) }}
                            @else
                                {{ __('Places available') }}
                            @endif
                        </span>
                    </span>
                    <span class="ri-headline text-xl">₹{{ number_format($distance->price_inr) }}</span>
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
                        <p class="mt-2 rounded-[12px] bg-lilac px-3 py-2.5 text-[13px] text-track-deep">{{ __('Your category is set by your age on race day (:range).', ['range' => $form->ageRange()]) }}</p>
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
                                <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="1" class="mt-0.5 h-5 w-5 rounded-[6px] border-[#C5CBDB] text-track focus:ring-track" @checked($old) @required($field->required)>
                                <span class="text-sm text-ink">{{ $field->label }}</span>
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
                                            <span class="inline-flex h-11 items-center rounded-full px-4 text-sm font-semibold ring-1 ring-inset ring-[#C5CBDB] transition peer-checked:bg-lilac peer-checked:text-track-deep peer-checked:ring-2 peer-checked:ring-track peer-focus-visible:outline peer-focus-visible:outline-[3px] peer-focus-visible:outline-[#F2A93B]">{{ $option }}</span>
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

    {{-- Pay bar: sticks to the bottom of the screen while the form is on it. --}}
    <div class="sticky bottom-0 z-10 sm:static -mx-[18px] space-y-2 rounded-b-[24px] border-t border-mist bg-white px-4 pb-4 pt-3.5 sm:-mx-8 sm:px-8" style="padding-bottom: max(1rem, env(safe-area-inset-bottom))">
        <div class="flex items-center justify-between gap-3">
            <span class="flex flex-col">
                <span class="text-[13px] text-muted">{{ __('To pay') }}</span>
                <span class="ri-headline text-2xl" x-text="prices[picked] ?? @js(__('from :price', ['price' => \App\View\LandingPage::inr(min(array_map(fn ($d) => $d->price_inr, $config->distances)))]))"></span>
            </span>
            <button type="submit" class="ri-glow inline-flex min-h-[52px] items-center justify-center rounded-full bg-track px-[22px] text-[15px] font-bold text-on-track transition duration-200 ease-track hover:brightness-95">{{ __('Continue to payment') }}</button>
        </div>
        <p class="flex items-center gap-2 text-xs text-muted">
            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 2a4 4 0 0 0-4 4v2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-1V6a4 4 0 0 0-4-4Zm2 6V6a2 2 0 1 0-4 0v2h4Z" clip-rule="evenodd"/></svg>
            {{ __('Your place is held for :minutes minutes while you pay.', ['minutes' => \App\Landing\Capacity::PENDING_HOLD_MINUTES]) }}
        </p>
    </div>
</form>
