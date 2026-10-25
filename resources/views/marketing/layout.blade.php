{{-- EventFlow SaaS marketing shell (/, /pricing). Not used by per-event landings. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', \App\Saas\Plans::brandName())</title>
        <meta name="description" content="@yield('description', __('Branded race registration, payments, EF- passes and QR check-in for Indian running, skating and cycling events. One Season Pass a year.'))">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-[#f7f6f1] text-[#0b1020]">
        @include('marketing.partials.nav')

        <main>
            @yield('content')
        </main>

        @include('marketing.partials.footer')
    </body>
</html>
