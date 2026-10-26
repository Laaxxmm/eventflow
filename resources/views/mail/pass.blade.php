<!DOCTYPE html>
<html>
<body style="margin:0; padding:24px; background:#F3F5FA; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #121A33; line-height: 1.55;">
<div style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:24px; padding:28px;">
    <p>Hi {{ $registration->participant_name }},</p>
    <p>Your registration for <strong>{{ $registration->event->name ?? 'the event' }}</strong> is confirmed.</p>
    <p>Pass number: <strong style="letter-spacing: .04em;">{{ $registration->pass_no }}</strong></p>
    <p>
        {{ $registration->event?->date?->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
        · {{ $registration->event->venue ?? '' }}
        @if($registration->distance_code)
            · {{ $registration->distance_code }}
        @endif
    </p>
    <p>Your participant pass is attached as a PNG and a PDF. Keep the QR ready for check-in. Do not post it publicly.</p>
    <p style="color: #4A5372; font-size: 12px;">Powered by EventFlow</p>
</div>
</body>
</html>
