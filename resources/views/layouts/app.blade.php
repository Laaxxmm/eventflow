{{--
    Organiser shell (Daylight Track): white sidebar on desktop, top bar + drawer on phones, chalk page.
    Pages pass $title, optional $header (h2 + actions) and $slot.
--}}
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
    <body class="bg-paper font-sans text-ink antialiased">
        {{-- The white sidebar column is painted on the wrapper so it runs the full page height. --}}
        <div x-data="{ open: false }" class="min-h-screen lg:flex lg:bg-[linear-gradient(to_right,#fff_247px,#E3E7F0_247px,#E3E7F0_248px,transparent_248px)]">
            @include('layouts.navigation')

            <div class="min-w-0 flex-1">
                @isset($header)
                    <header class="mx-auto flex max-w-[1180px] flex-wrap items-end justify-between gap-4 px-4 pb-2 pt-6 sm:px-6 lg:px-10 lg:pt-8 [&_h2]:ri-headline [&_h2]:text-[32px] [&_h2]:text-ink sm:[&_h2]:text-[40px]">
                        {{ $header }}
                    </header>
                @endisset

                <main class="[&>div.py-12]:py-6 [&>div.py-12]:lg:py-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
