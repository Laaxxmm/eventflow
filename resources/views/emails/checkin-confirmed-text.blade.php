{{ __('You are checked in at :event.', ['event' => $brand->eventName]) }}

{{ __('Pass / BIB number') }}: {{ $registration->pass_no }}
{{ __('Checked in') }}: {{ $registration->checked_in_at?->setTimezone(\App\Notifications\EmailBrand::TIMEZONE)->format('j M Y, g:i A') }} IST

{{ __('Sent by :org.', ['org' => $brand->orgName]) }}
