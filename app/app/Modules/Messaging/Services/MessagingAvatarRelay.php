<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use Illuminate\Support\Facades\Http;
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
                return null;
            }
            $body = $res->body();
            if ($body === '' || strlen($body) > 5 * 1024 * 1024) { // avatar > 5MB ⇒ bỏ
                return null;
            }
            $path = "tenants/{$tenantId}/messaging/avatars/".Str::uuid()->toString().'.jpg';
            $this->storage->disk()->put($path, $body);

            return $path;
        } catch (\Throwable) {
            return null;
        }
    }
}
