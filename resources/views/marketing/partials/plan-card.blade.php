{{-- One plan on /pricing (Daylight Track). Self-serve plans start a trial; Custom is contact-only. $key, $plan --}}
@php
    $featured = $plan['highlight'] ?? false;
    $price = \App\Saas\Plans::inr($plan['price_inr']);
    $term = $plan['term_label']
        ? __(':term, :renewal.', ['term' => __($plan['term_label']), 'renewal' => $plan['renews'] ? __('renews yearly') : __('one-off')])
        : null;
@endphp
<article id="{{ $key }}" data-plan="{{ $key }}" @class([
    'flex scroll-mt-24 flex-col gap-4 rounded-[28px] p-[30px]',
    'bg-track text-white shadow-[0_30px_60px_-30px_rgb(75_63_224/.7)]' => $featured,
    'bg-white' => ! $featured && $plan['self_serve'],
    'bg-[#E9ECF4]' => ! $plan['self_serve'],
])>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-lg font-bold">{{ $plan['name'] }}</h2>
        @if ($featured)
            <span class="rounded-full bg-heat px-2.5 py-1 text-xs font-bold text-on-heat">{{ __('Most organisers start here') }}</span>
        @endif
    </div>
    <p class="ri-headline text-[52px] leading-none">{{ $price ?? __('Quoted') }}</p>
    <p @class(['text-[15px] leading-relaxed', 'text-[#E4E1FF]' => $featured, 'text-muted' => ! $featured])>{{ __($plan['tagline']) }} {{ $term }}</p>
    <p @class(['text-sm font-semibold', 'text-white' => $featured, 'text-ink' => ! $featured])>{{ \App\Saas\Plans::limitsLabel($plan) }}</p>

    @if ($plan['self_serve'])
        <a href="{{ route('register', ['plan' => $key]) }}" @class([
            'mt-auto flex min-h-[48px] items-center justify-center rounded-full px-4 py-3.5 text-center font-bold transition duration-200 ease-track',
            'bg-white text-track-deep hover:bg-lilac' => $featured,
            'bg-lilac text-track-deep hover:bg-[#DCD8FF]' => ! $featured,
        ])>{{ __('Start free trial') }}</a>
    @else
        <a href="{{ \App\Support\Contact::url(__('EventFlow :plan plan', ['plan' => $plan['name']])) }}" data-contact-indefine
           class="mt-auto flex min-h-[48px] items-center justify-center rounded-full px-4 py-[13px] text-center font-bold text-ink ring-1 ring-inset ring-[#C5CBDB] transition duration-200 ease-track hover:ring-ink/40">{{ __('Talk to Indefine') }}</a>
    @endif
</article>
