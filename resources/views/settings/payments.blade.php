@php
    $names = ['cashfree' => 'Cashfree', 'razorpay' => 'Razorpay'];
    $current = $saved[$active];
    $input = 'mt-1 block w-full rounded-xl border-mist text-sm focus:border-ink focus:ring-ink';
    $label = 'text-sm font-medium text-ink';
    $tab = old('provider', $active);
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('Payments') }}</x-slot>
    <x-slot name="header">
        <div>
            <h2>{{ __('Payments') }}</h2>
            <p class="mt-1 text-sm text-muted">{{ __('Connect your own payment gateway so registration fees settle straight into your account.') }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-8 px-4 py-8 sm:px-6 lg:px-10">
        @if (session('status'))
            <p class="rounded-xl bg-green-50 p-4 text-sm font-medium text-green-800" role="status">{{ session('status') }}</p>
        @endif
        @error('test')
            <p class="rounded-xl bg-[#FBE4E4] p-4 text-sm font-medium text-danger" role="alert">{{ $message }}</p>
        @enderror

        {{-- Where the money goes now --}}
        <section class="rounded-[28px] bg-white p-6 sm:p-8">
            <p class="text-sm font-medium text-muted">{{ __('Registration payments go to') }}</p>
            <div class="mt-2 flex flex-wrap items-center justify-between gap-4">
                <div>
                    @if ($ownActive)
                        <p class="text-xl font-bold">{{ __('Your :provider account', ['provider' => $names[$active]]) }}</p>
                        <p class="mt-1 flex flex-wrap items-center gap-2 text-sm">
                            <span @class(['rounded-full px-2.5 py-0.5 text-xs font-semibold', 'bg-[#FDF0DA] text-[#6B4A12]' => $current['mode'] !== 'live', 'bg-green-100 text-green-800' => $current['mode'] === 'live'])>
                                {{ $current['mode'] === 'live' ? __('Live mode') : __('Test mode') }}
                            </span>
                            @if ($current['verified_at'])
                                <span class="text-green-700">{{ __('Connection checked :when', ['when' => \Illuminate\Support\Carbon::parse($current['verified_at'])->timezone('Asia/Kolkata')->format('j M, g:i A')]) }}</span>
                            @else
                                <span class="text-[#6B4A12]">{{ __('Not tested yet') }}</span>
                            @endif
                        </p>
                    @elseif ($platformReady)
                        <p class="text-xl font-bold">{{ __('EventFlow’s :provider account', ['provider' => $names[$active]]) }}</p>
                        <p class="mt-1 text-sm text-muted">{{ __('Connect your own account below to receive payments directly.') }}</p>
                    @else
                        <p class="text-xl font-bold text-danger">{{ __('Not set up') }}</p>
                        <p class="mt-1 text-sm text-muted">{{ __('Participants cannot pay until you connect Cashfree or Razorpay below.') }}</p>
                    @endif
                </div>
                @if ($ownActive)
                    <form method="POST" action="{{ route('settings.payments.test') }}">
                        @csrf
                        <button type="submit" class="rounded-xl bg-ink px-5 py-2.5 text-sm font-semibold text-white hover:bg-ink/90">{{ __('Test connection') }}</button>
                    </form>
                @endif
            </div>
        </section>

        {{-- Connect a gateway --}}
        <section class="rounded-[28px] bg-white" x-data="{ tab: @js($tab) }">
            <div class="flex gap-1 border-b border-[#EEF0F6] p-2" role="tablist">
                @foreach ($names as $key => $name)
                    <button type="button" role="tab" @click="tab = @js($key)" :aria-selected="tab === @js($key)"
                            class="flex flex-1 items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold"
                            :class="tab === @js($key) ? 'bg-paper text-ink' : 'text-muted hover:text-ink'">
                        {{ $name }}
                        @if ($saved[$key]['connected'])
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">{{ $active === $key ? __('In use') : __('Saved') }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            {{-- Cashfree --}}
            <div x-show="tab === 'cashfree'" class="p-6 sm:p-8" role="tabpanel">
                <form method="POST" action="{{ route('settings.payments.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="provider" value="cashfree">

                    <fieldset>
                        <legend class="{{ $label }}">{{ __('Mode') }}</legend>
                        <div class="mt-2 inline-flex rounded-xl bg-paper p-1 text-sm">
                            @foreach (['test' => __('Test (sandbox)'), 'live' => __('Live')] as $value => $text)
                                <label class="cursor-pointer rounded-lg px-4 py-1.5 font-medium has-[:checked]:bg-white has-[:checked]:shadow-sm">
                                    <input type="radio" name="mode" value="{{ $value }}" class="sr-only" @checked(old('mode', $saved['cashfree']['mode']) === $value)>
                                    {{ $text }}
                                </label>
                            @endforeach
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('mode')" />
                    </fieldset>

                    <div>
                        <label for="cf_app_id" class="{{ $label }}">{{ __('App ID') }}</label>
                        <input id="cf_app_id" name="app_id" type="text" autocomplete="off" class="{{ $input }}" value="{{ old('provider') === 'cashfree' ? old('app_id') : $saved['cashfree']['app_id'] }}">
                        <x-input-error class="mt-2" :messages="old('provider') === 'cashfree' ? $errors->get('app_id') : []" />
                    </div>
                    <div>
                        <label for="cf_secret_key" class="{{ $label }}">{{ __('Secret key') }}</label>
                        <input id="cf_secret_key" name="secret_key" type="password" autocomplete="new-password" class="{{ $input }}" placeholder="{{ $saved['cashfree']['secret_key'] ? __('Saved :mask — leave empty to keep', ['mask' => $saved['cashfree']['secret_key']]) : '' }}">
                        <x-input-error class="mt-2" :messages="old('provider') === 'cashfree' ? $errors->get('secret_key') : []" />
                    </div>
                    <div>
                        <label for="cf_webhook_secret" class="{{ $label }}">{{ __('Webhook secret') }} <span class="font-normal text-muted">{{ __('(optional; Cashfree signs webhooks with the secret key by default)') }}</span></label>
                        <input id="cf_webhook_secret" name="webhook_secret" type="password" autocomplete="new-password" class="{{ $input }}" placeholder="{{ $saved['cashfree']['webhook_secret'] ? __('Saved :mask — leave empty to keep', ['mask' => $saved['cashfree']['webhook_secret']]) : '' }}">
                    </div>

                    @include('settings.partials.webhook-url', ['url' => $webhooks['cashfree'], 'steps' => __('Cashfree Merchant Dashboard → Developers → Webhooks → Add webhook URL. Select the payment success and failure events.')])

                    <div class="flex flex-wrap items-center gap-3 border-t border-[#EEF0F6] pt-5">
                        <button type="submit" class="rounded-xl bg-ink px-5 py-2.5 text-sm font-semibold text-white hover:bg-ink/90">{{ __('Save and use Cashfree') }}</button>
                        <p class="text-xs text-muted">{{ __('Keys: Cashfree Merchant Dashboard → Developers → API Keys.') }}</p>
                    </div>
                </form>
                @if ($saved['cashfree']['connected'])
                    <form method="POST" action="{{ route('settings.payments.disconnect', 'cashfree') }}" class="mt-4" onsubmit="return confirm(@js(__('Remove your Cashfree keys from EventFlow?')))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-medium text-danger hover:underline">{{ __('Disconnect Cashfree') }}</button>
                    </form>
                @endif
            </div>

            {{-- Razorpay --}}
            <div x-show="tab === 'razorpay'" x-cloak class="p-6 sm:p-8" role="tabpanel">
                <form method="POST" action="{{ route('settings.payments.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="provider" value="razorpay">

                    <div>
                        <label for="rp_key_id" class="{{ $label }}">{{ __('Key ID') }} <span class="font-normal text-muted">{{ __('(rzp_test_… for test mode, rzp_live_… for live)') }}</span></label>
                        <input id="rp_key_id" name="key_id" type="text" autocomplete="off" class="{{ $input }}" value="{{ old('provider') === 'razorpay' ? old('key_id') : $saved['razorpay']['key_id'] }}">
                        <x-input-error class="mt-2" :messages="old('provider') === 'razorpay' ? $errors->get('key_id') : []" />
                    </div>
                    <div>
                        <label for="rp_key_secret" class="{{ $label }}">{{ __('Key secret') }}</label>
                        <input id="rp_key_secret" name="key_secret" type="password" autocomplete="new-password" class="{{ $input }}" placeholder="{{ $saved['razorpay']['key_secret'] ? __('Saved :mask — leave empty to keep', ['mask' => $saved['razorpay']['key_secret']]) : '' }}">
                        <x-input-error class="mt-2" :messages="old('provider') === 'razorpay' ? $errors->get('key_secret') : []" />
                    </div>
                    <div>
                        <label for="rp_webhook_secret" class="{{ $label }}">{{ __('Webhook secret') }}</label>
                        <input id="rp_webhook_secret" name="webhook_secret" type="password" autocomplete="new-password" class="{{ $input }}" placeholder="{{ $saved['razorpay']['webhook_secret'] ? __('Saved :mask — leave empty to keep', ['mask' => $saved['razorpay']['webhook_secret']]) : __('The secret you type when adding the webhook in Razorpay') }}">
                        <x-input-error class="mt-2" :messages="old('provider') === 'razorpay' ? $errors->get('webhook_secret') : []" />
                    </div>

                    @include('settings.partials.webhook-url', ['url' => $webhooks['razorpay'], 'steps' => __('Razorpay Dashboard → Account & Settings → Webhooks → Add new webhook. Paste this URL, choose a secret (enter it above) and select payment.captured and payment.failed.')])

                    <div class="flex flex-wrap items-center gap-3 border-t border-[#EEF0F6] pt-5">
                        <button type="submit" class="rounded-xl bg-ink px-5 py-2.5 text-sm font-semibold text-white hover:bg-ink/90">{{ __('Save and use Razorpay') }}</button>
                        <p class="text-xs text-muted">{{ __('Keys: Razorpay Dashboard → Account & Settings → API Keys.') }}</p>
                    </div>
                </form>
                @if ($saved['razorpay']['connected'])
                    <form method="POST" action="{{ route('settings.payments.disconnect', 'razorpay') }}" class="mt-4" onsubmit="return confirm(@js(__('Remove your Razorpay keys from EventFlow?')))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-medium text-danger hover:underline">{{ __('Disconnect Razorpay') }}</button>
                    </form>
                @endif
            </div>
        </section>

        <p class="flex items-start gap-2 text-sm text-muted">
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            {{ __('Keys are stored encrypted and only owners can open this page. Secrets are never shown again; leave a secret field empty to keep the saved one. Start in test mode, run a test registration, then switch to live keys.') }}
        </p>
    </div>
</x-app-layout>
