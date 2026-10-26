{{-- Event → Settings → Communications: payment reminders for unpaid registrations (Session N2). --}}
@php
    $statusMessages = [
        'reminders-on' => __('Payment reminders are on for this event.'),
        'reminders-saved' => __('Saved. Payment reminders are off for this event.'),
    ];
    $control = 'mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#0b1020] focus:ring-[#0b1020]';
    $delays = old('delays_hours', array_pad($reminders->delaysHours, $maxCap, $reminders->delaysHours[array_key_last($reminders->delaysHours)]));
    $waHelp = __('WhatsApp needs a Meta-approved template: body {{1}} name, {{2}} event, {{3}} date, {{4}} payment link.');
    $ordinal = [1 => __('1st'), 2 => __('2nd'), 3 => __('3rd'), 4 => __('4th'), 5 => __('5th')];
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('Communications') }} · {{ $event->name }}</x-slot>
    <x-slot name="header">
        <div class="min-w-0">
            <p class="text-sm text-slate-500"><a href="{{ route('organiser.events.show', $event) }}" class="hover:text-slate-800">{{ $event->name }}</a> · {{ __('Settings') }}</p>
            <h2>{{ __('Communications') }}</h2>
        </div>
        <a href="{{ route('dashboard.participants', ['event' => $event->id, 'status' => 'unpaid']) }}" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50">{{ trans_choice(':count unpaid registration|:count unpaid registrations', $unpaid) }} →</a>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6 lg:px-10">
        @if (isset($statusMessages[session('status')]))
            <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200" role="status">{{ $statusMessages[session('status')] }}</p>
        @endif
        @unless ($featureOn)
            <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">{{ __('Payment reminders are switched off for this installation (FEATURE_PAYMENT_REMINDERS). Settings are kept; nothing is sent.') }}</p>
        @endunless

        <div class="grid items-start gap-6 lg:grid-cols-[1fr_22rem]">
            <form method="POST" action="{{ route('organiser.events.communications.update', $event) }}" class="space-y-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 sm:p-8" x-data="{ on: @js((bool) old('enabled', $reminders->enabled)), max: @js((int) old('max', $reminders->max)) }">
                @csrf
                @method('PUT')

                <header class="flex items-start justify-between gap-6">
                    <div>
                        <h3 class="text-lg font-bold">{{ __('Payment reminders') }}</h3>
                        <p class="mt-1 max-w-xl text-sm text-slate-600">{{ __('Remind people who started registering but have not paid. Email is included in your plan; WhatsApp goes out too when you have the WhatsApp add-on. Reminders stop the moment they pay, opt out, or the event starts.') }}</p>
                    </div>
                    <label class="relative inline-flex shrink-0 cursor-pointer items-center" data-reminders-toggle>
                        <input type="hidden" name="enabled" value="0">
                        <input type="checkbox" name="enabled" value="1" class="peer sr-only" x-model="on" @checked(old('enabled', $reminders->enabled))>
                        <span class="h-7 w-12 rounded-full bg-slate-300 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-[#0b1020] peer-focus-visible:ring-offset-2"></span>
                        <span class="absolute left-1 top-1 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                        <span class="sr-only">{{ __('Send payment reminders for this event') }}</span>
                    </label>
                </header>

                <fieldset :class="on ? '' : 'opacity-60'" class="space-y-4">
                    <legend class="text-sm font-semibold">{{ __('Cadence') }}</legend>
                    <div>
                        <x-input-label for="max" :value="__('Reminders per registration')" />
                        <select id="max" name="max" x-model.number="max" class="{{ $control }} sm:w-48">
                            @for ($i = 1; $i <= $maxCap; $i++)
                                <option value="{{ $i }}" @selected((int) old('max', $reminders->max) === $i)>{{ $i }}</option>
                            @endfor
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('max')" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        @for ($i = 1; $i <= $maxCap; $i++)
                            <div x-show="max >= {{ $i }}" @if ($i > $reminders->max) x-cloak @endif>
                                <x-input-label for="delay_{{ $i }}" :value="$i === 1 ? __(':nth reminder, hours after registering', ['nth' => $ordinal[$i]]) : __(':nth reminder, hours after the previous one', ['nth' => $ordinal[$i]])" />
                                <input id="delay_{{ $i }}" type="number" min="1" max="720" name="delays_hours[]" value="{{ $delays[$i - 1] }}" class="{{ $control }}" :disabled="max < {{ $i }}">
                            </div>
                        @endfor
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('delays_hours.*')" />
                    <div>
                        <x-input-label for="before_event_hours" :value="__('From the 3rd reminder: send no later than this many hours before the start')" />
                        <input id="before_event_hours" type="number" min="0" max="336" name="before_event_hours" value="{{ old('before_event_hours', $reminders->beforeEventHours) }}" class="{{ $control }} sm:w-48">
                        <p class="mt-1 text-xs text-slate-500">{{ __('If the event is closer than that, the remaining reminders are skipped. Nothing is sent between 9 pm and 8 am (IST); reminders due then go out at 8 am.') }}</p>
                    </div>
                </fieldset>

                <fieldset :class="on ? '' : 'opacity-60'" class="space-y-4">
                    <legend class="text-sm font-semibold">{{ __('Message') }}</legend>
                    <div>
                        <x-input-label for="subject" :value="__('Email subject')" />
                        <input id="subject" name="subject" type="text" maxlength="150" value="{{ old('subject', $reminders->subject) }}" class="{{ $control }}">
                        <x-input-error class="mt-2" :messages="$errors->get('subject')" />
                    </div>
                    <div>
                        <x-input-label for="message" :value="__('Opening message')" />
                        <textarea id="message" name="message" rows="4" maxlength="1000" class="{{ $control }}">{{ old('message', $reminders->message) }}</textarea>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Placeholders: {name}, {event}, {date}, {org}. The email always adds the event details, a Pay now button, your reply-to address and a one-click opt-out.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('message')" />
                    </div>
                </fieldset>

                <button type="submit" class="rounded-xl bg-[#0b1020] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#1f2a4a]">{{ __('Save') }}</button>
            </form>

            <aside class="space-y-6">
                <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70" data-channels>
                    <h3 class="text-sm font-semibold">{{ __('Channels') }}</h3>
                    <ul class="mt-3 space-y-3 text-sm">
                        <li class="flex items-start justify-between gap-3">
                            <span>✉️ {{ __('Email') }}</span>
                            <span @class(['text-right text-xs', 'text-emerald-700' => $mail !== 'held', 'text-amber-700' => $mail === 'held'])>
                                {{ ['smtp' => __('Your SMTP (free)'), 'platform' => __('Platform mail'), 'held' => __('On hold: set up email')][$mail] }}
                            </span>
                        </li>
                        <li class="flex items-start justify-between gap-3">
                            <span>💬 {{ __('WhatsApp') }}</span>
                            <span @class(['text-right text-xs', 'text-emerald-700' => $whatsapp === 'on', 'text-slate-500' => $whatsapp !== 'on'])>
                                {{ ['on' => __('On (add-on)'), 'no-template' => __('Add a payment template'), 'off' => __('Add-on not active: skipped')][$whatsapp] }}
                            </span>
                        </li>
                    </ul>
                    @can('manage-org')
                        <a href="{{ route('settings.notifications.show') }}#{{ $mail === 'held' ? 'email' : 'whatsapp' }}" class="mt-3 inline-block text-xs font-medium text-slate-600 underline hover:text-slate-900">{{ __('Email & WhatsApp settings') }}</a>
                    @endcan
                </section>

                <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <h3 class="text-sm font-semibold">{{ __('Preview') }}</h3>
                    <p class="mt-3 text-xs uppercase tracking-wider text-slate-500">{{ __('Email subject') }}</p>
                    <p class="mt-1 text-sm font-medium" data-preview-subject>{{ $previewSubject }}</p>
                    <a href="{{ route('organiser.events.communications.preview', $event) }}" target="_blank" class="mt-2 inline-block text-xs font-medium text-slate-600 underline hover:text-slate-900">{{ __('Open the email') }} ↗</a>
                    <p class="mt-4 text-xs uppercase tracking-wider text-slate-500">{{ __('WhatsApp (shorter)') }}</p>
                    <p class="mt-1 rounded-2xl rounded-tl-sm bg-[#dcf8c6] p-3 text-sm text-slate-800" data-preview-whatsapp>{{ $previewWhatsApp }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $waHelp }}</p>
                </section>
            </aside>
        </div>

        <section class="rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70">
            <header class="px-6 pt-6 sm:px-8">
                <h3 class="text-lg font-bold">{{ __('Recent reminders') }}</h3>
            </header>
            @if ($logs->isEmpty())
                <p class="px-6 py-6 text-sm text-slate-600 sm:px-8">{{ __('None sent yet.') }}</p>
            @else
                <div class="overflow-x-auto px-6 pb-6 sm:px-8">
                    <table class="mt-4 min-w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-slate-500">
                                <th class="py-2 pe-4 font-medium">{{ __('Participant') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Reminder') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Channel') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Status') }}</th>
                                <th class="py-2 font-medium">{{ __('When') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($logs as $log)
                                <tr>
                                    <td class="py-2 pe-4">{{ $log->registration?->participant_name }}<span class="block text-xs text-slate-500">{{ $log->registration?->email }}</span></td>
                                    <td class="py-2 pe-4">{{ $log->kind === 'manual' ? __('Manual') : '#'.$log->sequence }}</td>
                                    <td class="py-2 pe-4">{{ $log->channel === 'whatsapp' ? 'WhatsApp' : __('Email') }}</td>
                                    <td class="py-2 pe-4" @if ($log->detail) title="{{ $log->detail }}" @endif>{{ __(ucfirst($log->status)) }}@if ($log->detail)<span class="block text-xs text-slate-500">{{ $log->detail }}</span>@endif</td>
                                    <td class="py-2 whitespace-nowrap">{{ ($log->sent_at ?? $log->updated_at)->setTimezone('Asia/Kolkata')->format('j M, g:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
