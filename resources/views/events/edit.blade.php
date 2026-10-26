<x-app-layout>
    <x-slot name="title">{{ __('Edit :event', ['event' => $event->name]) }}</x-slot>
    <x-slot name="header">
        <div class="min-w-0 space-y-2">
            <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm text-muted"><a href="{{ route('organiser.events.index') }}" class="hover:text-ink">{{ __('Events') }}</a> / <a href="{{ route('organiser.events.show', $event) }}" class="hover:text-ink">{{ $event->name }}</a> / {{ __('Edit') }}</nav>
            <h2 class="!text-[30px] sm:!text-[36px]">{{ $event->name }}</h2>
        </div>
    </x-slot>

    <div class="pb-12 pt-4">
        <div class="mx-auto max-w-[1180px] space-y-5 px-4 sm:px-6 lg:px-10">
            @if (in_array(session('status'), ['event-created', 'event-updated'], true))
                <div class="rounded-[22px] bg-[#E3F4EE] p-4 text-[#0B4A38]" role="status">
                    <p class="text-sm font-semibold">
                        {{ session('status') === 'event-created' ? __('Event created.') : __('Saved.') }}
                    </p>
                    @if ($event->status === \App\Landing\EventStatus::Published)
                        <p class="mt-1 text-sm">
                            {{ __('Shareable landing page:') }}
                            <a href="{{ route('landing.show', [$org->slug, $event->slug]) }}" class="break-all font-semibold underline">{{ route('landing.show', [$org->slug, $event->slug]) }}</a>
                        </p>
                    @endif
                </div>
            @endif

            <form method="post" action="{{ route('organiser.events.update', $event) }}" class="space-y-6" enctype="multipart/form-data">
                @csrf
                @method('put')
                @include('events.partials.form')

                <div class="sticky bottom-0 z-10 flex flex-wrap items-center gap-4 rounded-[22px] bg-white px-4 py-3.5 ri-float">
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                    <a href="{{ route('organiser.events.index') }}" class="text-sm font-semibold text-track hover:text-track-deep">{{ __('Back to events') }}</a>
                </div>
            </form>

            <section class="rounded-[28px] bg-white p-5 sm:p-8">
                <div class="max-w-xl">
                    <header>
                        <h2 class="text-lg font-semibold text-ink">{{ __('Delete event') }}</h2>
                        <p class="mt-1 text-sm text-muted">{{ __('Only events without registrations can be deleted. Close the event instead to stop new entries.') }}</p>
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
