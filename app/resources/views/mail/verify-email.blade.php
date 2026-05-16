@extends('notifications::layout')

@php
    $primary = config('notifications.brand.primary_color', '#10B981');
    $accent = config('notifications.brand.accent_color', '#059669');
    $brand = config('notifications.brand.name', 'CMBcoreSeller');
@endphp

@section('title', 'Xác thực email — ' . $brand)
@section('preheader', 'Xác thực email để bắt đầu sử dụng ' . $brand . '. Link hết hạn sau ' . $expiresInMinutes . ' phút.')

@section('content')
    <p style="margin:0 0 12px 0;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:{{ $accent }};">
        Xác thực tài khoản
    </p>
    <h1 class="h1-mob" style="margin:0 0 20px 0;font-size:28px;line-height:36px;font-weight:700;color:#0F172A;letter-spacing:-0.02em;">
        Xác thực địa chỉ email của bạn
    </h1>

    <p style="margin:0 0 16px 0;">Chào <strong>{{ $user->name }}</strong>,</p>
    <p style="margin:0 0 24px 0;color:#374151;">
        Cảm ơn bạn đã đăng ký <strong>{{ $brand }}</strong>. Vui lòng xác thực địa chỉ email
        <strong>{{ $user->email }}</strong> để mở khoá toàn bộ tính năng quản lý đơn hàng, kho và đối soát đa sàn.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="cta-mob" style="margin:0 0 24px 0;">
        <tr>
            <td align="center" style="border-radius:10px;background:linear-gradient(135deg,{{ $primary }} 0%, {{ $accent }} 100%);background-color:{{ $primary }};">
                <a href="{{ $verifyUrl }}"
                   style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:600;color:#FFFFFF;text-decoration:none;border-radius:10px;line-height:20px;">
                    Xác thực email →
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px 0;font-size:13px;color:#6B7280;">
        Nếu nút không hoạt động, hãy sao chép đường dẫn sau vào trình duyệt:
    </p>
    <p style="margin:0 0 28px 0;font-size:13px;word-break:break-all;">
        <a href="{{ $verifyUrl }}" style="color:{{ $accent }};text-decoration:underline;">{{ $verifyUrl }}</a>
    </p>

    <hr class="border-soft" style="border:0;border-top:1px solid #E5E7EB;margin:24px 0;">

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#F9FAFB;border:1px solid #E5E7EB;border-radius:12px;">
        <tr>
            <td style="padding:16px 18px;font-size:13px;color:#4B5563;line-height:20px;">
                <p style="margin:0 0 6px 0;font-weight:600;color:#111827;">
                    ⏱ Link xác thực hết hạn sau {{ $expiresInMinutes }} phút.
                </p>
                <p style="margin:0;">
                    Nếu bạn không đăng ký tài khoản tại {{ $brand }}, vui lòng bỏ qua email này — tài khoản sẽ không được kích hoạt.
                </p>
            </td>
        </tr>
    </table>
@endsection
