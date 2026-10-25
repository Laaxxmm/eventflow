<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #17213a; margin: 0; }
        .pass { border: 1px solid #e4e9f2; }
        .strip { background: {{ $colours['primary'] }}; color: {{ $colours['accent'] }}; padding: 12px 18px; font-size: 11px; letter-spacing: 1px; }
        .pad { padding: 18px 20px; }
        .org { color: {{ $colours['secondary'] }}; font-size: 12px; font-weight: bold; }
        .event { font-size: 22px; font-weight: bold; margin: 6px 0; }
        .muted { color: #79849a; font-size: 11px; }
        .label { color: #8791a5; font-size: 9px; letter-spacing: 1px; }
        .name { font-size: 20px; font-weight: bold; margin-top: 4px; }
        .reg { margin-top: 16px; border: 1px dashed #aaa4ff; background: #f2f1ff; padding: 10px 12px; }
        .code { font-family: DejaVu Sans, monospace; font-size: 16px; color: #4037bf; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
    </style>
</head>
<body>
    <div class="pass">
        <div class="strip">OFFICIAL PARTICIPANT PASS</div>
        <div class="pad">
            <div class="org">{{ $registration->event->org->name ?? 'EventFlow' }}</div>
            <div class="event">{{ $registration->event->name ?? 'Event' }}</div>
            <div class="muted">{{ $when }} · {{ $registration->event->venue ?? '' }}</div>
            <table style="margin-top: 18px;">
                <tr>
                    <td style="width: 65%;">
                        <div class="label">PARTICIPANT NAME</div>
                        <div class="name">{{ $registration->participant_name }}</div>
                        <div style="margin-top: 14px;">
                            <div class="label">MOBILE NUMBER</div>
                            <strong>{{ $registration->phone }}</strong>
                        </div>
                        <div style="margin-top: 14px;">
                            <div class="label">RACE CATEGORY</div>
                            <strong>{{ $registration->distance_code ?: 'Open' }}</strong>
                        </div>
                        <div class="reg">
                            <div class="label">UNIQUE REGISTRATION NUMBER</div>
                            <div class="code">{{ $registration->pass_no }}</div>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <img src="{{ $qrDataUri }}" width="160" height="160" alt="Check-in QR">
                        <div class="label" style="margin-top: 6px;">SCAN AT CHECK-IN</div>
                    </td>
                </tr>
            </table>
            <p class="muted" style="margin-top: 22px;">Powered by EventFlow · Present this pass at the race entrance.</p>
        </div>
    </div>
</body>
</html>
