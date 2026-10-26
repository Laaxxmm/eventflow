<x-guest-layout>
    <div class="text-center">
        <h1 class="text-xl font-bold text-ink">{{ __('No more payment reminders') }}</h1>
        <p class="mt-3 text-sm text-muted">{{ __('You will not get further payment reminders for :event.', ['event' => $brand->eventName]) }}</p>
        @if ($brand->replyTo)
            <p class="mt-3 text-sm text-muted">{{ __('Questions? Write to :email.', ['email' => $brand->replyTo]) }}</p>
        @endif
    </div>
</x-guest-layout>
