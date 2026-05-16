@php
    $brand = config('notifications.brand.name', 'CMBcoreSeller');
    $tagline = config('notifications.brand.tagline', 'Quản lý bán hàng đa sàn');
    $supportEmail = config('notifications.brand.support_email', 'support@cmbcore.com');
    $primary = config('notifications.brand.primary_color', '#10B981');
    $accent = config('notifications.brand.accent_color', '#059669');
    $logoUrl = config('notifications.brand.logo_url');
    $year = date('Y');
@endphp
<!DOCTYPE html>
<html lang="vi" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>@yield('title', $brand)</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <style>
        body, table, td, p, a, li { -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; }
        table, td { mso-table-lspace:0pt; mso-table-rspace:0pt; border-collapse:collapse; }
        img { -ms-interpolation-mode:bicubic; border:0; outline:none; text-decoration:none; display:block; }
        a { text-decoration:none; color:{{ $accent }}; }
        body { margin:0 !important; padding:0 !important; width:100% !important; background-color:#F3F4F6; }

        @media only screen and (max-width:600px) {
            .container { width:100% !important; max-width:100% !important; }
            .px-mob { padding-left:24px !important; padding-right:24px !important; }
            .py-mob { padding-top:32px !important; padding-bottom:32px !important; }
            .h1-mob { font-size:24px !important; line-height:32px !important; }
            .cta-mob { width:100% !important; }
            .cta-mob a { display:block !important; }
        }

        @media (prefers-color-scheme: dark) {
            .bg-page { background:#0B0F19 !important; }
            .bg-card { background:#111827 !important; }
            .text-body { color:#E5E7EB !important; }
            .text-muted { color:#9CA3AF !important; }
            .border-soft { border-color:#1F2937 !important; }
        }
    </style>
</head>
<body class="bg-page" style="margin:0;padding:0;background-color:#F3F4F6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;">

    {{-- Preheader: snippet preview ẩn --}}
    <div style="display:none;font-size:1px;color:#F3F4F6;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
        @yield('preheader', $brand)
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" class="bg-page" style="background-color:#F3F4F6;">
        <tr>
            <td align="center" style="padding:40px 16px;">

                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="container" style="width:600px;max-width:600px;">

                    {{-- HEADER --}}
                    <tr>
                        <td align="center" style="padding:0 0 24px 0;">
                            @if ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $brand }}" width="48" height="48" style="display:inline-block;border-radius:12px;">
                            @else
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td style="background:linear-gradient(135deg,{{ $primary }} 0%, {{ $accent }} 100%);background-color:{{ $primary }};border-radius:14px;padding:12px 18px;color:#FFFFFF;font-weight:700;font-size:18px;letter-spacing:-0.01em;">
                                            {{ $brand }}
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>

                    {{-- CARD --}}
                    <tr>
                        <td class="bg-card border-soft" style="background-color:#FFFFFF;border-radius:16px;border:1px solid #E5E7EB;box-shadow:0 1px 2px rgba(16,24,40,0.04);">

                            {{-- Accent strip --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="background:linear-gradient(90deg,{{ $primary }} 0%, {{ $accent }} 100%);background-color:{{ $primary }};height:4px;line-height:4px;font-size:4px;border-top-left-radius:16px;border-top-right-radius:16px;">&nbsp;</td>
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td class="px-mob py-mob text-body" style="padding:40px 48px;color:#1F2937;font-size:15px;line-height:24px;">
                                        @yield('content')
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- FOOTER --}}
                    <tr>
                        <td class="text-muted" align="center" style="padding:32px 24px 0 24px;color:#6B7280;font-size:12px;line-height:18px;">
                            <p style="margin:0 0 8px 0;font-weight:600;color:#374151;">{{ $brand }}</p>
                            <p style="margin:0 0 12px 0;">{{ $tagline }}</p>
                            <p style="margin:0 0 4px 0;">
                                Cần hỗ trợ? Email: <a href="mailto:{{ $supportEmail }}" style="color:{{ $accent }};text-decoration:none;">{{ $supportEmail }}</a>
                            </p>
                            <p style="margin:12px 0 0 0;color:#9CA3AF;">
                                © {{ $year }} {{ $brand }}. Email này được gửi tự động — vui lòng không trả lời.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
