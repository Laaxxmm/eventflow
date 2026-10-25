<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Certificates') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <header class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900">
                            {{ $event->name }}
                        </h2>

                        <p class="mt-1 text-sm text-gray-600">
                            {{ __('One A4 PDF per checked-in runner with their name, distance, the event and its date.') }}
                        </p>
                    </div>

                    <a href="{{ route('reports.race-sheet', $event) }}" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('Race sheet') }}
                    </a>
                </header>

                <dl class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-600">{{ __('Checked-in runners') }}</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($eligibleCount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-600">{{ __('Template') }}</dt>
                        <dd class="mt-1 text-gray-900">
                            {{ $settings->isUpload() ? __('Uploaded background') : __(config("eventflow.certificates.gallery.{$settings->template}.label", ucfirst($settings->template))) }}
                            &middot; {{ __(ucfirst($settings->orientation)) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-600">{{ __('Last batch') }}</dt>
                        <dd class="mt-1 text-gray-900">
                            @switch($batch['state'] ?? null)
                                @case('queued') {{ __('Queued') }} @break
                                @case('running') {{ __('Generating…') }} @break
                                @case('ready') {{ trans_choice(':count certificate ready|:count certificates ready', $batch['count'] ?? 0) }} @break
                                @case('failed') {{ __('Failed') }} @break
                                @default {{ __('Not generated yet') }}
                            @endswitch
                        </dd>
                    </div>
                </dl>

                @if (session('status'))
                    <p class="mt-6 font-medium text-sm text-green-600">{{ session('status') }}</p>
                @endif

                @if (($batch['state'] ?? null) === 'failed')
                    <p class="mt-6 font-medium text-sm text-red-600">{{ $batch['message'] ?? __('Certificate generation failed.') }}</p>
                @endif

                <div class="mt-6 flex flex-wrap items-center gap-4">
                    <form method="POST" action="{{ route('reports.certificates.generate', $event) }}">
                        @csrf
                        <x-primary-button :disabled="$eligibleCount === 0 || in_array($batch['state'] ?? null, ['queued', 'running'], true)">
                            {{ __('Generate certificates') }}
                        </x-primary-button>
                    </form>

                    @if ($zipReady)
                        <form method="GET" action="{{ route('reports.certificates.download', $event) }}">
                            <x-secondary-button type="submit">{{ __('Download ZIP') }}</x-secondary-button>
                        </form>
                    @endif
                </div>

                @if ($contact)
                    <p class="mt-6 text-sm text-gray-600">
                        {{ __('Need a custom certificate design? Contact Indefine at :contact.', ['contact' => $contact]) }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
