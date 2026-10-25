{{-- Copyable webhook URL with setup steps. $url, $steps --}}
<div class="rounded-2xl bg-slate-50 p-4" x-data="{ copied: false }">
    <p class="text-sm font-medium text-slate-700">{{ __('Webhook URL') }}</p>
    <div class="mt-2 flex flex-col gap-2 sm:flex-row">
        <input readonly value="{{ $url }}" class="flex-1 rounded-xl border-slate-300 bg-white font-mono text-xs" @focus="$el.select()" aria-label="{{ __('Webhook URL') }}">
        <button type="button" @click="navigator.clipboard.writeText(@js($url)); copied = true; setTimeout(() => copied = false, 2000)" class="rounded-xl px-4 py-2 text-sm font-semibold ring-1 ring-slate-300 hover:bg-white" x-text="copied ? @js(__('Copied')) : @js(__('Copy'))"></button>
    </div>
    <p class="mt-2 text-xs text-slate-500">{{ $steps }} {{ __('Payments are confirmed through this webhook, so add it before taking registrations.') }}</p>
</div>
