{{--
    Settings → Payments (Daylight Track, docs/design/daylight-track/Payments.dc.html): status banner,
    provider cards, one keys card per provider with the webhook URL.
--}}
@php
    $names = ['cashfree' => 'Cashfree', 'razorpay' => 'Razorpay'];
    $current = $saved[$active];
    $input = 'ri-input px-3.5 py-3 text-[15px]';
    $label = 'ri-label';
    $hint = 'mt-1.5 text-[13px] text-muted';
    $tab = old('provider', $active);
    $verified = $ownActive && $current['verified_at'];
    $banner = match (true) {
        $verified => ['bg' => 'bg-[#E3F4EE]', 'title' => 'text-[#0B4A38]', 'sub' => 'text-[#2D5A4C]', 'dot' => 'bg-success'],
        $ownActive => ['bg' => 'bg-[#FDF0DA]', 'title' => 'text-[#6B4A12]', 'sub' => 'text-[#6B4A12]', 'dot' => 'bg-warning'],
        $platformReady => ['bg' => 'bg-lilac', 'title' => 'text-track-deep', 'sub' => 'text-track-deep', 'dot' => 'bg-track'],
        default => ['bg' => 'bg-[#FBE4E4]', 'title' => 'text-[#6E1414]', 'sub' => 'text-[#6E1414]', 'dot' => 'bg-danger'],
    };
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('Payments') }}</x-slot>
    <x-slot name="header">
        <h2>{{ __('Payments') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-[1180px] space-y-5 px-4 pb-12 pt-4 sm:px-6 lg:px-10">
        <div class="max-w-[900px] space-y-5">
            @if (session('status'))
                <p class="rounded-[18px] bg-[#E3F4EE] px-4 py-3 text-sm text-[#0B4A38]" role="status">{{ session('status') }}</p>
            @endif
            @error('test')
                <p class="rounded-[18px] bg-[#FBE4E4] px-4 py-3 text-sm text-[#6E1414]" role="alert">{{ $message }}</p>
            @enderror

            {{-- Where the money goes now --}}
            <section class="flex flex-wrap items-center gap-[18px] rounded-[24px] p-[22px] {{ $banner['bg'] }}">
                <span aria-hidden="true" class="flex h-12 w-12 flex-none items-center justify-center rounded-full text-white {{ $banner['dot'] }}">
                    @if ($verified)
                        <svg class="h-[22px] w-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg>
                    @else
                        <svg class="h-[22px] w-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M12 7v6M12 17h.01"/></svg>
                    @endif
                </span>
                <div class="flex min-w-0 flex-[1_1_200px] flex-col gap-1">
                    @if ($ownActive)
                        <strong class="text-[17px] {{ $banner['title'] }}">{{ __('Entry fees go straight to your :provider account', ['provider' => $names[$active]]) }}</strong>
                        <span class="text-sm {{ $banner['sub'] }}">
                            {{ $current['mode'] === 'live' ? __('Live mode') : __('Test mode') }}.
                            @if ($current['verified_at'])
                                {{ __('Connection checked :when', ['when' => \Illuminate\Support\Carbon::parse($current['verified_at'])->diffForHumans()]) }}.
                            @else
                                {{ __('Not tested yet') }}.
                            @endif
                        </span>
                    @elseif ($platformReady)
                        <strong class="text-[17px] {{ $banner['title'] }}">{{ __('EventFlow’s :provider account', ['provider' => $names[$active]]) }}</strong>
                        <span class="text-sm {{ $banner['sub'] }}">{{ __('Connect your own account below to receive payments directly.') }}</span>
                    @else
                        <strong class="text-[17px] {{ $banner['title'] }}">{{ __('Not set up') }}</strong>
                        <span class="text-sm {{ $banner['sub'] }}">{{ __('Participants cannot pay until you connect Cashfree or Razorpay below.') }}</span>
                    @endif
                </div>
                @if ($ownActive)
                    <form method="POST" action="{{ route('settings.payments.test') }}" id="payments-test">
                        @csrf
                        <button type="submit" class="inline-flex min-h-[44px] items-center rounded-full bg-white px-[18px] text-sm font-bold {{ $banner['title'] }} hover:bg-white/80">{{ __('Test connection') }}</button>
                    </form>
                @endif
            </section>

            <div x-data="{ tab: @js($tab) }" class="space-y-5">
                {{-- Pick a gateway --}}
                <div role="group" aria-label="{{ __('Payment provider') }}" class="grid gap-3.5 sm:grid-cols-2">
                    @foreach ($names as $key => $name)
                        <button type="button" @click="tab = @js($key)" :aria-pressed="tab === @js($key) ? 'true' : 'false'" aria-pressed="{{ $tab === $key ? 'true' : 'false' }}"
                                class="flex min-h-[44px] items-center gap-3.5 rounded-[22px] border-2 bg-white p-5 text-left transition duration-200 ease-track"
                                :class="tab === @js($key) ? 'border-track' : 'border-transparent hover:border-mist'">
                            <span aria-hidden="true" class="flex h-5 w-5 flex-none items-center justify-center rounded-full border-2"
                                  :class="tab === @js($key) ? 'border-track' : 'border-[#8A92AB]'">
                                <span class="h-2.5 w-2.5 rounded-full bg-track" x-show="tab === @js($key)"></span>
                            </span>
                            <span class="flex flex-1 flex-col gap-0.5">
                                <strong class="text-base text-ink">{{ $name }}</strong>
                                <span class="text-[13px] text-muted">{{ __('UPI, cards, netbanking') }}</span>
                            </span>
                            @if ($saved[$key]['connected'])
                                <x-ri.chip tone="trial">{{ $active === $key ? __('In use') : __('Saved') }}</x-ri.chip>
                            @else
                                <span class="text-xs font-semibold text-muted">{{ __('Not set up') }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                {{-- Cashfree --}}
                <section x-show="tab === 'cashfree'" @if ($tab !== 'cashfree') x-cloak @endif class="rounded-[28px] bg-white p-[22px] sm:p-7">
                    <form method="POST" action="{{ route('settings.payments.update') }}" class="flex flex-col gap-5">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="provider" value="cashfree">

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="text-xl font-bold text-ink">{{ __(':provider keys', ['provider' => 'Cashfree']) }}</h2>
                            <div>
                                <x-ri.segmented name="mode" :label="__('Mode')" :value="old('mode', $saved['cashfree']['mode'])" :options="['test' => __('Test (sandbox)'), 'live' => __('Live')]" />
                                <x-input-error class="mt-2" :messages="$errors->get('mode')" />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
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
                        </div>
                        <div>
                            <label for="cf_webhook_secret" class="{{ $label }}">{{ __('Webhook secret') }}</label>
                            <input id="cf_webhook_secret" name="webhook_secret" type="password" autocomplete="new-password" class="{{ $input }}" placeholder="{{ $saved['cashfree']['webhook_secret'] ? __('Saved :mask — leave empty to keep', ['mask' => $saved['cashfree']['webhook_secret']]) : '' }}">
                            <p class="{{ $hint }}">{{ __('(optional; Cashfree signs webhooks with the secret key by default)') }}</p>
                        </div>

                        @include('settings.partials.webhook-url', ['provider' => 'Cashfree', 'url' => $webhooks['cashfree'], 'steps' => __('Cashfree Merchant Dashboard → Developers → Webhooks → Add webhook URL. Select the payment success and failure events.')])

                        <p class="text-[13px] text-muted">{{ __('Keys: Cashfree Merchant Dashboard → Developers → API Keys.') }}</p>

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#EEF0F6] pt-[18px]">
                            <span>
                                @if ($saved['cashfree']['connected'])
                                    <x-ri.button variant="danger-text" size="sm" type="submit" form="disconnect-cashfree" class="!px-1">{{ __('Disconnect Cashfree') }}</x-ri.button>
                                @endif
                            </span>
                            <span class="flex flex-wrap justify-end gap-2.5">
                                @if ($ownActive && $active === 'cashfree')
                                    <x-ri.button variant="secondary" size="md" type="submit" form="payments-test">{{ __('Test connection') }}</x-ri.button>
                                @endif
                                <x-ri.button variant="primary" size="md" type="submit" class="font-bold">{{ __('Save and use Cashfree') }}</x-ri.button>
                            </span>
                        </div>
                    </form>
                </section>

                {{-- Razorpay --}}
                <section x-show="tab === 'razorpay'" @if ($tab !== 'razorpay') x-cloak @endif class="rounded-[28px] bg-white p-[22px] sm:p-7">
                    <form method="POST" action="{{ route('settings.payments.update') }}" class="flex flex-col gap-5">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="provider" value="razorpay">

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="text-xl font-bold text-ink">{{ __(':provider keys', ['provider' => 'Razorpay']) }}</h2>
                            @if ($saved['razorpay']['connected'])
                                {{-- Razorpay's mode follows the key prefix, so this is a read-out, not a toggle. --}}
                                <x-ri.chip :tone="$saved['razorpay']['mode'] === 'live' ? 'paid' : 'unpaid'">{{ $saved['razorpay']['mode'] === 'live' ? __('Live mode') : __('Test mode') }}</x-ri.chip>
                            @endif
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="rp_key_id" class="{{ $label }}">{{ __('Key ID') }}</label>
                                <input id="rp_key_id" name="key_id" type="text" autocomplete="off" class="{{ $input }}" value="{{ old('provider') === 'razorpay' ? old('key_id') : $saved['razorpay']['key_id'] }}">
                                <p class="{{ $hint }}">{{ __('(rzp_test_… for test mode, rzp_live_… for live)') }}</p>
                                <x-input-error class="mt-2" :messages="old('provider') === 'razorpay' ? $errors->get('key_id') : []" />
                            </div>
                            <div>
                                <label for="rp_key_secret" class="{{ $label }}">{{ __('Key secret') }}</label>
                                <input id="rp_key_secret" name="key_secret" type="password" autocomplete="new-password" class="{{ $input }}" placeholder="{{ $saved['razorpay']['key_secret'] ? __('Saved :mask — leave empty to keep', ['mask' => $saved['razorpay']['key_secret']]) : '' }}">
                                <x-input-error class="mt-2" :messages="old('provider') === 'razorpay' ? $errors->get('key_secret') : []" />
                            </div>
                        </div>
                        <div>
                            <label for="rp_webhook_secret" class="{{ $label }}">{{ __('Webhook secret') }}</label>
                            <input id="rp_webhook_secret" name="webhook_secret" type="password" autocomplete="new-password" class="{{ $input }}" placeholder="{{ $saved['razorpay']['webhook_secret'] ? __('Saved :mask — leave empty to keep', ['mask' => $saved['razorpay']['webhook_secret']]) : __('The secret you type when adding the webhook in Razorpay') }}">
                            <x-input-error class="mt-2" :messages="old('provider') === 'razorpay' ? $errors->get('webhook_secret') : []" />
                        </div>

                        @include('settings.partials.webhook-url', ['provider' => 'Razorpay', 'url' => $webhooks['razorpay'], 'steps' => __('Razorpay Dashboard → Account & Settings → Webhooks → Add new webhook. Paste this URL, choose a secret (enter it above) and select payment.captured and payment.failed.')])

                        <p class="text-[13px] text-muted">{{ __('Keys: Razorpay Dashboard → Account & Settings → API Keys.') }}</p>

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#EEF0F6] pt-[18px]">
                            <span>
                                @if ($saved['razorpay']['connected'])
                                    <x-ri.button variant="danger-text" size="sm" type="submit" form="disconnect-razorpay" class="!px-1">{{ __('Disconnect Razorpay') }}</x-ri.button>
                                @endif
                            </span>
                            <span class="flex flex-wrap justify-end gap-2.5">
                                @if ($ownActive && $active === 'razorpay')
                                    <x-ri.button variant="secondary" size="md" type="submit" form="payments-test">{{ __('Test connection') }}</x-ri.button>
                                @endif
                                <x-ri.button variant="primary" size="md" type="submit" class="font-bold">{{ __('Save and use Razorpay') }}</x-ri.button>
                            </span>
                        </div>
                    </form>
                </section>
            </div>

            {{-- Disconnect forms live outside the key forms (no nesting); the buttons above point at them. --}}
            @foreach ($names as $key => $name)
                @if ($saved[$key]['connected'])
                    <form method="POST" action="{{ route('settings.payments.disconnect', $key) }}" id="disconnect-{{ $key }}" class="hidden" onsubmit="return confirm(@js($key === 'cashfree' ? __('Remove your Cashfree keys from EventFlow?') : __('Remove your Razorpay keys from EventFlow?')))">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            @endforeach

            <p class="flex items-start gap-2 text-sm text-muted">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                {{ __('Keys are stored encrypted and only owners can open this page. Secrets are never shown again; leave a secret field empty to keep the saved one. Start in test mode, run a test registration, then switch to live keys.') }}
            </p>
        </div>
    </div>
</x-app-layout>
