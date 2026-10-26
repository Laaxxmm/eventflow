{{--
    Finisher medal on its ribbon (docs/design/daylight-track/Event.dc.html): pure CSS 3D, sways and
    turns slowly; still under prefers-reduced-motion. $short (2–4 letters on the medal), $year.
--}}
@props(['short' => 'EF', 'year' => null])
<div {{ $attributes->merge(['class' => 'relative flex h-[230px] justify-center [perspective:900px]']) }} data-motion>
    <div class="ef-sway flex flex-col items-center [transform-style:preserve-3d]">
        <div aria-hidden="true" class="flex">
            <div class="h-[92px] w-[34px] origin-top skew-x-[14deg] bg-heat"></div>
            <div class="h-[92px] w-[34px] origin-top -skew-x-[14deg] bg-white"></div>
        </div>
        <div class="ef-medal -mt-2.5 [transform-style:preserve-3d]">
            <div role="img" aria-label="{{ __('Finisher medal') }}" class="relative flex h-[132px] w-[132px] items-center justify-center rounded-full shadow-[0_24px_40px_-16px_rgba(18,26,51,.6),inset_0_0_0_6px_rgba(255,255,255,.35)] [background:radial-gradient(circle_at_35%_30%,#FFE3A8_0%,#F2A93B_45%,#B8761A_100%)]">
                <div class="flex h-[98px] w-[98px] flex-col items-center justify-center gap-0.5 rounded-full border-2 border-[rgba(91,52,0,.35)] text-[#5B3400]">
                    <span class="ri-headline text-[26px] leading-none">{{ $short }}</span>
                    @if ($year)<span class="text-xs font-bold">{{ $year }}</span>@endif
                </div>
            </div>
        </div>
    </div>
</div>
