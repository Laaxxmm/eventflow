<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $event->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (in_array(session('status'), ['event-created', 'event-updated'], true))
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <p class="text-sm font-medium text-green-600">
                        {{ session('status') === 'event-created' ? __('Event created.') : __('Saved.') }}
                    </p>
                    @if ($event->status === \App\Landing\EventStatus::Published)
                        <p class="mt-2 text-sm text-gray-600">
                            {{ __('Shareable landing page:') }}
                            <a href="{{ route('landing.show', [$org->slug, $event->slug]) }}" class="underline hover:text-gray-900">{{ route('landing.show', [$org->slug, $event->slug]) }}</a>
                        </p>
                    @endif
                </div>
            @endif

            <form method="post" action="{{ route('organiser.events.update', $event) }}" class="space-y-6">
                @csrf
                @method('put')
                @include('events.partials.form')

                <div class="flex items-center gap-4 px-4 sm:px-0">
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                    <a href="{{ route('organiser.events.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">{{ __('Back to events') }}</a>
                </div>
            </form>

            <section class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">{{ __('Delete event') }}</h2>
                        <p class="mt-1 text-sm text-gray-600">{{ __('Only events without registrations can be deleted. Close the event instead to stop new entries.') }}</p>
                    </header>
                    <form method="post" action="{{ route('organiser.events.destroy', $event) }}" class="mt-6" onsubmit="return confirm(@js(__('Delete this event?')))">
                        @csrf
                        @method('delete')
                        <x-danger-button>{{ __('Delete event') }}</x-danger-button>
                        <x-input-error class="mt-2" :messages="$errors->get('event')" />
                    </form>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
