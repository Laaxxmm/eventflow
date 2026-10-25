{{-- Create-event wizard (App\Landing\EventWizard): Basics → Sport pack → Template → Branding & media → Review & publish.
     One form; Alpine shows one step at a time and checks each step's required fields before moving on. --}}
@php
    $old = [
        'name' => old('name', ''),
        'date' => old('date', ''),
        'venue' => old('venue', ''),
        'tagline' => old('tagline', ''),
        'sport' => old('sport', 'running'),
        'distances' => old('distances'),
        'template' => old('template'),
        'primary' => old('primary', '#d9ff45'),
        'secondary' => old('secondary', '#ff6e61'),
    ];
    $steps = [__('Basics'), __('Sport pack'), __('Page design'), __('Branding'), __('Publish')];
    $palettes = [
        'Track' => ['#d9ff45', '#ff6e61'],
        'Ocean' => ['#38bdf8', '#1e3a8a'],
        'Forest' => ['#facc15', '#166534'],
        'Sunset' => ['#fb923c', '#7c3aed'],
        'Classic' => ['#b91c1c', '#111827'],
    ];
    $control = 'mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900';
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('New event') }}</x-slot>
    <x-slot name="header">
        <div>
            <h2>{{ __('New event') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Five short steps. Your page goes live when you publish.') }}</p>
        </div>
    </x-slot>

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

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-10"
         x-data="eventWizard(@js($packs), @js($templates), @js($old), {{ $step }})">

        {{-- Stepper --}}
        <ol class="grid grid-cols-5 gap-2" aria-label="{{ __('Steps') }}">
            @foreach ($steps as $i => $label)
                <li>
                    <button type="button" @click="go({{ $i + 1 }})" class="group w-full text-left" :aria-current="step === {{ $i + 1 }} ? 'step' : null">
                        <span class="block h-1.5 rounded-full transition" :class="step >= {{ $i + 1 }} ? 'bg-[#0b1020]' : 'bg-slate-200'"></span>
                        <span class="mt-2 hidden text-xs font-semibold sm:block" :class="step === {{ $i + 1 }} ? 'text-slate-900' : 'text-slate-400'">{{ $i + 1 }}. {{ $label }}</span>
                    </button>
                </li>
            @endforeach
        </ol>
        <p class="mt-3 text-sm font-semibold text-slate-900 sm:hidden" x-text="'{{ __('Step') }} ' + step + ' / 5'"></p>

        @if ($errors->any())
            <div class="mt-6 rounded-2xl bg-red-50 p-4 text-sm text-red-700" role="alert">
                <p class="font-semibold">{{ __('Please check the highlighted fields.') }}</p>
                <ul class="mt-1 list-disc ps-5">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('organiser.events.store') }}" enctype="multipart/form-data" class="mt-8">
            @csrf
            <input type="hidden" name="sport" :value="sport">
            <input type="hidden" name="template" :value="template">

            {{-- 1. Basics --}}
            <section data-step="1" x-show="step === 1" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 sm:p-10">
                <h3 class="text-xl font-bold">{{ __('The basics') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('What, when and where. You can change all of it later.') }}</p>
                <div class="mt-8 grid gap-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="text-sm font-medium text-slate-700">{{ __('Event name') }}</label>
                        <input id="name" name="name" x-model="name" required maxlength="160" class="{{ $control }} text-lg" placeholder="{{ __('e.g. Bengaluru City Marathon 2027') }}">
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <label for="date" class="text-sm font-medium text-slate-700">{{ __('Start date and time (IST)') }}</label>
                        <input id="date" name="date" type="datetime-local" x-model="date" required min="{{ now('Asia/Kolkata')->format('Y-m-d\TH:i') }}" class="{{ $control }}">
                        <x-input-error class="mt-2" :messages="$errors->get('date')" />
                    </div>
                    <div>
                        <label for="venue" class="text-sm font-medium text-slate-700">{{ __('Venue') }}</label>
                        <input id="venue" name="venue" x-model="venue" required maxlength="255" class="{{ $control }}" placeholder="{{ __('Stadium, park or start line') }}">
                        <x-input-error class="mt-2" :messages="$errors->get('venue')" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="tagline" class="text-sm font-medium text-slate-700">{{ __('One-line tagline') }} <span class="font-normal text-slate-400">({{ __('optional') }})</span></label>
                        <input id="tagline" name="tagline" x-model="tagline" maxlength="255" class="{{ $control }}" placeholder="{{ __('e.g. 5K, 10K and Half Marathon. Chip timed.') }}">
                    </div>
                </div>
            </section>

            {{-- 2. Sport pack + distances --}}
            <section data-step="2" x-show="step === 2" x-cloak class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 sm:p-10">
                <h3 class="text-xl font-bold">{{ __('Sport pack') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('Sets sensible defaults: distances, age categories, form questions, FAQ, page design and certificate.') }}</p>
                <div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <template x-for="(pack, key) in packs" :key="key">
                        <button type="button" @click="pickSport(key)" class="rounded-2xl p-4 text-left ring-1 transition"
                                :class="sport === key ? 'bg-[#0b1020] text-white ring-[#0b1020]' : 'bg-white ring-slate-200 hover:ring-slate-400'">
                            <span class="block font-bold" x-text="pack.label"></span>
                            <span class="mt-1 block text-xs opacity-70" x-text="pack.distances.length ? pack.distances.length + ' {{ __('distances') }}' : '{{ __('Add your own distances') }}'"></span>
                        </button>
                    </template>
                </div>

                <h4 class="mt-10 font-semibold">{{ __('Distances and prices') }}</h4>
                <p class="mt-1 text-sm text-slate-500">{{ __('Set a price for each (0 = free). Leave places blank for no limit.') }}</p>
                <x-input-error class="mt-2" :messages="collect($errors->get('distances*'))->flatten()->all()" />
                <div class="mt-4 space-y-3">
                    <template x-for="(d, i) in distances" :key="i">
                        <div class="grid grid-cols-[1fr_auto] items-end gap-3 rounded-2xl bg-slate-50 p-3 sm:grid-cols-[6rem_1fr_8rem_8rem_auto]">
                            <label class="hidden sm:block"><span class="text-xs text-slate-500">{{ __('Code') }}</span>
                                <input :name="`distances[${i}][code]`" x-model="d.code" required pattern="[A-Za-z0-9_-]{1,20}" class="{{ $control }} text-sm" placeholder="10K"></label>
                            <label><span class="text-xs text-slate-500">{{ __('Name') }}</span>
                                <input :name="`distances[${i}][label]`" x-model="d.label" required maxlength="80" class="{{ $control }} text-sm" @input="if (!d.code) d.code = d.label.replace(/[^A-Za-z0-9]/g, '').slice(0, 20).toUpperCase()"></label>
                            <button type="button" @click="distances.splice(i, 1)" class="mb-1 rounded-lg p-2 text-slate-400 hover:bg-white hover:text-red-600 sm:order-last" aria-label="{{ __('Remove distance') }}">✕</button>
                            <label><span class="text-xs text-slate-500">{{ __('Price (₹)') }}</span>
                                <input type="number" min="0" :name="`distances[${i}][price_inr]`" x-model="d.price_inr" required class="{{ $control }} text-sm"></label>
                            <label><span class="text-xs text-slate-500">{{ __('Places') }}</span>
                                <input type="number" min="1" :name="`distances[${i}][capacity]`" x-model="d.capacity" class="{{ $control }} text-sm" placeholder="{{ __('No limit') }}"></label>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addDistance()" class="mt-4 rounded-xl px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">+ {{ __('Add distance') }}</button>
            </section>

            {{-- 3. Template --}}
            <section data-step="3" x-show="step === 3" x-cloak class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 sm:p-10">
                <h3 class="text-xl font-bold">{{ __('Page design') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('Five layouts, each built differently. Previews use your colours and sample content.') }}</p>
                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($templates as $key => $tpl)
                        <button type="button" @click="template = '{{ $key }}'; templateTouched = true"
                                class="group overflow-hidden rounded-2xl text-left ring-2 transition"
                                :class="template === '{{ $key }}' ? 'ring-[#0b1020]' : 'ring-slate-200 hover:ring-slate-400'">
                            <div class="relative h-56 overflow-hidden bg-slate-100">
                                <template x-if="step >= 3">
                                    <iframe :src="preview('{{ $key }}')" title="{{ $tpl['label'] }}" loading="lazy" tabindex="-1"
                                            class="pointer-events-none absolute left-0 top-0 h-[1120px] w-[1280px] origin-top-left scale-[0.25] sm:scale-[0.26] lg:scale-[0.235]"></iframe>
                                </template>
                            </div>
                            <div class="flex items-start justify-between gap-3 p-4">
                                <div>
                                    <p class="font-bold">{{ $tpl['label'] }} <span class="ms-1 text-xs font-medium text-slate-400">{{ $tpl['tone'] === 'dark' ? __('dark') : __('light') }}</span></p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $tpl['description'] }}</p>
                                </div>
                                <span class="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full ring-2" :class="template === '{{ $key }}' ? 'bg-[#0b1020] ring-[#0b1020] text-white' : 'ring-slate-300'"><span x-show="template === '{{ $key }}'" class="text-xs">✓</span></span>
                            </div>
                        </button>
                    @endforeach
                    <a href="mailto:{{ $contact }}?subject={{ rawurlencode(__('Custom event page template for :org', ['org' => $org->name])) }}"
                       class="flex flex-col justify-center rounded-2xl border-2 border-dashed border-slate-300 p-6 text-center hover:border-slate-500">
                        <span class="text-lg font-bold">{{ __('Need a custom template?') }}</span>
                        <span class="mt-2 text-sm text-slate-500">{{ __('Contact Indefine and we will design one for your series.') }}</span>
                        <span class="mt-4 text-sm font-semibold underline">{{ $contact }}</span>
                    </a>
                </div>
            </section>

            {{-- 4. Branding & media --}}
            <section data-step="4" x-show="step === 4" x-cloak class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 sm:p-10">
                <h3 class="text-xl font-bold">{{ __('Branding and media') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('Templates place your logo and photo in fixed spots, so they always line up.') }}</p>

                <div class="mt-8 grid gap-8 lg:grid-cols-2">
                    <div>
                        <p class="text-sm font-medium text-slate-700">{{ __('Colours') }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($palettes as $label => [$p, $s])
                                <button type="button" @click="usePalette('{{ $p }}', '{{ $s }}')" class="flex items-center gap-2 rounded-full py-1 pe-3 ps-1 text-xs font-semibold ring-1 ring-slate-200 hover:ring-slate-400">
                                    <span class="flex"><span class="h-5 w-5 rounded-full ring-2 ring-white" style="background: {{ $p }}"></span><span class="-ms-1.5 h-5 w-5 rounded-full ring-2 ring-white" style="background: {{ $s }}"></span></span>
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-4">
                            <label class="text-sm text-slate-600">{{ __('Primary (buttons)') }}
                                <span class="mt-1 flex items-center gap-2"><input type="color" name="primary" x-model="primary" class="h-10 w-14 cursor-pointer rounded-lg border-slate-300"><span class="font-mono text-xs" x-text="primary"></span></span></label>
                            <label class="text-sm text-slate-600">{{ __('Secondary (accents)') }}
                                <span class="mt-1 flex items-center gap-2"><input type="color" name="secondary" x-model="secondary" class="h-10 w-14 cursor-pointer rounded-lg border-slate-300"><span class="font-mono text-xs" x-text="secondary"></span></span></label>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label for="logo" class="text-sm font-medium text-slate-700">{{ __('Logo') }} <span class="font-normal text-slate-400">({{ __('optional') }})</span></label>
                            <p class="mt-1 text-xs text-slate-500">{{ __('Best: a PNG with a transparent background, at least 400 px wide. JPG and WebP work too. Max :mb MB.', ['mb' => \App\Landing\EventWizard::LOGO_MAX_KB / 1024]) }}</p>
                            <div class="mt-2 flex items-center gap-4">
                                <div class="flex h-16 w-28 items-center justify-center rounded-xl bg-[repeating-conic-gradient(#f1f5f9_0_25%,#fff_0_50%)] bg-[length:16px_16px] ring-1 ring-slate-200">
                                    <img x-show="logoUrl" :src="logoUrl" alt="" class="max-h-12 max-w-24">
                                </div>
                                <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" @change="readImage($event, 'logo')" class="text-sm file:me-3 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-white">
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('logo')" />
                            <p x-show="logoDark" x-cloak class="mt-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-800" role="status">
                                {{ __('Dark logo — pick a light template (Aurora/Clean) or a light hero background so it stays classy.') }}
                            </p>
                            <p x-show="logoUrl && logoOpaque" x-cloak class="mt-2 text-xs text-slate-500">{{ __('Tip: this logo has a solid background. A transparent PNG blends into every template.') }}</p>
                        </div>
                        <div>
                            <label for="hero" class="text-sm font-medium text-slate-700">{{ __('Event photo') }} <span class="font-normal text-slate-400">({{ __('optional') }})</span></label>
                            <p class="mt-1 text-xs text-slate-500">{{ __('Landscape, at least 1200 × 600 px (JPG, PNG or WebP, max :mb MB). Action shots from past editions work best.', ['mb' => \App\Landing\EventWizard::HERO_MAX_KB / 1024]) }}</p>
                            <div class="mt-2 flex items-center gap-4">
                                <div class="h-16 w-28 overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200"><img x-show="heroUrl" :src="heroUrl" alt="" class="h-full w-full object-cover"></div>
                                <input id="hero" name="hero" type="file" accept="image/jpeg,image/png,image/webp" @change="readImage($event, 'hero')" class="text-sm file:me-3 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-white">
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('hero')" />
                        </div>
                    </div>
                </div>
            </section>

            {{-- 5. Review & publish --}}
            <section data-step="5" x-show="step === 5" x-cloak class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 sm:p-10">
                <h3 class="text-xl font-bold">{{ __('Review and publish') }}</h3>
                <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_1fr]">
                    <dl class="divide-y divide-slate-100 rounded-2xl ring-1 ring-slate-200 text-sm">
                        <div class="flex justify-between gap-4 p-4"><dt class="text-slate-500">{{ __('Event') }}</dt><dd class="text-right font-semibold" x-text="name"></dd></div>
                        <div class="flex justify-between gap-4 p-4"><dt class="text-slate-500">{{ __('When') }}</dt><dd class="text-right" x-text="date ? new Date(date).toLocaleString('en-IN', { dateStyle: 'medium', timeStyle: 'short' }) : ''"></dd></div>
                        <div class="flex justify-between gap-4 p-4"><dt class="text-slate-500">{{ __('Venue') }}</dt><dd class="text-right" x-text="venue"></dd></div>
                        <div class="flex justify-between gap-4 p-4"><dt class="text-slate-500">{{ __('Sport pack') }}</dt><dd class="text-right" x-text="packs[sport].label"></dd></div>
                        <div class="flex justify-between gap-4 p-4"><dt class="text-slate-500">{{ __('Design') }}</dt><dd class="text-right" x-text="templates[template].label"></dd></div>
                        <div class="flex justify-between gap-4 p-4"><dt class="text-slate-500">{{ __('Colours') }}</dt><dd class="flex gap-1"><span class="h-5 w-5 rounded-full ring-1 ring-slate-200" :style="`background:${primary}`"></span><span class="h-5 w-5 rounded-full ring-1 ring-slate-200" :style="`background:${secondary}`"></span></dd></div>
                    </dl>
                    <div class="rounded-2xl ring-1 ring-slate-200">
                        <p class="border-b border-slate-100 p-4 text-sm font-semibold">{{ __('Distances') }}</p>
                        <ul class="divide-y divide-slate-100 text-sm">
                            <template x-for="d in distances"><li class="flex justify-between gap-4 p-4"><span x-text="d.label"></span><span class="font-semibold" x-text="money(d.price_inr)"></span></li></template>
                        </ul>
                        <p class="border-t border-slate-100 p-4 text-xs text-slate-500" x-text="`${packs[sport].label} {{ __('defaults add age categories, form questions and') }} ${packs[sport].faq} {{ __('FAQs. Edit them any time.') }}`"></p>
                    </div>
                </div>
                <div class="mt-6 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                    {{ __('Your page goes live at') }} <span class="font-mono font-semibold text-slate-900">{{ url($org->slug) }}/…</span> {{ __('as soon as you publish. Registrations and payments open straight away.') }}
                </div>
            </section>

            {{-- Navigation --}}
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                <button type="button" x-show="step > 1" @click="go(step - 1)" class="rounded-xl px-5 py-3 font-semibold text-slate-700 hover:bg-white">← {{ __('Back') }}</button>
                <span x-show="step === 1"></span>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="button" x-show="step < 5" @click="next()" class="rounded-xl bg-[#0b1020] px-6 py-3 font-semibold text-white hover:bg-[#1f2a4a]">{{ __('Continue') }} →</button>
                    <template x-if="step === 5">
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <button type="submit" name="publish" value="0" class="rounded-xl px-6 py-3 font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-white">{{ __('Save as draft') }}</button>
                            <button type="submit" name="publish" value="1" class="rounded-xl bg-[#d9ff45] px-6 py-3 font-bold text-[#0b1020] ring-1 ring-[#0b1020] hover:bg-[#c8ee34]">{{ __('Publish event') }}</button>
                        </div>
                    </template>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
