{{-- Copyable webhook URL with setup steps. $url, $steps, $provider --}}
<div class="space-y-3 rounded-[18px] bg-paper px-[18px] py-4" x-data="{ copied: false }">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <span class="flex min-w-0 flex-col gap-1">
            <strong class="text-sm text-ink">{{ __('Webhook URL to paste into :provider', ['provider' => $provider]) }}</strong>
            <code class="break-all text-[13px] text-[#3B4463]" aria-label="{{ __('Webhook URL') }}">{{ $url }}</code>
        </span>
        <button type="button" @click="navigator.clipboard.writeText(@js($url)); copied = true; setTimeout(() => copied = false, 2000)" x-text="copied ? @js(__('Copied')) : @js(__('Copy'))"
                class="inline-flex min-h-[44px] items-center rounded-full bg-white px-4 text-sm font-semibold text-ink ring-1 ring-inset ring-[#C5CBDB] transition duration-200 ease-track hover:ring-ink/40">{{ __('Copy') }}</button>
    </div>
    <p class="text-[13px] text-muted">{{ $steps }} {{ __('Payments are confirmed through this webhook, so add it before taking registrations.') }}</p>
</div>
