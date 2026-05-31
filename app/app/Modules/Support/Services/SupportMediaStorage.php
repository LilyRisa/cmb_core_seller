<?php

namespace CMBcoreSeller\Modules\Support\Services;

use CMBcoreSeller\Modules\Support\Models\SupportMessageAttachment;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Quy ước lưu trữ đính kèm CSKH — đường dẫn, disk, signed URL. Mô phỏng
 * Messaging\Services\MediaStorage nhưng RIÊNG cho module Support
 * (luật module: KHÔNG import ruột Messaging, kể cả qua {@see}).
 *
 * Key theo prefix tenant (cách ly multi-tenant ở tầng storage):
 *   tenants/{tenantId}/support/{yyyy}/{mm}/{conversationId}/{uuid}.{ext}
 */
class SupportMediaStorage
{
    public function diskName(): string
    {
        return (string) config('support.attachments.media_disk', 'local');
    }

    public function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    public function buildPath(int $tenantId, int $conversationId, string $extension): string
    {
        $ext = ltrim(preg_replace('/[^a-zA-Z0-9]/', '', $extension) ?: 'bin', '.');
        $yyyy = date('Y');
        $mm = date('m');
        $uuid = (string) Str::uuid();

        return "tenants/{$tenantId}/support/{$yyyy}/{$mm}/{$conversationId}/{$uuid}.{$ext}";
    }

    /**
     * Signed URL TTL ngắn cho FE. Disk local không hỗ trợ temporaryUrl ⇒ fallback url().
     * Trả null nếu chưa lưu được file.
     */
    public function temporaryUrl(SupportMessageAttachment $attachment): ?string
    {
        if ($attachment->status !== SupportMessageAttachment::STATUS_STORED || ! $attachment->storage_path) {
            return null;
        }

        $disk = $this->disk();
        $ttl = (int) config('support.attachments.signed_url_ttl', 300);

        try {
            return $disk->temporaryUrl($attachment->storage_path, now()->addSeconds($ttl));
        } catch (\Throwable) {
            try {
                return $disk->url($attachment->storage_path);
            } catch (\Throwable) {
                return null;
            }
        }
    }
}
