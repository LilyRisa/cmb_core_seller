@extends('notifications::layout')

@php
    $primary = config('notifications.brand.primary_color', '#10B981');
    $accent = config('notifications.brand.accent_color', '#059669');
    $brand = config('notifications.brand.name', 'CMBcoreSeller');
@endphp

@section('title', $subjectLine . ' — ' . $brand)
@section('preheader', $subjectLine)

@section('content')
    <p style="margin:0 0 12px 0;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:{{ $accent }};">
        Thông báo từ {{ $brand }}
    </p>
    <h1 class="h1-mob" style="margin:0 0 20px 0;font-size:24px;line-height:32px;font-weight:700;color:#0F172A;letter-spacing:-0.02em;">
        {{ $subjectLine }}
    </h1>

    <p style="margin:0 0 16px 0;color:#374151;">Chào <strong>{{ $user->name }}</strong>,</p>

    {{-- Body rendered từ markdown (đã escape HTML input) --}}
    <div class="text-body" style="font-size:14px;line-height:22px;color:#374151;">
        {!! $bodyHtml !!}
    </div>

    <hr class="border-soft" style="border:0;border-top:1px solid #E5E7EB;margin:28px 0 18px;">

    <p style="margin:0;font-size:12px;color:#94A3B8;">
        Đây là email thông báo từ đội ngũ {{ $brand }} (broadcast #{{ $broadcastId }}). Nếu có câu hỏi, vui lòng trả lời support@cmbcore.com.
    </p>
@endsection
