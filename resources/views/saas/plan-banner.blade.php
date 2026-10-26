{{-- Organiser warnings: races at their participant cap, races left, participant email on hold. $org --}}
@php
    $pressure = \App\Saas\PlanLimits::pressure($org);
    $mailHeld = ! \App\Notifications\NotificationSettings::for($org)->canSendParticipantMail($org);
@endphp
@foreach ($pressure as $row)
    <div @class(['rounded-[22px] p-5 text-sm', 'bg-[#FBE4E4] text-[#6E1414]' => $row['full'], 'bg-[#FDF0DA] text-[#6B4A12]' => ! $row['full']]) role="status">
        <p class="font-semibold">
            @if ($row['full'])
                {{ __(':race has reached its plan limit (:taken of :cap participants). New registrations are paused.', ['race' => $row['event']->name, 'taken' => number_format($row['taken']), 'cap' => number_format($row['cap'])]) }}
            @else
                {{ __(':race is close to its plan limit: :taken of :cap participants.', ['race' => $row['event']->name, 'taken' => number_format($row['taken']), 'cap' => number_format($row['cap'])]) }}
            @endif
        </p>
        <div class="mt-3">@include('saas.overage-actions', ['org' => $org, 'event' => $row['event']])</div>
    </div>
@endforeach
@if ($mailHeld)
    <div class="rounded-[22px] bg-[#FDF0DA] p-5 text-sm text-[#6B4A12]" role="status">
        <p class="font-semibold">{{ __('Participant emails are on hold.') }}</p>
        <p class="mt-1">{{ __('Connect your own SMTP (free) or add Platform mail so passes and reminders reach participants. Passes can still be downloaded after payment.') }}</p>
        @can('manage-org')
            <a href="{{ route('settings.notifications.show') }}" class="mt-2 inline-block font-semibold text-ink underline">{{ __('Set up email') }}</a>
        @endcan
    </div>
@endif
