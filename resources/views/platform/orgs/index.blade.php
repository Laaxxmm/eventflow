<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">
            {{ __('Platform: organisations') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="px-4 sm:px-0 text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <dl class="grid grid-cols-2 lg:grid-cols-4 gap-4 px-4 sm:px-0">
                @foreach ([__('Organisations') => $totals['orgs'], __('On trial') => $totals['trialing'], __('Active') => $totals['active'], __('Suspended') => $totals['suspended']] as $label => $value)
                    <div class="p-4 sm:p-6 rounded-[28px] bg-white">
                        <dt class="text-sm text-muted">{{ $label }}</dt>
                        <dd class="mt-1 text-2xl font-semibold text-ink">{{ number_format($value) }}</dd>
                    </div>
                @endforeach
            </dl>

            <div class="rounded-[28px] bg-white p-5 sm:p-8">
                <form method="get" action="{{ route('platform.orgs.index') }}" class="flex flex-col sm:flex-row sm:items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="q" :value="__('Search')" />
                        <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="$filters['q'] ?? ''" :placeholder="__('Name or slug')" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="border-mist focus:border-track focus:ring-track rounded-md shadow-sm mt-1 block w-full">
                            <option value="">{{ __('All') }}</option>
                            @foreach (['trialing' => __('On trial'), 'trial_expired' => __('Trial expired'), 'active' => __('Active'), 'past_due' => __('Past due'), 'cancelled' => __('Cancelled'), 'suspended' => __('Suspended')] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="plan" :value="__('Plan')" />
                        <select id="plan" name="plan" class="border-mist focus:border-track focus:ring-track rounded-md shadow-sm mt-1 block w-full">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($plans as $key => $plan)
                                <option value="{{ $key }}" @selected(($filters['plan'] ?? '') === $key)>{{ $plan['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>
                </form>

                @if ($orgs->isEmpty())
                    <p class="mt-6 text-sm text-muted">{{ __('No organisations match.') }}</p>
                @else
                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-muted border-b">
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Organisation') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Plan') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Status') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium text-right">{{ __('Users') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium text-right">{{ __('Events') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium text-right">{{ __('Registrations') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Signed up') }}</th>
                                    <th scope="col" class="py-2 font-medium"><span class="sr-only">{{ __('Actions') }}</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach ($orgs as $org)
                                    @php($expired = $org->subscription_status === 'trialing' && $org->trial_ends_at?->isPast())
                                    <tr>
                                        <td class="py-3 pe-4">
                                            <span class="block font-semibold text-ink">{{ $org->name }}</span>
                                            <a href="{{ url('/'.$org->slug) }}" class="text-muted underline hover:text-ink">/{{ $org->slug }}</a>
                                        </td>
                                        <td class="py-3 pe-4">
                                            {{ $plans[$org->plan]['name'] ?? '—' }}
                                            @if ($org->addons)
                                                <span class="block text-muted">+ {{ collect($org->addons)->map(fn ($a) => __(config("plans.addons.{$a}.name", $a)))->implode(', ') }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 pe-4">
                                            <span @class([
                                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                                'bg-[#FBE4E4] text-[#6E1414]' => $org->isSuspended() || $expired,
                                                'bg-green-100 text-green-800' => ! $org->isSuspended() && $org->subscription_status === 'active',
                                                'bg-paper text-ink' => ! $org->isSuspended() && ! $expired && $org->subscription_status !== 'active',
                                            ])>
                                                @if ($org->isSuspended())
                                                    {{ __('Suspended') }}
                                                @elseif ($expired)
                                                    {{ __('Trial expired') }}
                                                @else
                                                    {{ __(ucfirst(str_replace('_', ' ', $org->subscription_status))) }}
                                                @endif
                                            </span>
                                            @if ($org->subscription_status === 'trialing' && $org->trial_ends_at)
                                                <span class="block text-muted">{{ __('until :date', ['date' => $org->trial_ends_at->setTimezone('Asia/Kolkata')->format('j M Y')]) }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 pe-4 text-right">{{ $org->users_count }}</td>
                                        <td class="py-3 pe-4 text-right">{{ $org->events_count }}</td>
                                        <td class="py-3 pe-4 text-right">{{ number_format($org->registrations_count) }}</td>
                                        <td class="py-3 pe-4 whitespace-nowrap">{{ $org->created_at->setTimezone('Asia/Kolkata')->format('j M Y') }}</td>
                                        <td class="py-3 whitespace-nowrap text-right">
                                            <div class="flex justify-end gap-2">
                                                @if ($org->subscription_status === 'trialing')
                                                    <form method="post" action="{{ route('platform.orgs.extend-trial', $org) }}">
                                                        @csrf
                                                        <x-secondary-button type="submit">{{ __('Extend trial') }}</x-secondary-button>
                                                    </form>
                                                @endif
                                                @if ($org->isSuspended())
                                                    <form method="post" action="{{ route('platform.orgs.reactivate', $org) }}">
                                                        @csrf
                                                        <x-secondary-button type="submit">{{ __('Reactivate') }}</x-secondary-button>
                                                    </form>
                                                @else
                                                    <form method="post" action="{{ route('platform.orgs.suspend', $org) }}" onsubmit="return confirm(@js(__('Suspend :org? Its members will be signed out.', ['org' => $org->name])))">
                                                        @csrf
                                                        <x-danger-button>{{ __('Suspend') }}</x-danger-button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-6">{{ $orgs->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
