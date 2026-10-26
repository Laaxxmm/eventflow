@extends('emails.layout')

@section('preheader', __('Your pass for :event is attached. Show the QR at check-in.', ['event' => $brand->eventName]))
@section('heading', __('You\'re registered, :name!', ['name' => \Illuminate\Support\Str::of($registration->participant_name)->before(' ')]))

@section('content')
    <tr>
        <td style="padding:12px 32px 0; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:16px; line-height:1.6; color:#121A33;">
            {{ __('Payment received and your place is confirmed. Your participant pass is attached as a PDF (and a PNG for your phone).') }}
        </td>
    </tr>

    @include('emails.partials.pass-number')
    @include('emails.partials.details')

    @if ($registration->amount_paise > 0)
        <tr>
            <td style="padding:8px 32px 0; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:14px; color:#4A5372;">
                {{ __('Paid ₹:amount', ['amount' => number_format($registration->amount_paise / 100, 2)]) }}@if ($registration->gateway_payment_id) · {{ __('Payment ID :id', ['id' => $registration->gateway_payment_id]) }}@endif
            </td>
        </tr>
    @endif

    @include('emails.partials.buttons', [
        'primaryLabel' => __('View pass'),
        'primaryUrl' => $brand->passUrl,
        'secondaryLabel' => __('Add to calendar'),
        'secondaryUrl' => $brand->calendarUrl,
    ])

    <tr>
        <td style="padding:0 32px 24px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#FDF0DA; border-radius:12px;">
                <tr>
                    <td style="padding:14px 18px; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:14px; line-height:1.6; color:#6B4A12;">
                        <strong>{{ __('At the venue:') }}</strong>
                        {{ __('show the QR code on your pass (printed or on your phone) at check-in. It is unique to you, so please do not share it publicly.') }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
@endsection
