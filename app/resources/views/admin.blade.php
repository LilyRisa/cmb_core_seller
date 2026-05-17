<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" type="image/png" href="/images/logocmb.png">
    <title>CMBcore Admin</title>
    @vite(['resources/js/admin.tsx'])
</head>
<body>
    <div id="admin-root"></div>
</body>
</html>
