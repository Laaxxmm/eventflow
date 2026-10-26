{{--
    Branded email shell. Table layout + inline styles for email clients (Gmail, Outlook, Apple Mail).
    $brand: App\Notifications\EmailBrand. Sections: preheader, heading, content.
--}}
@php($font = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif")
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <title>@yield('heading')</title>
</head>
<body style="margin:0; padding:0; background:#F3F5FA; -webkit-text-size-adjust:100%;">
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all;">@yield('preheader')&#8199;&#847;&#8199;&#847;&#8199;&#847;</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F3F5FA;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; background:#ffffff; border-radius:24px; overflow:hidden;">
                    {{-- Brand header --}}
                    <tr>
                        <td style="background:{{ $brand->secondary }}; padding:22px 32px;">
                            @if ($brand->logoUrl)
                                <img src="{{ $brand->logoUrl }}" alt="{{ $brand->orgName }}" height="36" style="display:block; height:36px; width:auto; border:0; font-family:{{ $font }}; font-size:18px; font-weight:700; line-height:36px; color:{{ $brand->onSecondary }};">
                            @else
                                <span style="font-family:{{ $font }}; font-size:18px; font-weight:700; color:{{ $brand->onSecondary }};">{{ $brand->orgName }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr><td style="background:{{ $brand->primary }}; height:6px; line-height:6px; font-size:0;">&nbsp;</td></tr>

                    {{-- Heading --}}
                    <tr>
                        <td style="padding:36px 32px 8px; font-family:{{ $font }};">
                            <p style="margin:0 0 8px; font-size:14px; font-weight:600; color:#4A5372;">{{ $brand->eventName }}</p>
                            <h1 style="margin:0; font-size:26px; line-height:1.25; font-weight:800; letter-spacing:-0.4px; color:#121A33;">@yield('heading')</h1>
                        </td>
                    </tr>

                    @yield('content')

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:24px 32px 32px; border-top:1px solid #EEF0F6; font-family:{{ $font }}; font-size:12px; line-height:1.6; color:#4A5372;">
                            {{ __('Sent by :org.', ['org' => $brand->orgName]) }}
                            @if ($brand->replyTo)
                                {{ __('Questions? Just reply to this email to reach the organiser.') }}
                            @endif
                            <br>{{ __('Registrations powered by :app.', ['app' => config('app.name', 'EventFlow')]) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
