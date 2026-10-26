{{-- Auth pages (log in, sign up, password reset), Race Ink: ink race panel + paper form. Flows unchanged. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0E1320">

        <title>{{ \App\Saas\Plans::brandName() }}</title>

        <script>document.documentElement.classList.add('js')</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-paper font-sans text-ink antialiased">
        <div class="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">
            <aside class="relative isolate hidden overflow-hidden bg-midnight text-paper lg:block">
                <x-ri.photo :src="\App\View\SportImagery::pick('marketing', 1)" sport="running" class="absolute inset-0 -z-10" alt="" />
                <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink via-ink/60 to-ink/20" aria-hidden="true"></div>
                <div class="flex h-full flex-col justify-between p-12">
                    <a href="{{ route('home') }}">@include('marketing.partials.wordmark', ['class' => 'text-3xl'])</a>
                    <div>
                        <p class="ri-kicker text-heat">{{ __('Organiser access') }}</p>
                        <p class="ri-headline mt-5 text-8xl">{{ __('Race day') }}<br>{{ __('starts') }} <span class="text-heat">{{ __('here.') }}</span></p>
                    </div>
                </div>
            </aside>

            <main class="flex flex-col justify-center px-6 py-12 sm:px-12">
                <a href="{{ route('home') }}" class="mb-10 lg:hidden">@include('marketing.partials.wordmark', ['class' => 'text-3xl'])</a>
                <div class="w-full max-w-md">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
