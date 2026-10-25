{{-- Organiser navigation. $nav from App\View\Composers\NavigationComposer. Parent has x-data="{ open }". --}}
@php
    $user = Auth::user();
    $orgName = $user->org?->name ?? ($user->isPlatformAdmin() ? 'Indefine' : config('app.name'));
@endphp

{{-- Phone top bar --}}
<div class="sticky top-0 z-30 flex h-14 items-center justify-between bg-[#0b1020] px-4 text-white lg:hidden">
    <a href="{{ route('app') }}" class="text-lg font-extrabold tracking-tight">Event<span class="text-[#d9ff45]">Flow</span></a>
    <button type="button" @click="open = true" class="rounded-lg p-2 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-[#d9ff45]" aria-label="{{ __('Open menu') }}">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>
</div>

{{-- Drawer backdrop (phones) --}}
<div x-show="open" x-transition.opacity @click="open = false" class="fixed inset-0 z-40 bg-black/40 lg:hidden" style="display: none;"></div>

<aside
    :class="open ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-[#0b1020] text-white transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
    aria-label="{{ __('Main') }}"
>
    <div class="flex items-center justify-between px-6 pt-6">
        <a href="{{ route('app') }}" class="text-xl font-extrabold tracking-tight">Event<span class="text-[#d9ff45]">Flow</span></a>
        <button type="button" @click="open = false" class="rounded-lg p-1.5 text-white/60 hover:bg-white/10 lg:hidden" aria-label="{{ __('Close menu') }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
    </div>
    <p class="mt-1 truncate px-6 text-sm text-white/50">{{ $orgName }}</p>

    @if ($user->org && Route::has('organiser.events.create') && $user->can('view-dashboard'))
        <div class="px-4 pt-6">
            <a href="{{ route('organiser.events.create') }}" class="flex items-center justify-center gap-2 rounded-xl bg-[#d9ff45] px-4 py-2.5 text-sm font-bold text-[#0b1020] hover:bg-white">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                {{ __('New event') }}
            </a>
        </div>
    @endif

    <nav class="mt-6 flex-1 space-y-6 overflow-y-auto px-3">
        @foreach ($nav as $group)
            <div>
                @if ($group['label'])
                    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-white/40">{{ $group['label'] }}</p>
                @endif
                <ul class="space-y-1">
                    @foreach ($group['items'] as $item)
                        <li>
                            <a href="{{ $item['href'] }}" @class([
                                'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                                'bg-white/10 text-white' => $item['active'],
                                'text-white/70 hover:bg-white/5 hover:text-white' => ! $item['active'],
                            ]) @if ($item['active']) aria-current="page" @endif>
                                <svg class="h-5 w-5 shrink-0 {{ $item['active'] ? 'text-[#d9ff45]' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $item['icon'] }}"/></svg>
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="border-t border-white/10 p-4">
        <div class="flex items-center gap-3 px-2">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-sm font-bold">{{ \Illuminate\Support\Str::of($user->name)->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}</span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold">{{ $user->name }}</p>
                <p class="truncate text-xs text-white/50">{{ $user->email }}</p>
            </div>
        </div>
        <div class="mt-3 flex gap-2 text-sm">
            <a href="{{ route('profile.edit') }}" class="flex-1 rounded-lg px-3 py-2 text-center text-white/70 hover:bg-white/5 hover:text-white">{{ __('Profile') }}</a>
            <form method="POST" action="{{ route('logout') }}" class="flex-1">
                @csrf
                <button type="submit" class="w-full rounded-lg px-3 py-2 text-white/70 hover:bg-white/5 hover:text-white">{{ __('Log out') }}</button>
            </form>
        </div>
    </div>
</aside>
