{{--
    Organiser sidebar (Daylight Track, docs/design/daylight-track/Sidebar.dc.html): white, lilac active
    item, org card with plan usage, New event, user block at the bottom. $nav from
    App\View\Composers\NavigationComposer (groups unchanged; the Indefine group is admins only).
    Phones: a white top bar and a slide-in drawer. Parent has x-data="{ open }".
--}}
@php
    $user = Auth::user();
    $org = $user->org;
    $orgName = $org?->name ?? ($user->isPlatformAdmin() ? 'Indefine' : config('app.name'));
    $initials = fn (string $name) => \Illuminate\Support\Str::of($name)->explode(' ')->filter()->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->implode('');
    $plan = $org?->plan ? (\App\Saas\Plans::all()[$org->plan] ?? null) : null;
    $racesAllowed = $org ? \App\Saas\PlanLimits::racesAllowed($org) : null;
    $usage = $plan
        ? ($racesAllowed !== null
            ? trans_choice(':plan, :used of :count race|:plan, :used of :count races', $racesAllowed, ['plan' => __($plan['name']), 'used' => \App\Saas\PlanLimits::racesUsed($org), 'count' => $racesAllowed])
            : __($plan['name']))
        : ($org ? __('No plan yet') : __('Platform admin'));
@endphp

{{-- Phone top bar --}}
<div class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-[#E3E7F0] bg-white px-4 lg:hidden">
    <a href="{{ route('app') }}" class="ri-headline text-xl text-ink">EventFlow</a>
    <button type="button" @click="open = true" class="flex h-11 w-11 items-center justify-center rounded-full text-ink hover:bg-paper" aria-label="{{ __('Open menu') }}" :aria-expanded="open.toString()">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>
</div>

{{-- Drawer backdrop (phones) --}}
<div x-show="open" x-transition.opacity @click="open = false" class="fixed inset-0 z-40 bg-ink/30 lg:hidden" style="display: none;"></div>

<nav aria-label="{{ __('Main') }}"
     :class="open ? 'translate-x-0' : '-translate-x-full'"
     class="fixed inset-y-0 left-0 z-50 flex w-[248px] -translate-x-full flex-col gap-5 overflow-y-auto border-r border-[#E3E7F0] bg-white px-4 py-[22px] text-ink transition-transform duration-200 ease-track lg:sticky lg:top-0 lg:h-screen lg:shrink-0 lg:translate-x-0"
     @keydown.escape.window="open = false">
    <div class="flex items-center justify-between px-2">
        <a href="{{ route('app') }}" class="ri-headline text-[22px] text-ink">EventFlow</a>
        <button type="button" @click="open = false" class="flex h-11 w-11 items-center justify-center rounded-full text-muted hover:bg-paper lg:hidden" aria-label="{{ __('Close menu') }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
    </div>

    <div class="flex items-center gap-2.5 rounded-2xl bg-paper p-2.5" data-org-card>
        <span aria-hidden="true" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-track text-[13px] font-extrabold text-white">{{ $initials($orgName) }}</span>
        <span class="flex min-w-0 flex-col">
            <strong class="truncate text-sm">{{ $orgName }}</strong>
            <span class="truncate text-xs text-muted">{{ $usage }}</span>
        </span>
    </div>

    @if ($org && Route::has('organiser.events.create') && $user->can('view-dashboard'))
        <a href="{{ route('organiser.events.create') }}" class="flex min-h-[44px] items-center justify-center gap-2 rounded-full bg-track px-4 py-[13px] text-[15px] font-bold text-white hover:bg-track-deep">
            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            {{ __('New event') }}
        </a>
    @endif

    @foreach ($nav as $group)
        <div class="flex flex-col gap-0.5">
            @if ($group['label'])
                <span class="px-3 pb-1.5 text-[13px] font-semibold text-muted">{{ $group['label'] }}</span>
            @endif
            @foreach ($group['items'] as $item)
                <a href="{{ $item['href'] }}" @if ($item['active']) aria-current="page" @endif @class([
                    'flex min-h-[44px] items-center gap-3 rounded-xl px-3 py-[11px] text-[15px] transition-colors',
                    'bg-lilac font-bold text-track-deep' => $item['active'],
                    'font-medium text-[#3B4463] hover:bg-paper' => ! $item['active'],
                ])>
                    <svg aria-hidden="true" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $item['icon'] }}"/></svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    @endforeach

    <div class="mt-auto flex items-center gap-2.5 border-t border-[#E3E7F0] p-2.5">
        <span aria-hidden="true" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-lilac text-[13px] font-bold text-track-deep">{{ $initials($user->name) }}</span>
        <span class="flex min-w-0 flex-1 flex-col">
            <strong class="truncate text-sm">{{ $user->name }}</strong>
            <a href="{{ route('profile.edit') }}" class="text-xs text-muted underline-offset-2 hover:underline">{{ __('Profile') }}</a>
        </span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="min-h-[44px] px-1 text-[13px] text-muted hover:text-ink">{{ __('Log out') }}</button>
        </form>
    </div>
</nav>
