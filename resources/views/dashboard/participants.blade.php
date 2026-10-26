@php
    $inr = fn (int $paise) => \Illuminate\Support\Number::currency($paise / 100, in: 'INR', locale: 'en_IN');
    $statusValue = $status instanceof \App\Payments\PaymentStatus ? $status->value : $status;
    $tabs = ['' => __('All'), 'paid' => __('Paid'), 'unpaid' => __('Unpaid')];
    $ist = fn ($t) => $t?->setTimezone('Asia/Kolkata')->format('j M, g:i A');
    $canRemind = \App\Reminders\ReminderSchedule::featureOn();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Participants') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <header>
                    <h2 class="text-lg font-medium text-gray-900">
                        {{ $event?->name ?? __('All events') }}
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        {{ trans_choice(':count participant|:count participants', $registrations->total()) }}
                    </p>
                </header>

                <nav class="mt-4 flex gap-2 text-sm" aria-label="{{ __('Payment status') }}" data-payment-tabs>
                    @foreach ($tabs as $value => $label)
                        <a href="{{ route('dashboard.participants', array_filter(['event' => $event?->id, 'status' => $value])) }}" @class([
                            'rounded-lg px-3 py-1.5 font-medium',
                            'bg-gray-900 text-white' => ($statusValue ?? '') === $value,
                            'text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50' => ($statusValue ?? '') !== $value,
                        ])>{{ $label }}</a>
                    @endforeach
                </nav>

                @if (session()->has('reminders_queued'))
                    <div class="mt-4 rounded-md bg-green-50 p-4 text-sm text-green-800" role="status">
                        <p class="font-medium">
                            {{ trans_choice('{0} No reminders sent.|{1} 1 payment reminder queued.|[2,*] :count payment reminders queued.', session('reminders_queued'), ['count' => session('reminders_queued')]) }}
                            @if (session('reminders_deferred'))
                                {{ __('It is quiet hours (9 pm–8 am IST), so they go out at 8 am.') }}
                            @endif
                        </p>
                        @if (session('reminders_skipped'))
                            <ul class="mt-2 list-disc ps-5 text-gray-700">
                                @foreach (session('reminders_skipped') as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif

                <form method="GET" action="{{ route('dashboard.participants') }}" class="mt-6 flex flex-wrap items-end gap-4">
                    <div>
                        <x-input-label for="event" :value="__('Event')" />
                        <select id="event" name="event" class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All events') }}</option>
                            @foreach ($events as $option)
                                <option value="{{ $option->id }}" @selected($event?->is($option))>{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('Any status') }}</option>
                            <option value="unpaid" @selected($statusValue === 'unpaid')>{{ __('Unpaid (pending or failed)') }}</option>
                            @foreach ($statuses as $option)
                                <option value="{{ $option->value }}" @selected($status === $option)>{{ __(ucfirst($option->value)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-primary-button>{{ __('Filter') }}</x-primary-button>

                    @can('export-participants')
                        <x-secondary-button type="submit" formaction="{{ route('dashboard.participants.export') }}">
                            {{ __('Export CSV') }}
                        </x-secondary-button>
                    @endcan
                </form>

                @if ($registrations->isEmpty())
                    <p class="mt-6 text-sm text-gray-600">
                        {{ __('No participants match these filters.') }}
                    </p>
                @else
                    <form method="POST" action="{{ route('dashboard.participants.remind') }}" id="remind-form" x-data="{ picked: [] }">
                    @csrf
                    @if ($canRemind)
                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <button type="submit" :disabled="picked.length === 0" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-40" data-remind-now>
                                {{ __('Send reminder now') }} <span x-show="picked.length" x-text="'(' + picked.length + ')'"></span>
                            </button>
                            <p class="text-xs text-gray-500">{{ __('Select unpaid participants. Manual reminders follow quiet hours and are not sent twice within :h hours.', ['h' => config('reminders.manual_cooldown_hours')]) }}</p>
                        </div>
                    @endif
                    <div class="relative mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-gray-600">
                                    @if ($canRemind)
                                        <th scope="col" class="py-2 pe-3"><span class="sr-only">{{ __('Select') }}</span></th>
                                    @endif
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Pass no') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Name') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Contact') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Event') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Distance') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Status') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium text-right">{{ __('Amount') }}</th>
                                    <th scope="col" class="py-2 pe-4 font-medium">{{ __('Checked in') }}</th>
                                    <th scope="col" class="py-2 font-medium">{{ __('Reminders') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-900">
                                @foreach ($registrations as $r)
                                    @php
                                        $unpaid = in_array($r->status, \App\Reminders\ReminderSchedule::UNPAID, true);
                                        $last = \App\Reminders\ReminderSchedule::last($r);
                                        $next = $unpaid ? \App\Reminders\ReminderSchedule::next($r) : null;
                                        $channels = $r->reminderLogs->where('status', 'sent')->pluck('channel')->unique();
                                    @endphp
                                    <tr data-registration="{{ $r->id }}">
                                        @if ($canRemind)
                                            <td class="py-3 pe-3">
                                                @if ($unpaid && ! $r->reminders_opted_out_at)
                                                    <input type="checkbox" name="registrations[]" value="{{ $r->id }}" x-model="picked" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900" aria-label="{{ __('Select :name', ['name' => $r->participant_name]) }}">
                                                @endif
                                            </td>
                                        @endif
                                        <td class="py-3 pe-4 font-mono whitespace-nowrap">{{ $r->pass_no }}</td>
                                        <td class="py-3 pe-4">{{ $r->participant_name }}</td>
                                        <td class="py-3 pe-4">
                                            <div>{{ $r->email }}</div>
                                            <div class="text-gray-600">{{ $r->phone }}</div>
                                        </td>
                                        <td class="py-3 pe-4">{{ $r->event?->name }}</td>
                                        <td class="py-3 pe-4">{{ $r->distance_code }}</td>
                                        <td class="py-3 pe-4">{{ __(ucfirst($r->status->value)) }}</td>
                                        <td class="py-3 pe-4 text-right whitespace-nowrap">{{ $inr($r->amount_paise) }}</td>
                                        <td class="py-3 pe-4 whitespace-nowrap">{{ $r->checked_in_at?->format('d M Y, H:i') ?? '—' }}</td>
                                        <td class="py-3 text-xs text-gray-600" data-reminder-cell>
                                            @if ($last)
                                                <div title="{{ $r->reminderLogs->where('channel', 'mail')->sortBy('id')->map(fn ($l) => ($l->kind === 'manual' ? __('Manual') : '#'.$l->sequence).' · '.$l->status.' · '.$ist($l->sent_at ?? $l->created_at))->implode("\n") }}">
                                                    {{ __('Last') }}: {{ $ist($last->sent_at ?? $last->created_at) }}
                                                    <span class="ms-1" aria-label="{{ __('Channels') }}">@if ($channels->contains('mail'))✉️@endif @if ($channels->contains('whatsapp'))💬@endif</span>
                                                </div>
                                            @endif
                                            @if ($r->reminders_opted_out_at)
                                                <div class="text-amber-700">{{ __('Opted out') }}</div>
                                            @elseif ($next)
                                                <div>{{ __('Next') }}: {{ $ist($next[1]) }}</div>
                                            @elseif (! $last)
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    </form>

                    <div class="mt-6">
                        {{ $registrations->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
