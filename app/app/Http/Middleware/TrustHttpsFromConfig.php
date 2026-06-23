<?php

namespace CMBcoreSeller\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ép scheme `https` cho request khi `APP_URL` là https (và không phải môi trường local).
 *
 * Bối cảnh sự cố (SPEC 0022): TLS cắt ở Cloudflare, nginx-proxy-manager lắng nghe
 * HTTP :80 nên forward `X-Forwarded-Proto: http`. Laravel (TRUSTED_PROXIES=*) tin
 * header đó ⇒ coi request là http ⇒ dựng lại chữ ký signed-URL trên `http://…`,
 * không khớp link đã ký bằng `https` APP_URL ⇒ MỌI link xác thực email / reset mật
 * khẩu đều đọc thành "không hợp lệ / hết hạn".
 *
 * Proxy đã được sửa để gửi `X-Forwarded-Proto: https`; middleware này là lớp phòng
 * thủ phía app — đồng bộ scheme cho cả việc SINH url lẫn KIỂM TRA chữ ký, để một lần
 * lỡ cấu hình proxy sai sẽ không làm hỏng toàn bộ luồng signed URL.
 */
class TrustHttpsFromConfig
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldForceHttps()) {
            // Header được TrustProxies tin tưởng ⇒ Request::isSecure()/getScheme() = https
            // (dùng cho kiểm tra chữ ký). HTTPS server var + forceScheme phủ phần sinh URL.
            $request->headers->set('X-Forwarded-Proto', 'https');
            $request->server->set('HTTPS', 'on');
            URL::forceScheme('https');
        }

        return $next($request);
    }

    private function shouldForceHttps(): bool
    {
        // Chỉ áp cho môi trường chạy sau proxy thật (prod/staging). local + testing
        // dùng http://localhost / test client ⇒ ép https sẽ làm sai redirect & cookie.
        return ! app()->environment(['local', 'testing'])
            && str_starts_with((string) config('app.url'), 'https://');
    }
}
