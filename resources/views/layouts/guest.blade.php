{{--
    Auth pages (log in, sign up, password reset), Daylight Track (Login.dc.html, Signup.dc.html):
    form on chalk beside a plain indigo panel. Pass <x-slot:aside> to fill the panel (sign up puts the
    plan picker there); without it the panel is decorative and only shows from lg up. Flows unchanged.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#F3F5FA">

        <title>{{ \App\Saas\Plans::brandName() }}</title>

        <script>document.documentElement.classList.add('js')</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-paper font-sans text-ink antialiased">
        <div class="flex min-h-screen flex-wrap">
            <main class="flex min-w-0 flex-[1_1_520px] flex-col gap-10 px-5 pb-12 pt-8 sm:px-10">
                <a href="{{ route('home') }}" class="flex min-h-[44px] items-center gap-2.5 self-start">
                    @include('marketing.partials.wordmark')
                    @if (config('plans.brand.by'))
                        <span class="text-[13px] text-muted">{{ __('by :by', ['by' => config('plans.brand.by')]) }}</span>
                    @endif
                </a>
                <div class="m-auto w-full max-w-[30rem]">
                    {{ $slot }}
                </div>
            </main>

            <aside @class(['relative isolate m-4 min-w-0 flex-[1_1_520px] flex-col gap-8 overflow-hidden rounded-[32px] bg-track p-8 text-white sm:p-12', 'flex' => isset($aside), 'hidden justify-between lg:flex' => ! isset($aside)]) @unless (isset($aside)) aria-hidden="true" @endunless>
                <svg class="pointer-events-none absolute -bottom-[200px] -right-[160px] -z-10" width="760" height="460" viewBox="0 0 760 460" fill="none" stroke="#fff" stroke-opacity=".16" stroke-width="2" aria-hidden="true">
                    <rect x="1" y="1" width="758" height="458" rx="229"/><rect x="29" y="29" width="702" height="402" rx="201"/><rect x="57" y="57" width="646" height="346" rx="173"/><rect x="85" y="85" width="590" height="290" rx="145"/>
                </svg>
                @isset($aside)
                    {{ $aside }}
                @else
                    <p class="ri-headline max-w-[26rem] text-5xl">{{ __('Race morning starts here.') }}</p>
                    <div class="ri-float flex w-[280px] -rotate-[4deg] flex-col gap-2.5 self-end rounded-[24px] bg-white p-5 text-ink">
                        <span class="text-[13px] text-muted">{{ __('Today at gate 2') }}</span>
                        <span class="ri-headline whitespace-nowrap text-[30px] leading-none">{{ __(':count checked in', ['count' => 412]) }}</span>
                        <span class="h-2 rounded bg-lilac"><span class="block h-full w-[41%] rounded bg-heat"></span></span>
                        <span class="text-[13px] text-muted">{{ __('Bengaluru City Marathon 2027') }}</span>
                    </div>
                @endisset
            </aside>
        </div>
    </body>
</html>
