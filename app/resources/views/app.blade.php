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
    @vite(['resources/js/app.tsx'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
