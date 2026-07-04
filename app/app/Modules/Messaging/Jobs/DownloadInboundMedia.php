<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Messaging\Services\MediaRelayService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Relay 1 inbound attachment (URL TTL ngắn từ sàn) vào object storage.
 *
 * Queue: `messaging-media` (tries 3, backoff 30/120/600). `MediaRelayService`
 * tự nuốt lỗi → status=failed (URL chết không retry vô hạn); job chỉ throw khi
 * lỗi hạ tầng để retry. SPEC-0024 §6.4.
 */
class DownloadInboundMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $attachmentId)
    {
        $this->onQueue('messaging-media');
    }

    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(MediaRelayService $relay): void
    {
        // Eager-load message PHẢI bỏ TenantScope: job chạy không có tenant context ⇒
        // `->with('message')` thường sẽ áp TenantScope của Message ⇒ message=null ⇒
        // relayInbound đọc `$attachment->message->conversation_id` trên null (ảnh không tải được).
        $attachment = MessageAttachment::withoutGlobalScope(TenantScope::class)
            ->with(['message' => fn ($q) => $q->withoutGlobalScope(TenantScope::class)])
            ->find($this->attachmentId);

        if (! $attachment) {
            return;
        }

        $relay->relayInbound($attachment);

        // Voice khách → transcribe (STT) nếu đã tải xong.
        $fresh = $attachment->fresh();
        if ($fresh && $fresh->isAudioLike() && $fresh->status === MessageAttachment::STATUS_DOWNLOADED) {
            TranscribeInboundAudio::dispatch($fresh->id);
        }
    }
}
