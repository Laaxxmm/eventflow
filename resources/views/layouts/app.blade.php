{{-- Organiser shell: sidebar on desktop, top bar + drawer on phones. Pages pass $header (title) and $slot. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'EventFlow') }}</title>

        <script>document.documentElement.classList.add('js')</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-[#f5f6fa] text-slate-900">
        {{-- The dark sidebar column is painted on the wrapper so it runs the full page height
             (prints, full-page captures, long pages); the menu itself stays sticky inside it. --}}
        <div x-data="{ open: false }" class="min-h-screen lg:flex lg:bg-[linear-gradient(to_right,#0b1020_18rem,transparent_18rem)]">
            @include('layouts.navigation')

            <div class="min-w-0 flex-1">
                @isset($header)
                    <header class="border-b border-slate-200/70 bg-[#f5f6fa]/90 backdrop-blur lg:sticky lg:top-0 lg:z-20">
                        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-5 sm:px-6 lg:px-10 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:tracking-tight [&_h2]:text-slate-900">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="[&>div.py-12]:py-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
