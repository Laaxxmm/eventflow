{{-- Create-event wizard (App\Landing\EventWizard), Daylight Track (docs/design/daylight-track/Wizard.dc.html):
     Basics → Sport pack → Page design → Logo, photo and colours → Review and publish.
     One form; Alpine shows one step at a time and checks each step's required fields before moving on. --}}
@php
    $old = [
        'name' => old('name', ''),
        'date' => old('date', ''),
        'venue' => old('venue', ''),
        'tagline' => old('tagline', ''),
        'sport' => old('sport', 'running'),
        'distances' => old('distances'),
        'template' => old('template', $presetTemplate ?? null), // ?template= from the public gallery
        'primary' => old('primary', '#4b3fe0'),
        'secondary' => old('secondary', '#f2a93b'),
    ];
    $steps = [__('Basics'), __('Sport pack'), __('Page design'), __('Logo, photo and colours'), __('Review and publish')];
    $titles = [__('The basics'), __('Sport pack and prices'), __('Pick a page design'), __('Logo, photo and colours'), __('Review and publish')];
    $continue = [__('Continue to sport pack'), __('Continue to page design'), __('Continue to branding'), __('Continue to review')];
    $palettes = [
        'Daylight' => ['#4b3fe0', '#f2a93b'],
        'Ocean' => ['#38bdf8', '#1e3a8a'],
        'Forest' => ['#facc15', '#166534'],
        'Sunset' => ['#fb923c', '#7c3aed'],
        'Classic' => ['#b91c1c', '#111827'],
        'Volt' => ['#d9ff45', '#ff6e61'],
    ];
    $card = 'rounded-[28px] bg-white p-5 sm:p-8';
    $file = 'text-sm text-muted file:me-3 file:min-h-[40px] file:rounded-full file:border-0 file:bg-ink file:px-4 file:font-semibold file:text-white';
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('New event') }}</x-slot>

    <script>
        window.eventWizard = (packs, templates, old, firstStep) => ({
            step: firstStep,
            visited: firstStep,
            packs, templates,
            sport: old.sport,
            template: old.template || packs[old.sport].template,
            templateTouched: !!old.template,
            distances: old.distances || packs[old.sport].distances.map(d => ({ ...d, price_inr: '', capacity: '' })),
            primary: old.primary,
            secondary: old.secondary,
            name: old.name, date: old.date, venue: old.venue, tagline: old.tagline,
            logoUrl: null, heroUrl: null, logoDark: false, logoOpaque: false,
            go(n) { if (n <= this.visited || this.valid()) { this.step = n; this.visited = Math.max(this.visited, n); window.scrollTo({ top: 0, behavior: 'smooth' }); } },
            next() { if (this.valid()) this.go(this.step + 1); },
            valid() {
                const panel = this.$root.querySelector(`[data-step="${this.step}"]`);
                for (const el of panel.querySelectorAll('input, select, textarea')) { if (!el.checkValidity()) { el.reportValidity(); return false; } }
                if (this.step === 2 && this.distances.length === 0) { alert(@js(__('Add at least one distance.'))); return false; }
                return true;
            },
            pickSport(s) {
                this.sport = s;
                this.distances = this.packs[s].distances.map(d => ({ ...d, price_inr: '', capacity: '' }));
                if (!this.templateTouched) this.template = this.packs[s].template;
            },
            addDistance() { this.distances.push({ code: '', label: '', price_inr: '', capacity: '' }); },
            preview(t) {
                const q = new URLSearchParams({ primary: this.primary, secondary: this.secondary, name: this.name || '' });
                return @js(url('/organiser/templates')) + '/' + t + '/preview?' + q;
            },
            usePalette(p, s) { this.primary = p; this.secondary = s; },
            readImage(event, kind) {
                const file = event.target.files[0];
                if (!file) { this[kind + 'Url'] = null; return; }
                const url = URL.createObjectURL(file);
                this[kind + 'Url'] = url;
                if (kind !== 'logo') return;
                const img = new Image();
                img.onload = () => {
                    const c = document.createElement('canvas'); c.width = c.height = 48;
                    const ctx = c.getContext('2d'); ctx.drawImage(img, 0, 0, 48, 48);
                    const px = ctx.getImageData(0, 0, 48, 48).data;
                    let sum = 0, n = 0, clear = false;
                    const lin = v => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
                    for (let i = 0; i < px.length; i += 4) {
                        if (px[i + 3] < 127) { clear = true; continue; }
                        sum += 0.2126 * lin(px[i]) + 0.7152 * lin(px[i + 1]) + 0.0722 * lin(px[i + 2]); n++;
                    }
                    this.logoDark = n > 0 && sum / n < {{ \App\Landing\LogoInspector::DARK_THRESHOLD }};
                    this.logoOpaque = !clear;
                };
                img.src = url;
            },
            money(v) { return v === '' || v === null ? '—' : (Number(v) === 0 ? @js(__('Free')) : '₹' + Number(v).toLocaleString('en-IN')); },
        });
    </script>

    <div class="mx-auto flex max-w-[1180px] flex-col gap-6 px-4 pb-12 pt-6 sm:px-6 lg:px-10 lg:pt-8"
         x-data="eventWizard(@js($packs), @js($templates), @js($old), {{ $step }})">

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm text-muted" x-text="@js(__('New event, step')) + ' ' + step + ' ' + @js(__('of 5'))">{{ __('New event, step') }} {{ $step }} {{ __('of 5') }}</p>
                <h1 class="ri-headline mt-1 text-[30px] sm:text-[36px]" x-text="@js($titles)[step - 1]">{{ $titles[$step - 1] }}</h1>
            </div>
            <p class="text-sm text-muted">{{ __('Saved as a draft. Nothing goes live until you publish.') }}</p>
        </div>

        {{-- Stepper --}}
        <ol class="grid grid-cols-5 gap-2 text-sm font-semibold" aria-label="{{ __('Steps') }}">
            @foreach ($steps as $i => $label)
                <li>
                    <button type="button" @click="go({{ $i + 1 }})" class="flex w-full flex-col gap-2 text-left"
                            :class="step === {{ $i + 1 }} ? 'text-ink' : (step > {{ $i + 1 }} ? 'text-track-deep' : 'text-muted')"
                            :aria-current="step === {{ $i + 1 }} ? 'step' : null">
                        <span class="block h-1.5 rounded-full transition-colors duration-300 ease-track"
                              :class="step > {{ $i + 1 }} ? 'bg-track' : (step === {{ $i + 1 }} ? 'bg-[linear-gradient(90deg,#4B3FE0_50%,#C9C5FA_50%)]' : 'bg-mist')"></span>
                        <span class="hidden sm:block">{{ $i + 1 }}. {{ $label }}</span>
                    </button>
                </li>
            @endforeach
        </ol>

        @if ($errors->any())
            <div class="rounded-[22px] bg-[#FBE4E4] p-4 text-sm text-[#6E1414]" role="alert">
                <p class="font-semibold">{{ __('Please check the highlighted fields.') }}</p>
                <ul class="mt-1 list-disc ps-5">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('organiser.events.store') }}" enctype="multipart/form-data" class="flex flex-col gap-6">
            @csrf
            <input type="hidden" name="sport" :value="sport">
            <input type="hidden" name="template" :value="template">

            {{-- 1. Basics --}}
            <section data-step="1" x-show="step === 1" class="{{ $card }}">
                <p class="text-[15px] text-muted">{{ __('What, when and where. You can change all of it later.') }}</p>
                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="ri-label">{{ __('Event name') }}</label>
                        <input id="name" name="name" x-model="name" required maxlength="160" class="ri-input text-lg" placeholder="{{ __('e.g. Bengaluru City Marathon 2027') }}">
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <label for="date" class="ri-label">{{ __('Start date and time (IST)') }}</label>
                        <input id="date" name="date" type="datetime-local" x-model="date" required min="{{ now('Asia/Kolkata')->format('Y-m-d\TH:i') }}" class="ri-input">
                        <x-input-error class="mt-2" :messages="$errors->get('date')" />
                    </div>
                    <div>
                        <label for="venue" class="ri-label">{{ __('Venue') }}</label>
                        <input id="venue" name="venue" x-model="venue" required maxlength="255" class="ri-input" placeholder="{{ __('Stadium, park or start line') }}">
                        <x-input-error class="mt-2" :messages="$errors->get('venue')" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="tagline" class="ri-label">{{ __('One-line tagline') }} <span class="font-normal text-muted">({{ __('optional') }})</span></label>
                        <input id="tagline" name="tagline" x-model="tagline" maxlength="255" class="ri-input" placeholder="{{ __('e.g. 5K, 10K and Half Marathon. Chip timed.') }}">
                    </div>
                </div>
            </section>

            {{-- 2. Sport pack + distances --}}
            <section data-step="2" x-show="step === 2" x-cloak class="{{ $card }}">
                <p class="text-[15px] text-muted">{{ __('Sets sensible defaults: distances, age categories, form questions, FAQ, page design and certificate.') }}</p>
                <div class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <template x-for="(pack, key) in packs" :key="key">
                        <button type="button" @click="pickSport(key)" class="min-h-[44px] rounded-[20px] border-2 p-4 text-left transition duration-200 ease-track"
                                :aria-pressed="(sport === key).toString()"
                                :class="sport === key ? 'border-track bg-lilac text-track-deep' : 'border-[#EEF0F6] bg-white hover:border-mist'">
                            <span class="block font-bold" x-text="pack.label"></span>
                            <span class="mt-1 block text-[13px] text-muted" x-text="pack.distances.length ? pack.distances.length + ' {{ __('distances') }}' : '{{ __('Add your own distances') }}'"></span>
                        </button>
                    </template>
                </div>

                <h3 class="mt-8 text-lg font-bold">{{ __('Distances and prices') }}</h3>
                <p class="mt-1 text-sm text-muted">{{ __('Set a price for each (0 = free). Leave places blank for no limit.') }}</p>
                <x-input-error class="mt-2" :messages="collect($errors->get('distances*'))->flatten()->all()" />
                <div class="mt-4 space-y-3">
                    <template x-for="(d, i) in distances" :key="i">
                        <div class="grid grid-cols-[1fr_auto] items-end gap-3 rounded-[20px] bg-paper p-3 sm:grid-cols-[6rem_1fr_8rem_8rem_auto]">
                            <label class="hidden sm:block"><span class="text-xs font-semibold text-muted">{{ __('Code') }}</span>
                                <input :name="`distances[${i}][code]`" x-model="d.code" required pattern="[A-Za-z0-9_-]{1,20}" class="ri-input text-sm" placeholder="10K"></label>
                            <label><span class="text-xs font-semibold text-muted">{{ __('Name') }}</span>
                                <input :name="`distances[${i}][label]`" x-model="d.label" required maxlength="80" class="ri-input text-sm" @input="if (!d.code) d.code = d.label.replace(/[^A-Za-z0-9]/g, '').slice(0, 20).toUpperCase()"></label>
                            <button type="button" @click="distances.splice(i, 1)" class="mb-0.5 flex h-11 w-11 items-center justify-center rounded-full text-muted hover:bg-white hover:text-danger sm:order-last" aria-label="{{ __('Remove distance') }}">✕</button>
                            <label><span class="text-xs font-semibold text-muted">{{ __('Price (₹)') }}</span>
                                <input type="number" min="0" :name="`distances[${i}][price_inr]`" x-model="d.price_inr" required class="ri-input text-sm"></label>
                            <label><span class="text-xs font-semibold text-muted">{{ __('Places') }}</span>
                                <input type="number" min="1" :name="`distances[${i}][capacity]`" x-model="d.capacity" class="ri-input text-sm" placeholder="{{ __('No limit') }}"></label>
                        </div>
                    </template>
                </div>
                <x-ri.button variant="secondary" size="sm" class="mt-4" @click="addDistance()">+ {{ __('Add distance') }}</x-ri.button>
            </section>

            {{-- 3. Page design --}}
            <section data-step="3" x-show="step === 3" x-cloak class="flex flex-wrap items-start gap-6">
                <div class="grid min-w-0 flex-[3_1_520px] gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr))]">
                    @foreach ($templates as $key => $tpl)
                        <button type="button" @click="template = '{{ $key }}'; templateTouched = true" :aria-pressed="(template === '{{ $key }}').toString()"
                                class="flex flex-col gap-3 rounded-[24px] border-[3px] bg-white p-2.5 text-left transition duration-200 ease-track"
                                :class="template === '{{ $key }}' ? 'border-track' : 'border-transparent hover:border-mist'">
                            <span class="relative block h-[150px] rounded-2xl" style="background: {{ $tpl['swatch'] ?? '#ECEAFF' }}">
                                <span @class(['ri-headline absolute inset-x-3 bottom-3 text-lg', 'text-center' => $key === 'heritage-crest', 'text-ink' => $key === 'stadium-split', 'text-white' => $key !== 'stadium-split'])>{{ __('Your race name') }}</span>
                                <span class="absolute right-2.5 top-2.5 rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-track-deep"
                                      x-text="packs[sport].template === '{{ $key }}' ? packs[sport].label + ' ' + @js(__('pack pick')) : @js(__($tpl['sport'] ?? ''))">{{ __($tpl['sport'] ?? '') }}</span>
                            </span>
                            <span class="flex flex-col gap-1 px-1.5 pb-1.5">
                                <strong class="text-base">{{ $tpl['label'] }}</strong>
                                <span class="text-[13px] text-muted">{{ __($tpl['blurb'] ?? $tpl['description']) }}</span>
                            </span>
                        </button>
                    @endforeach
                    <a href="{{ \App\Support\Contact::customDesign(__('Custom event page template for :org', ['org' => $org->name])) }}" data-contact-indefine
                       class="flex flex-col justify-center rounded-[24px] border-2 border-dashed border-[#C5CBDB] p-6 text-center hover:border-track">
                        <span class="text-lg font-bold">{{ __('Need a custom template?') }}</span>
                        <span class="mt-2 text-sm text-muted">{{ __('Contact Indefine and we will design one for your series.') }}</span>
                        <span class="mt-4 text-sm font-semibold text-track">{{ $contact }}</span>
                    </a>
                </div>

                <aside class="flex min-w-0 flex-[2_1_320px] flex-col items-center gap-4 rounded-[28px] bg-white p-[22px]">
                    <p class="self-stretch text-sm font-semibold">{{ __('How runners will see it') }}</p>
                    <div class="ri-float w-[250px] rounded-[34px] bg-ink p-2">
                        <div class="relative h-[480px] overflow-hidden rounded-[27px] bg-white">
                            <template x-if="step >= 3">
                                <iframe :src="preview(template)" title="{{ __('Phone preview') }}" loading="lazy" tabindex="-1"
                                        class="pointer-events-none absolute left-0 top-0 h-[800px] w-[390px] origin-top-left scale-[0.6]"></iframe>
                            </template>
                        </div>
                    </div>
                    <p class="text-center text-[13px] text-muted">{{ __('Your logo, photo and colours come in the next step.') }}</p>
                </aside>
            </section>

            {{-- 4. Logo, photo and colours --}}
            <section data-step="4" x-show="step === 4" x-cloak class="{{ $card }}">
                <p class="text-[15px] text-muted">{{ __('Templates place your logo and photo in fixed spots, so they always line up.') }}</p>

                <div class="mt-6 grid gap-8 lg:grid-cols-2">
                    <div>
                        <p class="ri-label">{{ __('Colours') }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($palettes as $label => [$p, $s])
                                <button type="button" @click="usePalette('{{ $p }}', '{{ $s }}')" class="flex min-h-[40px] items-center gap-2 rounded-full bg-white py-1 pe-3.5 ps-1.5 text-[13px] font-semibold ring-1 ring-inset ring-mist hover:ring-ink/30"
                                        :aria-pressed="(primary === '{{ $p }}' && secondary === '{{ $s }}').toString()">
                                    <span class="flex"><span class="h-6 w-6 rounded-full ring-2 ring-white" style="background: {{ $p }}"></span><span class="-ms-2 h-6 w-6 rounded-full ring-2 ring-white" style="background: {{ $s }}"></span></span>
                                    {{ __($label) }}
                                </button>
                            @endforeach
                        </div>
                        <div class="mt-5 grid grid-cols-2 gap-4">
                            <label class="text-sm text-muted">{{ __('Primary (buttons)') }}
                                <span class="mt-1.5 flex items-center gap-2"><input type="color" name="primary" x-model="primary" class="h-11 w-14 cursor-pointer rounded-[12px] border-[#C5CBDB]"><span class="font-mono text-xs text-ink" x-text="primary"></span></span></label>
                            <label class="text-sm text-muted">{{ __('Secondary (accents)') }}
                                <span class="mt-1.5 flex items-center gap-2"><input type="color" name="secondary" x-model="secondary" class="h-11 w-14 cursor-pointer rounded-[12px] border-[#C5CBDB]"><span class="font-mono text-xs text-ink" x-text="secondary"></span></span></label>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label for="logo" class="ri-label">{{ __('Logo') }} <span class="font-normal text-muted">({{ __('optional') }})</span></label>
                            <p class="mt-1 text-[13px] text-muted">{{ __('Best: a PNG with a transparent background, at least 400 px wide. JPG and WebP work too. Max :mb MB.', ['mb' => \App\Landing\EventWizard::LOGO_MAX_KB / 1024]) }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-4">
                                <div class="flex h-16 w-28 items-center justify-center rounded-[14px] bg-[repeating-conic-gradient(#EEF0F6_0_25%,#fff_0_50%)] bg-[length:16px_16px] ring-1 ring-mist">
                                    <img x-show="logoUrl" :src="logoUrl" alt="" class="max-h-12 max-w-24">
                                </div>
                                <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" @change="readImage($event, 'logo')" class="{{ $file }}">
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('logo')" />
                            <p x-show="logoDark" x-cloak class="mt-3 rounded-[14px] bg-[#FDF0DA] p-3 text-sm text-[#6B4A12]" role="status">
                                {{ __('Dark logo — pick a light template (Stadium Split/Heritage Crest) or a light hero background so it stays classy.') }}
                            </p>
                            <p x-show="logoUrl && logoOpaque" x-cloak class="mt-2 text-[13px] text-muted">{{ __('Tip: this logo has a solid background. A transparent PNG blends into every template.') }}</p>
                        </div>
                        <div>
                            <label for="hero" class="ri-label">{{ __('Event photo') }} <span class="font-normal text-muted">({{ __('optional') }})</span></label>
                            <p class="mt-1 text-[13px] text-muted">{{ __('Landscape, at least 1200 × 600 px (JPG, PNG or WebP, max :mb MB). Action shots from past editions work best.', ['mb' => \App\Landing\EventWizard::HERO_MAX_KB / 1024]) }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-4">
                                <div class="h-16 w-28 overflow-hidden rounded-[14px] bg-lilac"><img x-show="heroUrl" :src="heroUrl" alt="" class="h-full w-full object-cover"></div>
                                <input id="hero" name="hero" type="file" accept="image/jpeg,image/png,image/webp" @change="readImage($event, 'hero')" class="{{ $file }}">
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('hero')" />
                        </div>
                    </div>
                </div>
            </section>

            {{-- 5. Review and publish --}}
            <section data-step="5" x-show="step === 5" x-cloak class="{{ $card }}">
                <div class="grid gap-5 lg:grid-cols-2">
                    <dl class="divide-y divide-[#EEF0F6] rounded-[20px] bg-paper text-sm">
                        <div class="flex justify-between gap-4 p-4"><dt class="text-muted">{{ __('Event') }}</dt><dd class="text-right font-semibold" x-text="name"></dd></div>
                        <div class="flex justify-between gap-4 p-4"><dt class="text-muted">{{ __('When') }}</dt><dd class="text-right" x-text="date ? new Date(date).toLocaleString('en-IN', { dateStyle: 'medium', timeStyle: 'short' }) : ''"></dd></div>
                        <div class="flex justify-between gap-4 p-4"><dt class="text-muted">{{ __('Venue') }}</dt><dd class="text-right" x-text="venue"></dd></div>
                        <div class="flex justify-between gap-4 p-4"><dt class="text-muted">{{ __('Sport pack') }}</dt><dd class="text-right" x-text="packs[sport].label"></dd></div>
                        <div class="flex justify-between gap-4 p-4"><dt class="text-muted">{{ __('Design') }}</dt><dd class="text-right" x-text="templates[template].label"></dd></div>
                        <div class="flex justify-between gap-4 p-4"><dt class="text-muted">{{ __('Colours') }}</dt><dd class="flex gap-1"><span class="h-5 w-5 rounded-full ring-1 ring-mist" :style="`background:${primary}`"></span><span class="h-5 w-5 rounded-full ring-1 ring-mist" :style="`background:${secondary}`"></span></dd></div>
                    </dl>
                    <div class="rounded-[20px] bg-paper">
                        <p class="border-b border-[#EEF0F6] p-4 text-sm font-semibold">{{ __('Distances') }}</p>
                        <ul class="divide-y divide-[#EEF0F6] text-sm">
                            <template x-for="d in distances"><li class="flex justify-between gap-4 p-4"><span x-text="d.label"></span><span class="font-semibold" x-text="money(d.price_inr)"></span></li></template>
                        </ul>
                        <p class="border-t border-[#EEF0F6] p-4 text-[13px] text-muted" x-text="`${packs[sport].label} {{ __('defaults add age categories, form questions and') }} ${packs[sport].faq} {{ __('FAQs. Edit them any time.') }}`"></p>
                    </div>
                </div>
                <p class="mt-5 rounded-[20px] bg-lilac p-4 text-sm text-track-deep">
                    {{ __('Your page goes live at') }} <span class="break-all font-semibold">{{ url($org->slug) }}/…</span> {{ __('as soon as you publish. Registrations and payments open straight away.') }}
                </p>
            </section>

            {{-- Navigation --}}
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-[22px] bg-white px-4 py-3.5">
                <button type="button" x-show="step > 1" @click="go(step - 1)" class="min-h-[44px] rounded-full bg-paper px-[18px] font-semibold text-ink hover:bg-mist">{{ __('Back') }}</button>
                <span x-show="step === 1"></span>
                <span class="hidden text-sm text-muted sm:inline" x-show="step === 3" x-text="templates[template].label + ' ' + @js(__('selected'))"></span>
                <div class="flex flex-wrap gap-3">
                    @foreach ($continue as $i => $label)
                        <button type="button" x-show="step === {{ $i + 1 }}" @if ($i + 1 !== $step) x-cloak @endif @click="next()" class="ri-glow min-h-[48px] rounded-full bg-track px-[22px] font-bold text-white hover:bg-track-deep">{{ $label }}</button>
                    @endforeach
                    <template x-if="step === 5">
                        <div class="flex flex-wrap gap-3">
                            <button type="submit" name="publish" value="0" class="min-h-[48px] rounded-full bg-white px-[22px] font-semibold text-ink ring-1 ring-inset ring-[#C5CBDB] hover:ring-ink/40">{{ __('Save as draft') }}</button>
                            <button type="submit" name="publish" value="1" class="ri-glow min-h-[48px] rounded-full bg-track px-[22px] font-bold text-white hover:bg-track-deep">{{ __('Publish event') }}</button>
                        </div>
                    </template>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
