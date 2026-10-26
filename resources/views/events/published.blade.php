<x-app-layout>
    <x-slot name="title">{{ $event->name }}</x-slot>
    <x-slot name="header">
        <h2>{{ $event->status === 'published' ? __('Your event is live') : __('Draft saved') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-10">
        <section class="overflow-hidden rounded-[28px] bg-white">
            <div class="h-2" style="background: linear-gradient(90deg, {{ $event->colours['primary'] ?? '#d9ff45' }}, {{ $event->colours['secondary'] ?? '#ff6e61' }})"></div>
            <div class="p-6 sm:p-10">
                <p class="text-sm font-semibold uppercase tracking-wider text-muted">{{ $template['label'] ?? '' }} {{ __('design') }}</p>
                <h3 class="mt-2 text-3xl font-extrabold tracking-tight">{{ $event->name }}</h3>
                <p class="mt-1 text-muted">{{ $event->date->timezone('Asia/Kolkata')->format('l, j F Y · g:i A') }} · {{ $event->venue }}</p>

                @if ($event->status === 'published')
                    <div class="mt-8" x-data="{ copied: false }">
                        <label for="share-url" class="text-sm font-semibold text-ink">{{ __('Share this link') }}</label>
                        <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                            <input id="share-url" readonly value="{{ $url }}" class="flex-1 rounded-xl border-mist bg-paper font-mono text-sm" @focus="$el.select()">
                            <button type="button" @click="navigator.clipboard.writeText('{{ $url }}'); copied = true; setTimeout(() => copied = false, 2000)" class="rounded-full px-4 py-2.5 text-sm font-semibold ring-1 ring-mist hover:bg-paper" x-text="copied ? '{{ __('Copied') }}' : '{{ __('Copy link') }}'"></button>
                            <a href="{{ $url }}" target="_blank" class="rounded-xl bg-ink px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-ink/90">{{ __('View page') }} ↗</a>
                        </div>
                    </div>
                @else
                    <p class="mt-8 rounded-2xl bg-paper p-4 text-sm text-muted">{{ __('Drafts are not public. Publish from the event settings when you are ready.') }}</p>
                @endif

                @if (session('logo_dark'))
                    <p class="mt-6 rounded-xl bg-[#FDF0DA] p-4 text-sm text-[#6B4A12]" role="status">{{ __('Dark logo — pick a light template (Stadium Split/Heritage Crest) or a light hero background so it stays classy.') }}</p>
                @endif
                @if (session('logo_opaque'))
                    <p class="mt-3 text-sm text-muted">{{ __('Tip: your logo has a solid background. A transparent PNG blends into every template.') }}</p>
                @endif
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                [__('Preview and publish'), __('See your page on desktop and mobile, switch template, publish or unpublish.'), route('organiser.events.show', $event), __('Event overview')],
                [__('Fine-tune'), __('Age categories, form questions, FAQ, certificate and heats.'), route('organiser.events.edit', $event), __('Event settings')],
                [__('Email'), __('Branded emails with the pass PDF go out after payment once your email is set up: your own SMTP (free) or Platform mail.'), Route::has('settings.notifications.show') ? route('settings.notifications.show') : null, __('Email & WhatsApp')],
                [__('Dashboard'), __('Watch registrations and payments come in.'), route('dashboard'), __('Go to home')],
            ] as [$title, $body, $href, $cta])
                @if ($href)
                    <a href="{{ $href }}" class="rounded-2xl bg-white p-5 hover:ring-[#9AA2BA]">
                        <p class="font-bold">{{ $title }}</p>
                        <p class="mt-1 text-sm text-muted">{{ $body }}</p>
                        <p class="mt-4 text-sm font-semibold">{{ $cta }} →</p>
                    </a>
                @endif
            @endforeach
        </section>
    </div>
</x-app-layout>
