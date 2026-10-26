@php
    $gallery = (array) config('eventflow.certificates.gallery', []);
    $palettes = (array) config('eventflow.certificates.palettes', []);
    $credentials = (array) config('eventflow.certificates.credentials', []);
    $px = (array) config('eventflow.certificates.px', []);
    $selected = old('template', $settings->template);
    $orientation = old('orientation', $settings->orientation);
    $signatories = old('signatories', array_pad($settings->signatories ?: (array) config('eventflow.certificates.default_signatories'), 2, ['name' => null, 'role' => null]));
    $busy = in_array($batch['state'] ?? null, ['queued', 'running'], true);
    $swatch = fn (string $key) => match ($key) {
        'brand' => [$event->colours['primary'] ?? '#1d3b8b', $event->colours['secondary'] ?? '#e8553f'],
        default => [$palettes[$key]['accent'], $palettes[$key]['accent2']],
    };
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
        @include('saas.addon-lock', ['addonKey' => 'certificates'])
        @php($certsUnlocked = \App\Saas\EventAddons::unlocked($event, \App\Enums\Module::Certificates))
        @if (session('status'))
            <p class="rounded-xl bg-green-50 p-4 text-sm font-medium text-green-800" role="status">{{ session('status') }}</p>
        @endif

        {{-- Overview + bulk --}}
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 sm:p-8">
            <p class="text-sm text-slate-600">{{ __('Print-ready A4 PDFs for every checked-in participant, each with a unique EF-CERT number and a QR code anyone can scan to verify it.') }}</p>

            <dl class="mt-6 grid gap-4 sm:grid-cols-4">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">{{ __('Checked-in') }}</dt>
                    <dd class="mt-1 text-2xl font-bold">{{ number_format($eligibleCount) }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">{{ __('Issued') }}</dt>
                    <dd class="mt-1 text-2xl font-bold">{{ number_format($issuedCount) }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">{{ __('Design') }}</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $settings->isUpload() ? __('Uploaded background') : __($gallery[$settings->template]['label'] ?? $settings->template) }}
                        &middot; {{ __(ucfirst($settings->orientation)) }}
                    </dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">{{ __('Last ZIP') }}</dt>
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
                    <button type="submit" @disabled($eligibleCount === 0 || $busy || ! $certsUnlocked) @unless ($certsUnlocked) title="{{ __('Locked: add the certificates add-on') }}" @endunless class="rounded-xl bg-[#0b1020] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#1f2a4a] disabled:cursor-not-allowed disabled:opacity-40">
                        {{ __('Generate certificates') }}@unless ($certsUnlocked) 🔒@endunless
                    </button>
                </form>
                @if ($zipReady && $certsUnlocked)
                    <a href="{{ route('reports.certificates.download', $event) }}" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">{{ __('Download ZIP') }}</a>
                @endif
            </div>
        </section>

        {{-- Design --}}
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 sm:p-8"
                 x-data="{ template: @js($selected), orientation: @js($orientation), orientations: @js(collect($gallery)->map(fn ($t) => $t['orientations'] ?? ['landscape'])->put('upload', ['landscape', 'portrait'])) }"
                 x-effect="if (! orientations[template].includes(orientation)) orientation = orientations[template][0]">
            <h3 class="text-lg font-bold">{{ __('Design') }}</h3>
            <p class="mt-1 text-sm text-slate-600">{{ __('Five print-ready designs. Your logo, colours and participant details are filled in automatically.') }}</p>

            <form method="POST" action="{{ route('reports.certificates.design', $event) }}" enctype="multipart/form-data" class="mt-6 space-y-8">
                @csrf
                @method('PUT')

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($gallery as $key => $t)
                        <label class="relative cursor-pointer overflow-hidden rounded-2xl bg-white ring-1 transition"
                               :class="template === @js($key) ? 'ring-2 ring-[#0b1020]' : 'ring-slate-200 hover:ring-slate-400'">
                            <input type="radio" name="template" value="{{ $key }}" x-model="template" class="sr-only">
                            <div class="flex aspect-[297/210] items-center justify-center bg-slate-100 p-3">
                                <img src="{{ route('certificates.thumbnail', $key) }}" alt="{{ __(':name certificate', ['name' => $t['label']]) }}" loading="lazy" class="max-h-full rounded-sm shadow-md">
                            </div>
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="font-semibold">{{ __($t['label']) }}</p>
                                    <a href="{{ route('reports.certificates.preview', [$event, $key, 'download' => 1]) }}" class="shrink-0 text-sm font-medium text-slate-600 underline hover:text-slate-900" @click.stop>{{ __('Download sample') }}</a>
                                </div>
                                <p class="mt-0.5 text-sm text-slate-500">{{ __($t['description']) }}</p>
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
                            <p class="mt-0.5 text-sm text-slate-500">{{ __('Full-bleed background; we print the participant details and QR on top.') }}</p>
                        </div>
                    </label>
                </div>
                <x-input-error :messages="$errors->get('template')" />

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="space-y-6">
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
                        </div>

                        <fieldset x-show="template !== 'upload'">
                            <legend class="text-sm font-medium text-slate-700">{{ __('Colours') }}</legend>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($palettes as $key => $palette)
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl px-3 py-2 text-sm ring-1 ring-slate-200 has-[:checked]:ring-2 has-[:checked]:ring-[#0b1020]">
                                        <input type="radio" name="palette" value="{{ $key }}" class="sr-only" @checked(old('palette', $settings->palette ?? (($event->colours['primary'] ?? null) ? 'brand' : 'gold')) === $key)>
                                        <span class="flex">@foreach ($swatch($key) as $hex)<span class="h-4 w-4 rounded-full ring-2 ring-white first:ml-0 -ml-1" style="background: {{ $hex }}"></span>@endforeach</span>
                                        {{ __($palette['label']) }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <div x-show="template !== 'upload'">
                            <label for="credential" class="text-sm font-medium text-slate-700">{{ __('Credential line') }}</label>
                            <select id="credential" name="credential" class="mt-2 block w-full rounded-xl border-slate-300 text-sm">
                                @foreach ($credentials as $key => $label)
                                    <option value="{{ $key }}" @selected(old('credential', $settings->credential ?? 'completion') === $key)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <label x-show="template === 'midnight-elite'" x-cloak class="flex items-start gap-3 rounded-xl bg-slate-50 p-4 text-sm">
                            <input type="checkbox" name="variant" value="print" class="mt-0.5 rounded border-slate-300" @checked(old('variant', $settings->variant) === 'print')>
                            <span><span class="font-medium">{{ __('Print-safe light version') }}</span><br><span class="text-slate-500">{{ __('Ivory field instead of navy. Use it for office printers; keep the dark version for screens and professional print.') }}</span></span>
                        </label>
                    </div>

                    <div class="space-y-6" x-show="template !== 'upload'">
                        <fieldset>
                            <legend class="text-sm font-medium text-slate-700">{{ __('Signatures') }}</legend>
                            <p class="text-xs text-slate-500">{{ __('Leave a name empty to print a blank line for a wet signature.') }}</p>
                            <div class="mt-2 space-y-3">
                                @foreach ([0, 1] as $i)
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="text" name="signatories[{{ $i }}][name]" value="{{ $signatories[$i]['name'] ?? '' }}" maxlength="80" placeholder="{{ __('Name') }}" class="rounded-xl border-slate-300 text-sm" aria-label="{{ __('Signature :n name', ['n' => $i + 1]) }}">
                                        <input type="text" name="signatories[{{ $i }}][role]" value="{{ $signatories[$i]['role'] ?? '' }}" maxlength="60" placeholder="{{ $i === 0 ? __('Race Director') : __('Chief Guest') }}" class="rounded-xl border-slate-300 text-sm" aria-label="{{ __('Signature :n role', ['n' => $i + 1]) }}">
                                    </div>
                                @endforeach
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('signatories.*')" />
                        </fieldset>

                        <label class="flex items-center gap-3 text-sm">
                            <input type="hidden" name="stamp" value="0">
                            <input type="checkbox" name="stamp" value="1" class="rounded border-slate-300" @checked(old('stamp', $settings->stamp ?? true))>
                            <span class="font-medium">{{ __('Show the round organiser stamp / seal') }}</span>
                        </label>
                    </div>
                </div>

                <div x-show="template === 'upload'" x-cloak class="rounded-2xl bg-slate-50 p-5">
                    <label for="background" class="text-sm font-medium text-slate-700">{{ __('A4 background (PNG or JPG)') }}</label>
                    <input id="background" name="background" type="file" accept=".png,.jpg,.jpeg,image/png,image/jpeg"
                           class="mt-2 block w-full text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-[#0b1020] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white">
                    <p class="mt-2 text-sm text-slate-600">
                        {{ __('Exactly :lw × :lh px for landscape or :pw × :ph px for portrait (A4 at 300 dpi). Export PDF artwork as PNG.', ['lw' => $px['landscape'][0] ?? 3508, 'lh' => $px['landscape'][1] ?? 2480, 'pw' => $px['portrait'][0] ?? 2480, 'ph' => $px['portrait'][1] ?? 3508]) }}
                        {{ __('Keep the centre, the lower corners and the bottom-right QR spot clear.') }}
                    </p>
                    @if ($settings->isUpload())
                        <p class="mt-2 text-sm text-slate-500">{{ __('Current background: :file. Leave empty to keep it.', ['file' => basename((string) $settings->background)]) }}</p>
                    @endif
                    <x-input-error class="mt-2" :messages="$errors->get('background')" />
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-6">
                    <p class="text-sm text-slate-600">
                        {{ __('Need a custom certificate design?') }}
                        <a href="{{ \App\Support\Contact::customDesign(__('Custom certificate design for :event', ['event' => $event->name])) }}" class="font-semibold text-slate-900 underline" data-contact-indefine>{{ __('Contact Indefine') }}</a>
                    </p>
                    <button type="submit" class="rounded-xl bg-[#0b1020] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#1f2a4a]">{{ __('Save design') }}</button>
                </div>
            </form>
        </section>

        {{-- Participants --}}
        <section class="rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70">
            <div class="flex flex-wrap items-center justify-between gap-4 p-6 sm:px-8">
                <div>
                    <h3 class="text-lg font-bold">{{ __('Participants') }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ __('Checked-in participants. Download issues the certificate if needed; re-issue renders it again with the same number.') }}</p>
                </div>
                <form method="GET" class="w-full sm:w-64">
                    <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('Search name or pass') }}" class="w-full rounded-xl border-slate-300 text-sm" aria-label="{{ __('Search participants') }}">
                </form>
            </div>

            @if ($participants->isEmpty())
                <p class="border-t border-slate-100 px-6 py-10 text-center text-sm text-slate-500 sm:px-8">
                    {{ $search !== '' ? __('No checked-in participant matches “:q”.', ['q' => $search]) : __('Certificates appear here once participants are checked in.') }}
                </p>
            @else
                <ul class="divide-y divide-slate-100 border-t border-slate-100">
                    @foreach ($participants as $r)
                        @php($cert = $issued[$r->id] ?? null)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 sm:px-8">
                            <div class="min-w-0">
                                <p class="truncate font-semibold">{{ $r->participant_name }}</p>
                                <p class="text-sm text-slate-500">
                                    {{ $r->pass_no }}
                                    @if ($cert?->issued_at)
                                        &middot; <span class="font-mono">{{ $cert->cert_no }}</span> &middot; {{ __('issued :date', ['date' => $cert->issued_at->timezone('Asia/Kolkata')->format('j M, g:i A')]) }}
                                    @else
                                        &middot; {{ __('Not issued yet') }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex gap-2 text-sm">
                                @if ($certsUnlocked)
                                <a href="{{ route('reports.certificates.show', [$event, $r]) }}" class="rounded-lg bg-[#0b1020] px-3 py-1.5 font-medium text-white hover:bg-[#1f2a4a]">{{ __('Download') }}</a>
                                <form method="POST" action="{{ route('reports.certificates.reissue', [$event, $r]) }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg px-3 py-1.5 font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">{{ $cert?->issued_at ? __('Re-issue') : __('Issue') }}</button>
                                </form>
                                @else
                                    <span class="rounded-lg px-3 py-1.5 font-medium text-slate-400 ring-1 ring-slate-200" title="{{ __('Locked: add the certificates add-on') }}">{{ __('Download') }} 🔒</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($participants->hasPages())
                    <div class="border-t border-slate-100 px-6 py-4 sm:px-8">{{ $participants->links() }}</div>
                @endif
            @endif
        </section>
    </div>
</x-app-layout>
