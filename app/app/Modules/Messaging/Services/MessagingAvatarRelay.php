<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Tải avatar (page / buyer) từ URL CDN Facebook (hết hạn) về object storage,
 * trả storage_path ổn định. Best-effort: lỗi ⇒ trả null (không chặn backfill).
 */
class MessagingAvatarRelay
{
    public function __construct(private MediaStorage $storage) {}

    public function relay(int $tenantId, string $url): ?string
    {
        try {
            $res = Http::timeout(20)->retry(2, 300)->get($url);
            if (! $res->successful()) {
                Log::warning('messaging.avatarRelay: tải ảnh CDN thất bại', [
                    'tenant' => $tenantId,
                    'host' => parse_url($url, PHP_URL_HOST),
                    'status' => $res->status(),
                ]);

                return null;
            }
            $body = $res->body();
            if ($body === '' || strlen($body) > 5 * 1024 * 1024) { // avatar > 5MB ⇒ bỏ
                Log::warning('messaging.avatarRelay: bỏ qua (rỗng/quá lớn)', ['tenant' => $tenantId, 'bytes' => strlen($body)]);

                return null;
            }
            $path = "tenants/{$tenantId}/messaging/avatars/".Str::uuid()->toString().'.jpg';
            $this->storage->disk()->put($path, $body);

            return $path;
        } catch (\Throwable $e) {
            // Thường gặp: object storage chưa cấu hình (R2/MinIO thiếu env) ⇒ avatar fallback về
            // URL CDN FB (hết hạn) ⇒ vỡ ảnh → hiện chữ cái. Log để thấy rõ.
            Log::warning('messaging.avatarRelay: lỗi lưu object storage (R2/MinIO chưa cấu hình?)', [
                'tenant' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
