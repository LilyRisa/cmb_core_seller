<?php

namespace CMBcoreSeller\Modules\Support\Services;

use CMBcoreSeller\Modules\Support\Exceptions\AttachmentInvalid;
use Illuminate\Http\UploadedFile;

/**
 * Validate + lưu file đính kèm CSKH (upload trực tiếp). Mô phỏng phần outbound của
 * Messaging\Services\MediaRelayService — RIÊNG cho Support (luật module: KHÔNG import
 * Services của module khác, kể cả qua {@see}).
 *
 * `kind` (image|video|file) suy ra từ MIME thật (server-detected) qua whitelist
 * `config('support.attachments.allowed_mime')`; không cần FE khai báo. Vi phạm
 * MIME/size ⇒ {@see AttachmentInvalid} (controller → 422 ATTACHMENT_INVALID).
 */
class SupportMediaService
{
    public function __construct(private SupportMediaStorage $storage) {}

    /**
     * Suy ra loại (image|video|file) từ MIME — đồng thời là bước validate MIME.
     *
     * @throws AttachmentInvalid khi MIME không nằm trong whitelist nào
     */
    public function resolveKind(string $mime): string
    {
        $map = (array) config('support.attachments.allowed_mime', []);
        foreach ($map as $kind => $mimes) {
            if (in_array($mime, (array) $mimes, true)) {
                return (string) $kind;
            }
        }

        throw AttachmentInvalid::mime($mime);
    }

    /**
     * @throws AttachmentInvalid khi vượt giới hạn dung lượng của `kind`
     */
    public function assertSize(string $kind, int $sizeBytes): void
    {
        $limit = (int) config("support.attachments.limits.{$kind}", 25 * 1024 * 1024);
        if ($sizeBytes > $limit) {
            throw AttachmentInvalid::size($kind, $limit);
        }
    }

    /**
     * Validate 1 file (MIME → kind + size). Trả metadata cơ bản, CHƯA lưu disk.
     *
     * @return array{kind:string, mime:string, size_bytes:int}
     *
     * @throws AttachmentInvalid
     */
    public function validate(UploadedFile $file): array
    {
        $mime = $file->getMimeType() ?: $file->getClientMimeType();
        $size = (int) $file->getSize();
        $kind = $this->resolveKind((string) $mime);
        $this->assertSize($kind, $size);

        return ['kind' => $kind, 'mime' => (string) $mime, 'size_bytes' => $size];
    }

    /**
     * Lưu file vào disk (giả định đã {@see validate}).
     *
     * @return array{storage_path:string, checksum:string, filename:string}
     */
    public function store(int $tenantId, int $conversationId, UploadedFile $file): array
    {
        $ext = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
        $path = $this->storage->buildPath($tenantId, $conversationId, $ext);

        $stream = fopen($file->getRealPath(), 'rb');
        $this->storage->disk()->writeStream($path, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        return [
            'storage_path' => $path,
            'checksum' => hash_file('sha256', $file->getRealPath()) ?: '',
            'filename' => $file->getClientOriginalName(),
        ];
    }
}
