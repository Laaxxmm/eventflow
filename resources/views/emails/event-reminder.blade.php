@extends('emails.layout')

@section('preheader', __(':event starts :when.', ['event' => $brand->eventName, 'when' => $brand->date?->format('D j M, g:i A')]))
@section('heading', __('See you tomorrow'))

@section('content')
    <tr>
        <td style="padding:12px 32px 0; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:16px; line-height:1.6; color:#121A33;">
            {{ __('A quick reminder before race day. Arrive early, and keep your pass QR ready for check-in.') }}
        </td>
    </tr>

    @include('emails.partials.details')
    @include('emails.partials.pass-number')
    @include('emails.partials.buttons', [
        'primaryLabel' => __('View pass'),
        'primaryUrl' => $brand->passUrl,
        'secondaryLabel' => __('Event page'),
        'secondaryUrl' => $brand->eventUrl,
    ])
@endsection
