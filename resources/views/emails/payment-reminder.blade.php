@extends('emails.layout')

@section('preheader', __('Your entry for :event is waiting for payment.', ['event' => $brand->eventName]))
@section('heading', __('Complete your entry'))

@section('content')
    <tr>
        <td style="padding:12px 32px 0; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:16px; line-height:1.6; color:#121A33;">
            {!! nl2br(e($intro)) !!}
        </td>
    </tr>

    @include('emails.partials.details')
    @include('emails.partials.buttons', [
        'primaryLabel' => __('Pay now'),
        'primaryUrl' => $payUrl,
        'secondaryLabel' => __('Event page'),
        'secondaryUrl' => $brand->eventUrl,
    ])

    <tr>
        <td style="padding:0 32px 24px; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:13px; line-height:1.6; color:#4A5372;">
            @if ($brand->replyTo)
                {{ __('Questions? Reply to this email or write to :email.', ['email' => $brand->replyTo]) }}<br>
            @endif
            {{ __('Already paid? Thank you, you can ignore this email.') }}
            <a href="{{ $stopUrl }}" style="color:#4A5372;">{{ __('Stop payment reminders') }}</a>
        </td>
    </tr>
@endsection
