@php($inr = fn (int $paise) => \Illuminate\Support\Number::currency($paise / 100, in: 'INR', locale: 'en_IN'))

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <dl class="grid grid-cols-2 lg:grid-cols-4 gap-4 px-4 sm:px-0">
                @foreach ([
                    __('Registrations') => number_format($totals['registrations']),
                    __('Paid') => number_format($totals['paid']),
                    __('Paid total') => $inr($totals['paid_total_paise']),
                    __('Checked in') => number_format($totals['checked_in']),
                ] as $label => $value)
                    <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg">
                        <dt class="text-sm text-gray-600">{{ $label }}</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <header class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900">
                            {{ __('My events') }}
                        </h2>

                        <p class="mt-1 text-sm text-gray-600">
                            {{ __('Registrations, payments and check-ins for every event in your organisation.') }}
                        </p>
                    </div>

                    <a href="{{ route('dashboard.participants') }}" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('View all participants') }}
                    </a>
                </header>

                @if ($events->isEmpty())
                    <p class="mt-6 text-sm text-gray-600">
                        {{ __('No events yet.') }}
                    </p>
                @else
                    <div class="relative mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-gray-600">
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Event') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Date') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium text-right">{{ __('Registrations') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium text-right">{{ __('Paid') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium text-right">{{ __('Paid total') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium text-right">{{ __('Checked in') }}</th>
                                    <th scope="col" class="py-2"><span class="sr-only">{{ __('Participants') }}</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-900">
                                @foreach ($events as $event)
                                    <tr>
                                        <td class="py-3 pe-4">
                                            <div class="font-medium">{{ $event->name }}</div>
                                            <div class="text-gray-600">{{ $event->venue }}</div>
                                        </td>
                                        <td class="py-3 pe-4 whitespace-nowrap">{{ $event->date->format('d M Y') }}</td>
                                        <td class="py-3 pe-4 text-right">{{ number_format($event->registrations_count) }}</td>
                                        <td class="py-3 pe-4 text-right">{{ number_format($event->paid_count) }}</td>
                                        <td class="py-3 pe-4 text-right whitespace-nowrap">{{ $inr((int) $event->paid_total_paise) }}</td>
                                        <td class="py-3 pe-4 text-right">{{ number_format($event->checked_in_count) }}</td>
                                        <td class="py-3 text-right">
                                            <a href="{{ route('dashboard.participants', ['event' => $event->id]) }}" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                {{ __('Participants') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
