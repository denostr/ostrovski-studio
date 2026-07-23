<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
</head>
<body style="margin:0;padding:24px;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;color:#1a1a1a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e2e2e2;">
        <tr>
            <td style="padding:28px 32px 8px;">
                <p style="margin:0 0 4px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#8f8c86;">OSTROVSKI</p>
                <h1 style="margin:0;font-size:20px;font-weight:600;">{{ __('emails.enquiry.heading') }}</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:16px 32px 28px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.6;">
                    <tr>
                        <td style="padding:8px 0;border-top:1px solid #eee;width:120px;color:#8f8c86;">{{ __('emails.enquiry.service') }}</td>
                        <td style="padding:8px 0;border-top:1px solid #eee;font-weight:600;">{{ __('services.'.$enquiry['service'].'.title') }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;border-top:1px solid #eee;color:#8f8c86;">{{ __('emails.enquiry.name') }}</td>
                        <td style="padding:8px 0;border-top:1px solid #eee;">{{ $enquiry['name'] }}</td>
                    </tr>
                    @if ($enquiry['phone'])
                        <tr>
                            <td style="padding:8px 0;border-top:1px solid #eee;color:#8f8c86;">{{ __('emails.enquiry.phone') }}</td>
                            <td style="padding:8px 0;border-top:1px solid #eee;">{{ $enquiry['phone'] }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:8px 0;border-top:1px solid #eee;color:#8f8c86;">{{ __('emails.enquiry.email') }}</td>
                        <td style="padding:8px 0;border-top:1px solid #eee;"><a href="mailto:{{ $enquiry['email'] }}" style="color:#1a1a1a;">{{ $enquiry['email'] }}</a></td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;border-top:1px solid #eee;color:#8f8c86;vertical-align:top;">{{ __('emails.enquiry.message') }}</td>
                        <td style="padding:8px 0;border-top:1px solid #eee;white-space:pre-line;">{{ $enquiry['message'] }}</td>
                    </tr>
                </table>
                <p style="margin:20px 0 0;font-size:12px;color:#8f8c86;">{{ __('emails.enquiry.reply_hint') }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
