<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Events') }}
            </h2>
            <a href="{{ route('organiser.events.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('New event') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'event-deleted')
                <p class="px-4 sm:px-0 text-sm text-green-600">{{ __('Event deleted.') }}</p>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <header>
                    <h2 class="text-lg font-medium text-gray-900">{{ $org->name }}</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('Public page:') }}
                        <a href="{{ route('landing.org', $org->slug) }}" class="underline hover:text-gray-900">{{ route('landing.org', $org->slug) }}</a>
                    </p>
                </header>

                @if ($events->isEmpty())
                    <p class="mt-6 text-sm text-gray-600">{{ __('No events yet. Create your first event to get a shareable landing page.') }}</p>
                @else
                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-600 border-b">
                                    <th class="py-2 pr-4 font-medium">{{ __('Event') }}</th>
                                    <th class="py-2 pr-4 font-medium">{{ __('Date') }}</th>
                                    <th class="py-2 pr-4 font-medium">{{ __('Status') }}</th>
                                    <th class="py-2 pr-4 font-medium text-right">{{ __('Registrations') }}</th>
                                    <th class="py-2 font-medium"><span class="sr-only">{{ __('Actions') }}</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach ($events as $event)
                                    <tr>
                                        <td class="py-3 pr-4">
                                            <span class="font-medium text-gray-900">{{ $event->name }}</span>
                                            <span class="block text-gray-500">{{ $event->venue }}</span>
                                        </td>
                                        <td class="py-3 pr-4 whitespace-nowrap">{{ $event->date->setTimezone(\App\Landing\ConfigCompiler::TIMEZONE)->format('j M Y, g:i A') }}</td>
                                        <td class="py-3 pr-4">{{ __(ucfirst($event->status)) }}</td>
                                        <td class="py-3 pr-4 text-right">{{ $event->registrations_count }}</td>
                                        <td class="py-3 text-right whitespace-nowrap space-x-3">
                                            @if ($event->status === \App\Landing\EventStatus::Published)
                                                <a href="{{ route('landing.show', [$org->slug, $event->slug]) }}" class="underline text-gray-600 hover:text-gray-900">{{ __('View') }}</a>
                                            @endif
                                            <a href="{{ route('organiser.events.edit', $event) }}" class="underline text-gray-600 hover:text-gray-900">{{ __('Edit') }}</a>
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
