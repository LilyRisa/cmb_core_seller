<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="/images/logocmb.png">
    <link rel="apple-touch-icon" href="/images/logocmb.png">
    <title>{{ config('app.name', 'CMBcoreSeller') }}</title>
    @vite(['resources/js/app.tsx'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
