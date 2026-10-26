{{-- Copyable webhook URL with setup steps. $url, $steps --}}
<div class="rounded-2xl bg-paper p-4" x-data="{ copied: false }">
    <p class="text-sm font-semibold text-ink">{{ __('Webhook URL') }}</p>
    <div class="mt-2 flex flex-col gap-2 sm:flex-row">
        <input readonly value="{{ $url }}" class="flex-1 rounded-xl border-mist bg-white font-mono text-xs" @focus="$el.select()" aria-label="{{ __('Webhook URL') }}">
        <button type="button" @click="navigator.clipboard.writeText(@js($url)); copied = true; setTimeout(() => copied = false, 2000)" class="rounded-full px-4 py-2 text-sm font-semibold ring-1 ring-mist hover:bg-white" x-text="copied ? @js(__('Copied')) : @js(__('Copy'))"></button>
    </div>
    <p class="mt-2 text-xs text-muted">{{ $steps }} {{ __('Payments are confirmed through this webhook, so add it before taking registrations.') }}</p>
</div>
