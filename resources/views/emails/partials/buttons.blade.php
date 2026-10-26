{{-- Primary + secondary CTA. $brand, $primaryLabel, $primaryUrl, optional $secondaryLabel, $secondaryUrl --}}
@php($font = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif")
<tr>
    <td style="padding:16px 32px 28px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
            <tr>
                @if ($primaryUrl)
                    <td style="border-radius:999px; background:{{ $brand->primary }};">
                        <a href="{{ $primaryUrl }}" style="display:inline-block; padding:13px 26px; font-family:{{ $font }}; font-size:15px; font-weight:700; color:{{ $brand->onPrimary }}; text-decoration:none; border-radius:999px;">{{ $primaryLabel }}</a>
                    </td>
                    <td style="width:10px;">&nbsp;</td>
                @endif
                @if (! empty($secondaryUrl))
                    <td style="border-radius:999px; border:1px solid #d1d5db;">
                        <a href="{{ $secondaryUrl }}" style="display:inline-block; padding:12px 22px; font-family:{{ $font }}; font-size:15px; font-weight:600; color:#121A33; text-decoration:none; border-radius:999px;">{{ $secondaryLabel }}</a>
                    </td>
                @endif
            </tr>
        </table>
    </td>
</tr>
