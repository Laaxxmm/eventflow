{{ __('Complete your entry: :event', ['event' => $brand->eventName]) }}

{{ $intro }}

{{ __('When') }}: {{ $brand->dateLine() }}
{{ __('Where') }}: {{ $brand->venue }}
{{ __('Participant') }}: {{ $registration->participant_name }}

{{ __('Pay now') }}: {{ $payUrl }}
@if ($brand->replyTo)
{{ __('Questions? Write to :email.', ['email' => $brand->replyTo]) }}
@endif

{{ __('Already paid? Thank you, you can ignore this email.') }}
{{ __('Stop payment reminders') }}: {{ $stopUrl }}

{{ __('Sent by :org.', ['org' => $brand->orgName]) }}
