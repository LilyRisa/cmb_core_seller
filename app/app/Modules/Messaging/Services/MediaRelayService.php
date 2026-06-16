<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Modules\Messaging\Exceptions\AttachmentInvalid;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

/**
 * IO cho media tin nhắn — 2 chiều:
 *
 *  - Outbound: NV upload file ⇒ {@see storeUpload} validate (MIME/size) + lưu disk,
 *    trả metadata để controller ghi `message_attachments` (status=downloaded).
 *  - Inbound : sàn chỉ cho URL TTL ngắn ⇒ {@see relayInbound} tải về disk
 *    (job `DownloadInboundMedia`), set storage_path + checksum + status.
 *
 * Validate dùng config `messaging.allowed_mime` + `messaging.limits`. Sai ⇒
 * {@see AttachmentInvalid} (controller → 422 ATTACHMENT_INVALID).
 */
class MediaRelayService
{
    public function __construct(private MediaStorage $storage) {}

    /**
     * Validate + lưu file upload outbound vào disk.
     *
     * @return array{storage_path:string, mime:string, size_bytes:int, checksum:string, filename:string}
     */
    public function storeUpload(int $tenantId, int $conversationId, UploadedFile $file, string $kind): array
    {
        $mime = $file->getMimeType() ?: $file->getClientMimeType();
        $size = (int) $file->getSize();

        $this->assertValid($kind, (string) $mime, $size);

        $ext = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
        $path = $this->storage->buildPath($tenantId, $conversationId, $ext);

        $stream = fopen($file->getRealPath(), 'rb');
        $this->storage->disk()->writeStream($path, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        return [
            'storage_path' => $path,
            'mime' => (string) $mime,
            'size_bytes' => $size,
            'checksum' => hash_file('sha256', $file->getRealPath()) ?: '',
            'filename' => $file->getClientOriginalName(),
        ];
    }

    /**
     * Tải media inbound từ `external_url` về disk. Idempotent: đã downloaded ⇒ skip.
     * Lỗi (URL hết hạn / quá lớn) ⇒ status=failed + ghi error, KHÔNG ném (job
     * không retry vô hạn vào URL chết — caller quyết retry qua tries).
     */
    public function relayInbound(MessageAttachment $attachment): void
    {
        if ($attachment->status === MessageAttachment::STATUS_DOWNLOADED && $attachment->storage_path) {
            return;
        }
        if (! $attachment->external_url) {
            $attachment->update(['status' => MessageAttachment::STATUS_FAILED, 'error' => 'no_external_url']);

            return;
        }

        try {
            $response = $this->http()->get($attachment->external_url);
            if (! $response->successful()) {
                $attachment->update([
                    'status' => MessageAttachment::STATUS_FAILED,
                    'error' => 'http_'.$response->status(),
                ]);

                return;
            }

            $body = $response->body();
            $size = strlen($body);
            $mime = $attachment->mime ?: ($response->header('Content-Type') ?: 'application/octet-stream');

            // Size guard (dùng limit theo kind; vượt ⇒ failed, không lưu)
            $limit = $this->limitFor($attachment->kind);
            if ($size > $limit) {
                $attachment->update(['status' => MessageAttachment::STATUS_FAILED, 'error' => 'too_large']);

                return;
            }

            $ext = pathinfo((string) parse_url($attachment->external_url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'bin';
            // null-safe: message có thể null (TenantScope/đã xoá) — KHÔNG được crash làm hỏng tải ảnh.
            $conversationId = (int) ($attachment->message?->conversation_id ?? 0);
            $path = $this->storage->buildPath((int) $attachment->tenant_id, $conversationId, $ext);

            $this->storage->disk()->put($path, $body);

            $attachment->update([
                'storage_path' => $path,
                'mime' => $mime,
                'size_bytes' => $size,
                'checksum' => hash('sha256', $body),
                'status' => MessageAttachment::STATUS_DOWNLOADED,
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            $attachment->update([
                'status' => MessageAttachment::STATUS_FAILED,
                'error' => substr($e->getMessage(), 0, 250),
            ]);
        }
    }

    /**
     * @throws AttachmentInvalid
     */
    public function assertValid(string $kind, string $mime, int $sizeBytes): void
    {
        $allowed = (array) config("messaging.allowed_mime.{$kind}", []);
        if ($allowed !== [] && ! in_array($mime, $allowed, true)) {
            throw AttachmentInvalid::mime($kind, $mime);
        }

        $limit = $this->limitFor($kind);
        if ($sizeBytes > $limit) {
            throw AttachmentInvalid::size($kind, $limit);
        }
    }

    private function limitFor(string $kind): int
    {
        return (int) config("messaging.limits.{$kind}", 25 * 1024 * 1024);
    }

    private function http(): PendingRequest
    {
        return Http::timeout(30)->retry(2, 500);
    }
}
