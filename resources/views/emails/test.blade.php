<!DOCTYPE html>
<html lang="en">
<body style="margin:0; padding:24px; background:#F3F5FA; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:16px;">
        <tr>
            <td style="padding:32px;">
                <h1 style="margin:0 0 12px; font-size:22px; color:#121A33;">{{ __('It works.') }}</h1>
                <p style="margin:0; font-size:15px; line-height:1.6; color:#121A33;">
                    {{ __(':org can send email through :route. Participant emails will go out this way.', ['org' => $org->name, 'route' => $route]) }}
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
