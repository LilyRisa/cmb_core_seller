<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đang hoàn tất kết nối…</title>
</head>
<body style="font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;color:#475569;background:#F8FAFC">
    <p>Đang hoàn tất kết nối…</p>
    <script>
        (function () {
            var redirect = @json($redirect);
            try {
                if (window.opener && !window.opener.closed) {
                    window.opener.postMessage({ source: 'cmb-oauth', redirect: redirect }, window.location.origin);
                    window.close();
                    return;
                }
            } catch (e) { /* opener cross-origin / đã đóng — rơi xuống fallback */ }
            window.location.replace(redirect);
        })();
    </script>
</body>
</html>
