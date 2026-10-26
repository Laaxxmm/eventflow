{{-- Profile (Daylight Track, docs/design/daylight-track/Profile.dc.html): details, password, delete. --}}
@php
    $initials = \Illuminate\Support\Str::of($user->name)->explode(' ')->filter()->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->implode('');
    $role = $user->is_platform_admin ? __('Platform admin') : ($user->role ? __(ucfirst($user->role->value)) : null);
@endphp
<x-app-layout>
    <x-slot name="title">{{ __('Profile') }}</x-slot>
    <x-slot name="header">
        <div class="flex min-w-0 items-center gap-4 sm:gap-[18px]">
            <span aria-hidden="true" class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-lilac text-xl font-extrabold text-track-deep sm:h-[72px] sm:w-[72px] sm:text-2xl">{{ $initials }}</span>
            <div class="min-w-0">
                <h2 class="truncate">{{ $user->name }}</h2>
                @if ($role)
                    <p class="mt-1 text-[15px] text-muted">{{ $user->org ? __(':role at :org', ['role' => $role, 'org' => $user->org->name]) : $role }}</p>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-[1180px] px-4 pb-12 pt-4 sm:px-6 lg:px-10">
        <div class="max-w-[900px] space-y-5">
            <section class="rounded-[28px] bg-white p-[22px] sm:p-[26px]">
                @include('profile.partials.update-profile-information-form')
            </section>

            <section class="rounded-[28px] bg-white p-[22px] sm:p-[26px]">
                @include('profile.partials.update-password-form')
            </section>

            <section class="rounded-[28px] bg-white p-[22px] ring-1 ring-inset ring-[#F1C9C9] sm:p-[26px]">
                @include('profile.partials.delete-user-form')
            </section>
        </div>
    </div>
</x-app-layout>
