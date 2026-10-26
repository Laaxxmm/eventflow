<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Email and WhatsApp') }}
        </h2>
    </x-slot>

    @php
        $control = 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full';
        $statusMessages = [
            'platform-saved' => __('Saved.'),
            'platform-tested' => __('Test email sent through the platform mailer.'),
            'smtp-saved' => __('SMTP settings saved. Send a test email to start using them.'),
            'smtp-verified' => __('Test email sent. Participant emails now go through your SMTP server.'),
            'whatsapp-saved' => __('WhatsApp settings saved.'),
            'whatsapp-verified' => __('Test message sent. Check the phone.'),
        ];
        $badge = fn (bool $ok) => $ok ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700';
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (isset($statusMessages[session('status')]))
                <p class="px-4 sm:px-0 text-sm font-medium text-green-600">{{ $statusMessages[session('status')] }}</p>
            @endif

            @php
                $contactEmail = config('plans.brand.contact_email');
            @endphp
            <nav class="px-4 sm:px-0 flex flex-wrap gap-4 text-sm font-medium" aria-label="{{ __('Sections') }}">
                <a href="#email" class="text-gray-700 underline hover:text-gray-900">{{ __('Email') }}</a>
                <a href="#whatsapp" class="text-gray-700 underline hover:text-gray-900">{{ __('WhatsApp') }}</a>
                <a href="#deliveries" class="text-gray-700 underline hover:text-gray-900">{{ __('Delivery status') }}</a>
            </nav>

            {{-- How participant email goes out right now --}}
            <section id="email" @class([
                'p-4 sm:p-6 shadow sm:rounded-lg ring-1',
                'bg-green-50 ring-green-200 text-green-900' => $settings->canSendParticipantMail($org),
                'bg-amber-50 ring-amber-200 text-amber-900' => ! $settings->canSendParticipantMail($org),
            ]) role="status">
                @if ($settings->usesCustomSmtp($org))
                    <p class="font-semibold">{{ __('Participant emails go through your SMTP server (:from).', ['from' => $settings->smtp_from_email]) }}</p>
                @elseif ($settings->usesPlatformMail($org))
                    <p class="font-semibold">{{ __('Participant emails go through Platform mail from :address.', ['address' => $platformFrom->getAddress()]) }}</p>
                @else
                    <p class="font-semibold">{{ __('Participant emails are on hold.') }}</p>
                    <p class="mt-1 text-sm">
                        {{ __('Connect your own SMTP below (free on every plan) or add Platform mail. Until then passes and reminders are not emailed; participants can still download their pass after paying.') }}
                        @if ($heldCount)
                            {{ trans_choice(':count email held in the last 30 days.|:count emails held in the last 30 days.', $heldCount) }}
                        @endif
                    </p>
                @endif
            </section>

            {{-- Sender details, used by both email paths --}}
            <section class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">{{ __('Participant emails') }}</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ __('Email is included in every plan. These details apply whichever way your mail is sent.') }}
                        </p>
                    </header>

                    <form method="post" action="{{ route('settings.notifications.platform') }}" class="mt-6 space-y-6">
                        @csrf
                        @method('put')
                        <div>
                            <x-input-label for="from_name" :value="__('Sender name')" />
                            <x-text-input id="from_name" name="from_name" type="text" class="mt-1 block w-full" :value="old('from_name', $settings->from_name)" :placeholder="$org->name" maxlength="120" />
                            <x-input-error class="mt-2" :messages="$errors->get('from_name')" />
                        </div>
                        <div>
                            <x-input-label for="reply_to_email" :value="__('Reply-to email')" />
                            <x-text-input id="reply_to_email" name="reply_to_email" type="email" class="mt-1 block w-full" :value="old('reply_to_email', $settings->reply_to_email)" :placeholder="__('Defaults to the org owner\'s email')" />
                            <x-input-error class="mt-2" :messages="$errors->get('reply_to_email')" />
                        </div>
                        @foreach (['send_reminders' => __('Send a reminder email 24 hours before the event'), 'send_checkin_confirmation' => __('Email participants when they are checked in')] as $key => $label)
                            <label for="{{ $key }}" class="flex items-center gap-3">
                                <input type="hidden" name="{{ $key }}" value="0">
                                <input id="{{ $key }}" type="checkbox" name="{{ $key }}" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old($key, $settings->{$key}))>
                                <span class="text-sm text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach
                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                    </form>


                    <h3 class="mt-8 font-medium text-gray-900">{{ __('Preview emails') }}</h3>
                    @if ($hasRegistration)
                        <p class="mt-1 text-sm text-gray-600">{{ __('Shown with your latest registration and your event branding.') }}</p>
                        <ul class="mt-2 flex flex-wrap gap-4 text-sm">
                            @foreach (['registration-confirmed' => __('Registration confirmed'), 'event-reminder' => __('Event reminder'), 'checkin-confirmed' => __('Check-in confirmed')] as $type => $label)
                                <li><a href="{{ route('settings.notifications.preview', $type) }}" target="_blank" class="underline text-gray-600 hover:text-gray-900">{{ $label }}</a></li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-1 text-sm text-gray-600">{{ __('Previews appear once your first registration comes in.') }}</p>
                    @endif
                </div>
            </section>

            {{-- Own SMTP (free) --}}
            <section id="smtp" class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">
                            {{ __('Use my SMTP') }}
                            <span class="ms-2 inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">{{ __('Free') }}</span>
                            <span class="ms-1 inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badge($settings->usesCustomSmtp($org)) }}">
                                {{ $settings->usesCustomSmtp($org) ? __('In use') : ($settings->smtp_enabled ? __('Not verified') : __('Off')) }}
                            </span>
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">{{ __('Send participant emails from your own mailbox or domain, at no extra charge. Mail goes through your server only after a test email succeeds, and it is used ahead of Platform mail.') }}</p>
                    </header>

                    <details class="mt-4 rounded-md bg-gray-50 p-4 text-sm text-gray-700">
                        <summary class="cursor-pointer font-medium text-gray-900">{{ __('What to enter') }}</summary>
                        <dl class="mt-3 space-y-2">
                            <div><dt class="font-medium">{{ __('Host and port') }}</dt><dd>{{ __('From your email provider. Google Workspace or Gmail: smtp.gmail.com, port 587, STARTTLS. Microsoft 365: smtp.office365.com, 587, STARTTLS. Zoho Mail India: smtp.zoho.in, 465, SSL/TLS.') }}</dd></div>
                            <div><dt class="font-medium">{{ __('Encryption') }}</dt><dd>{{ __('STARTTLS for port 587, SSL/TLS for port 465. Use None only on a private relay.') }}</dd></div>
                            <div><dt class="font-medium">{{ __('Username') }}</dt><dd>{{ __('Usually the full email address.') }}</dd></div>
                            <div><dt class="font-medium">{{ __('Password') }}</dt><dd>{{ __('Your SMTP password. Google needs an app password: turn on 2-Step Verification, then create one at myaccount.google.com/apppasswords and paste the 16 characters. Never your normal sign-in password.') }}</dd></div>
                            <div><dt class="font-medium">{{ __('From email') }}</dt><dd>{{ __('An address that mailbox may send as. Google caps sending (about 500 a day on Gmail, 2,000 on Workspace); for bigger fields use your domain\'s SMTP or Platform mail.') }}</dd></div>
                        </dl>
                    </details>
                        <form method="post" action="{{ route('settings.notifications.smtp') }}" class="mt-6 space-y-6">
                            @csrf
                            @method('put')
                            <label for="smtp_enabled" class="flex items-center gap-3">
                                <input type="hidden" name="smtp_enabled" value="0">
                                <input id="smtp_enabled" type="checkbox" name="smtp_enabled" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('smtp_enabled', $settings->smtp_enabled))>
                                <span class="text-sm text-gray-700">{{ __('Use my SMTP server') }}</span>
                            </label>
                            <div class="grid gap-6 sm:grid-cols-3">
                                <div class="sm:col-span-2">
                                    <x-input-label for="smtp_host" :value="__('Host')" />
                                    <x-text-input id="smtp_host" name="smtp_host" type="text" class="mt-1 block w-full" :value="old('smtp_host', $settings->smtp_host)" placeholder="smtp.example.com" />
                                    <x-input-error class="mt-2" :messages="$errors->get('smtp_host')" />
                                </div>
                                <div>
                                    <x-input-label for="smtp_port" :value="__('Port')" />
                                    <x-text-input id="smtp_port" name="smtp_port" type="number" min="1" max="65535" class="mt-1 block w-full" :value="old('smtp_port', $settings->smtp_port ?? 587)" />
                                    <x-input-error class="mt-2" :messages="$errors->get('smtp_port')" />
                                </div>
                            </div>
                            <div>
                                <x-input-label for="smtp_encryption" :value="__('Encryption')" />
                                <select id="smtp_encryption" name="smtp_encryption" class="{{ $control }}">
                                    @foreach (['tls' => __('STARTTLS (usually port 587)'), 'ssl' => __('SSL/TLS (usually port 465)'), 'none' => __('None')] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('smtp_encryption', $settings->smtp_encryption ?? 'tls') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid gap-6 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="smtp_username" :value="__('Username')" />
                                    <x-text-input id="smtp_username" name="smtp_username" type="text" class="mt-1 block w-full" :value="old('smtp_username', $settings->smtp_username)" autocomplete="off" />
                                </div>
                                <div>
                                    <x-input-label for="smtp_password" :value="__('Password')" />
                                    <x-text-input id="smtp_password" name="smtp_password" type="password" class="mt-1 block w-full" autocomplete="new-password" :placeholder="$settings->smtp_password ? __('Saved (leave blank to keep)') : ''" />
                                    <x-input-error class="mt-2" :messages="$errors->get('smtp_password')" />
                                </div>
                                <div>
                                    <x-input-label for="smtp_from_email" :value="__('From email')" />
                                    <x-text-input id="smtp_from_email" name="smtp_from_email" type="email" class="mt-1 block w-full" :value="old('smtp_from_email', $settings->smtp_from_email)" />
                                    <x-input-error class="mt-2" :messages="$errors->get('smtp_from_email')" />
                                </div>
                                <div>
                                    <x-input-label for="smtp_from_name" :value="__('From name')" />
                                    <x-text-input id="smtp_from_name" name="smtp_from_name" type="text" class="mt-1 block w-full" :value="old('smtp_from_name', $settings->smtp_from_name)" :placeholder="$org->name" />
                                </div>
                            </div>
                            <p class="text-xs text-gray-500">{{ __('The password is stored encrypted. Changing the server details turns your SMTP off until a new test succeeds.') }}</p>
                            <x-primary-button>{{ __('Save SMTP') }}</x-primary-button>
                        </form>

                        <form method="post" action="{{ route('settings.notifications.smtp.test') }}" class="mt-6 flex flex-col sm:flex-row sm:items-end gap-3">
                            @csrf
                            <div class="flex-1">
                                <x-input-label for="smtp_test_email" :value="__('Send a test email through my server to')" />
                                <x-text-input id="smtp_test_email" name="test_email" type="email" class="mt-1 block w-full" :value="auth()->user()->email" required />
                            </div>
                            <x-secondary-button type="submit">{{ __('Send test') }}</x-secondary-button>
                        </form>
                        <x-input-error class="mt-2" :messages="$errors->get('smtp_test')" />
                </div>
            </section>

            {{-- Platform mail (paid add-on) --}}
            <section id="platform-mail" class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">
                            {{ __('Platform mail') }}
                            <span class="ms-2 inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800">{{ __('Add-on') }}</span>
                            @if ($platformMailIncluded)
                                <span class="ms-1 inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badge($settings->usesPlatformMail($org)) }}">
                                    {{ $settings->usesPlatformMail($org) ? __('In use') : __('Standby (your SMTP is in use)') }}
                                </span>
                            @endif
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">{{ __('No SMTP of your own? Indefine sends participant emails for you (Brevo or Amazon SES) from :address, with your sender name, and replies come to you.', ['address' => $platformFrom->getAddress()]) }}</p>
                    </header>

                    @if ($platformMailIncluded)
                        <form method="post" action="{{ route('settings.notifications.platform.test') }}" class="mt-6 flex flex-col sm:flex-row sm:items-end gap-3">
                            @csrf
                            <div class="flex-1">
                                <x-input-label for="platform_test_email" :value="__('Send a test email to')" />
                                <x-text-input id="platform_test_email" name="test_email" type="email" class="mt-1 block w-full" :value="auth()->user()->email" required />
                            </div>
                            <x-secondary-button type="submit">{{ __('Send test') }}</x-secondary-button>
                        </form>
                        <x-input-error class="mt-2" :messages="$errors->get('platform_test')" />
                    @else
                        <div class="mt-6 rounded-md bg-indigo-50 p-4 text-sm text-indigo-900">
                            <p>{{ __('Platform mail is a paid add-on and is not on your plan.') }}</p>
                            <a href="mailto:{{ $contactEmail }}?subject={{ rawurlencode(__('Platform mail add-on for :org', ['org' => $org->name])) }}" class="mt-2 inline-block font-semibold underline">{{ __('Add Platform mail') }}</a>
                        </div>
                    @endif
                </div>
            </section>

            {{-- WhatsApp (paid add-on) --}}
            <section id="whatsapp" class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">
                            {{ __('WhatsApp notifications') }}
                            <span class="ms-2 inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800">{{ __('Add-on') }}</span>
                            @if ($whatsappModule)
                                <span class="ms-2 inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badge($settings->usesWhatsApp($org)) }}">
                                    {{ $settings->usesWhatsApp($org) ? __('On') : __('Off') }}
                                </span>
                            @endif
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">{{ __('Send the confirmation (with a link to the pass PDF) and the event reminder on WhatsApp through your own WhatsApp Business number (Meta Cloud API). Email is sent as well when your email is set up.') }}</p>
                    </header>

                    @if (! $whatsappModule)
                        <div class="mt-6 rounded-md bg-indigo-50 p-4 text-sm text-indigo-900">
                            <p>{{ __('WhatsApp is a paid add-on and is not on your plan, so nothing is sent on WhatsApp.') }}</p>
                            <a href="mailto:{{ $contactEmail }}?subject={{ rawurlencode(__('WhatsApp add-on for :org', ['org' => $org->name])) }}" class="mt-2 inline-block font-semibold underline">{{ __('Add WhatsApp') }}</a>
                        </div>
                    @else
                        @php
                            $checklist = [
                            __('A WhatsApp Business account in Meta Business Manager, with a phone number added') => filled($settings->whatsapp_phone_number_id),
                            __('A permanent access token (System User) with whatsapp_business_messaging permission') => filled($settings->whatsapp_token),
                            __('An approved template for the confirmation: document header + body {{1}} name, {{2}} event, {{3}} pass no, {{4}} date') => filled($settings->whatsapp_template_confirmed),
                            __('Optional: an approved reminder template: body {{1}} name, {{2}} event, {{3}} date, {{4}} venue') => filled($settings->whatsapp_template_reminder),
                            __('A test message sent from this page') => $settings->whatsapp_verified_at !== null,
                            ];
                        @endphp
                        @unless ($settings->usesWhatsApp($org))
                            <div class="mt-6 rounded-md bg-gray-50 p-4">
                                <h3 class="text-sm font-medium text-gray-900">{{ __('Setup checklist') }}</h3>
                                <ul class="mt-2 space-y-1 text-sm text-gray-700">
                                    @foreach ($checklist as $step => $done)
                                        <li class="flex gap-2"><span aria-hidden="true">{{ $done ? '✓' : '○' }}</span><span>{{ $step }}</span></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endunless

                        <form method="post" action="{{ route('settings.notifications.whatsapp') }}" class="mt-6 space-y-6">
                            @csrf
                            @method('put')
                            <label for="whatsapp_enabled" class="flex items-center gap-3">
                                <input type="hidden" name="whatsapp_enabled" value="0">
                                <input id="whatsapp_enabled" type="checkbox" name="whatsapp_enabled" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('whatsapp_enabled', $settings->whatsapp_enabled))>
                                <span class="text-sm text-gray-700">{{ __('Send WhatsApp notifications') }}</span>
                            </label>
                            <div>
                                <x-input-label for="whatsapp_provider" :value="__('Provider')" />
                                <select id="whatsapp_provider" name="whatsapp_provider" class="{{ $control }}">
                                    @foreach (\App\Services\WhatsApp\WhatsAppGatewayFactory::PROVIDERS as $value => $label)
                                        <option value="{{ $value }}" @selected(old('whatsapp_provider', $settings->whatsapp_provider) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="whatsapp_phone_number_id" :value="__('Phone number ID')" />
                                <x-text-input id="whatsapp_phone_number_id" name="whatsapp_phone_number_id" type="text" inputmode="numeric" class="mt-1 block w-full" :value="old('whatsapp_phone_number_id', $settings->whatsapp_phone_number_id)" />
                                <x-input-error class="mt-2" :messages="$errors->get('whatsapp_phone_number_id')" />
                            </div>
                            <div>
                                <x-input-label for="whatsapp_token" :value="__('Access token')" />
                                <x-text-input id="whatsapp_token" name="whatsapp_token" type="password" class="mt-1 block w-full" autocomplete="off" :placeholder="$settings->whatsapp_token ? __('Saved (leave blank to keep)') : ''" />
                                <x-input-error class="mt-2" :messages="$errors->get('whatsapp_token')" />
                            </div>
                            <div class="grid gap-6 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="whatsapp_template_confirmed" :value="__('Confirmation template name')" />
                                    <x-text-input id="whatsapp_template_confirmed" name="whatsapp_template_confirmed" type="text" class="mt-1 block w-full" :value="old('whatsapp_template_confirmed', $settings->whatsapp_template_confirmed)" placeholder="registration_confirmed" />
                                    <x-input-error class="mt-2" :messages="$errors->get('whatsapp_template_confirmed')" />
                                </div>
                                <div>
                                    <x-input-label for="whatsapp_template_reminder" :value="__('Reminder template name (optional)')" />
                                    <x-text-input id="whatsapp_template_reminder" name="whatsapp_template_reminder" type="text" class="mt-1 block w-full" :value="old('whatsapp_template_reminder', $settings->whatsapp_template_reminder)" placeholder="event_reminder" />
                                    <x-input-error class="mt-2" :messages="$errors->get('whatsapp_template_reminder')" />
                                </div>
                                <div>
                                    <x-input-label for="whatsapp_language" :value="__('Template language code')" />
                                    <x-text-input id="whatsapp_language" name="whatsapp_language" type="text" class="mt-1 block w-full" :value="old('whatsapp_language', $settings->whatsapp_language)" placeholder="en" />
                                    <x-input-error class="mt-2" :messages="$errors->get('whatsapp_language')" />
                                </div>
                            </div>
                            <p class="text-xs text-gray-500">{{ __('The token is stored encrypted. If WhatsApp fails, registrations and emails are not affected.') }}</p>
                            <x-primary-button>{{ __('Save WhatsApp') }}</x-primary-button>
                        </form>

                        <form method="post" action="{{ route('settings.notifications.whatsapp.test') }}" class="mt-6 flex flex-col sm:flex-row sm:items-end gap-3">
                            @csrf
                            <div class="flex-1">
                                <x-input-label for="test_phone" :value="__('Send Meta\'s hello_world test to')" />
                                <x-text-input id="test_phone" name="test_phone" type="tel" class="mt-1 block w-full" :placeholder="__('Mobile number')" required />
                            </div>
                            <x-secondary-button type="submit">{{ __('Send test') }}</x-secondary-button>
                        </form>
                        <x-input-error class="mt-2" :messages="$errors->get('whatsapp_test')" />
                    @endif
                </div>
            </section>

            {{-- Delivery log --}}
            <section id="deliveries" class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <header>
                    <h2 class="text-lg font-medium text-gray-900">{{ __('Delivery status') }}</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('Latest participant notifications. Failed sends are retried automatically for about 40 minutes.') }}
                        @if ($failedCount)
                            <span class="font-medium text-red-600">{{ trans_choice(':count failed in the last 30 days.|:count failed in the last 30 days.', $failedCount) }}</span>
                        @endif
                    </p>
                </header>

                @if ($deliveries->isEmpty())
                    <p class="mt-6 text-sm text-gray-600">{{ __('Nothing sent yet.') }}</p>
                @else
                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-600 border-b">
                                    <th class="py-2 pe-4 font-medium">{{ __('Participant') }}</th>
                                    <th class="py-2 pe-4 font-medium">{{ __('Message') }}</th>
                                    <th class="py-2 pe-4 font-medium">{{ __('Channel') }}</th>
                                    <th class="py-2 pe-4 font-medium">{{ __('Status') }}</th>
                                    <th class="py-2 font-medium">{{ __('Updated') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach ($deliveries as $delivery)
                                    <tr>
                                        <td class="py-3 pe-4">
                                            <span class="block">{{ $delivery->registration?->participant_name ?? '—' }}</span>
                                            <span class="block text-gray-500">{{ $delivery->recipient }}</span>
                                        </td>
                                        <td class="py-3 pe-4">{{ __(ucfirst(str_replace('_', ' ', $delivery->type))) }}</td>
                                        <td class="py-3 pe-4">{{ $delivery->channel === 'whatsapp' ? 'WhatsApp' : __('Email') }}</td>
                                        <td class="py-3 pe-4">
                                            <span @class([
                                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                                'bg-green-100 text-green-800' => $delivery->status === 'sent',
                                                'bg-red-100 text-red-800' => $delivery->status === 'failed',
                                                'bg-gray-100 text-gray-700' => in_array($delivery->status, ['queued', 'skipped'], true),
                                            ]) @if ($delivery->error) title="{{ $delivery->error }}" @endif>{{ __(ucfirst($delivery->status)) }}</span>
                                        </td>
                                        <td class="py-3 whitespace-nowrap">{{ $delivery->updated_at->setTimezone('Asia/Kolkata')->format('j M, g:i A') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
