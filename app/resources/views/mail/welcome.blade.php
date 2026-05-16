@extends('notifications::layout')

@php
    $primary = config('notifications.brand.primary_color', '#10B981');
    $accent = config('notifications.brand.accent_color', '#059669');
    $brand = config('notifications.brand.name', 'CMBcoreSeller');
@endphp

@section('title', 'Chào mừng đến với ' . $brand)
@section('preheader', 'Bắt đầu sử dụng ' . $brand . ' với bộ chỉ dẫn nhanh dưới đây.')

@section('content')
    <p style="margin:0 0 12px 0;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:{{ $accent }};">
        Tài khoản đã sẵn sàng
    </p>
    <h1 class="h1-mob" style="margin:0 0 20px 0;font-size:28px;line-height:36px;font-weight:700;color:#0F172A;letter-spacing:-0.02em;">
        Chào mừng đến với {{ $brand }} 🎉
    </h1>

    <p style="margin:0 0 16px 0;">Xin chào <strong>{{ $user->name }}</strong>,</p>
    <p style="margin:0 0 24px 0;color:#374151;">
        Email của bạn đã được xác thực thành công. Bạn đang trong gói <strong>Trial 14 ngày</strong>
        với đầy đủ tính năng — không cần thêm thẻ thanh toán. Dưới đây là 3 việc nên làm ngay để khởi động:
    </p>

    {{-- Checklist 3 bước --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 28px 0;">
        <tr>
            <td style="padding:14px 16px;border:1px solid #E5E7EB;border-radius:12px;background-color:#F9FAFB;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td valign="top" width="36" style="padding-right:12px;">
                            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,{{ $primary }} 0%, {{ $accent }} 100%);background-color:{{ $primary }};color:#FFFFFF;text-align:center;line-height:28px;font-weight:700;font-size:13px;">1</div>
                        </td>
                        <td valign="top" style="font-size:14px;line-height:20px;color:#111827;">
                            <strong>Kết nối gian hàng đầu tiên</strong><br>
                            <span style="color:#6B7280;">Vào "Gian hàng" → bấm "Kết nối TikTok / Shopee / Lazada" để đồng bộ đơn tự động.</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr><td style="height:10px;line-height:10px;font-size:10px;">&nbsp;</td></tr>
        <tr>
            <td style="padding:14px 16px;border:1px solid #E5E7EB;border-radius:12px;background-color:#F9FAFB;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td valign="top" width="36" style="padding-right:12px;">
                            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,{{ $primary }} 0%, {{ $accent }} 100%);background-color:{{ $primary }};color:#FFFFFF;text-align:center;line-height:28px;font-weight:700;font-size:13px;">2</div>
                        </td>
                        <td valign="top" style="font-size:14px;line-height:20px;color:#111827;">
                            <strong>Khai báo SKU và tồn kho</strong><br>
                            <span style="color:#6B7280;">"Sản phẩm" → tạo SKU master, ghép listing từ sàn, nhập tồn đầu kỳ. Đẩy tồn ngược ra sàn tự động.</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr><td style="height:10px;line-height:10px;font-size:10px;">&nbsp;</td></tr>
        <tr>
            <td style="padding:14px 16px;border:1px solid #E5E7EB;border-radius:12px;background-color:#F9FAFB;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td valign="top" width="36" style="padding-right:12px;">
                            <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,{{ $primary }} 0%, {{ $accent }} 100%);background-color:{{ $primary }};color:#FFFFFF;text-align:center;line-height:28px;font-weight:700;font-size:13px;">3</div>
                        </td>
                        <td valign="top" style="font-size:14px;line-height:20px;color:#111827;">
                            <strong>Mời nhân viên</strong><br>
                            <span style="color:#6B7280;">"Cài đặt" → "Thành viên" → mời theo email, phân quyền theo vai trò (xử lý đơn / kho / kế toán).</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

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
