{{--
    Settings → Email and WhatsApp (Daylight Track, docs/design/daylight-track/Notifications.dc.html):
    Email / WhatsApp segmented tabs in the header, email status banner, sender + SMTP cards beside a
    live preview, WhatsApp add-on, delivery log under both tabs.
--}}
@php
    $statusMessages = [
        'platform-saved' => __('Saved.'),
        'platform-tested' => __('Test email sent through the platform mailer.'),
        'smtp-saved' => __('SMTP settings saved. Send a test email to start using them.'),
        'smtp-verified' => __('Test email sent. Participant emails now go through your SMTP server.'),
        'whatsapp-saved' => __('WhatsApp settings saved.'),
        'whatsapp-verified' => __('Test message sent. Check the phone.'),
    ];
    $paymentTemplateHelp = __('Used by event payment reminders: body {{1}} name, {{2}} event, {{3}} date, {{4}} payment link. Leave blank to send reminders by email only.');
    $field = 'ri-input px-3.5 py-3 text-[15px]';
    $card = 'flex flex-col gap-4 rounded-[28px] bg-white p-[22px] sm:p-[26px]';
    $row = 'border-t border-[#EEF0F6] py-3';
    $waFirst = str_starts_with((string) session('status'), 'whatsapp')
        || $errors->hasAny(['whatsapp_phone_number_id', 'whatsapp_token', 'whatsapp_template_confirmed', 'whatsapp_template_reminder', 'whatsapp_template_payment', 'whatsapp_language', 'whatsapp_test']);
    $startTab = $waFirst ? 'whatsapp' : 'email';
    $canMail = $settings->canSendParticipantMail($org);
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('Email and WhatsApp') }}</x-slot>
    <x-slot name="header">
        <h2>{{ __('Email and WhatsApp') }}</h2>
        <div x-data="{ tab: location.hash === '#whatsapp' ? 'whatsapp' : @js($startTab) }" x-init="$watch('tab', v => $dispatch('notify-tab', v))">
            <x-ri.segmented model="tab" :value="$startTab" :label="__('Sections')" :options="['email' => __('Email'), 'whatsapp' => __('WhatsApp')]" />
        </div>
    </x-slot>

    <div class="mx-auto max-w-[1180px] space-y-5 px-4 pb-12 pt-4 sm:px-6 lg:px-10" x-data="{ tab: location.hash === '#whatsapp' ? 'whatsapp' : @js($startTab) }" @notify-tab.window="tab = $event.detail">
        @if (isset($statusMessages[session('status')]))
            <p class="rounded-[18px] bg-[#E3F4EE] px-4 py-3 text-sm text-[#0B4A38]" role="status">{{ $statusMessages[session('status')] }}</p>
        @endif

        {{-- ===== Email ===== --}}
        <div id="email" class="space-y-5" x-show="tab === 'email'" @if ($startTab !== 'email') x-cloak @endif>
            {{-- How participant email goes out right now --}}
            <section @class([
                'flex flex-wrap items-center justify-between gap-3 rounded-[22px] px-[22px] py-[18px]',
                'bg-[#E3F4EE] text-[#0B4A38]' => $canMail,
                'bg-[#FDF0DA] text-[#6B4A12]' => ! $canMail,
            ]) role="status">
                <span class="flex min-w-0 flex-[1_1_280px] flex-col gap-0.5">
                    @if ($settings->usesCustomSmtp($org))
                        <strong>{{ __('Participant emails go through your SMTP server (:from).', ['from' => $settings->smtp_from_email]) }}</strong>
                        <span class="text-sm text-[#2D5A4C]">{{ __('Free on every plan.') }}</span>
                    @elseif ($settings->usesPlatformMail($org))
                        <strong>{{ __('Participant emails go through Platform mail from :address.', ['address' => $platformFrom->getAddress()]) }}</strong>
                    @else
                        <strong>{{ __('Participant emails are on hold.') }}</strong>
                        <span class="text-sm">
                            {{ __('Connect your own SMTP below (free on every plan) or add Platform mail. Until then passes and reminders are not emailed; participants can still download their pass after paying.') }}
                            @if ($heldCount)
                                {{ trans_choice(':count email held in the last 30 days.|:count emails held in the last 30 days.', $heldCount) }}
                            @endif
                        </span>
                    @endif
                </span>
                @if ($settings->usesCustomSmtp($org) || ($settings->usesPlatformMail($org) && $platformMailIncluded))
                    <form method="post" action="{{ $settings->usesCustomSmtp($org) ? route('settings.notifications.smtp.test') : route('settings.notifications.platform.test') }}">
                        @csrf
                        <input type="hidden" name="test_email" value="{{ auth()->user()->email }}">
                        <button type="submit" class="inline-flex min-h-[44px] items-center rounded-full bg-white px-4 text-sm font-bold text-[#0B4A38] hover:bg-white/80">{{ __('Send a test email') }}</button>
                    </form>
                @else
                    <a href="#smtp" class="inline-flex min-h-[44px] items-center rounded-full bg-white px-4 text-sm font-bold text-[#6B4A12] hover:bg-white/80">{{ __('Use my SMTP') }}</a>
                @endif
            </section>

            <div class="flex flex-wrap items-start gap-5">
                <div class="flex min-w-0 flex-[1_1_420px] flex-col gap-5">
                    {{-- Sender details, used by both email paths --}}
                    <section class="{{ $card }}">
                        <header>
                            <h2 class="text-lg font-bold text-ink">{{ __('What runners receive') }}</h2>
                            <p class="mt-1 text-sm text-muted">{{ __('Email is included in every plan. These details apply whichever way your mail is sent.') }}</p>
                        </header>
                        <form method="post" action="{{ route('settings.notifications.platform') }}" class="flex flex-col gap-4">
                            @csrf
                            @method('put')
                            <div class="grid gap-3.5 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="from_name" :value="__('Sender name')" />
                                    <x-text-input id="from_name" name="from_name" type="text" class="{{ $field }}" :value="old('from_name', $settings->from_name)" :placeholder="$org->name" maxlength="120" />
                                    <x-input-error class="mt-2" :messages="$errors->get('from_name')" />
                                </div>
                                <div>
                                    <x-input-label for="reply_to_email" :value="__('Reply-to email')" />
                                    <x-text-input id="reply_to_email" name="reply_to_email" type="email" class="{{ $field }}" :value="old('reply_to_email', $settings->reply_to_email)" :placeholder="__('Defaults to the org owner\'s email')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('reply_to_email')" />
                                </div>
                            </div>
                            <div>
                                @foreach (['send_reminders' => __('Send a reminder email 24 hours before the event'), 'send_checkin_confirmation' => __('Email participants when they are checked in')] as $key => $label)
                                    <x-ri.switch :name="$key" :checked="(bool) old($key, $settings->{$key})" :label="$label" class="{{ $row }} [&_.text-sm]:text-[15px] [&_.text-sm]:font-normal" />
                                @endforeach
                            </div>
                            <div class="flex justify-end">
                                <x-ri.button variant="primary" size="md" type="submit" class="font-bold">{{ __('Save') }}</x-ri.button>
                            </div>
                        </form>
                    </section>

                    {{-- Own SMTP (free) --}}
                    <section id="smtp" class="{{ $card }}">
                        <header>
                            <h2 class="flex flex-wrap items-center gap-2 text-lg font-bold text-ink">
                                {{ __('Use my SMTP') }}
                                <x-ri.chip tone="paid">{{ __('Free') }}</x-ri.chip>
                                <x-ri.chip :tone="$settings->usesCustomSmtp($org) ? 'paid' : 'ended'">
                                    {{ $settings->usesCustomSmtp($org) ? __('In use') : ($settings->smtp_enabled ? __('Not verified') : __('Off')) }}
                                </x-ri.chip>
                            </h2>
                            <p class="mt-1 text-sm text-muted">{{ __('Send participant emails from your own mailbox or domain, at no extra charge. Mail goes through your server only after a test email succeeds, and it is used ahead of Platform mail.') }}</p>
                        </header>

                        <details class="rounded-[18px] bg-paper px-4 py-3 text-sm text-ink">
                            <summary class="flex min-h-[32px] cursor-pointer items-center font-semibold">{{ __('What to enter') }}</summary>
                            <dl class="mt-3 space-y-2">
                                <div><dt class="font-semibold">{{ __('Host and port') }}</dt><dd class="text-muted">{{ __('From your email provider. Google Workspace or Gmail: smtp.gmail.com, port 587, STARTTLS. Microsoft 365: smtp.office365.com, 587, STARTTLS. Zoho Mail India: smtp.zoho.in, 465, SSL/TLS.') }}</dd></div>
                                <div><dt class="font-semibold">{{ __('Encryption') }}</dt><dd class="text-muted">{{ __('STARTTLS for port 587, SSL/TLS for port 465. Use None only on a private relay.') }}</dd></div>
                                <div><dt class="font-semibold">{{ __('Username') }}</dt><dd class="text-muted">{{ __('Usually the full email address.') }}</dd></div>
                                <div><dt class="font-semibold">{{ __('Password') }}</dt><dd class="text-muted">{{ __('Your SMTP password. Google needs an app password: turn on 2-Step Verification, then create one at myaccount.google.com/apppasswords and paste the 16 characters. Never your normal sign-in password.') }}</dd></div>
                                <div><dt class="font-semibold">{{ __('From email') }}</dt><dd class="text-muted">{{ __('An address that mailbox may send as. Google caps sending (about 500 a day on Gmail, 2,000 on Workspace); for bigger fields use your domain\'s SMTP or Platform mail.') }}</dd></div>
                            </dl>
                        </details>

                        <form method="post" action="{{ route('settings.notifications.smtp') }}" class="flex flex-col gap-4">
                            @csrf
                            @method('put')
                            <x-ri.switch name="smtp_enabled" :checked="(bool) old('smtp_enabled', $settings->smtp_enabled)" :label="__('Use my SMTP server')" class="{{ $row }} border-t-0 pt-0 [&_.text-sm]:text-[15px] [&_.text-sm]:font-normal" />
                            <div class="grid gap-3.5 sm:grid-cols-3">
                                <div class="sm:col-span-2">
                                    <x-input-label for="smtp_host" :value="__('Host')" />
                                    <x-text-input id="smtp_host" name="smtp_host" type="text" class="{{ $field }}" :value="old('smtp_host', $settings->smtp_host)" placeholder="smtp.example.com" />
                                    <x-input-error class="mt-2" :messages="$errors->get('smtp_host')" />
                                </div>
                                <div>
                                    <x-input-label for="smtp_port" :value="__('Port')" />
                                    <x-text-input id="smtp_port" name="smtp_port" type="number" min="1" max="65535" class="{{ $field }}" :value="old('smtp_port', $settings->smtp_port ?? 587)" />
                                    <x-input-error class="mt-2" :messages="$errors->get('smtp_port')" />
                                </div>
                            </div>
                            <div>
                                <x-input-label for="smtp_encryption" :value="__('Encryption')" />
                                <select id="smtp_encryption" name="smtp_encryption" class="{{ $field }}">
                                    @foreach (['tls' => __('STARTTLS (usually port 587)'), 'ssl' => __('SSL/TLS (usually port 465)'), 'none' => __('None')] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('smtp_encryption', $settings->smtp_encryption ?? 'tls') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid gap-3.5 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="smtp_username" :value="__('Username')" />
                                    <x-text-input id="smtp_username" name="smtp_username" type="text" class="{{ $field }}" :value="old('smtp_username', $settings->smtp_username)" autocomplete="off" />
                                </div>
                                <div>
                                    <x-input-label for="smtp_password" :value="__('Password')" />
                                    <x-text-input id="smtp_password" name="smtp_password" type="password" class="{{ $field }}" autocomplete="new-password" :placeholder="$settings->smtp_password ? __('Saved (leave blank to keep)') : ''" />
                                    <x-input-error class="mt-2" :messages="$errors->get('smtp_password')" />
                                </div>
                                <div>
                                    <x-input-label for="smtp_from_email" :value="__('From email')" />
                                    <x-text-input id="smtp_from_email" name="smtp_from_email" type="email" class="{{ $field }}" :value="old('smtp_from_email', $settings->smtp_from_email)" />
                                    <x-input-error class="mt-2" :messages="$errors->get('smtp_from_email')" />
                                </div>
                                <div>
                                    <x-input-label for="smtp_from_name" :value="__('From name')" />
                                    <x-text-input id="smtp_from_name" name="smtp_from_name" type="text" class="{{ $field }}" :value="old('smtp_from_name', $settings->smtp_from_name)" :placeholder="$org->name" />
                                </div>
                            </div>
                            <p class="text-[13px] text-muted">{{ __('The password is stored encrypted. Changing the server details turns your SMTP off until a new test succeeds.') }}</p>
                            <div class="flex justify-end">
                                <x-ri.button variant="primary" size="md" type="submit" class="font-bold">{{ __('Save SMTP') }}</x-ri.button>
                            </div>
                        </form>

                        <form method="post" action="{{ route('settings.notifications.smtp.test') }}" class="flex flex-col gap-3 border-t border-[#EEF0F6] pt-4 sm:flex-row sm:items-end">
                            @csrf
                            <div class="flex-1">
                                <x-input-label for="smtp_test_email" :value="__('Send a test email through my server to')" />
                                <x-text-input id="smtp_test_email" name="test_email" type="email" class="{{ $field }}" :value="auth()->user()->email" required />
                            </div>
                            <x-ri.button variant="secondary" size="md" type="submit">{{ __('Send test') }}</x-ri.button>
                        </form>
                        <x-input-error :messages="$errors->get('smtp_test')" />
                    </section>

                    {{-- Platform mail (paid add-on) --}}
                    <section id="platform-mail" class="{{ $card }}">
                        <header>
                            <h2 class="flex flex-wrap items-center gap-2 text-lg font-bold text-ink">
                                {{ __('Platform mail') }}
                                <x-ri.chip tone="trial">{{ __('Add-on') }}</x-ri.chip>
                                @if ($platformMailIncluded)
                                    <x-ri.chip :tone="$settings->usesPlatformMail($org) ? 'paid' : 'ended'">
                                        {{ $settings->usesPlatformMail($org) ? __('In use') : __('Standby (your SMTP is in use)') }}
                                    </x-ri.chip>
                                @endif
                            </h2>
                            <p class="mt-1 text-sm text-muted">{{ __('No SMTP of your own? Indefine sends participant emails for you (Brevo or Amazon SES) from :address, with your sender name, and replies come to you.', ['address' => $platformFrom->getAddress()]) }}</p>
                        </header>

                        @if ($platformMailIncluded)
                            <form method="post" action="{{ route('settings.notifications.platform.test') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                                @csrf
                                <div class="flex-1">
                                    <x-input-label for="platform_test_email" :value="__('Send a test email to')" />
                                    <x-text-input id="platform_test_email" name="test_email" type="email" class="{{ $field }}" :value="auth()->user()->email" required />
                                </div>
                                <x-ri.button variant="secondary" size="md" type="submit">{{ __('Send test') }}</x-ri.button>
                            </form>
                            <x-input-error :messages="$errors->get('platform_test')" />
                        @else
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-[18px] bg-lilac px-4 py-3 text-sm text-track-deep">
                                <p>{{ __('Platform mail is a paid add-on and is not on your plan.') }}</p>
                                <a href="{{ \App\Support\Contact::url(__('Platform mail add-on for :org', ['org' => $org->name])) }}" class="inline-flex min-h-[44px] items-center font-bold underline-offset-4 hover:underline">{{ __('Add Platform mail') }}</a>
                            </div>
                        @endif
                    </section>
                </div>

                {{-- Preview --}}
                <section class="{{ $card }} min-w-0 flex-[1_1_380px]">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-bold text-ink">{{ __('Preview') }}</h2>
                        <span class="text-[13px] text-muted">{{ __('Registration confirmed') }}</span>
                    </div>
                    @if ($hasRegistration)
                        <div class="overflow-hidden rounded-[20px] bg-paper">
                            <iframe src="{{ route('settings.notifications.preview', 'registration-confirmed') }}" title="{{ __('Registration confirmed') }}" loading="lazy" class="block h-[560px] w-full border-0"></iframe>
                        </div>
                        <p class="text-[13px] text-muted">{{ __('Shown with your latest registration and your event branding.') }}</p>
                        <ul class="flex flex-wrap gap-2 text-sm">
                            @foreach (['registration-confirmed' => __('Registration confirmed'), 'event-reminder' => __('Event reminder'), 'checkin-confirmed' => __('Check-in confirmed')] as $type => $label)
                                <li><a href="{{ route('settings.notifications.preview', $type) }}" target="_blank" rel="noopener" class="inline-flex min-h-[44px] items-center rounded-full bg-paper px-4 font-semibold text-ink hover:bg-mist">{{ $label }}</a></li>
                            @endforeach
                        </ul>
                    @else
                        <p class="rounded-[20px] bg-paper p-5 text-sm text-muted">{{ __('Previews appear once your first registration comes in.') }}</p>
                    @endif
                </section>
            </div>
        </div>

        {{-- ===== WhatsApp ===== --}}
        <div id="whatsapp" x-show="tab === 'whatsapp'" @if ($startTab !== 'whatsapp') x-cloak @endif>
            @if (! $whatsappModule)
                <section class="flex flex-wrap items-center gap-8 rounded-[28px] bg-white p-[22px] sm:p-9">
                    <div class="flex min-w-0 flex-[1_1_320px] flex-col gap-3">
                        <x-ri.chip tone="unpaid" class="self-start">{{ __('Add-on') }}</x-ri.chip>
                        <h2 class="ri-headline text-[30px] text-ink">{{ __('WhatsApp notifications') }}</h2>
                        <p class="text-base leading-relaxed text-muted">{{ __('Runners get their pass and race-day reminders where they already read messages. Ask Indefine to add WhatsApp to your plan, then connect your Meta business number here.') }}</p>
                        <p class="text-sm text-muted">{{ __('WhatsApp is a paid add-on and is not on your plan, so nothing is sent on WhatsApp.') }}</p>
                        <div class="mt-1.5 flex flex-wrap gap-2.5">
                            <x-ri.button variant="primary" size="md" class="font-bold" :href="\App\Support\Contact::url(__('WhatsApp add-on for :org', ['org' => $org->name]))">{{ __('Add WhatsApp') }}</x-ri.button>
                            <x-ri.button variant="secondary" size="md" :href="\App\Support\Contact::url()">{{ __('Ask a question') }}</x-ri.button>
                        </div>
                    </div>
                    <div aria-hidden="true" class="flex flex-[0_1_300px] flex-col gap-2.5 rounded-[24px] bg-[#E9ECF4] p-[18px]">
                        <span class="max-w-[85%] self-start rounded-[16px_16px_16px_4px] bg-white px-3.5 py-3 text-sm leading-snug">{{ __('Hi, your pass for :org is ready. Show it at the gate on race morning.', ['org' => $org->name]) }}</span>
                        <span class="flex h-[150px] w-[150px] items-center justify-center self-start rounded-2xl bg-white text-xs text-muted">{{ __('Pass image') }}</span>
                    </div>
                </section>
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
                <section class="{{ $card }} max-w-[900px]">
                    <header>
                        <h2 class="flex flex-wrap items-center gap-2 text-lg font-bold text-ink">
                            {{ __('WhatsApp notifications') }}
                            <x-ri.chip tone="trial">{{ __('Add-on') }}</x-ri.chip>
                            <x-ri.chip :tone="$settings->usesWhatsApp($org) ? 'paid' : 'ended'">{{ $settings->usesWhatsApp($org) ? __('On') : __('Off') }}</x-ri.chip>
                        </h2>
                        <p class="mt-1 text-sm text-muted">{{ __('Send the confirmation (with a link to the pass PDF) and the event reminder on WhatsApp through your own WhatsApp Business number (Meta Cloud API). Email is sent as well when your email is set up.') }}</p>
                    </header>

                    @unless ($settings->usesWhatsApp($org))
                        <div class="rounded-[18px] bg-paper p-4">
                            <h3 class="text-sm font-semibold text-ink">{{ __('Setup checklist') }}</h3>
                            <ul class="mt-2 space-y-1.5 text-sm text-ink">
                                @foreach ($checklist as $step => $done)
                                    <li class="flex gap-2">
                                        <span aria-hidden="true" @class(['mt-0.5 flex h-4 w-4 flex-none items-center justify-center rounded-full text-[10px] font-bold', 'bg-success text-white' => $done, 'ring-1 ring-inset ring-[#8A92AB]' => ! $done])>{{ $done ? '✓' : '' }}</span>
                                        <span>{{ $step }}<span class="sr-only">{{ $done ? __('(done)') : __('(to do)') }}</span></span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endunless

                    <form method="post" action="{{ route('settings.notifications.whatsapp') }}" class="flex flex-col gap-4">
                        @csrf
                        @method('put')
                        <x-ri.switch name="whatsapp_enabled" :checked="(bool) old('whatsapp_enabled', $settings->whatsapp_enabled)" :label="__('Send WhatsApp notifications')" class="[&_.text-sm]:text-[15px] [&_.text-sm]:font-normal" />
                        <div class="grid gap-3.5 sm:grid-cols-2">
                            <div>
                                <x-input-label for="whatsapp_provider" :value="__('Provider')" />
                                <select id="whatsapp_provider" name="whatsapp_provider" class="{{ $field }}">
                                    @foreach (\App\Services\WhatsApp\WhatsAppGatewayFactory::PROVIDERS as $value => $label)
                                        <option value="{{ $value }}" @selected(old('whatsapp_provider', $settings->whatsapp_provider) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="whatsapp_phone_number_id" :value="__('Phone number ID')" />
                                <x-text-input id="whatsapp_phone_number_id" name="whatsapp_phone_number_id" type="text" inputmode="numeric" class="{{ $field }}" :value="old('whatsapp_phone_number_id', $settings->whatsapp_phone_number_id)" />
                                <x-input-error class="mt-2" :messages="$errors->get('whatsapp_phone_number_id')" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="whatsapp_token" :value="__('Access token')" />
                            <x-text-input id="whatsapp_token" name="whatsapp_token" type="password" class="{{ $field }}" autocomplete="off" :placeholder="$settings->whatsapp_token ? __('Saved (leave blank to keep)') : ''" />
                            <x-input-error class="mt-2" :messages="$errors->get('whatsapp_token')" />
                        </div>
                        <div class="grid gap-3.5 sm:grid-cols-2">
                            <div>
                                <x-input-label for="whatsapp_template_confirmed" :value="__('Confirmation template name')" />
                                <x-text-input id="whatsapp_template_confirmed" name="whatsapp_template_confirmed" type="text" class="{{ $field }}" :value="old('whatsapp_template_confirmed', $settings->whatsapp_template_confirmed)" placeholder="registration_confirmed" />
                                <x-input-error class="mt-2" :messages="$errors->get('whatsapp_template_confirmed')" />
                            </div>
                            <div>
                                <x-input-label for="whatsapp_template_reminder" :value="__('Reminder template name (optional)')" />
                                <x-text-input id="whatsapp_template_reminder" name="whatsapp_template_reminder" type="text" class="{{ $field }}" :value="old('whatsapp_template_reminder', $settings->whatsapp_template_reminder)" placeholder="event_reminder" />
                                <x-input-error class="mt-2" :messages="$errors->get('whatsapp_template_reminder')" />
                            </div>
                            <div>
                                <x-input-label for="whatsapp_template_payment" :value="__('Payment reminder template (optional)')" />
                                <x-text-input id="whatsapp_template_payment" name="whatsapp_template_payment" type="text" class="{{ $field }}" :value="old('whatsapp_template_payment', $settings->whatsapp_template_payment)" placeholder="payment_reminder" />
                                <p class="mt-1.5 text-[13px] text-muted">{{ $paymentTemplateHelp }}</p>
                                <x-input-error class="mt-2" :messages="$errors->get('whatsapp_template_payment')" />
                            </div>
                            <div>
                                <x-input-label for="whatsapp_language" :value="__('Template language code')" />
                                <x-text-input id="whatsapp_language" name="whatsapp_language" type="text" class="{{ $field }}" :value="old('whatsapp_language', $settings->whatsapp_language)" placeholder="en" />
                                <x-input-error class="mt-2" :messages="$errors->get('whatsapp_language')" />
                            </div>
                        </div>
                        <p class="text-[13px] text-muted">{{ __('The token is stored encrypted. If WhatsApp fails, registrations and emails are not affected.') }}</p>
                        <div class="flex justify-end">
                            <x-ri.button variant="primary" size="md" type="submit" class="font-bold">{{ __('Save WhatsApp') }}</x-ri.button>
                        </div>
                    </form>

                    <form method="post" action="{{ route('settings.notifications.whatsapp.test') }}" class="flex flex-col gap-3 border-t border-[#EEF0F6] pt-4 sm:flex-row sm:items-end">
                        @csrf
                        <div class="flex-1">
                            <x-input-label for="test_phone" :value="__('Send Meta\'s hello_world test to')" />
                            <x-text-input id="test_phone" name="test_phone" type="tel" class="{{ $field }}" :placeholder="__('Mobile number')" required />
                        </div>
                        <x-ri.button variant="secondary" size="md" type="submit">{{ __('Send test') }}</x-ri.button>
                    </form>
                    <x-input-error :messages="$errors->get('whatsapp_test')" />
                </section>
            @endif
        </div>

        {{-- Delivery log (both channels) --}}
        <section id="deliveries" class="{{ $card }}">
            <header>
                <h2 class="text-lg font-bold text-ink">{{ __('Delivery status') }}</h2>
                <p class="mt-1 text-sm text-muted">
                    {{ __('Latest participant notifications. Failed sends are retried automatically for about 40 minutes.') }}
                    @if ($failedCount)
                        <span class="font-semibold text-danger">{{ trans_choice(':count failed in the last 30 days.|:count failed in the last 30 days.', $failedCount) }}</span>
                    @endif
                </p>
            </header>

            @if ($deliveries->isEmpty())
                <p class="text-sm text-muted">{{ __('Nothing sent yet.') }}</p>
            @else
                <div class="relative overflow-x-auto">
                    <table class="w-full min-w-[640px] text-sm">
                        <thead>
                            <tr class="bg-paper text-left text-[13px] text-muted">
                                <th scope="col" class="rounded-s-xl px-4 py-3 font-semibold">{{ __('Participant') }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __('Message') }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __('Channel') }}</th>
                                <th scope="col" class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                                <th scope="col" class="rounded-e-xl px-4 py-3 font-semibold">{{ __('Updated') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#EEF0F6]">
                            @foreach ($deliveries as $delivery)
                                <tr>
                                    <td class="px-4 py-3">
                                        <span class="block font-semibold text-ink">{{ $delivery->registration?->participant_name ?? '—' }}</span>
                                        <span class="block text-muted">{{ $delivery->recipient }}</span>
                                    </td>
                                    <td class="px-4 py-3">{{ __(ucfirst(str_replace('_', ' ', $delivery->type))) }}</td>
                                    <td class="px-4 py-3">{{ $delivery->channel === 'whatsapp' ? 'WhatsApp' : __('Email') }}</td>
                                    <td class="px-4 py-3">
                                        <x-ri.chip :tone="match ($delivery->status) { 'sent' => 'paid', 'failed' => 'suspended', default => 'ended' }" :title="$delivery->error">{{ __(ucfirst($delivery->status)) }}</x-ri.chip>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $delivery->updated_at->setTimezone('Asia/Kolkata')->format('j M, g:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
