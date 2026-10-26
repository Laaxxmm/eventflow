{{--
    One landing template on /templates (Daylight Track). The preview is drawn from the template's
    swatch gradient: public/images/templates/*.jpg are Race Ink-era screenshots and would clash.
    $key, $tpl (label, tone, swatch, sport, blurb, description), $sampleName, $sampleDate
--}}
@php
    $dark = ($tpl['tone'] ?? 'light') === 'dark';
    $sampleName ??= \Symfony\Component\Yaml\Yaml::parseFile(base_path(\App\View\SampleEvent::YAML))['name'] ?? __('Your race');
    $sampleDate ??= now()->addMonths(2)->isoFormat('ddd D MMM YYYY');
@endphp
<article data-template-card="{{ $key }}" class="flex flex-col gap-[18px] rounded-[32px] bg-white p-3.5">
    <a href="{{ route('templates.show', $key) }}" class="relative block h-[240px] overflow-hidden rounded-[22px] sm:h-[340px]" style="background: {{ $tpl['swatch'] }}" tabindex="-1" aria-hidden="true">
        <span class="absolute inset-0 bg-[linear-gradient(180deg,rgb(18_26_51/.3)_0%,rgb(18_26_51/0)_25%,rgb(18_26_51/0)_40%,rgb(18_26_51/.55)_100%)]"></span>
        <span class="absolute inset-x-0 top-0 flex items-center justify-between px-[18px] py-3.5 text-xs font-bold text-white">
            <span>{{ __('Demo Runners') }}</span>
            <span @class(['rounded-full px-3 py-1.5', 'bg-heat text-on-heat' => $dark, 'bg-white text-ink' => ! $dark])>{{ __('Register') }}</span>
        </span>
        <span class="absolute inset-x-6 bottom-6 flex flex-col gap-2 text-white">
            <span class="text-xs font-semibold opacity-90">{{ $sampleDate }}</span>
            <span class="ri-headline text-[28px] leading-none sm:text-[38px]">{{ $sampleName }}</span>
        </span>
    </a>
    <div class="flex flex-wrap items-center justify-between gap-4 px-3 pb-3 pt-1">
        <div class="flex max-w-[26rem] flex-col gap-1">
            <div class="flex flex-wrap items-center gap-2.5">
                <h2 id="template-{{ $key }}" class="text-[22px] font-bold">{{ $tpl['label'] }}</h2>
                <span class="rounded-full bg-lilac px-2.5 py-1 text-xs font-semibold text-track-deep">{{ __($tpl['sport']) }}</span>
            </div>
            <p class="text-[15px] leading-normal text-muted">{{ __($tpl['description']) }}</p>
        </div>
        <div class="flex flex-wrap gap-2.5">
            <x-ri.button variant="secondary" size="md" :href="route('templates.show', $key)" aria-describedby="template-{{ $key }}">{{ __('Live preview') }}</x-ri.button>
            <x-ri.button variant="primary" size="md" :href="\App\Http\Controllers\Marketing\TemplateGalleryController::useUrl($key)" aria-describedby="template-{{ $key }}">{{ __('Use this template') }}</x-ri.button>
        </div>
    </div>
</article>
