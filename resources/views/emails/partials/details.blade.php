{{-- Event details rows. $brand, $registration --}}
@php($font = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif")
@php($rows = array_filter([
    __('When') => $brand->dateLine(),
    __('Where') => $brand->venue,
    __('Distance') => $brand->distanceLabel,
    __('Category') => isset($registration->answers['age_band']) ? $registration->answers['age_band'] : null,
    __('Participant') => $registration->participant_name,
]))
<tr>
    <td style="padding:16px 32px 8px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-family:{{ $font }}; font-size:15px;">
            @foreach ($rows as $label => $value)
                <tr>
                    <td style="padding:10px 0; border-bottom:1px solid #eef0f3; color:#6b7280; width:120px; vertical-align:top;">{{ $label }}</td>
                    <td style="padding:10px 0; border-bottom:1px solid #eef0f3; color:#0f172a; font-weight:600;">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    </td>
</tr>
