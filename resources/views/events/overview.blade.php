{{--
    Event → Overview (Session UX-H2). The organiser's own public page in a live preview
    (desktop / mobile), Open public URL, Change template, Edit content, Publish / Unpublish,
    and the event's add-ons. The preview iframe is organiser-only (organiser.events.preview),
    so drafts show too; ?template= tries a template before applying it.
--}}
@php
    $statusMessages = [
        'template-updated' => __('Template applied. The preview shows the page runners will see.'),
        'event-published' => __('Your page is live.'),
        'event-unpublished' => __('Your page is offline. Runners see a 404 until you publish again.'),
    ];
    $chip = [
        'published' => ['Live', 'bg-emerald-100 text-emerald-800'],
        'draft' => ['Draft', 'bg-slate-100 text-slate-700'],
        'closed' => ['Closed', 'bg-amber-100 text-amber-800'],
    ][$event->status] ?? [ucfirst($event->status), 'bg-slate-100 text-slate-700'];
    $previewUrl = route('organiser.events.preview', $event);
@endphp

<x-app-layout>
    <x-slot name="title">{{ $event->name }}</x-slot>
    <x-slot name="header">
        <div class="min-w-0">
            <p class="text-sm text-slate-500">{{ __('Event overview') }}</p>
            <h2 class="flex flex-wrap items-center gap-3">
                <span class="truncate">{{ $event->name }}</span>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $chip[1] }}" data-status-chip>{{ __($chip[0]) }}</span>
            </h2>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($live)
                <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-[#0b1020] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#1f2a4a]" data-open-public>{{ __('Open public URL') }} ↗</a>
            @endif
            <a href="{{ route('organiser.events.edit', $event) }}" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">{{ __('Edit content') }}</a>
            <a href="{{ route('organiser.events.communications', $event) }}" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50" data-communications-link>{{ __('Communications') }}</a>
            <form method="POST" action="{{ route('organiser.events.status', $event) }}">
                @csrf
                @method('PATCH')
                @if ($live)
                    <input type="hidden" name="status" value="draft">
                    <button type="submit" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">{{ __('Unpublish') }}</button>
                @else
                    <input type="hidden" name="status" value="published">
                    <button type="submit" class="rounded-xl bg-heat px-4 py-2.5 text-sm font-semibold text-on-heat hover:brightness-95">{{ __('Publish') }}</button>
                @endif
            </form>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-10"
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
            <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200" role="status">{{ $statusMessages[session('status')] }}</p>
        @endif
        @error('status')
            <p class="rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-800 ring-1 ring-red-200" role="alert">{{ $message }} <a href="{{ route('account.plan') }}" class="font-semibold underline">{{ __('Plan & billing') }}</a></p>
        @enderror
        @error('template')
            <p class="rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-800 ring-1 ring-red-200" role="alert">{{ $message }}</p>
        @enderror

        <div class="grid items-start gap-6 xl:grid-cols-[1fr_20rem]">
            {{-- Live preview --}}
            <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70" aria-labelledby="preview-heading">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3">
                    <div class="min-w-0">
                        <h3 id="preview-heading" class="text-sm font-semibold">{{ __('Live preview') }}</h3>
                        <p class="truncate text-xs text-slate-500">
                            @if ($live)
                                <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="hover:text-slate-800">{{ preg_replace('#^https?://#', '', $publicUrl) }}</a>
                            @else
                                {{ __('Not live yet: only you can see this preview.') }}
                            @endif
                        </p>
                    </div>
                    <div class="inline-flex rounded-xl bg-slate-100 p-1 text-sm font-medium" role="group" aria-label="{{ __('Preview size') }}">
                        <button type="button" class="rounded-lg px-3 py-1.5" :class="device === 'desktop' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500'" @click="device = 'desktop'; $nextTick(() => fit())" :aria-pressed="device === 'desktop'" data-device="desktop">{{ __('Desktop') }}</button>
                        <button type="button" class="rounded-lg px-3 py-1.5" :class="device === 'mobile' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500'" @click="device = 'mobile'; $nextTick(() => fit())" :aria-pressed="device === 'mobile'" data-device="mobile">{{ __('Mobile') }}</button>
                    </div>
                </div>

                <div class="bg-slate-100 p-4 sm:p-6">
                    {{-- Desktop: a 1280px page scaled to fit. --}}
                    <div x-show="device === 'desktop'" x-ref="stage" class="relative mx-auto w-full overflow-hidden rounded-xl bg-white shadow ring-1 ring-slate-200" :style="`height: ${Math.round(760 * scale)}px`">
                        <iframe :src="src" src="{{ $previewUrl }}" title="{{ __('Desktop preview of :event', ['event' => $event->name]) }}" loading="lazy" data-preview-frame
                                class="absolute left-0 top-0 origin-top-left border-0" style="width: 1280px; height: 760px" :style="`width: 1280px; height: 760px; transform: scale(${scale})`"></iframe>
                    </div>
                    {{-- Mobile: real 390px width in a phone frame. --}}
                    {{-- Mobile: a real 390 x 844 phone viewport, scaled down to fit narrow screens. --}}
                    <div x-show="device === 'mobile'" x-cloak x-ref="phone" class="mx-auto w-[410px] max-w-full" data-phone-frame>
                        <div class="mx-auto rounded-[2.6rem] bg-[#0b1020] p-2.5 shadow-xl" :style="`width: ${Math.round(390 * phoneScale) + 20}px`">
                            <div class="relative overflow-hidden rounded-[2.1rem] bg-white" :style="`height: ${Math.round(844 * phoneScale)}px`">
                                <iframe x-bind:src="device === 'mobile' ? src : ''" title="{{ __('Mobile preview of :event', ['event' => $event->name]) }}"
                                        class="absolute left-0 top-0 origin-top-left border-0" :style="`width: 390px; height: 844px; transform: scale(${phoneScale})`"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="space-y-6">
                {{-- Change template --}}
                <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <h3 class="text-sm font-semibold">{{ __('Change template') }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Pick one to try it in the preview. Your logo and colours carry over.') }}</p>
                    <form method="POST" action="{{ route('organiser.events.template', $event) }}" class="mt-4 space-y-2">
                        @csrf
                        @method('PATCH')
                        @foreach ($templates as $key => $tpl)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2 ring-1 ring-slate-200 has-[:checked]:ring-2 has-[:checked]:ring-[#0b1020]">
                                <input type="radio" name="template" value="{{ $key }}" x-model="template" class="text-[#0b1020] focus:ring-[#0b1020]" @checked($template === $key)>
                                <span class="flex-1 text-sm font-medium">{{ $tpl['label'] }}</span>
                                @if ($template === $key)
                                    <span class="text-xs text-slate-500">{{ __('Current') }}</span>
                                @endif
                            </label>
                        @endforeach
                        <button type="submit" x-show="template !== saved" x-cloak class="mt-2 w-full rounded-xl bg-[#0b1020] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#1f2a4a]">{{ __('Apply template') }}</button>
                        <p x-show="template !== saved" x-cloak class="text-xs text-slate-500">{{ $live ? __('Applies to your live page straight away.') : __('Saved to your draft.') }}</p>
                    </form>
                    <a href="{{ route('templates.index') }}" target="_blank" class="mt-3 inline-block text-xs font-medium text-slate-500 underline hover:text-slate-800">{{ __('See all templates') }} ↗</a>
                </section>

                {{-- Add-ons --}}
                <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70" id="add-ons">
                    <h3 class="text-sm font-semibold">{{ __('Add-ons for this event') }}</h3>
                    <ul class="mt-3 space-y-3">
                        @foreach ($addons as $key => $row)
                            <li class="flex items-start justify-between gap-3 text-sm" data-event-addon="{{ $key }}">
                                <div>
                                    <p class="font-medium">{{ __($row['addon']['name']) }}</p>
                                    <p class="text-xs text-slate-500">
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
                                    <a href="{{ route($key === 'race_sheet' ? 'reports.race-sheet' : 'reports.certificates', $event) }}" class="shrink-0 font-medium text-slate-700 underline hover:text-slate-900">{{ __('Open') }}</a>
                                @else
                                    <a href="{{ \App\Saas\EventAddons::requestUrl($event, $key) }}" class="shrink-0 rounded-lg bg-[#0b1020] px-3 py-1.5 font-semibold text-white hover:bg-[#1f2a4a]">{{ __('Unlock') }}</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>

                {{-- Numbers --}}
                <section class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                        <p class="text-xs text-slate-500">{{ __('Registrations') }}</p>
                        <p class="mt-1 text-2xl font-bold">{{ number_format($stats['registrations']) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                        <p class="text-xs text-slate-500">{{ __('Paid') }}</p>
                        <p class="mt-1 text-2xl font-bold">{{ number_format($stats['paid']) }}</p>
                    </div>
                    <a href="{{ route('dashboard.participants', ['event' => $event->id]) }}" class="col-span-2 text-center text-sm font-medium text-slate-600 underline hover:text-slate-900">{{ __('Participants') }} →</a>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
