{{ __('Reminder: :event', ['event' => $brand->eventName]) }}

{{ __('When') }}: {{ $brand->dateLine() }}
{{ __('Where') }}: {{ $brand->venue }}
{{ __('Pass / BIB number') }}: {{ $registration->pass_no }}
@if ($brand->passUrl)
{{ __('View pass') }}: {{ $brand->passUrl }}
@endif

{{ __('Sent by :org.', ['org' => $brand->orgName]) }}
