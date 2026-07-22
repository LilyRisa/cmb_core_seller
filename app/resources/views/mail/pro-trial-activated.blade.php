@extends('notifications::layout')

@php
    $primary = config('notifications.brand.primary_color', '#10B981');
    $accent = config('notifications.brand.accent_color', '#059669');
    $brand = config('notifications.brand.name', 'CMBcoreSeller');
@endphp

@section('title', 'Gói Pro trải nghiệm đã kích hoạt')
@section('preheader', 'Bạn đang dùng thử toàn bộ tính năng gói Pro miễn phí đến ' . $expiresAt->format('d/m/Y') . '.')

@section('content')
    <p style="margin:0 0 12px 0;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:{{ $accent }};">
        Trải nghiệm Pro đã kích hoạt
    </p>
    <h1 class="h1-mob" style="margin:0 0 20px 0;font-size:28px;line-height:36px;font-weight:700;color:#0F172A;letter-spacing:-0.02em;">
        Chúc mừng, bạn đang dùng thử gói Pro! 🎉
    </h1>

    <p style="margin:0 0 16px 0;">Xin chào <strong>{{ $user->name }}</strong>,</p>
    <p style="margin:0 0 24px 0;color:#374151;">
        Gói <strong>Pro trải nghiệm</strong> của bạn đã được kích hoạt từ
        <strong>{{ $grantedAt->format('d/m/Y') }}</strong> đến hết
        <strong>{{ $expiresAt->format('d/m/Y') }}</strong> — toàn bộ tính năng Pro (nhắn tin AI,
        quảng cáo, kế toán nâng cao, báo cáo lợi nhuận…) đều mở khoá miễn phí trong thời gian này.
        Khi hết hạn, hệ thống sẽ tự động chuyển về gói trước đó — không mất phí, không cần huỷ.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="cta-mob" style="margin:0 0 28px 0;">
        <tr>
            <td align="center" style="border-radius:10px;background:linear-gradient(135deg,{{ $primary }} 0%, {{ $accent }} 100%);background-color:{{ $primary }};">
                <a href="{{ $appUrl }}"
                   style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:600;color:#FFFFFF;text-decoration:none;border-radius:10px;line-height:20px;">
                    Mở bảng điều khiển →
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:13px;color:#6B7280;">
        Có thắc mắc? Trả lời email này không tới được — vui lòng liên hệ
        <a href="mailto:{{ config('notifications.brand.support_email') }}" style="color:{{ $accent }};text-decoration:underline;">{{ config('notifications.brand.support_email') }}</a>
        và đội ngũ chúng tôi sẽ phản hồi trong vòng 24 giờ.
    </p>
@endsection
