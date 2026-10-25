@php
    $gallery = (array) config('eventflow.certificates.gallery', []);
    $px = (array) config('eventflow.certificates.px', []);
    $contact = $contact ?: config('plans.brand.contact_email');
    $selected = old('template', $settings->template);
    $orientation = old('orientation', $settings->orientation);
    $busy = in_array($batch['state'] ?? null, ['queued', 'running'], true);
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('Certificates') }} · {{ $event->name }}</x-slot>
    <x-slot name="header">
        <div class="min-w-0">
            <h2>{{ __('Certificates') }}</h2>
            <p class="mt-1 truncate text-sm text-slate-500">{{ $event->name }}</p>
        </div>
        @if (Route::has('reports.race-sheet'))
            <a href="{{ route('reports.race-sheet', $event) }}" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">{{ __('Race sheet') }}</a>
        @endif
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:px-6 lg:px-10">
        @if (session('status'))
            <p class="rounded-xl bg-green-50 p-4 text-sm font-medium text-green-800" role="status">{{ session('status') }}</p>
        @endif

        {{-- Generate --}}
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 sm:p-8">
            <p class="text-sm text-slate-600">{{ __('One print-ready A4 PDF per checked-in runner: name, BIB, event, distance, time (if recorded), date and your logo.') }}</p>

            <dl class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">{{ __('Checked-in runners') }}</dt>
                    <dd class="mt-1 text-2xl font-bold">{{ number_format($eligibleCount) }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">{{ __('Design') }}</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $settings->isUpload() ? __('Uploaded background') : __($gallery[$settings->template]['label'] ?? ucfirst($settings->template)) }}
                        &middot; {{ __(ucfirst($settings->orientation)) }}
                    </dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">{{ __('Last batch') }}</dt>
                    <dd class="mt-1 font-semibold">
                        @switch($batch['state'] ?? null)
                            @case('queued') {{ __('Queued') }} @break
                            @case('running') {{ __('Generating…') }} @break
                            @case('ready') {{ trans_choice(':count certificate ready|:count certificates ready', $batch['count'] ?? 0) }} @break
                            @case('failed') {{ __('Failed') }} @break
                            @default {{ __('Not generated yet') }}
                        @endswitch
                    </dd>
                </div>
            </dl>

            @if (($batch['state'] ?? null) === 'failed')
                <p class="mt-6 rounded-xl bg-red-50 p-4 text-sm font-medium text-red-700">{{ $batch['message'] ?? __('Certificate generation failed.') }}</p>
            @endif

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('reports.certificates.generate', $event) }}">
                    @csrf
                    <button type="submit" @disabled($eligibleCount === 0 || $busy) class="rounded-xl bg-[#0b1020] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#1f2a4a] disabled:cursor-not-allowed disabled:opacity-40">
                        {{ __('Generate certificates') }}
                    </button>
                </form>
                @if ($zipReady)
                    <a href="{{ route('reports.certificates.download', $event) }}" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">{{ __('Download ZIP') }}</a>
                @endif
            </div>
        </section>

        {{-- Design --}}
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 sm:p-8" x-data="{ template: @js($selected), orientation: @js($orientation), orientations: @js(collect($gallery)->map(fn ($t) => $t['orientations'] ?? ['landscape'])->put('upload', ['landscape', 'portrait'])) }"
                 x-effect="if (! orientations[template].includes(orientation)) orientation = orientations[template][0]">
            <h3 class="text-lg font-bold">{{ __('Design') }}</h3>
            <p class="mt-1 text-sm text-slate-600">{{ __('Pick a template. Your event colours and logo are applied automatically.') }}</p>

            <form method="POST" action="{{ route('reports.certificates.design', $event) }}" enctype="multipart/form-data" class="mt-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($gallery as $key => $t)
                        <label class="group relative cursor-pointer overflow-hidden rounded-2xl ring-1 transition"
                               :class="template === @js($key) ? 'ring-2 ring-[#0b1020]' : 'ring-slate-200 hover:ring-slate-400'">
                            <input type="radio" name="template" value="{{ $key }}" x-model="template" class="sr-only">
                            <div class="flex aspect-[297/210] items-center justify-center bg-slate-100 p-3">
                                <img src="{{ asset("images/certificates/{$key}-landscape.png") }}" alt="{{ __(':name certificate preview', ['name' => $t['label']]) }}" loading="lazy" class="max-h-full rounded shadow-md">
                            </div>
                            <div class="flex items-start justify-between gap-3 p-4">
                                <div>
                                    <p class="font-semibold">{{ __($t['label']) }}</p>
                                    <p class="mt-0.5 text-sm text-slate-500">{{ __($t['description']) }}</p>
                                </div>
                                <a href="{{ route('reports.certificates.preview', [$event, $key]) }}" target="_blank" class="shrink-0 text-sm font-medium text-slate-600 underline hover:text-slate-900" @click.stop>{{ __('PDF') }}</a>
                            </div>
                        </label>
                    @endforeach

                    <label class="relative flex cursor-pointer flex-col overflow-hidden rounded-2xl ring-1 transition"
                           :class="template === 'upload' ? 'ring-2 ring-[#0b1020]' : 'ring-slate-200 hover:ring-slate-400'">
                        <input type="radio" name="template" value="upload" x-model="template" class="sr-only">
                        <div class="flex aspect-[297/210] flex-col items-center justify-center gap-2 border-b border-dashed border-slate-300 bg-slate-50 p-4 text-center">
                            <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0-4 4m4-4 4 4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                            <p class="text-sm font-medium text-slate-600">{{ __('Your own A4 artwork') }}</p>
                        </div>
                        <div class="p-4">
                            <p class="font-semibold">{{ __('Upload your own') }}</p>
                            <p class="mt-0.5 text-sm text-slate-500">{{ __('Full-bleed background; we print the runner details on top.') }}</p>
                        </div>
                    </label>
                </div>
                <x-input-error :messages="$errors->get('template')" />

                <div>
                    <p class="text-sm font-medium text-slate-700">{{ __('Orientation') }}</p>
                    <div class="mt-2 inline-flex rounded-xl bg-slate-100 p-1 text-sm">
                        @foreach (['landscape' => __('Landscape'), 'portrait' => __('Portrait')] as $value => $label)
                            <label class="cursor-pointer rounded-lg px-4 py-1.5 font-medium"
                                   :class="{ 'bg-white shadow-sm': orientation === @js($value), 'cursor-not-allowed opacity-40': ! orientations[template].includes(@js($value)) }">
                                <input type="radio" name="orientation" value="{{ $value }}" x-model="orientation" class="sr-only" :disabled="! orientations[template].includes(@js($value))">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('orientation')" />
                </div>

                <div x-show="template === 'upload'" x-cloak class="rounded-2xl bg-slate-50 p-5">
                    <label for="background" class="text-sm font-medium text-slate-700">{{ __('A4 background (PNG or JPG)') }}</label>
                    <input id="background" name="background" type="file" accept=".png,.jpg,.jpeg,image/png,image/jpeg"
                           class="mt-2 block w-full text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-[#0b1020] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white">
                    <p class="mt-2 text-sm text-slate-600">
                        {{ __('Exactly :lw × :lh px for landscape or :pw × :ph px for portrait (A4 at 300 dpi). Export PDF artwork as PNG.', ['lw' => $px['landscape'][0] ?? 3508, 'lh' => $px['landscape'][1] ?? 2480, 'pw' => $px['portrait'][0] ?? 2480, 'ph' => $px['portrait'][1] ?? 3508]) }}
                        {{ __('Keep the centre and the two lower corners clear: the name, event, distance, time, date, BIB and logo are printed there.') }}
                    </p>
                    @if ($settings->isUpload())
                        <p class="mt-2 text-sm text-slate-500">{{ __('Current background: :file. Leave empty to keep it.', ['file' => basename((string) $settings->background)]) }}</p>
                    @endif
                    <x-input-error class="mt-2" :messages="$errors->get('background')" />
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-6">
                    <p class="text-sm text-slate-600">
                        {{ __('Need a custom certificate design?') }}
                        @if ($contact)
                            <a href="mailto:{{ $contact }}" class="font-semibold text-slate-900 underline">{{ __('Contact Indefine') }}</a>
                        @else
                            <span class="font-semibold text-slate-900">{{ __('Contact Indefine') }}</span>
                        @endif
                    </p>
                    <button type="submit" class="rounded-xl bg-[#0b1020] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#1f2a4a]">{{ __('Save design') }}</button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
