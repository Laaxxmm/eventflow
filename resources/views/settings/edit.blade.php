{{--
    Settings → Modules (Daylight Track, docs/design/daylight-track/Modules.dc.html): Season Pass summary,
    "Always on" list, optional modules as <x-ri.switch> rows. Form posts modules[] (checked values only).
--}}
@php
    $about = [
        'registration' => __('Your race page and entry form'),
        'payments' => __('Checkout and entry fees'),
        'passes' => __('QR pass as an image and PDF after payment'),
        'checkin' => __('Volunteers scan passes at the gate'),
        'race_sheets' => __('Heats and start lists you can print'),
        'certificates' => __('Finisher certificates after race day'),
        'email_branding' => __('Indefine sends participant emails for you'),
        'sms_whatsapp' => __('Passes and reminders on WhatsApp'),
    ];
    $row = 'flex items-center justify-between gap-4 border-t border-[#EEF0F6] py-3.5';
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('Modules') }}</x-slot>
    <x-slot name="header">
        <div>
            <h2>{{ __('Modules') }}</h2>
            <p class="mt-1.5 text-[15px] text-muted">{{ __('Switch off what your events don’t need. The menu and your event pages follow.') }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-[1180px] space-y-5 px-4 pb-12 pt-4 sm:px-6 lg:px-10">
        <div class="max-w-[900px] space-y-5">
            @if (session('status') === 'modules-updated')
                <p class="rounded-[18px] bg-[#E3F4EE] px-4 py-3 text-sm text-[#0B4A38]" role="status">{{ __('Saved.') }}</p>
            @endif

            <section class="rounded-[28px] bg-white p-[22px] sm:p-[26px]">
                <h2 class="text-lg font-bold text-ink">{{ __('Season Pass') }}</h2>
                <p class="mt-1 text-sm text-muted">{{ __('One pass covers every event in the season. Registration, payments and passes are always included.') }}</p>

                @if ($pass)
                    <dl class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-[18px] bg-paper px-4 py-3">
                            <dt class="text-[13px] text-muted">{{ __('Plan') }}</dt>
                            <dd class="mt-0.5 font-bold text-ink">{{ $pass->plan }}</dd>
                        </div>
                        <div class="rounded-[18px] bg-paper px-4 py-3">
                            <dt class="text-[13px] text-muted">{{ __('Valid') }}</dt>
                            <dd class="mt-0.5 font-bold text-ink">{{ $pass->starts_on->format('d M Y') }} &ndash; {{ $pass->ends_on->format('d M Y') }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="mt-4 rounded-[18px] bg-[#FDF0DA] px-4 py-3 text-sm text-[#6B4A12]">
                        {{ __('No active Season Pass. You can take registrations and payments; check-in, race sheets and certificates need a pass.') }}
                    </p>
                @endif

                <p class="mt-3 text-sm text-muted">
                    @if ($contact)
                        {{ __('To buy or renew a Season Pass, contact Indefine at :contact.', ['contact' => $contact]) }}
                    @else
                        {{ __('To buy or renew a Season Pass, contact Indefine.') }}
                    @endif
                </p>
            </section>

            <form method="post" action="{{ route('settings.modules.update') }}" class="space-y-5">
                @csrf
                @method('patch')

                <section class="rounded-[28px] bg-white px-[22px] py-2 sm:px-[26px]">
                    <h2 class="pb-2 pt-[18px] text-lg font-bold text-ink">{{ __('Always on') }}</h2>
                    @foreach ($core as $module)
                        <div class="{{ $row }}">
                            <span class="min-w-0">
                                <strong class="block text-ink">{{ \App\Entitlements\EnsureModuleEnabled::label($module) }}</strong>
                                <span class="text-sm text-muted">{{ $about[$module->value] ?? '' }}</span>
                            </span>
                            <span class="shrink-0 text-[13px] font-semibold text-muted">{{ __('Always on') }}</span>
                        </div>
                    @endforeach
                    @if (Route::has('settings.notifications.show'))
                        <div class="{{ $row }}">
                            <span class="min-w-0">
                                <strong class="block text-ink">{{ __('Email with your SMTP') }}</strong>
                                <span class="text-sm text-muted">{{ __('(own SMTP free; Platform mail and WhatsApp add-ons)') }}</span>
                            </span>
                            <a href="{{ route('settings.notifications.show') }}" class="inline-flex min-h-[44px] shrink-0 items-center text-sm font-bold text-track hover:text-track-deep">{{ __('Set up') }}</a>
                        </div>
                    @endif
                </section>

                <section class="rounded-[28px] bg-white px-[22px] py-2 sm:px-[26px]">
                    <h2 class="pb-2 pt-[18px] text-lg font-bold text-ink">{{ __('Optional') }}</h2>
                    <p class="pb-3 text-sm text-muted">{{ __('Switch off the modules your events don\'t use. Only modules in your Season Pass can be switched on.') }}</p>
                    @foreach ($optional as $row_)
                        <div class="border-t border-[#EEF0F6] py-2">
                            <x-ri.switch name="modules[]" :id="'module_'.$row_['module']->value" :value="$row_['module']->value" :hidden="false"
                                         :checked="$row_['on']" :disabled="! $row_['included']"
                                         :label="$row_['label']" :hint="$row_['included'] ? ($about[$row_['module']->value] ?? null) : __('This module is not in your Season Pass.')"
                                         class="[&_.text-sm.font-semibold]:text-base [&_.text-sm.font-semibold]:font-bold" />
                            @unless ($row_['included'])
                                <a href="{{ \App\Support\Contact::url(__('Add :module to the Season Pass for :org', ['module' => $row_['label'], 'org' => $org->name])) }}" class="inline-flex min-h-[44px] items-center text-sm font-bold text-track hover:text-track-deep">{{ __('Add it') }}</a>
                            @endunless
                        </div>
                    @endforeach
                </section>

                <div class="flex justify-end">
                    <x-ri.button variant="primary" size="md" type="submit" class="font-bold">{{ __('Save') }}</x-ri.button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
