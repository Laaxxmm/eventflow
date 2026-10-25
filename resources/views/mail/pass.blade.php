<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #17213a; line-height: 1.5;">
    <p>Hi {{ $registration->participant_name }},</p>
    <p>Your registration for <strong>{{ $registration->event->name ?? 'the event' }}</strong> is confirmed.</p>
    <p>Pass number: <strong style="font-family: monospace;">{{ $registration->pass_no }}</strong></p>
    <p>
        {{ $registration->event?->date?->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
        · {{ $registration->event->venue ?? '' }}
        @if($registration->distance_code)
            · {{ $registration->distance_code }}
        @endif
    </p>
    <p>Your participant pass is attached as a PNG and a PDF. Keep the QR ready for check-in. Do not post it publicly.</p>
    <p style="color: #79849a; font-size: 12px;">Powered by EventFlow</p>
</body>
</html>
