{{--
    Event → Overview (Daylight Track, docs/design/daylight-track/Overview.dc.html). Places by distance,
    the organiser's own public page in a live preview (desktop / mobile), Change template, Edit content,
    Publish / Unpublish and the event's add-ons. The preview iframe is organiser-only
    (organiser.events.preview), so drafts show too; ?template= tries a template before applying it.
--}}
@php
    $statusMessages = [
        'template-updated' => __('Template applied. The preview shows the page runners will see.'),
        'event-published' => __('Your page is live.'),
        'event-unpublished' => __('Your page is offline. Runners see a 404 until you publish again.'),
    ];
    $chip = [
        'published' => ['Live', 'live'],
        'draft' => ['Draft', 'neutral'],
        'closed' => ['Closed', 'ended'],
    ][$event->status] ?? [ucfirst($event->status), 'neutral'];
    $previewUrl = route('organiser.events.preview', $event);
    $pill = 'inline-flex min-h-[44px] items-center rounded-full px-[18px] text-sm font-semibold';
    $outline = $pill.' bg-white text-ink ring-1 ring-inset ring-mist hover:ring-ink/30';
@endphp

<x-app-layout>
    <x-slot name="title">{{ $event->name }}</x-slot>
    <x-slot name="header">
        <div class="min-w-0 space-y-2">
            <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm text-muted"><a href="{{ route('organiser.events.index') }}" class="text-muted hover:text-ink">{{ __('Events') }}</a> / {{ $event->name }}</nav>
            <h2 class="flex flex-wrap items-center gap-3 !text-[30px] sm:!text-[36px]">
                <span class="min-w-0">{{ $event->name }}</span>
                <x-ri.chip :tone="$chip[1]" class="font-sans !text-[13px] !font-bold tracking-normal" data-status-chip>{{ __($chip[0]) }}</x-ri.chip>
            </h2>
            <p class="text-[15px] text-muted">
                {{ $event->date->setTimezone(\App\Landing\ConfigCompiler::TIMEZONE)->format('D j M Y, g:i a') }}.
                @if ($live)
                    <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="break-all font-medium text-track hover:text-track-deep">{{ preg_replace('#^https?://#', '', $publicUrl) }}</a>
                @else
                    {{ __('Not live yet: only you can see this preview.') }}
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('organiser.events.edit', $event) }}" class="{{ $pill }} bg-ink text-white hover:bg-ink/90">{{ __('Edit content') }}</a>
            @if ($live)
                <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="{{ $outline }}" data-open-public>{{ __('Open public URL') }}</a>
            @endif
            <a href="{{ route('organiser.events.communications', $event) }}" class="{{ $outline }}" data-communications-link>{{ __('Communications') }}</a>
            <form method="POST" action="{{ route('organiser.events.status', $event) }}">
                @csrf
                @method('PATCH')
                @if ($live)
                    <input type="hidden" name="status" value="draft">
                    <button type="submit" class="{{ $outline }}">{{ __('Unpublish') }}</button>
                @else
                    <input type="hidden" name="status" value="published">
                    <button type="submit" class="{{ $pill }} bg-track text-white ri-glow hover:bg-track-deep">{{ __('Publish') }}</button>
                @endif
            </form>
        </div>
    </x-slot>

    <div class="mx-auto max-w-[1180px] space-y-5 px-4 pb-12 pt-4 sm:px-6 lg:px-10"
         x-data="{
            device: 'desktop',
            template: @js($template),
            saved: @js($template),
            base: @js($previewUrl),
            scale: 1,
            get src() { return this.template && this.template !== this.saved ? this.base + '?template=' + encodeURIComponent(this.template) : this.base },
            phoneScale: 1,
            fit() {
                const w = this.$refs.stage?.clientWidth || 1280; this.scale = Math.min(1, w / 1280);
                const p = this.$refs.phone?.clientWidth || 406; this.phoneScale = Math.min(1, (p - 20) / 390);
            },
         }"
         x-init="fit(); window.addEventListener('resize', () => fit())">

        @if (isset($statusMessages[session('status')]))
            <p class="rounded-[18px] bg-[#E3F4EE] px-4 py-3 text-sm font-medium text-[#0B4A38]" role="status">{{ $statusMessages[session('status')] }}</p>
        @endif
        @error('status')
            <p class="rounded-[18px] bg-[#FBE4E4] px-4 py-3 text-sm font-medium text-[#6E1414]" role="alert">{{ $message }} <a href="{{ route('account.plan') }}" class="font-semibold underline">{{ __('Plan & billing') }}</a></p>
        @enderror
        @error('template')
            <p class="rounded-[18px] bg-[#FBE4E4] px-4 py-3 text-sm font-medium text-[#6E1414]" role="alert">{{ $message }}</p>
        @enderror

        {{-- Places by distance --}}
        @if ($places)
            <section aria-label="{{ __('Places by distance') }}" class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(150px,1fr))]">
                @foreach ($places as $d)
                    <div class="rounded-[20px] bg-white p-[18px]">
                        <div class="flex justify-between gap-2 text-sm">
                            <strong>{{ $d['label'] }}</strong>
                            <span class="text-muted">{{ number_format($d['taken']) }}{{ $d['capacity'] ? ' / '.number_format($d['capacity']) : '' }}</span>
                        </div>
                        <x-ri.places-bar class="mt-2.5" :capacity="$d['capacity']" :left="$d['capacity'] ? max(0, $d['capacity'] - $d['taken']) : null" />
                    </div>
                @endforeach
            </section>
        @endif

        <div class="flex flex-wrap items-start gap-5">
            {{-- Live preview --}}
            <section class="flex min-w-0 flex-[3_1_560px] flex-col gap-4 rounded-[28px] bg-white p-4 sm:p-[22px]" aria-labelledby="preview-heading">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 id="preview-heading" class="text-lg font-bold">{{ __('Live preview') }}</h3>
                    <div class="flex rounded-full bg-paper p-1" role="group" aria-label="{{ __('Preview size') }}">
                        @foreach (['desktop' => __('Desktop'), 'mobile' => __('Mobile')] as $key => $label)
                            <button type="button" class="min-h-[40px] rounded-full px-4 text-sm font-semibold text-ink" :class="device === '{{ $key }}' ? 'bg-white shadow-sm' : ''" @click="device = '{{ $key }}'; $nextTick(() => fit())" :aria-pressed="(device === '{{ $key }}').toString()" aria-pressed="{{ $key === 'desktop' ? 'true' : 'false' }}" data-device="{{ $key }}">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="flex min-h-[300px] items-center justify-center rounded-[20px] bg-[#E9ECF4] p-3 sm:min-h-[480px] sm:p-6">
                    {{-- Desktop: a 1280px page scaled to fit. --}}
                    <div x-show="device === 'desktop'" x-ref="stage" class="ri-float relative w-full overflow-hidden rounded-2xl bg-white" :style="`height: ${Math.round(760 * scale)}px`">
                        <iframe :src="src" src="{{ $previewUrl }}" title="{{ __('Desktop preview of :event', ['event' => $event->name]) }}" loading="lazy" data-preview-frame
                                class="absolute left-0 top-0 origin-top-left border-0" style="width: 1280px; height: 760px" :style="`width: 1280px; height: 760px; transform: scale(${scale})`"></iframe>
                    </div>
                    {{-- Mobile: a real 390 x 844 phone viewport, scaled down to fit narrow screens. --}}
                    <div x-show="device === 'mobile'" x-cloak x-ref="phone" class="mx-auto w-[410px] max-w-full" data-phone-frame>
                        <div class="ri-float mx-auto rounded-[2.6rem] bg-ink p-2.5" :style="`width: ${Math.round(390 * phoneScale) + 20}px`">
                            <div class="relative overflow-hidden rounded-[2.1rem] bg-white" :style="`height: ${Math.round(844 * phoneScale)}px`">
                                <iframe x-bind:src="device === 'mobile' ? src : ''" title="{{ __('Mobile preview of :event', ['event' => $event->name]) }}"
                                        class="absolute left-0 top-0 origin-top-left border-0" :style="`width: 390px; height: 844px; transform: scale(${phoneScale})`"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="flex min-w-0 flex-[2_1_340px] flex-col gap-5">
                {{-- Change template --}}
                <section class="flex flex-col gap-3 rounded-[28px] bg-white p-[22px]">
                    <h3 class="text-lg font-bold">{{ __('Change template') }}</h3>
                    <p class="text-sm text-muted">{{ __('Pick one to try it in the preview. Your logo and colours carry over.') }}</p>
                    <form method="POST" action="{{ route('organiser.events.template', $event) }}" class="flex flex-col gap-2">
                        @csrf
                        @method('PATCH')
                        @foreach ($templates as $key => $tpl)
                            <label class="flex min-h-[44px] cursor-pointer items-center gap-3 rounded-2xl border-2 border-[#EEF0F6] bg-white p-2.5 has-[:checked]:border-track has-[:focus-visible]:outline has-[:focus-visible]:outline-[3px] has-[:focus-visible]:outline-[#F2A93B]">
                                <input type="radio" name="template" value="{{ $key }}" x-model="template" class="sr-only" @checked($template === $key)>
                                <span aria-hidden="true" class="h-10 w-[52px] flex-none rounded-[10px]" style="background: {{ $tpl['swatch'] ?? '#ECEAFF' }}"></span>
                                <span class="flex-1 text-[15px] font-semibold">{{ $tpl['label'] }}</span>
                                @if ($template === $key)
                                    <span class="text-xs font-bold text-track-deep">{{ __('Current') }}</span>
                                @else
                                    <span class="text-xs font-bold text-track-deep" x-show="template === '{{ $key }}'" x-cloak>{{ __('Trying') }}</span>
                                @endif
                            </label>
                        @endforeach
                        <button type="submit" x-show="template !== saved" x-cloak class="ri-glow mt-1 min-h-[48px] rounded-full bg-track px-4 text-[15px] font-bold text-white hover:bg-track-deep">{{ __('Apply template') }}</button>
                        <p x-show="template !== saved" x-cloak class="text-[13px] text-muted">{{ $live ? __('Applies to your live page straight away.') : __('Saved to your draft.') }}</p>
                    </form>
                    <a href="{{ route('templates.index') }}" target="_blank" class="text-sm font-semibold text-track hover:text-track-deep">{{ __('See all templates') }}</a>
                </section>

                {{-- Add-ons --}}
                <section class="flex flex-col rounded-[28px] bg-white p-[22px]" id="add-ons">
                    <h3 class="mb-2 text-lg font-bold">{{ __('Add-ons for this event') }}</h3>
                    <ul class="divide-y divide-[#EEF0F6]">
                        @foreach ($addons as $key => $row)
                            <li class="flex items-center justify-between gap-3 py-2.5 text-[15px]" data-event-addon="{{ $key }}">
                                <div>
                                    <p>{{ __($row['addon']['name']) }}</p>
                                    <p @class(['text-[13px]', 'font-semibold text-success' => $row['unlocked'], 'text-muted' => ! $row['unlocked']])>
                                        @if ($row['via'] === 'plan')
                                            {{ __('Included in your plan') }}
                                        @elseif ($row['via'] === 'addon')
                                            {{ __('Added to this event') }}
                                        @else
                                            {{ \App\Saas\Plans::inr($row['addon']['price_inr']) }} · {{ __('locked') }}
                                        @endif
                                    </p>
                                </div>
                                @if ($row['unlocked'])
                                    <a href="{{ match ($key) { 'race_sheet' => route('reports.race-sheet', $event), 'certificates' => route('reports.certificates', $event), default => route('settings.domain.show', ['event' => $event->id]) } }}" class="shrink-0 text-sm font-semibold text-track hover:text-track-deep">{{ $key === 'custom_domain' ? __('Connect') : __('Open') }}</a>
                                @else
                                    <a href="{{ \App\Saas\EventAddons::requestUrl($event, $key) }}" class="inline-flex min-h-[40px] shrink-0 items-center rounded-full bg-ink px-4 text-sm font-semibold text-white hover:bg-ink/90">{{ __('Unlock') }}</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>

                {{-- Numbers --}}
                <section class="flex items-center justify-between gap-3 rounded-[28px] bg-white p-[22px] text-[15px]">
                    <p>{!! __(':r registrations, :p paid', ['r' => '<strong>'.number_format($stats['registrations']).'</strong>', 'p' => '<strong>'.number_format($stats['paid']).'</strong>']) !!}</p>
                    <a href="{{ route('dashboard.participants', ['event' => $event->id]) }}" class="shrink-0 text-sm font-semibold text-track hover:text-track-deep">{{ __('Participants') }}</a>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
