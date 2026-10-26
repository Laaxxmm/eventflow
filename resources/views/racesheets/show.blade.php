{{--
    Race sheet (Daylight Track, docs/design/daylight-track/RaceSheet.dc.html): event line, headline, CSV / PDF,
    category filter pills (client-side, by age band or the first group key), one white card per heat.
--}}
@php
    $sheetUnlocked = \App\Saas\EventAddons::unlocked($event, \App\Enums\Module::RaceSheets);
    $filterKey = in_array('age_band', $groupKeys, true) ? 'age_band' : ($groupKeys[0] ?? null);
    $filters = $filterKey ? collect($sheet->heats)->map(fn ($h) => $h->group[$filterKey] ?? null)->filter()->unique()->values() : collect();
    $entrantCount = collect($sheet->heats)->sum(fn ($h) => count($h->registrationIds));
    $title = $isStartList ? __('Start lists') : __('Race sheet');
    $pill = 'inline-flex min-h-[44px] items-center rounded-full px-4 text-sm font-semibold transition duration-200 ease-track';
@endphp

<x-app-layout>
    <x-slot name="title">{{ $title }} · {{ $event->name }}</x-slot>
    <x-slot name="header">
        <div class="min-w-0">
            <p class="text-sm text-muted">{{ $event->name }}@if ($event->date), {{ $event->date->timezone('Asia/Kolkata')->format('D j M Y') }}@endif</p>
            <h2 class="mt-1">{{ $title }}</h2>
            <p class="mt-1.5 max-w-2xl text-[15px] text-muted">
                @if ($isStartList)
                    {{ __('No heats are configured for this event, so paid entrants are listed by distance, age band and gender.') }}
                @else
                    {{ __('Heats of up to :size, grouped by :groups.', ['size' => $sheet->config->heatSize, 'groups' => implode(', ', array_map('mb_strtolower', $headings))]) }}
                @endif
                @if ($entrantCount)
                    @if ($isStartList)
                        {{ trans_choice(':count paid entrant in :heats lists.|:count paid entrants in :heats lists.', $entrantCount, ['heats' => count($sheet->heats)]) }}
                    @else
                        {{ trans_choice(':count paid entrant in :heats heats.|:count paid entrants in :heats heats.', $entrantCount, ['heats' => count($sheet->heats)]) }}
                    @endif
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($sheetUnlocked)
                <x-ri.button variant="secondary" size="sm" :href="route('reports.race-sheet.csv', $event)">{{ __('Export CSV') }}</x-ri.button>
                <x-ri.button variant="ink" size="sm" :href="route('reports.race-sheet.pdf', $event)">{{ __('Print PDF') }}</x-ri.button>
            @else
                <x-ri.button variant="secondary" size="sm" disabled title="{{ __('Locked: add the race sheet add-on') }}">{{ __('Export CSV') }}</x-ri.button>
                <x-ri.button variant="ink" size="sm" disabled title="{{ __('Locked: add the race sheet add-on') }}">{{ __('Print PDF') }}</x-ri.button>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-[1180px] space-y-5 px-4 pb-12 pt-4 sm:px-6 lg:px-10" x-data="{ cat: '' }">
        @include('saas.addon-lock', ['addonKey' => 'race_sheet'])

        @if (empty($sheet->heats))
            <p class="rounded-[28px] bg-white p-[22px] text-sm text-muted">{{ __('No paid entrants yet.') }}</p>
        @endif

        @if ($filters->count() > 1)
            <div class="flex flex-wrap gap-2" role="group" aria-label="{{ $headings[$filterKey] ?? __('Category') }}">
                <button type="button" @click="cat = ''" :aria-pressed="(cat === '').toString()" aria-pressed="true"
                        class="{{ $pill }}" :class="cat === '' ? 'bg-ink text-white' : 'bg-white text-ink ring-1 ring-inset ring-mist hover:ring-ink/30'">{{ __('All categories') }}</button>
                @foreach ($filters as $value)
                    <button type="button" @click="cat = @js($value)" :aria-pressed="(cat === @js($value)).toString()" aria-pressed="false"
                            class="{{ $pill }}" :class="cat === @js($value) ? 'bg-ink text-white' : 'bg-white text-ink ring-1 ring-inset ring-mist hover:ring-ink/30'">{{ $value }}</button>
                @endforeach
            </div>
        @endif

        <div class="grid items-start gap-4 lg:grid-cols-2">
            @foreach ($sheet->heats as $heat)
                <section class="min-w-0 rounded-[24px] bg-white px-4 py-5 sm:px-[22px]" @if ($filterKey) x-show="cat === '' || cat === @js($heat->group[$filterKey] ?? '')" @endif>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <h3 class="flex min-w-0 items-center gap-3 text-[17px] font-bold text-ink">
                            <span class="ri-headline flex h-11 w-11 flex-none items-center justify-center rounded-[14px] bg-lilac text-lg text-track-deep" aria-hidden="true">{{ $isStartList ? $loop->iteration : $heat->number }}</span>
                            <span class="min-w-0">
                                {{ implode(' · ', $heat->group) }}
                                @unless ($isStartList)
                                    <span class="sr-only">, {{ __('Heat :number', ['number' => $heat->number]) }}</span>
                                @endunless
                            </span>
                        </h3>
                        <span class="flex-none text-[13px] text-muted">{{ trans_choice(':count entrant|:count entrants', count($heat->registrationIds)) }}</span>
                    </div>

                    <div class="relative overflow-x-auto">
                        <table class="w-full min-w-[460px] border-collapse text-sm">
                            <thead>
                                <tr class="whitespace-nowrap text-left text-xs text-muted">
                                    <th scope="col" class="py-2 pe-3 font-semibold">{{ $isStartList ? __('No.') : __('Lane') }}</th>
                                    <th scope="col" class="py-2 pe-3 font-semibold">{{ __('Pass no') }}</th>
                                    <th scope="col" class="py-2 pe-3 font-semibold">{{ __('Name') }}</th>
                                    <th scope="col" class="py-2 pe-3 font-semibold">{{ __('Age') }}</th>
                                    <th scope="col" class="py-2 text-right font-semibold">{{ __('Checked in') }}</th>
                                </tr>
                            </thead>
                            <tbody class="text-ink">
                                @foreach ($heat->registrationIds as $i => $id)
                                    @php
                                        $r = $entrants[$id];
                                    @endphp
                                    <tr class="border-t border-[#EEF0F6]">
                                        <td class="ri-headline py-2.5 pe-3 text-base text-track">{{ $i + 1 }}</td>
                                        <td class="whitespace-nowrap py-2.5 pe-3 text-[#3B4463]">{{ $r->pass_no }}</td>
                                        <td class="py-2.5 pe-3 font-semibold">{{ $r->participant_name }}</td>
                                        <td class="py-2.5 pe-3">{{ $r->answers['age'] ?? '—' }}</td>
                                        <td @class(['py-2.5 text-right font-semibold', 'text-success' => $r->checked_in_at, 'text-warning' => ! $r->checked_in_at])>{{ $r->checked_in_at ? __('Yes') : __('No') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
