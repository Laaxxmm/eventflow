<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">
            {{ __('Org Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-[28px] bg-white p-5 sm:p-8">
                <section class="max-w-xl">
                    <header>
                        <h2 class="text-lg font-semibold text-ink">
                            {{ __('Season Pass') }}
                        </h2>

                        <p class="mt-1 text-sm text-muted">
                            {{ __('One pass covers every event in the season. Registration, payments and passes are always included.') }}
                        </p>
                    </header>

                    @if ($pass)
                        <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="text-muted">{{ __('Plan') }}</dt>
                                <dd class="mt-1 text-ink">{{ $pass->plan }}</dd>
                            </div>
                            <div>
                                <dt class="text-muted">{{ __('Valid') }}</dt>
                                <dd class="mt-1 text-ink">{{ $pass->starts_on->format('d M Y') }} &ndash; {{ $pass->ends_on->format('d M Y') }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="mt-6 text-sm text-ink">
                            {{ __('No active Season Pass. You can take registrations and payments; check-in, race sheets and certificates need a pass.') }}
                        </p>
                    @endif

                    <p class="mt-4 text-sm text-muted">
                        @if ($contact)
                            {{ __('To buy or renew a Season Pass, contact Indefine at :contact.', ['contact' => $contact]) }}
                        @else
                            {{ __('To buy or renew a Season Pass, contact Indefine.') }}
                        @endif
                    </p>
                </section>
            </div>

            <div class="rounded-[28px] bg-white p-5 sm:p-8">
                <section class="max-w-xl">
                    <header>
                        <h2 class="text-lg font-semibold text-ink">
                            {{ __('Modules') }}
                        </h2>

                        <p class="mt-1 text-sm text-muted">
                            {{ __('Switch off the modules your events don\'t use. Only modules in your Season Pass can be switched on.') }}
                        </p>

                        @if (Route::has('settings.notifications.show'))
                            <p class="mt-2 text-sm text-muted">
                                <a href="{{ route('settings.notifications.show') }}" class="underline text-sm text-muted hover:text-ink rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-track">{{ __('Email and WhatsApp') }}</a>
                                {{ __('(own SMTP free; Platform mail and WhatsApp add-ons)') }}
                            </p>
                        @endif
                    </header>

                    <form method="post" action="{{ route('settings.modules.update') }}" class="mt-6 space-y-6">
                        @csrf
                        @method('patch')

                        <fieldset class="space-y-3">
                            <legend class="font-medium text-sm text-ink">{{ __('Always on') }}</legend>
                            @foreach ($core as $module)
                                <label class="flex items-center">
                                    <input type="checkbox" class="rounded border-mist text-track shadow-sm focus:ring-track opacity-50" checked disabled>
                                    <span class="ms-2 text-sm text-muted">{{ \App\Entitlements\EnsureModuleEnabled::label($module) }}</span>
                                </label>
                            @endforeach
                        </fieldset>

                        <fieldset class="space-y-3">
                            <legend class="font-medium text-sm text-ink">{{ __('Optional') }}</legend>
                            @foreach ($optional as $row)
                                <label for="module_{{ $row['module']->value }}" class="flex items-center">
                                    <input id="module_{{ $row['module']->value }}" type="checkbox" name="modules[]" value="{{ $row['module']->value }}"
                                        class="rounded border-mist text-track shadow-sm focus:ring-track {{ $row['included'] ? '' : 'opacity-50' }}"
                                        @checked($row['on']) @disabled(! $row['included'])>
                                    <span class="ms-2 text-sm text-muted">
                                        {{ $row['label'] }}
                                        @unless ($row['included'])
                                            <span class="text-muted">&middot; {{ __('not in your Season Pass') }}</span>
                                        @endunless
                                    </span>
                                </label>
                            @endforeach
                        </fieldset>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save') }}</x-primary-button>

                            @if (session('status') === 'modules-updated')
                                <p
                                    x-data="{ show: true }"
                                    x-show="show"
                                    x-transition
                                    x-init="setTimeout(() => show = false, 2000)"
                                    class="text-sm text-muted"
                                >{{ __('Saved.') }}</p>
                            @endif
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
