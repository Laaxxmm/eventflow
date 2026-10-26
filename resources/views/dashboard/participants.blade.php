{{--
    Participants (Daylight Track, docs/design/daylight-track/Participants.dc.html): payment tabs with counts,
    search and event filter, bulk reminder bar (quiet hours), one white table card.
--}}
@php
    $inr = fn (int $paise) => \Illuminate\Support\Number::currency($paise / 100, in: 'INR', locale: 'en_IN');
    $statusValue = $status instanceof \App\Payments\PaymentStatus ? $status->value : $status;
    $tabs = ['' => __('All'), 'paid' => __('Paid'), 'unpaid' => __('Unpaid')];
    $ist = fn ($t) => $t?->setTimezone('Asia/Kolkata')->format('j M, g:i A');
    $canRemind = \App\Reminders\ReminderSchedule::featureOn();
    $query = fn (array $extra) => array_filter(['event' => $event?->id, 'status' => $statusValue, 'q' => $search] + $extra, fn ($v) => $v !== null && $v !== '');
    $field = 'min-h-[44px] rounded-full border-0 bg-white px-4 text-sm text-ink ring-1 ring-inset ring-mist focus:ring-2 focus:ring-track';
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('Participants') }}</x-slot>
    <x-slot name="header">
        <div>
            <h2>{{ __('Participants') }}</h2>
            <p class="mt-1.5 text-[15px] text-muted">{{ $event?->name ?? __('All events') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('export-participants')
                <x-ri.button variant="secondary" size="sm" :href="route('dashboard.participants.export', $query([]))">{{ __('Export CSV') }}</x-ri.button>
            @endcan
            <x-ri.button variant="primary" size="sm" :href="route('dashboard.participants.create', array_filter(['event' => $event?->id]))" data-add-participant>{{ __('Add participant') }}</x-ri.button>
        </div>
    </x-slot>

    <div class="mx-auto max-w-[1180px] space-y-5 px-4 pb-12 pt-4 sm:px-6 lg:px-10">
        @if ($added = session('participant_added'))
            <div class="rounded-[18px] bg-[#E3F4EE] px-4 py-3 text-sm text-[#0B4A38]" role="status" data-participant-added>
                {{ __(':name added (:pass).', ['name' => $added['name'], 'pass' => $added['pass']]) }}
                {{ match ($added['status']) {
                    'unpaid' => __('Marked unpaid: send a payment reminder from this list when you are ready.'),
                    default => __('Their pass is being generated and emailed.'),
                } }}
                <a href="{{ route('dashboard.participants.create', array_filter(['event' => $event?->id])) }}" class="ms-1 font-semibold underline">{{ __('Add another') }}</a>
            </div>
        @endif

        @if (session()->has('reminders_queued'))
            <div class="rounded-[18px] bg-[#E3F4EE] px-4 py-3 text-sm text-[#0B4A38]" role="status">
                <p class="font-semibold">
                    {{ trans_choice('{0} No reminders sent.|{1} 1 payment reminder queued.|[2,*] :count payment reminders queued.', session('reminders_queued'), ['count' => session('reminders_queued')]) }}
                    @if (session('reminders_deferred'))
                        {{ __('It is quiet hours (9 pm–8 am IST), so they go out at 8 am.') }}
                    @endif
                </p>
                @if (session('reminders_skipped'))
                    <ul class="mt-2 list-disc ps-5">
                        @foreach (session('reminders_skipped') as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <nav class="flex flex-wrap rounded-full bg-white p-1" aria-label="{{ __('Payment status') }}" data-payment-tabs>
                @foreach ($tabs as $value => $label)
                    @php
                        $current = ($statusValue ?? '') === $value;
                    @endphp
                    <a href="{{ route('dashboard.participants', $query(['status' => $value ?: null])) }}" @if ($current) aria-current="page" @endif @class([
                        'inline-flex min-h-[40px] items-center rounded-full px-4 text-sm font-semibold',
                        'bg-ink text-white' => $current,
                        'text-ink hover:bg-paper' => ! $current,
                    ])>{{ $label }} {{ number_format($counts[$value] ?? 0) }}</a>
                @endforeach
            </nav>

            <form method="GET" action="{{ route('dashboard.participants') }}" class="flex flex-wrap gap-2" role="search">
                @if ($statusValue)<input type="hidden" name="status" value="{{ $statusValue }}">@endif
                <label class="flex-1 sm:flex-none">
                    <span class="sr-only">{{ __('Search') }}</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('Name, phone or EF- number') }}" class="{{ $field }} w-full sm:w-60">
                </label>
                <label>
                    <span class="sr-only">{{ __('Event') }}</span>
                    <select id="event" name="event" class="{{ $field }} pe-10" onchange="this.form.submit()">
                        <option value="">{{ __('All events') }}</option>
                        @foreach ($events as $option)
                            <option value="{{ $option->id }}" @selected($event?->is($option))>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span class="sr-only">{{ __('Status') }}</span>
                    <select id="status" name="status" class="{{ $field }} pe-10" onchange="this.form.submit()">
                        <option value="">{{ __('Any status') }}</option>
                        <option value="unpaid" @selected($statusValue === 'unpaid')>{{ __('Unpaid (pending or failed)') }}</option>
                        @foreach ($statuses as $option)
                            <option value="{{ $option->value }}" @selected($status === $option)>{{ __(ucfirst($option->value)) }}</option>
                        @endforeach
                    </select>
                </label>
                <noscript><x-ri.button variant="ink" size="sm" type="submit">{{ __('Filter') }}</x-ri.button></noscript>
            </form>
        </div>

        @if ($registrations->isEmpty())
            <p class="rounded-[24px] bg-white p-8 text-center text-muted">{{ __('No participants match these filters.') }}</p>
        @else
            <form method="POST" action="{{ route('dashboard.participants.remind') }}" id="remind-form" x-data="{ picked: [] }" class="space-y-5">
                @csrf
                @if ($canRemind)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-[18px] bg-lilac px-4 py-3">
                        <span class="text-sm font-semibold text-track-deep" x-text="picked.length + ' ' + @js(__('unpaid selected'))">0 {{ __('unpaid selected') }}</span>
                        <span class="flex flex-wrap items-center gap-3">
                            <span class="text-[13px] text-[#3B4463]">{{ __('Quiet hours are 9 pm to 8 am IST, so late reminders wait till morning. Not sent twice within :h hours.', ['h' => config('reminders.manual_cooldown_hours')]) }}</span>
                            <button type="submit" :disabled="picked.length === 0" class="min-h-[44px] rounded-full bg-track px-4 text-sm font-bold text-white hover:bg-track-deep disabled:cursor-not-allowed disabled:opacity-50" data-remind-now>{{ __('Send reminder now') }}</button>
                        </span>
                    </div>
                @endif

                <div class="relative overflow-x-auto rounded-[24px] bg-white">
                    <table class="w-full min-w-[960px] text-sm">
                        <thead>
                            <tr class="text-left text-[13px] text-muted">
                                @if ($canRemind)
                                    <th scope="col" class="w-5 py-4 ps-[18px]"><span class="sr-only">{{ __('Select') }}</span></th>
                                @endif
                                <th scope="col" class="px-3 py-4 font-semibold">{{ __('Pass no') }}</th>
                                <th scope="col" class="px-3 py-4 font-semibold">{{ __('Name') }}</th>
                                <th scope="col" class="px-3 py-4 font-semibold">{{ __('Event and distance') }}</th>
                                <th scope="col" class="px-3 py-4 font-semibold">{{ __('Payment') }}</th>
                                <th scope="col" class="px-3 py-4 font-semibold">{{ __('Checked in') }}</th>
                                <th scope="col" class="px-3 py-4 pe-[18px] font-semibold">{{ __('Reminders') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($registrations as $r)
                                @php
                                    $unpaid = in_array($r->status, \App\Reminders\ReminderSchedule::UNPAID, true);
                                    $last = \App\Reminders\ReminderSchedule::last($r);
                                    $next = $unpaid ? \App\Reminders\ReminderSchedule::next($r) : null;
                                    $channels = $r->reminderLogs->where('status', 'sent')->pluck('channel')->unique();
                                    [$payLabel, $payTone] = match (true) {
                                        $r->isComplimentary() => [__('Complimentary'), 'trial'],
                                        $r->status === \App\Payments\PaymentStatus::Paid => [__('Paid'), 'paid'],
                                        $r->status === \App\Payments\PaymentStatus::Refunded => [__('Refunded'), 'ended'],
                                        $r->status === \App\Payments\PaymentStatus::Failed => [__('Failed'), 'suspended'],
                                        default => [__('Unpaid'), 'unpaid'],
                                    };
                                @endphp
                                <tr class="border-t border-[#EEF0F6] has-[:checked]:bg-[#F8F7FF]" data-registration="{{ $r->id }}">
                                    @if ($canRemind)
                                        <td class="py-3.5 ps-[18px]">
                                            @if ($unpaid && ! $r->reminders_opted_out_at)
                                                <input type="checkbox" name="registrations[]" value="{{ $r->id }}" x-model="picked" class="h-[18px] w-[18px] rounded border-[#C5CBDB] text-track focus:ring-track" aria-label="{{ __('Select :name', ['name' => $r->participant_name]) }}">
                                            @endif
                                        </td>
                                    @endif
                                    <td class="whitespace-nowrap px-3 py-3.5 font-semibold tracking-[.02em] text-[#3B4463]">{{ $r->pass_no }}</td>
                                    <td class="px-3 py-3.5">
                                        <strong class="block">{{ $r->participant_name }}
                                            @if ($r->isManual())
                                                <span class="ms-1 rounded-full bg-paper px-2 py-0.5 text-[11px] font-semibold text-muted" title="{{ $r->notes }}" data-manual-badge>{{ __('Added') }}</span>
                                            @endif
                                        </strong>
                                        <span class="block text-[13px] text-muted">{{ $r->phone }}</span>
                                        <span class="block text-[13px] text-muted">{{ $r->email }}</span>
                                    </td>
                                    <td class="px-3 py-3.5">
                                        <span class="block">{{ $r->event?->name }}</span>
                                        <span class="text-[13px] text-muted">{{ $r->distance_code }}</span>
                                    </td>
                                    <td class="px-3 py-3.5">
                                        <x-ri.chip :tone="$payTone">{{ $payLabel }}</x-ri.chip>
                                        <span class="mt-1 block whitespace-nowrap text-[13px] text-muted">{{ $inr($r->amount_paise) }}@if ($r->payment_method === \App\Models\Registration::PAID_OFFLINE) · {{ __('offline') }}@endif</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3.5 text-muted">{{ $r->checked_in_at ? $ist($r->checked_in_at) : __('Not yet') }}</td>
                                    <td class="px-3 py-3.5 pe-[18px] text-[13px] text-muted" data-reminder-cell>
                                        @if ($last)
                                            <div title="{{ $r->reminderLogs->where('channel', 'mail')->sortBy('id')->map(fn ($l) => ($l->kind === 'manual' ? __('Manual') : '#'.$l->sequence).' · '.$l->status.' · '.$ist($l->sent_at ?? $l->created_at))->implode("\n") }}">
                                                {{ __('Last') }}: {{ $ist($last->sent_at ?? $last->created_at) }}
                                                <span class="ms-1" aria-label="{{ __('Channels') }}">@if ($channels->contains('mail'))✉️@endif @if ($channels->contains('whatsapp'))💬@endif</span>
                                            </div>
                                        @endif
                                        @if ($r->reminders_opted_out_at)
                                            <div class="text-warning">{{ __('Opted out') }}</div>
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

            <div class="flex flex-wrap items-center justify-between gap-3 text-[13px] text-muted">
                <p>{{ __('Showing :n of :total.', ['n' => number_format($registrations->count()), 'total' => number_format($registrations->total())]) }}</p>
                {{ $registrations->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
