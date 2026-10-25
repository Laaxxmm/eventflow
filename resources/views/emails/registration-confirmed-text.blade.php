{{ __('You\'re registered for :event.', ['event' => $brand->eventName]) }}

{{ __('Pass / BIB number') }}: {{ $registration->pass_no }}
{{ __('When') }}: {{ $brand->dateLine() }}
{{ __('Where') }}: {{ $brand->venue }}
@if ($brand->distanceLabel)
{{ __('Distance') }}: {{ $brand->distanceLabel }}
@endif

{{ __('Your pass is attached (PDF and PNG). Show its QR code at check-in.') }}
@if ($brand->passUrl)
{{ __('View pass') }}: {{ $brand->passUrl }}
@endif

{{ __('Sent by :org.', ['org' => $brand->orgName]) }}
