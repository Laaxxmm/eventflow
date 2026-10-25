<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New event') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="get" action="{{ route('organiser.events.create') }}" class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <x-input-label for="sport_pack" :value="__('Start from a sport pack')" />
                    <div class="mt-1 flex gap-3">
                        <select id="sport_pack" name="sport" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full">
                            @foreach (\App\Enums\Sport::cases() as $sport)
                                <option value="{{ $sport->value }}" @selected($input['sport'] === $sport->value)>{{ __(ucfirst($sport->value)) }}</option>
                            @endforeach
                        </select>
                        <x-secondary-button type="submit">{{ __('Load') }}</x-secondary-button>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Prefills distances, age categories and questions. Unsaved changes below are lost.') }}</p>
                </div>
            </form>

            <form method="post" action="{{ route('organiser.events.store') }}" class="space-y-6">
                @csrf
                @include('events.partials.form')

                <div class="flex items-center gap-4 px-4 sm:px-0">
                    <x-primary-button>{{ __('Create event') }}</x-primary-button>
                    <a href="{{ route('organiser.events.index') }}" class="text-sm text-gray-600 underline hover:text-gray-900">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
