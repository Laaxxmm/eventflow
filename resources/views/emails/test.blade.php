<!DOCTYPE html>
<html lang="en">
<body style="margin:0; padding:24px; background:#f2f4f7; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:16px;">
        <tr>
            <td style="padding:32px;">
                <h1 style="margin:0 0 12px; font-size:22px; color:#0f172a;">{{ __('It works.') }}</h1>
                <p style="margin:0; font-size:15px; line-height:1.6; color:#374151;">
                    {{ __(':org can send email through :route. Participant emails will go out this way.', ['org' => $org->name, 'route' => $route]) }}
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
