<x-app-layout>
    <x-slot name="title">{{ $event->name }}</x-slot>
    <x-slot name="header">
        <h2>{{ $event->status === 'published' ? __('Your event is live') : __('Draft saved') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-10">
        <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/70">
            <div class="h-2" style="background: linear-gradient(90deg, {{ $event->colours['primary'] ?? '#d9ff45' }}, {{ $event->colours['secondary'] ?? '#ff6e61' }})"></div>
            <div class="p-6 sm:p-10">
                <p class="text-sm font-semibold uppercase tracking-wider text-slate-500">{{ $template['label'] ?? '' }} {{ __('design') }}</p>
                <h3 class="mt-2 text-3xl font-extrabold tracking-tight">{{ $event->name }}</h3>
                <p class="mt-1 text-slate-500">{{ $event->date->timezone('Asia/Kolkata')->format('l, j F Y · g:i A') }} · {{ $event->venue }}</p>

                @if ($event->status === 'published')
                    <div class="mt-8" x-data="{ copied: false }">
                        <label for="share-url" class="text-sm font-medium text-slate-700">{{ __('Share this link') }}</label>
                        <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                            <input id="share-url" readonly value="{{ $url }}" class="flex-1 rounded-xl border-slate-300 bg-slate-50 font-mono text-sm" @focus="$el.select()">
                            <button type="button" @click="navigator.clipboard.writeText('{{ $url }}'); copied = true; setTimeout(() => copied = false, 2000)" class="rounded-xl px-4 py-2.5 text-sm font-semibold ring-1 ring-slate-300 hover:bg-slate-50" x-text="copied ? '{{ __('Copied') }}' : '{{ __('Copy link') }}'"></button>
                            <a href="{{ $url }}" target="_blank" class="rounded-xl bg-[#0b1020] px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-[#1f2a4a]">{{ __('View page') }} ↗</a>
                        </div>
                    </div>
                @else
                    <p class="mt-8 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">{{ __('Drafts are not public. Publish from the event settings when you are ready.') }}</p>
                @endif

                @if (session('logo_dark'))
                    <p class="mt-6 rounded-xl bg-amber-50 p-4 text-sm text-amber-800" role="status">{{ __('Dark logo — pick a light template (Aurora/Clean) or a light hero background so it stays classy.') }}</p>
                @endif
                @if (session('logo_opaque'))
                    <p class="mt-3 text-sm text-slate-500">{{ __('Tip: your logo has a solid background. A transparent PNG blends into every template.') }}</p>
                @endif
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-3">
            @foreach ([
                [__('Fine-tune'), __('Age categories, form questions, FAQ, certificate and heats.'), route('organiser.events.edit', $event), __('Event settings')],
                [__('Email'), __('Branded emails with the pass PDF go out after payment once your email is set up: your own SMTP (free) or Platform mail.'), Route::has('settings.notifications.show') ? route('settings.notifications.show') : null, __('Email & WhatsApp')],
                [__('Dashboard'), __('Watch registrations and payments come in.'), route('dashboard'), __('Go to home')],
            ] as [$title, $body, $href, $cta])
                @if ($href)
                    <a href="{{ $href }}" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 hover:ring-slate-400">
                        <p class="font-bold">{{ $title }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $body }}</p>
                        <p class="mt-4 text-sm font-semibold">{{ $cta }} →</p>
                    </a>
                @endif
            @endforeach
        </section>
    </div>
</x-app-layout>
