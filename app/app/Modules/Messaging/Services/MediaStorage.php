<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Trung tâm quy ước lưu trữ media tin nhắn — đường dẫn, disk, signed URL.
 *
 * Mọi key theo prefix tenant (cách ly multi-tenant ở tầng storage):
 *   tenants/{tenantId}/messaging/{yyyy}/{mm}/{conversationId}/{uuid}.{ext}
 *
 * Disk + TTL đọc từ `config/messaging.php`. Tách service để test
 * (`Storage::fake(disk)`) và để connector/job/controller dùng chung 1 quy ước.
 */
class MediaStorage
{
    public function diskName(): string
    {
        return (string) config('messaging.media_disk', 'local');
    }

    public function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    /**
     * Dựng storage key cho 1 attachment mới.
     */
    public function buildPath(int $tenantId, int $conversationId, string $extension): string
    {
        $ext = ltrim(preg_replace('/[^a-zA-Z0-9]/', '', $extension) ?: 'bin', '.');
        $yyyy = date('Y');
        $mm = date('m');
        $uuid = (string) Str::uuid();

        return "tenants/{$tenantId}/messaging/{$yyyy}/{$mm}/{$conversationId}/{$uuid}.{$ext}";
    }

    /**
     * Signed URL TTL ngắn cho FE. Disk local không hỗ trợ temporaryUrl ⇒ fallback url().
     * Trả null nếu attachment chưa downloaded.
     */
    public function temporaryUrl(MessageAttachment $attachment): ?string
    {
        if ($attachment->status !== MessageAttachment::STATUS_DOWNLOADED || ! $attachment->storage_path) {
            return null;
        }

        $disk = $this->disk();
        $ttl = (int) config('messaging.signed_url_ttl', 300);

        try {
            return $disk->temporaryUrl($attachment->storage_path, now()->addSeconds($ttl));
        } catch (\Throwable) {
            // Disk local / driver không hỗ trợ signed URL — fallback url() (dev only).
            try {
                return $disk->url($attachment->storage_path);
            } catch (\Throwable) {
                return null;
            }
        }
    }
}
