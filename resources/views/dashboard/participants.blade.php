@php($inr = fn (int $paise) => \Illuminate\Support\Number::currency($paise / 100, in: 'INR', locale: 'en_IN'))

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Participants') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <header>
                    <h2 class="text-lg font-medium text-gray-900">
                        {{ $event?->name ?? __('All events') }}
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        {{ trans_choice(':count participant|:count participants', $registrations->total()) }}
                    </p>
                </header>

                <form method="GET" action="{{ route('dashboard.participants') }}" class="mt-6 flex flex-wrap items-end gap-4">
                    <div>
                        <x-input-label for="event" :value="__('Event')" />
                        <select id="event" name="event" class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All events') }}</option>
                            @foreach ($events as $option)
                                <option value="{{ $option->id }}" @selected($event?->is($option))>{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('Any status') }}</option>
                            @foreach ($statuses as $option)
                                <option value="{{ $option->value }}" @selected($status === $option)>{{ __(ucfirst($option->value)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-primary-button>{{ __('Filter') }}</x-primary-button>

                    @can('export-participants')
                        <x-secondary-button type="submit" formaction="{{ route('dashboard.participants.export') }}">
                            {{ __('Export CSV') }}
                        </x-secondary-button>
                    @endcan
                </form>

                @if ($registrations->isEmpty())
                    <p class="mt-6 text-sm text-gray-600">
                        {{ __('No participants match these filters.') }}
                    </p>
                @else
                    <div class="relative mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-gray-600">
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Pass no') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Name') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Contact') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Event') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Distance') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Status') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium text-right">{{ __('Amount') }}</th>
                                    <th scope="col" class="py-2 font-medium">{{ __('Checked in') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-900">
                                @foreach ($registrations as $r)
                                    <tr>
                                        <td class="py-3 pe-4 font-mono whitespace-nowrap">{{ $r->pass_no }}</td>
                                        <td class="py-3 pe-4">{{ $r->participant_name }}</td>
                                        <td class="py-3 pe-4">
                                            <div>{{ $r->email }}</div>
                                            <div class="text-gray-600">{{ $r->phone }}</div>
                                        </td>
                                        <td class="py-3 pe-4">{{ $r->event?->name }}</td>
                                        <td class="py-3 pe-4">{{ $r->distance_code }}</td>
                                        <td class="py-3 pe-4">{{ __(ucfirst($r->status->value)) }}</td>
                                        <td class="py-3 pe-4 text-right whitespace-nowrap">{{ $inr($r->amount_paise) }}</td>
                                        <td class="py-3 whitespace-nowrap">{{ $r->checked_in_at?->format('d M Y, H:i') ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $registrations->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
