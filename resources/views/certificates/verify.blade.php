<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>{{ __('Certificate :no', ['no' => $certificate->cert_no]) }}</title>
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-[#f5f3ee] font-sans text-ink antialiased">
        <main class="mx-auto flex min-h-screen max-w-xl items-center px-4 py-12">
            <section class="w-full overflow-hidden rounded-[28px] bg-white">
                <div class="h-1.5 bg-gradient-to-r from-[#8a651e] via-[#f3de96] to-[#8a651e]"></div>
                <div class="p-8 sm:p-10">
                    <p class="flex items-center gap-2 text-sm font-semibold text-[#0B4A38]">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                        {{ __('Valid certificate') }}
                    </p>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight">{{ $participant }}</h1>
                    <p class="mt-2 text-muted">{{ $event->name }}</p>
                    <dl class="mt-8 grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-muted">{{ __('Certificate') }}</dt><dd class="mt-1 font-mono font-semibold">{{ $certificate->cert_no }}</dd></div>
                        <div><dt class="text-muted">{{ __('Event date') }}</dt><dd class="mt-1 font-semibold">{{ $event->date?->timezone('Asia/Kolkata')->format('j M Y') }}</dd></div>
                        <div><dt class="text-muted">{{ __('Organiser') }}</dt><dd class="mt-1 font-semibold">{{ $event->org?->name }}</dd></div>
                        <div><dt class="text-muted">{{ __('Issued') }}</dt><dd class="mt-1 font-semibold">{{ $certificate->issued_at->timezone('Asia/Kolkata')->format('j M Y') }}</dd></div>
                    </dl>
                    <p class="mt-8 text-xs text-muted">{{ __('Issued through EventFlow. The details above should match the printed certificate.') }}</p>
                </div>
            </section>
        </main>
    </body>
</html>
