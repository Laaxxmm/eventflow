{{-- What every self-serve plan includes for participant messaging. $class --}}
<p class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm {{ $class ?? '' }}">
    <span class="font-semibold">{{ __('Email ✓') }}</span>
    <span aria-hidden="true" class="opacity-40">·</span>
    <span>{{ __('Own SMTP free') }}</span>
    <span aria-hidden="true" class="opacity-40">·</span>
    <span>{{ __('Platform mail add-on') }}</span>
    <span aria-hidden="true" class="opacity-40">·</span>
    <span>{{ __('WhatsApp add-on') }}</span>
</p>
