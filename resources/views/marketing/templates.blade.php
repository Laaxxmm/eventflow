@extends('marketing.layout')

@section('title', __('Race site templates · :brand', ['brand' => \App\Saas\Plans::brandName()]))
@section('description', __('Five sports-editorial race site templates for Indian running, cycling and skating events. Preview each one, then start a free trial with it.'))

@section('content')
    <section class="bg-ink text-paper">
        <div class="mx-auto max-w-7xl px-4 pb-16 pt-20 sm:px-6 lg:px-8">
            <p class="ri-kicker text-heat">{{ __('Templates') }}</p>
            <h1 class="ri-headline mt-5 max-w-5xl text-6xl sm:text-8xl">{{ __('Your runners land on a') }} <span class="text-heat">{{ __('sports page,') }}</span> {{ __('not a form.') }}</h1>
            <p class="mt-8 max-w-2xl text-lg text-paper/75">{{ __('Five race-day layouts, each tinted with your logo colours. Open one to see it with a sample marathon, then use it for your event.') }}</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid gap-x-8 gap-y-14 md:grid-cols-2">
            @foreach ($templates as $key => $tpl)
                <div class="flex flex-col">
                    @include('marketing.partials.template-card', ['index' => $loop->iteration, 'large' => true])
                    <div class="mt-5 flex flex-wrap gap-3">
                        <x-ri.button variant="heat" size="md" :href="\App\Http\Controllers\Marketing\TemplateGalleryController::useUrl($key)">{{ __('Use this template') }}</x-ri.button>
                        <x-ri.button variant="ghost" size="md" :href="route('templates.show', $key)">{{ __('Live preview') }}</x-ri.button>
                    </div>
                </div>
            @endforeach
            <div class="flex flex-col justify-center border-2 border-dashed border-ink/30 p-8">
                <p class="font-display text-3xl font-extrabold uppercase">{{ __('Every template includes') }}</p>
                <ul class="mt-5 space-y-3 text-sm">
                    @foreach ([__('Your logo and brand colours, contrast-checked'), __('Distances and prices, schedule, sponsors, FAQ'), __('Register one tap away on mobile'), __('QR pass by email after payment')] as $item)
                        <li class="flex gap-3">@include('marketing.partials.tick'){{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    @include('marketing.partials.cta-band')
@endsection
