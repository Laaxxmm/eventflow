{{--
    Platform console: organisations (Daylight Track, docs/design/daylight-track/PlatformOrgs.dc.html).
    Totals, search + filters, one white table card. Actions are the existing extend-trial / suspend / reactivate.
--}}
@php
    use App\Saas\PlanLimits;
    $field = 'min-h-[44px] rounded-full border-0 bg-white px-4 text-sm text-ink ring-1 ring-inset ring-mist focus:ring-2 focus:ring-track';
    $action = 'inline-flex min-h-[44px] items-center px-1.5 text-sm font-semibold';
@endphp
<x-app-layout>
    <x-slot name="title">{{ __('Organisations') }}</x-slot>
    <x-slot name="header">
        <div>
            <p class="text-sm text-muted">{{ __('Indefine only') }}</p>
            <h2 class="mt-1">{{ __('Organisations') }}</h2>
        </div>
        <form method="get" action="{{ route('platform.orgs.index') }}" class="flex w-full flex-wrap gap-2 sm:w-auto" role="search">
            <label class="min-w-0 basis-full sm:basis-auto">
                <span class="sr-only">{{ __('Search organisations') }}</span>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Name or slug') }}" class="{{ $field }} w-full sm:w-[280px]">
            </label>
            <label class="min-w-0 flex-1 sm:flex-none">
                <span class="sr-only">{{ __('Status') }}</span>
                <select name="status" class="{{ $field }} w-full pe-9" onchange="this.form.submit()">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (['trialing' => __('On trial'), 'trial_expired' => __('Trial expired'), 'active' => __('Active'), 'past_due' => __('Past due'), 'cancelled' => __('Cancelled'), 'suspended' => __('Suspended')] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="min-w-0 flex-1 sm:flex-none">
                <span class="sr-only">{{ __('Plan') }}</span>
                <select name="plan" class="{{ $field }} w-full pe-9" onchange="this.form.submit()">
                    <option value="">{{ __('All plans') }}</option>
                    @foreach ($plans as $key => $plan)
                        <option value="{{ $key }}" @selected(($filters['plan'] ?? '') === $key)>{{ $plan['name'] }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="sr-only focus:not-sr-only focus:rounded-full focus:bg-ink focus:px-4 focus:text-white">{{ __('Filter') }}</button>
        </form>
    </x-slot>

    <div class="mx-auto max-w-[1180px] space-y-5 px-4 pb-12 pt-4 sm:px-6 lg:px-10">
        @if (session('status'))
            <p class="rounded-[18px] bg-[#E3F4EE] px-4 py-3 text-sm text-[#0B4A38]" role="status">{{ session('status') }}</p>
        @endif

        <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="{{ __('Totals') }}">
            @foreach ([__('Organisations') => $totals['orgs'], __('On trial') => $totals['trialing'], __('Paying') => $totals['active'], __('Suspended') => $totals['suspended']] as $label => $value)
                <div class="flex min-w-0 flex-col gap-1.5 rounded-[22px] bg-white p-[18px] sm:p-5">
                    <dt class="text-sm text-muted">{{ $label }}</dt>
                    <dd class="ri-headline text-[26px] leading-tight sm:text-[30px]">{{ number_format($value) }}</dd>
                </div>
            @endforeach
        </dl>

        @if ($orgs->isEmpty())
            <p class="rounded-[24px] bg-white px-5 py-8 text-center text-sm text-muted">{{ __('No organisations match.') }}</p>
        @else
            <div class="relative overflow-x-auto rounded-[24px] bg-white">
                <table class="w-full min-w-[960px] text-sm">
                    <thead>
                        <tr class="text-left text-[13px] text-muted">
                            <th scope="col" class="px-5 py-4 font-semibold">{{ __('Organisation') }}</th>
                            <th scope="col" class="pe-4 font-semibold">{{ __('Plan') }}</th>
                            <th scope="col" class="pe-4 font-semibold">{{ __('Valid until') }}</th>
                            <th scope="col" class="pe-4 font-semibold">{{ __('Races') }}</th>
                            <th scope="col" class="pe-4 font-semibold">{{ __('Status') }}</th>
                            <th scope="col" class="pe-5 text-right font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orgs as $org)
                            @php
                                $expired = $org->subscription_status === 'trialing' && $org->trial_ends_at?->isPast();
                                // ponytail: one pass lookup per row (25 per page); eager-load passes if this page grows.
                                $pass = PlanLimits::pass($org);
                                $until = $org->subscription_status === 'trialing' ? $org->trial_ends_at : $pass?->ends_on;
                                $tone = match (true) {
                                    $org->isSuspended() => 'suspended',
                                    $expired => 'unpaid',
                                    $org->subscription_status === 'active' => 'paid',
                                    $org->subscription_status === 'trialing' => 'trial',
                                    default => 'ended',
                                };
                            @endphp
                            <tr class="border-t border-[#EEF0F6] align-middle">
                                <td class="px-5 py-3.5">
                                    <strong class="block text-[15px] text-ink">{{ $org->name }}</strong>
                                    <span class="text-[13px] text-muted">
                                        <a href="{{ url('/'.$org->slug) }}" class="underline-offset-2 hover:text-ink hover:underline">/{{ $org->slug }}</a>
                                        · {{ trans_choice('{1} 1 user|[0,*] :count users', $org->users_count) }}
                                        · {{ __('since :date', ['date' => $org->created_at->setTimezone('Asia/Kolkata')->format('j M Y')]) }}
                                    </span>
                                </td>
                                <td class="pe-4">
                                    {{ $plans[$org->plan]['name'] ?? '—' }}
                                    @if ($org->addons)
                                        <span class="block text-[13px] text-muted">+ {{ collect($org->addons)->map(fn ($a) => __(config("plans.addons.{$a}.name", $a)))->implode(', ') }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap pe-4 text-[#3B4463]">{{ $until ? $until->setTimezone('Asia/Kolkata')->format('d-M-Y') : '—' }}</td>
                                <td class="whitespace-nowrap pe-4">
                                    {{ $pass?->max_events ? __(':used of :allowed', ['used' => PlanLimits::racesUsed($org), 'allowed' => $pass->max_events]) : number_format($org->events_count) }}
                                    <span class="block text-[13px] text-muted">{{ trans_choice('{1} 1 registration|[0,*] :count registrations', $org->registrations_count, ['count' => number_format($org->registrations_count)]) }}</span>
                                </td>
                                <td class="pe-4">
                                    <x-ri.chip :tone="$tone">
                                        @if ($org->isSuspended())
                                            {{ __('Suspended') }}
                                        @elseif ($expired)
                                            {{ __('Trial expired') }}
                                        @else
                                            {{ __(ucfirst(str_replace('_', ' ', $org->subscription_status))) }}
                                        @endif
                                    </x-ri.chip>
                                </td>
                                <td class="whitespace-nowrap pe-5 text-right">
                                    <div class="flex justify-end gap-3">
                                        @if ($org->subscription_status === 'trialing')
                                            <form method="post" action="{{ route('platform.orgs.extend-trial', $org) }}">
                                                @csrf
                                                <button type="submit" class="{{ $action }} text-track hover:text-track-deep">{{ __('Extend trial') }}</button>
                                            </form>
                                        @endif
                                        @if ($org->isSuspended())
                                            <form method="post" action="{{ route('platform.orgs.reactivate', $org) }}">
                                                @csrf
                                                <button type="submit" class="{{ $action }} text-track hover:text-track-deep">{{ __('Reactivate') }}</button>
                                            </form>
                                        @else
                                            <form method="post" action="{{ route('platform.orgs.suspend', $org) }}" onsubmit="return confirm(@js(__('Suspend :org? Its members will be signed out.', ['org' => $org->name])))">
                                                @csrf
                                                <button type="submit" class="{{ $action }} text-danger hover:underline">{{ __('Suspend') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>{{ $orgs->links() }}</div>
        @endif
    </div>
</x-app-layout>
