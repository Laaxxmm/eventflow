<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">
            {{ $isStartList ? __('Start lists') : __('Race sheet') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('saas.addon-lock', ['addonKey' => 'race_sheet'])
            @php($sheetUnlocked = \App\Saas\EventAddons::unlocked($event, \App\Enums\Module::RaceSheets))
            <div class="rounded-[28px] bg-white p-5 sm:p-8">
                <header class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-ink">
                            {{ $event->name }}
                        </h2>

                        <p class="mt-1 text-sm text-muted">
                            @if ($isStartList)
                                {{ __('No heats are configured for this event, so paid entrants are listed by distance, age band and gender.') }}
                            @else
                                {{ __('Heats of up to :size, grouped by :groups.', ['size' => $sheet->config->heatSize, 'groups' => implode(', ', array_map('mb_strtolower', $headings))]) }}
                            @endif
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        @if ($sheetUnlocked)
                            <form method="GET" action="{{ route('reports.race-sheet.pdf', $event) }}">
                                <x-primary-button>{{ __('Print PDF') }}</x-primary-button>
                            </form>
                            <form method="GET" action="{{ route('reports.race-sheet.csv', $event) }}">
                                <x-secondary-button type="submit">{{ __('Export CSV') }}</x-secondary-button>
                            </form>
                        @else
                            <x-primary-button disabled title="{{ __('Locked: add the race sheet add-on') }}">{{ __('Print PDF') }} 🔒</x-primary-button>
                            <x-secondary-button disabled title="{{ __('Locked: add the race sheet add-on') }}">{{ __('Export CSV') }} 🔒</x-secondary-button>
                        @endif
                    </div>
                </header>

                @if (empty($sheet->heats))
                    <p class="mt-6 text-sm text-muted">
                        {{ __('No paid entrants yet.') }}
                    </p>
                @endif
            </div>

            @foreach ($sheet->heats as $heat)
                <section class="rounded-[28px] bg-white p-5 sm:p-8">
                    <h3 class="text-lg font-semibold text-ink">
                        {{ implode(' · ', $heat->group) }}
                        @unless ($isStartList)
                            <span class="text-muted">&mdash; {{ __('Heat :number', ['number' => $heat->number]) }}</span>
                        @endunless
                    </h3>

                    <div class="relative mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-[#EEF0F6] text-sm">
                            <thead>
                                <tr class="text-left text-muted">
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ $isStartList ? __('No.') : __('Lane') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Pass no') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Name') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Age') }}</th>
                                    <th scope="col" class="py-2 font-medium">{{ __('Checked in') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#EEF0F6] text-ink">
                                @foreach ($heat->registrationIds as $i => $id)
                                    @php($r = $entrants[$id])
                                    <tr>
                                        <td class="py-3 pe-4">{{ $i + 1 }}</td>
                                        <td class="py-3 pe-4 font-mono whitespace-nowrap">{{ $r->pass_no }}</td>
                                        <td class="py-3 pe-4">{{ $r->participant_name }}</td>
                                        <td class="py-3 pe-4">{{ $r->answers['age'] ?? '—' }}</td>
                                        <td class="py-3">{{ $r->checked_in_at ? __('Yes') : '—' }}</td>
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
