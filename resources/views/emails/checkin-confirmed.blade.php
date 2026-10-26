@extends('emails.layout')

@section('preheader', __('You are checked in at :event. Have a great race!', ['event' => $brand->eventName]))
@section('heading', __('You\'re checked in'))

@section('content')
    <tr>
        <td style="padding:12px 32px 0; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:16px; line-height:1.6; color:#121A33;">
            {{ __('Checked in at :time IST. Have a great race!', ['time' => $registration->checked_in_at?->setTimezone(\App\Notifications\EmailBrand::TIMEZONE)->format('g:i A')]) }}
        </td>
    </tr>

    @include('emails.partials.pass-number')
    @include('emails.partials.details')
    <tr><td style="height:20px; line-height:20px; font-size:0;">&nbsp;</td></tr>
@endsection
