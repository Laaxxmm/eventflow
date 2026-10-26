@extends('marketing.layout')

@section('title', __('Race site templates · :brand', ['brand' => \App\Saas\Plans::brandName()]))
@section('description', __('Five sports-editorial race site templates for Indian running, cycling and skating events. Preview each one, then start a free trial with it.'))

{{-- /templates, Daylight Track (Templates.dc.html). Cards preview each template on the demo marathon. --}}
@php
    $sampleName = \Symfony\Component\Yaml\Yaml::parseFile(base_path(\App\View\SampleEvent::YAML))['name'] ?? __('Your race');
    $sampleDate = now()->addMonths(2)->isoFormat('ddd D MMM YYYY');
@endphp

@section('content')
    <section class="mx-auto flex max-w-[1280px] flex-wrap items-end justify-between gap-8 px-5 py-10 sm:px-10 sm:py-12 lg:flex-nowrap">
        <h1 class="ri-headline max-w-[46rem] text-[clamp(2.4rem,5vw,4.5rem)]">{{ __('Your runners land on a sports page, not a form') }}</h1>
        <p class="max-w-[28rem] text-lg leading-relaxed text-muted">{{ __('Five designs. Each one takes your logo, colours and photos, and you can switch between them any time before or after you publish.') }}</p>
    </section>

    <section class="mx-auto grid max-w-[1280px] gap-7 px-5 pb-14 sm:px-10 lg:grid-cols-2">
        @foreach ($templates as $key => $tpl)
            @include('marketing.partials.template-card')
        @endforeach
    </section>

    <section class="mx-auto max-w-[1280px] px-5 pb-24 pt-6 sm:px-10">
        <div class="grid gap-7 rounded-[32px] bg-lilac p-7 sm:grid-cols-2 sm:p-10 lg:grid-cols-4">
            <h2 class="ri-headline text-[30px] leading-[1.1]">{{ __('Every template includes') }}</h2>
            @foreach ([
                [__('Your brand, checked'), __('Logo and colours, with contrast checked so Register always reads.')],
                [__('Everything runners ask'), __('Distances and prices, schedule, venue, sponsors and FAQ.')],
                [__('One tap to register'), __('A Register bar that stays on screen on phones, and a QR pass by email after payment.')],
            ] as [$title, $body])
                <p class="text-[15px] leading-relaxed"><strong class="mb-1 block">{{ $title }}</strong>{{ $body }}</p>
            @endforeach
        </div>
    </section>
@endsection
