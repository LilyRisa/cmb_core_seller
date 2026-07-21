<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO --}}
    @php($seoTitle = 'CMBcore – Phần mềm quản lý bán hàng đa sàn Shopee, TikTok Shop, Lazada')
    @php($seoDesc = 'CMBcore – nền tảng quản lý bán hàng đa sàn: đồng bộ đơn hàng, tồn kho, in vận đơn, đối soát và chăm sóc khách hàng cho Shopee, TikTok Shop, Lazada trên một hệ thống.')
    @php($seoUrl = rtrim(config('app.url'), '/'))
    @php($seoImage = $seoUrl.'/images/og-banner.jpg')
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDesc }}">
    <meta name="keywords" content="quản lý bán hàng đa sàn, phần mềm bán hàng online, đồng bộ đơn hàng, đồng bộ tồn kho, in vận đơn, đối soát sàn, Shopee, TikTok Shop, Lazada, quản lý gian hàng">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="{{ $seoUrl }}">
    <meta name="theme-color" content="#2563EB">

    {{-- Open Graph (Facebook / Zalo …) --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="CMBcore">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    <meta property="og:url" content="{{ $seoUrl }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="896">

    {{-- Twitter card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDesc }}">
    <meta name="twitter:image" content="{{ $seoImage }}">

    <link rel="icon" type="image/png" href="/images/logocmb.png">
    <link rel="apple-touch-icon" href="/images/logocmb.png">
    @php($fbPixelId = system_setting('growth.facebook.enabled', false) ? system_setting('growth.facebook.pixel_id') : null)
    @if($fbPixelId)
        {{-- Meta Pixel base code (SPEC 2026-07-22) — chỉ nhúng khi admin đã bật + cấu hình
             Pixel ID ở /admin/settings (tab "Tăng trưởng"). Set cookie _fbp/_fbc dùng chung
             cho Conversions API (xem lib/acquisition.ts readFacebookCookies). --}}
        <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ $fbPixelId }}');
        fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
          src="https://www.facebook.com/tr?id={{ $fbPixelId }}&ev=PageView&noscript=1"
        /></noscript>
    @endif
    @vite(['resources/js/app.tsx'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
