{{--
    Certificates (Daylight Track, docs/design/daylight-track/Certificates.dc.html): status tiles, design list on
    the left, live A4 preview of the selected design on the right with its options, then the participant list.
--}}
@php
    $gallery = (array) config('eventflow.certificates.gallery', []);
    $palettes = (array) config('eventflow.certificates.palettes', []);
    $credentials = (array) config('eventflow.certificates.credentials', []);
    $px = (array) config('eventflow.certificates.px', []);
    $selected = old('template', $settings->template);
    $orientation = old('orientation', $settings->orientation);
    $palette = old('palette', $settings->palette ?? (($event->colours['primary'] ?? null) ? 'brand' : 'gold'));
    $credential = old('credential', $settings->credential ?? 'completion');
    $variant = old('variant', $settings->variant) === 'print';
    $signatories = old('signatories', array_pad($settings->signatories ?: (array) config('eventflow.certificates.default_signatories'), 2, ['name' => null, 'role' => null]));
    $busy = in_array($batch['state'] ?? null, ['queued', 'running'], true);
    $certsUnlocked = \App\Saas\EventAddons::unlocked($event, \App\Enums\Module::Certificates);
    $swatch = fn (string $key) => match ($key) {
        'brand' => [$event->colours['primary'] ?? '#1d3b8b', $event->colours['secondary'] ?? '#e8553f'],
        default => [$palettes[$key]['accent'], $palettes[$key]['accent2']],
    };
    $previewBase = route('reports.certificates.preview', [$event, '__T__']);
    $thumbBase = route('certificates.thumbnail', '__T__');
    $labels = collect($gallery)->map(fn ($t) => __($t['label']))->put('upload', __('Your own artwork'));
    $tile = 'rounded-[22px] bg-white p-5';
    $seg = 'inline-flex min-h-[40px] cursor-pointer items-center rounded-full px-4 text-sm font-semibold transition duration-200 ease-track has-[:focus-visible]:outline has-[:focus-visible]:outline-[3px] has-[:focus-visible]:outline-[#F2A93B]';
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('Certificates') }} · {{ $event->name }}</x-slot>
    <x-slot name="header">
        <div class="min-w-0">
            <p class="truncate text-sm text-muted">{{ $event->name }}@if ($event->date), {{ $event->date->timezone('Asia/Kolkata')->format('D j M Y') }}@endif</p>
            <h2 class="mt-1">{{ __('Certificates') }}</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            @if (array_key_exists($settings->template, $gallery))
                <x-ri.button variant="secondary" size="sm" :href="route('reports.certificates.preview', [$event, $settings->template, 'download' => 1])">{{ __('Download sample') }}</x-ri.button>
            @endif
            <form method="POST" action="{{ route('reports.certificates.generate', $event) }}">
                @csrf
                <x-ri.button type="submit" variant="primary" size="sm" :disabled="$eligibleCount === 0 || $busy || ! $certsUnlocked" :title="$certsUnlocked ? null : __('Locked: add the certificates add-on')">
                    {{ $eligibleCount > 0 ? trans_choice('Generate :count certificate|Generate :count certificates', $eligibleCount, ['count' => number_format($eligibleCount)]) : __('Generate certificates') }}
                </x-ri.button>
            </form>
        </div>
    </x-slot>

    <div class="mx-auto max-w-[1180px] space-y-5 px-4 pb-12 pt-4 sm:px-6 lg:px-10">
        @include('saas.addon-lock', ['addonKey' => 'certificates'])

        @if (session('status'))
            <p class="rounded-[18px] bg-[#E3F4EE] px-4 py-3 text-sm text-[#0B4A38]" role="status">{{ session('status') }}</p>
        @endif

        @if (($batch['state'] ?? null) === 'failed')
            <p class="rounded-[18px] bg-[#FBE4E4] px-4 py-3 text-sm text-[#6E1414]" role="alert">{{ $batch['message'] ?? __('Certificate generation failed.') }}</p>
        @endif

        {{-- Status --}}
        <section aria-label="{{ __('Status') }}" class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="{{ $tile }}">
                <p class="text-sm text-muted">{{ __('Checked in') }}</p>
                <p class="ri-headline mt-1.5 text-[30px]">{{ number_format($eligibleCount) }}</p>
            </div>
            <div class="{{ $tile }}">
                <p class="text-sm text-muted">{{ __('Issued') }}</p>
                <p class="ri-headline mt-1.5 text-[30px]">{{ number_format($issuedCount) }}</p>
            </div>
            <div class="{{ $tile }} col-span-2 sm:col-span-1">
                <p class="text-sm text-muted">{{ __('Last ZIP') }}</p>
                <p class="mt-2.5 text-[15px] font-semibold">
                    @switch($batch['state'] ?? null)
                        @case('queued') {{ __('Queued') }} @break
                        @case('running') {{ __('Generating…') }} @break
                        @case('ready') {{ trans_choice(':count certificate ready|:count certificates ready', $batch['count'] ?? 0) }} @break
                        @case('failed') {{ __('Failed') }} @break
                        @default {{ __('Not generated yet') }}
                    @endswitch
                </p>
                @if ($zipReady && $certsUnlocked)
                    <a href="{{ route('reports.certificates.download', $event) }}" class="mt-1 inline-flex min-h-[44px] items-center text-sm font-semibold text-track hover:text-track-deep">{{ __('Download ZIP') }}</a>
                @endif
            </div>
        </section>

        {{-- Design + preview --}}
        <form method="POST" action="{{ route('reports.certificates.design', $event) }}" enctype="multipart/form-data"
              class="flex flex-wrap items-start gap-5"
              x-data="{
                  template: @js($selected), orientation: @js($orientation), palette: @js($palette), credential: @js($credential), variant: @js($variant),
                  orientations: @js(collect($gallery)->map(fn ($t) => $t['orientations'] ?? ['landscape'])->put('upload', ['landscape', 'portrait'])),
                  labels: @js($labels),
                  previewUrl() {
                      const q = new URLSearchParams({ orientation: this.orientation, palette: this.palette, credential: this.credential });
                      if (this.template === 'midnight-elite' && this.variant) q.set('variant', 'print');
                      return @js($previewBase).replace('__T__', this.template) + '?' + q;
                  },
              }"
              x-effect="if (! orientations[template].includes(orientation)) orientation = orientations[template][0]">
            @csrf
            @method('PUT')

            <section class="flex min-w-0 flex-[1_1_300px] flex-col gap-2.5 rounded-[28px] bg-white p-[22px]">
                <h3 class="mb-1.5 text-lg font-bold">{{ __('Design') }}</h3>
                <p class="-mt-1 mb-1 text-sm text-muted">{{ __('Five print-ready designs. Your logo, colours and participant details are filled in automatically.') }}</p>

                @foreach ($gallery as $key => $t)
                    <label class="flex min-h-[44px] cursor-pointer items-center gap-3 rounded-2xl border-2 border-[#EEF0F6] bg-white p-2.5 text-ink transition duration-200 ease-track hover:border-mist has-[:checked]:border-track has-[:focus-visible]:outline has-[:focus-visible]:outline-[3px] has-[:focus-visible]:outline-[#F2A93B]">
                        <input type="radio" name="template" value="{{ $key }}" x-model="template" class="sr-only" @checked($selected === $key)>
                        <img src="{{ route('certificates.thumbnail', $key) }}" alt="" loading="lazy" class="h-[42px] w-[60px] flex-none rounded-lg object-cover ring-1 ring-mist">
                        <span class="min-w-0">
                            <span class="block text-[15px] font-semibold">{{ __($t['label']) }}</span>
                            <span class="block text-[13px] leading-snug text-muted">{{ __($t['description']) }}</span>
                        </span>
                    </label>
                @endforeach

                <label class="mt-1.5 flex cursor-pointer flex-col gap-1 rounded-2xl border-2 border-dashed border-[#C5CBDB] p-4 transition duration-200 ease-track hover:border-[#9AA2BA] has-[:checked]:border-solid has-[:checked]:border-track has-[:focus-visible]:outline has-[:focus-visible]:outline-[3px] has-[:focus-visible]:outline-[#F2A93B]">
                    <input type="radio" name="template" value="upload" x-model="template" class="sr-only" @checked($selected === 'upload')>
                    <strong class="text-[15px]">{{ __('Upload your own') }}</strong>
                    <span class="text-[13px] text-muted">{{ __('Full-bleed A4 artwork. We print the name, details and QR on top.') }}</span>
                </label>
                <x-input-error :messages="$errors->get('template')" />

                <p class="mt-2 text-sm text-muted">
                    {{ __('Need a custom certificate design?') }}
                    <a href="{{ \App\Support\Contact::customDesign(__('Custom certificate design for :event', ['event' => $event->name])) }}" class="font-semibold text-track underline-offset-4 hover:text-track-deep hover:underline" data-contact-indefine>{{ __('Contact Indefine') }}</a>
                </p>
            </section>

            <div class="flex min-w-0 flex-[3_1_560px] flex-col gap-5">
                <section class="flex flex-col gap-4 rounded-[28px] bg-white p-[22px]">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-lg font-bold">{{ __('Preview') }}</h3>
                        <span class="text-[13px] text-muted">
                            <span x-text="labels[template]">{{ $labels[$selected] ?? '' }}</span>,
                            <span x-text="orientation === 'portrait' ? @js(__('A4 portrait')) : @js(__('A4 landscape'))">{{ $orientation === 'portrait' ? __('A4 portrait') : __('A4 landscape') }}</span>
                        </span>
                    </div>

                    <div class="flex justify-center rounded-[20px] bg-[#E9ECF4] p-4 sm:p-7">
                        <img x-show="template !== 'upload'" src="{{ array_key_exists($selected, $gallery) ? route('certificates.thumbnail', $selected) : '' }}"
                             :src="template !== 'upload' ? @js($thumbBase).replace('__T__', template) : ''"
                             :alt="labels[template] + ' ' + @js(__('certificate'))" alt="{{ ($labels[$selected] ?? '') }} {{ __('certificate') }}"
                             class="ri-float aspect-[297/210] w-full max-w-[680px] bg-white object-contain" @if ($selected === 'upload') style="display: none" @endif>
                        <div x-show="template === 'upload'" @if ($selected !== 'upload') style="display: none" @endif
                             class="flex aspect-[297/210] w-full max-w-[680px] flex-col items-center justify-center gap-2 border-2 border-dashed border-[#C5CBDB] bg-white p-6 text-center">
                            <svg class="h-8 w-8 text-[#8A92AB]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0-4 4m4-4 4 4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                            <p class="text-sm font-semibold">{{ __('Your own A4 artwork') }}</p>
                            <p class="max-w-sm text-[13px] text-muted">{{ $settings->isUpload() ? __('Current background: :file.', ['file' => basename((string) $settings->background)]) : __('Upload a background below, then save.') }}</p>
                        </div>
                    </div>

                    <p x-show="template !== 'upload'" class="text-sm text-muted">
                        {{ __('The thumbnail shows the layout. For the exact page with your colours, wording and logo:') }}
                        <a href="{{ array_key_exists($selected, $gallery) ? route('reports.certificates.preview', [$event, $selected]) : '#' }}" :href="previewUrl()" target="_blank" rel="noopener" class="font-semibold text-track underline-offset-4 hover:text-track-deep hover:underline">{{ __('Open the full A4 preview') }}</a>
                    </p>
                </section>

                <section class="space-y-6 rounded-[28px] bg-white p-[22px]">
                    <h3 class="text-lg font-bold">{{ __('Options') }}</h3>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="space-y-6">
                            <div>
                                <p class="ri-label">{{ __('Orientation') }}</p>
                                <div class="mt-2 inline-flex rounded-full bg-paper p-1 ring-1 ring-inset ring-mist" role="radiogroup" aria-label="{{ __('Orientation') }}">
                                    @foreach (['landscape' => __('Landscape'), 'portrait' => __('Portrait')] as $value => $label)
                                        <label class="{{ $seg }}"
                                               :class="{ 'bg-white text-ink shadow-sm': orientation === @js($value), 'text-muted': orientation !== @js($value), '!cursor-not-allowed opacity-40': ! orientations[template].includes(@js($value)) }">
                                            <input type="radio" name="orientation" value="{{ $value }}" x-model="orientation" class="sr-only" @checked($orientation === $value) :disabled="! orientations[template].includes(@js($value))">
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <fieldset x-show="template !== 'upload'">
                                <legend class="ri-label">{{ __('Colours') }}</legend>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($palettes as $key => $p)
                                        <label class="flex min-h-[44px] cursor-pointer items-center gap-2 rounded-full bg-white px-3 text-sm font-medium ring-1 ring-inset ring-mist has-[:checked]:bg-lilac has-[:checked]:text-track-deep has-[:checked]:ring-2 has-[:checked]:ring-track has-[:focus-visible]:outline has-[:focus-visible]:outline-[3px] has-[:focus-visible]:outline-[#F2A93B]">
                                            <input type="radio" name="palette" value="{{ $key }}" x-model="palette" class="sr-only" @checked($palette === $key)>
                                            <span class="flex" aria-hidden="true">@foreach ($swatch($key) as $hex)<span class="-ml-1 h-4 w-4 rounded-full ring-2 ring-white first:ml-0" style="background: {{ $hex }}"></span>@endforeach</span>
                                            {{ __($p['label']) }}
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>

                            <div x-show="template !== 'upload'">
                                <label for="credential" class="ri-label">{{ __('Credential line') }}</label>
                                <select id="credential" name="credential" x-model="credential" class="ri-input min-h-[44px] text-sm">
                                    @foreach ($credentials as $key => $label)
                                        <option value="{{ $key }}" @selected($credential === $key)>{{ __($label) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <label x-show="template === 'midnight-elite'" x-cloak class="flex cursor-pointer items-start gap-3 rounded-[18px] bg-paper p-4 text-sm">
                                <input type="checkbox" name="variant" value="print" x-model="variant" class="mt-0.5 h-5 w-5 rounded border-[#C5CBDB] text-track focus:ring-track" @checked($variant)>
                                <span><span class="font-semibold">{{ __('Print-safe light version') }}</span><br><span class="text-muted">{{ __('Ivory field instead of navy. Use it for office printers; keep the dark version for screens and professional print.') }}</span></span>
                            </label>
                        </div>

                        <div class="space-y-6" x-show="template !== 'upload'">
                            <fieldset>
                                <legend class="ri-label">{{ __('Signatures') }}</legend>
                                <p class="text-[13px] text-muted">{{ __('Leave a name empty to print a blank line for a wet signature.') }}</p>
                                <div class="mt-2 space-y-3">
                                    @foreach ([0, 1] as $i)
                                        <div class="grid grid-cols-2 gap-2">
                                            <input type="text" name="signatories[{{ $i }}][name]" value="{{ $signatories[$i]['name'] ?? '' }}" maxlength="80" placeholder="{{ __('Name') }}" class="ri-input !mt-0 min-h-[44px] text-sm" aria-label="{{ __('Signature :n name', ['n' => $i + 1]) }}">
                                            <input type="text" name="signatories[{{ $i }}][role]" value="{{ $signatories[$i]['role'] ?? '' }}" maxlength="60" placeholder="{{ $i === 0 ? __('Race Director') : __('Chief Guest') }}" class="ri-input !mt-0 min-h-[44px] text-sm" aria-label="{{ __('Signature :n role', ['n' => $i + 1]) }}">
                                        </div>
                                    @endforeach
                                </div>
                                <x-input-error class="mt-2" :messages="$errors->get('signatories.*')" />
                            </fieldset>

                            <label class="flex min-h-[44px] cursor-pointer items-center gap-3 text-sm">
                                <input type="hidden" name="stamp" value="0">
                                <input type="checkbox" name="stamp" value="1" class="h-5 w-5 rounded border-[#C5CBDB] text-track focus:ring-track" @checked(old('stamp', $settings->stamp ?? true))>
                                <span class="font-semibold">{{ __('Show the round organiser stamp / seal') }}</span>
                            </label>
                        </div>
                    </div>

                    <div x-show="template === 'upload'" x-cloak class="rounded-[18px] bg-paper p-5">
                        <label for="background" class="ri-label">{{ __('A4 background (PNG or JPG)') }}</label>
                        <input id="background" name="background" type="file" accept=".png,.jpg,.jpeg,image/png,image/jpeg"
                               class="mt-2 block w-full text-sm text-muted file:mr-4 file:min-h-[44px] file:rounded-full file:border-0 file:bg-ink file:px-5 file:text-sm file:font-semibold file:text-white">
                        <p class="mt-2 text-sm text-muted">
                            {{ __('Exactly :lw × :lh px for landscape or :pw × :ph px for portrait (A4 at 300 dpi). Export PDF artwork as PNG.', ['lw' => $px['landscape'][0] ?? 3508, 'lh' => $px['landscape'][1] ?? 2480, 'pw' => $px['portrait'][0] ?? 2480, 'ph' => $px['portrait'][1] ?? 3508]) }}
                            {{ __('Keep the centre, the lower corners and the bottom-right QR spot clear.') }}
                        </p>
                        @if ($settings->isUpload())
                            <p class="mt-2 text-sm text-muted">{{ __('Current background: :file. Leave empty to keep it.', ['file' => basename((string) $settings->background)]) }}</p>
                        @endif
                        <x-input-error class="mt-2" :messages="$errors->get('background')" />
                    </div>

                    <div class="flex justify-end border-t border-[#EEF0F6] pt-5">
                        <x-ri.button type="submit" variant="ink" size="md">{{ __('Save design') }}</x-ri.button>
                    </div>
                </section>
            </div>
        </form>

        {{-- Participants --}}
        <section class="rounded-[28px] bg-white">
            <div class="flex flex-wrap items-center justify-between gap-4 p-[22px]">
                <div>
                    <h3 class="text-lg font-bold">{{ __('Participants') }}</h3>
                    <p class="mt-1 text-sm text-muted">{{ __('Checked-in participants. Download issues the certificate if needed; re-issue renders it again with the same number.') }}</p>
                </div>
                <form method="GET" class="w-full sm:w-64" role="search">
                    <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('Search name or pass') }}" class="min-h-[44px] w-full rounded-full border-0 bg-white px-4 text-sm text-ink ring-1 ring-inset ring-mist focus:ring-2 focus:ring-track" aria-label="{{ __('Search participants') }}">
                </form>
            </div>

            @if ($participants->isEmpty())
                <p class="border-t border-[#EEF0F6] px-[22px] py-10 text-center text-sm text-muted">
                    {{ $search !== '' ? __('No checked-in participant matches “:q”.', ['q' => $search]) : __('Certificates appear here once participants are checked in.') }}
                </p>
            @else
                <ul class="divide-y divide-[#EEF0F6] border-t border-[#EEF0F6]">
                    @foreach ($participants as $r)
                        @php
                            $cert = $issued[$r->id] ?? null;
                        @endphp
                        <li class="flex flex-wrap items-center justify-between gap-3 px-[22px] py-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold">{{ $r->participant_name }}</p>
                                <p class="text-sm text-muted">
                                    {{ $r->pass_no }}
                                    @if ($cert?->issued_at)
                                        &middot; <span class="font-mono">{{ $cert->cert_no }}</span> &middot; {{ __('issued :date', ['date' => $cert->issued_at->timezone('Asia/Kolkata')->format('j M, g:i A')]) }}
                                    @else
                                        &middot; {{ __('Not issued yet') }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex gap-2">
                                @if ($certsUnlocked)
                                    <x-ri.button variant="ink" size="sm" :href="route('reports.certificates.show', [$event, $r])">{{ __('Download') }}</x-ri.button>
                                    <form method="POST" action="{{ route('reports.certificates.reissue', [$event, $r]) }}">
                                        @csrf
                                        <x-ri.button type="submit" variant="secondary" size="sm">{{ $cert?->issued_at ? __('Re-issue') : __('Issue') }}</x-ri.button>
                                    </form>
                                @else
                                    <x-ri.button variant="secondary" size="sm" disabled title="{{ __('Locked: add the certificates add-on') }}">{{ __('Download') }}</x-ri.button>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($participants->hasPages())
                    <div class="border-t border-[#EEF0F6] px-[22px] py-4">{{ $participants->links() }}</div>
                @endif
            @endif
        </section>
    </div>
</x-app-layout>
