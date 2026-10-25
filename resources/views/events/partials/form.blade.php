{{-- Event setup (port of the prototype's create-event screen). $input: App\Landing\EventForm::toInput() --}}
@php
    $v = fn (string $key, $default = null) => old($key, data_get($input, $key, $default));
    $control = 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full';
    $rows = fn (string $list, int $min) => max($min, count((array) $v($list, [])) + 2);
    $standardLabels = [
        'age' => __('Age'), 'gender' => __('Gender'), 'city' => __('City'),
        'emergency_contact_name' => __('Emergency contact name'), 'emergency_contact_phone' => __('Emergency contact phone'),
        'tshirt_size' => __('T-shirt size'),
    ];
@endphp

<input type="hidden" name="sport" value="{{ $v('sport', 'generic') }}">

<section class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('Event details') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('The landing page link is created from the event name.') }} {{ __('Sport pack:') }} {{ __(ucfirst($v('sport', 'generic'))) }}</p>
    </header>

    <div class="mt-6 grid gap-6 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <x-input-label for="name" :value="__('Event name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="$v('name')" required maxlength="160" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>
        <div>
            <x-input-label for="date" :value="__('Date and start time (IST)')" />
            <x-text-input id="date" name="date" type="datetime-local" class="mt-1 block w-full" :value="$v('date')" required />
            <x-input-error class="mt-2" :messages="$errors->get('date')" />
        </div>
        <div>
            <x-input-label for="status" :value="__('Status')" />
            <select id="status" name="status" class="{{ $control }}">
                @foreach (\App\Landing\EventStatus::ALL as $status)
                    <option value="{{ $status }}" @selected($v('status') === $status)>{{ __(ucfirst($status)) }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="venue" :value="__('Venue / start location')" />
            <x-text-input id="venue" name="venue" type="text" class="mt-1 block w-full" :value="$v('venue')" required maxlength="255" />
            <x-input-error class="mt-2" :messages="$errors->get('venue')" />
        </div>
    </div>
</section>

<section class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('Landing page design') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Brand colours theme the whole landing page.') }}</p>
    </header>

    <div class="mt-6 grid gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="branding_primary" :value="__('Primary colour')" />
            <x-text-input id="branding_primary" name="branding[primary]" type="color" class="mt-1 block h-10 w-24" :value="$v('branding.primary')" required />
            <x-input-error class="mt-2" :messages="$errors->get('branding.primary')" />
        </div>
        <div>
            <x-input-label for="branding_secondary" :value="__('Secondary colour')" />
            <x-text-input id="branding_secondary" name="branding[secondary]" type="color" class="mt-1 block h-10 w-24" :value="$v('branding.secondary')" required />
            <x-input-error class="mt-2" :messages="$errors->get('branding.secondary')" />
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="hero_title" :value="__('Headline (optional)')" />
            <x-text-input id="hero_title" name="hero[title]" type="text" class="mt-1 block w-full" :value="$v('hero.title')" maxlength="160" />
            <x-input-error class="mt-2" :messages="$errors->get('hero.title')" />
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="hero_subtitle" :value="__('Short description (optional)')" />
            <x-text-input id="hero_subtitle" name="hero[subtitle]" type="text" class="mt-1 block w-full" :value="$v('hero.subtitle')" maxlength="255" />
            <x-input-error class="mt-2" :messages="$errors->get('hero.subtitle')" />
        </div>
        <div>
            <x-input-label for="branding_logo" :value="__('Logo path or URL (optional)')" />
            <x-text-input id="branding_logo" name="branding[logo]" type="text" class="mt-1 block w-full" :value="$v('branding.logo')" />
            <x-input-error class="mt-2" :messages="$errors->get('branding.logo')" />
        </div>
        <div>
            <x-input-label for="hero_image" :value="__('Hero image path or URL (optional)')" />
            <x-text-input id="hero_image" name="hero[image]" type="text" class="mt-1 block w-full" :value="$v('hero.image')" />
            <x-input-error class="mt-2" :messages="$errors->get('hero.image')" />
        </div>
    </div>
</section>

<section class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('Race categories') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Each distance has its own price and place limit. Leave a row blank to skip it.') }}</p>
    </header>
    <x-input-error class="mt-4" :messages="$errors->get('distances')" />

    <div class="mt-6 space-y-4">
        @for ($i = 0; $i < $rows('distances', \App\Landing\EventForm::ROWS['distances']); $i++)
            <div class="grid gap-3 grid-cols-2 sm:grid-cols-4">
                <div>
                    <x-input-label for="distances_{{ $i }}_code" :value="__('Code')" />
                    <x-text-input id="distances_{{ $i }}_code" name="distances[{{ $i }}][code]" type="text" class="mt-1 block w-full" :value="$v('distances.'.$i.'.code')" maxlength="20" />
                </div>
                <div>
                    <x-input-label for="distances_{{ $i }}_label" :value="__('Name')" />
                    <x-text-input id="distances_{{ $i }}_label" name="distances[{{ $i }}][label]" type="text" class="mt-1 block w-full" :value="$v('distances.'.$i.'.label')" maxlength="80" />
                </div>
                <div>
                    <x-input-label for="distances_{{ $i }}_price" :value="__('Price (₹)')" />
                    <x-text-input id="distances_{{ $i }}_price" name="distances[{{ $i }}][price_inr]" type="number" min="0" class="mt-1 block w-full" :value="$v('distances.'.$i.'.price_inr')" />
                </div>
                <div>
                    <x-input-label for="distances_{{ $i }}_capacity" :value="__('Places (blank = no limit)')" />
                    <x-text-input id="distances_{{ $i }}_capacity" name="distances[{{ $i }}][capacity]" type="number" min="1" class="mt-1 block w-full" :value="$v('distances.'.$i.'.capacity')" />
                </div>
                <x-input-error class="col-span-full" :messages="collect($errors->get('distances.'.$i.'.*'))->flatten()->all()" />
            </div>
        @endfor
    </div>
</section>

<section class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('Age categories') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Registrants give their date of birth; their age on race day must fall in one category. Leave all blank for no age limit.') }}</p>
    </header>
    <x-input-error class="mt-4" :messages="$errors->get('age_bands')" />

    <div class="mt-6 space-y-4">
        @for ($i = 0; $i < $rows('age_bands', \App\Landing\EventForm::ROWS['age_bands']); $i++)
            <div class="grid gap-3 grid-cols-3">
                <div>
                    <x-input-label for="age_bands_{{ $i }}_label" :value="__('Category')" />
                    <x-text-input id="age_bands_{{ $i }}_label" name="age_bands[{{ $i }}][label]" type="text" class="mt-1 block w-full" :value="$v('age_bands.'.$i.'.label')" maxlength="40" />
                </div>
                <div>
                    <x-input-label for="age_bands_{{ $i }}_min" :value="__('Min age')" />
                    <x-text-input id="age_bands_{{ $i }}_min" name="age_bands[{{ $i }}][min]" type="number" min="0" max="120" class="mt-1 block w-full" :value="$v('age_bands.'.$i.'.min')" />
                </div>
                <div>
                    <x-input-label for="age_bands_{{ $i }}_max" :value="__('Max age')" />
                    <x-text-input id="age_bands_{{ $i }}_max" name="age_bands[{{ $i }}][max]" type="number" min="0" max="120" class="mt-1 block w-full" :value="$v('age_bands.'.$i.'.max')" />
                </div>
                <x-input-error class="col-span-full" :messages="collect($errors->get('age_bands.'.$i.'.*'))->flatten()->all()" />
            </div>
        @endfor
    </div>
</section>

<section class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('Registration form') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Name, email, mobile number and distance are always asked. Choose what else to collect.') }}</p>
    </header>

    <div class="mt-6 grid gap-3 sm:grid-cols-2">
        @foreach (\App\Landing\EventForm::STANDARD_FIELDS as $key => $definition)
            <label for="show_{{ $key }}" class="flex items-center gap-3 rounded-md border border-gray-200 p-3">
                <input type="hidden" name="show[{{ $key }}]" value="0">
                <input id="show_{{ $key }}" type="checkbox" name="show[{{ $key }}]" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked($v('show.'.$key))>
                <span class="text-sm text-gray-700">{{ $standardLabels[$key] }}</span>
            </label>
        @endforeach
    </div>

    <h3 class="mt-8 font-medium text-gray-900">{{ __('Custom questions') }}</h3>
    <p class="mt-1 text-sm text-gray-600">{{ __('For dropdown, radio and checkbox questions, list the choices separated by commas.') }}</p>
    <x-input-error class="mt-4" :messages="$errors->get('custom')" />

    <div class="mt-4 space-y-6">
        @for ($i = 0; $i < $rows('custom', \App\Landing\EventForm::ROWS['custom']); $i++)
            <div class="grid gap-3 sm:grid-cols-4 rounded-md border border-gray-200 p-3">
                <input type="hidden" name="custom[{{ $i }}][key]" value="{{ $v('custom.'.$i.'.key') }}">
                <div class="sm:col-span-2">
                    <x-input-label for="custom_{{ $i }}_label" :value="__('Question')" />
                    <x-text-input id="custom_{{ $i }}_label" name="custom[{{ $i }}][label]" type="text" class="mt-1 block w-full" :value="$v('custom.'.$i.'.label')" maxlength="80" />
                </div>
                <div>
                    <x-input-label for="custom_{{ $i }}_type" :value="__('Answer type')" />
                    <select id="custom_{{ $i }}_type" name="custom[{{ $i }}][type]" class="{{ $control }}">
                        @foreach (\App\Landing\EventForm::FIELD_TYPES as $type)
                            <option value="{{ $type }}" @selected($v('custom.'.$i.'.type', 'text') === $type)>{{ __(ucfirst($type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end pb-2">
                    <label for="custom_{{ $i }}_required" class="inline-flex items-center gap-2">
                        <input type="hidden" name="custom[{{ $i }}][required]" value="0">
                        <input id="custom_{{ $i }}_required" type="checkbox" name="custom[{{ $i }}][required]" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked($v('custom.'.$i.'.required'))>
                        <span class="text-sm text-gray-700">{{ __('Required') }}</span>
                    </label>
                </div>
                <div class="sm:col-span-4">
                    <x-input-label for="custom_{{ $i }}_options" :value="__('Choices')" />
                    <x-text-input id="custom_{{ $i }}_options" name="custom[{{ $i }}][options]" type="text" class="mt-1 block w-full" :value="$v('custom.'.$i.'.options')" />
                </div>
                <x-input-error class="sm:col-span-4" :messages="collect($errors->get('custom.'.$i.'.*'))->flatten()->all()" />
            </div>
        @endfor
    </div>
</section>

<section class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('FAQ') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Shown at the bottom of the landing page.') }}</p>
    </header>

    <div class="mt-6 space-y-4">
        @for ($i = 0; $i < $rows('faq', \App\Landing\EventForm::ROWS['faq']); $i++)
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <x-input-label for="faq_{{ $i }}_q" :value="__('Question')" />
                    <x-text-input id="faq_{{ $i }}_q" name="faq[{{ $i }}][q]" type="text" class="mt-1 block w-full" :value="$v('faq.'.$i.'.q')" />
                </div>
                <div>
                    <x-input-label for="faq_{{ $i }}_a" :value="__('Answer')" />
                    <textarea id="faq_{{ $i }}_a" name="faq[{{ $i }}][a]" rows="2" class="{{ $control }}">{{ $v('faq.'.$i.'.a') }}</textarea>
                </div>
                <x-input-error class="sm:col-span-2" :messages="collect($errors->get('faq.'.$i.'.*'))->flatten()->all()" />
            </div>
        @endfor
    </div>
</section>

<section class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('Race sheets and certificates') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Used when these modules are on in your Season Pass.') }}</p>
    </header>

    <div class="mt-6 grid gap-6 sm:grid-cols-3">
        <div>
            <x-input-label for="race_sheet_heat_size" :value="__('Entries per heat')" />
            <x-text-input id="race_sheet_heat_size" name="race_sheet[heat_size]" type="number" min="2" max="64" list="heat_size_presets" class="mt-1 block w-full" :value="$v('race_sheet.heat_size')" />
            <datalist id="heat_size_presets">
                @foreach (config('eventflow.race_sheets.heat_size_presets') as $preset)
                    <option value="{{ $preset }}"></option>
                @endforeach
            </datalist>
            <p class="mt-1 text-xs text-gray-500">{{ __('6, 8 or 10, or any number from 2 to 64. Blank = mass start.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('race_sheet.heat_size')" />
        </div>
        <div>
            <x-input-label for="race_sheet_group_by" :value="__('Group heats by')" />
            <x-text-input id="race_sheet_group_by" name="race_sheet[group_by]" type="text" class="mt-1 block w-full" :value="$v('race_sheet.group_by')" placeholder="distance, age_band, gender" />
            <x-input-error class="mt-2" :messages="collect($errors->get('race_sheet.group_by*'))->flatten()->all()" />
        </div>
        <div>
            <x-input-label for="race_sheet_seeding" :value="__('Seeding')" />
            <select id="race_sheet_seeding" name="race_sheet[seeding]" class="{{ $control }}">
                <option value="registration_order" @selected($v('race_sheet.seeding') === 'registration_order')>{{ __('Registration order') }}</option>
                <option value="random" @selected($v('race_sheet.seeding') === 'random')>{{ __('Random') }}</option>
            </select>
        </div>
        <div>
            <x-input-label for="certificate_template" :value="__('Certificate template')" />
            <select id="certificate_template" name="certificate[template]" class="{{ $control }}">
                <option value="">{{ __('No certificate') }}</option>
                @foreach (config('eventflow.certificates.gallery') as $key => $template)
                    <option value="{{ $key }}" @selected($v('certificate.template') === $key)>{{ __($template['label']) }}</option>
                @endforeach
                <option value="upload" @selected($v('certificate.template') === 'upload')>{{ __('Uploaded A4 background') }}</option>
            </select>
            <x-input-error class="mt-2" :messages="collect($errors->get('certificate*'))->flatten()->all()" />
        </div>
        <div>
            <x-input-label for="certificate_orientation" :value="__('Orientation')" />
            <select id="certificate_orientation" name="certificate[orientation]" class="{{ $control }}">
                <option value="landscape" @selected($v('certificate.orientation') === 'landscape')>{{ __('Landscape') }}</option>
                <option value="portrait" @selected($v('certificate.orientation') === 'portrait')>{{ __('Portrait') }}</option>
            </select>
        </div>
        <p class="sm:col-span-3 text-sm text-gray-600">
            {{ __('Need a custom certificate layout? Contact Indefine:') }} {{ config('eventflow.certificates.custom_contact') ?: __('ask your account manager') }}
        </p>
    </div>
</section>
