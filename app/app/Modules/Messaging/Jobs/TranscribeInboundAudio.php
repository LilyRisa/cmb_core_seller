<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\Contracts\AudioTranscriber;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Messaging\Services\MediaStorage;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Transcribe 1 ghi âm inbound → text (Groq whisper), lưu `transcript`.
 * tries=3; hết lần vẫn lỗi ⇒ failed() log & bỏ qua (không vỡ luồng).
 */
class TranscribeInboundAudio implements ShouldQueue
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

    public function handle(): void
    {
        // Job chạy không có tenant context ⇒ phải bỏ TenantScope khi tìm attachment
        // (giống DownloadInboundMedia) nếu không sẽ null-noop âm thầm.
        $att = MessageAttachment::withoutGlobalScope(TenantScope::class)->find($this->attachmentId);
        if (! $att || $att->kind !== MessageAttachment::KIND_AUDIO
            || $att->status !== MessageAttachment::STATUS_DOWNLOADED
            || $att->transcript !== null || ! $att->storage_path) {
            return;
        }

        $registry = app(AiAssistantRegistry::class);
        $credits = app(AiCreditMeter::class);
        $media = app(MediaStorage::class);

        $code = trim((string) system_setting('messaging.transcription.provider_code', ''));
        if ($code === '' || ! in_array($code, $registry->activeProviders('transcription'), true)) {
            return;
        }

        $connector = $registry->for($code);
        if (! $connector instanceof AudioTranscriber) {
            return;
        }

        $tenantId = (int) $att->tenant_id;
        if (! $credits->canUse($tenantId, 1)) {
            return;
        }

        $bytes = (string) $media->disk()->get($att->storage_path);
        if ($bytes === '') {
            return;
        }

        $text = $connector->transcribeAudio(
            new AiContext(tenantId: $tenantId, providerCode: $code, model: null, meta: ['mode' => 'transcription']),
            $bytes,
            (string) ($att->mime ?: 'audio/mpeg'),
            (string) ($att->filename ?: 'audio.mp3'),
        );

        // Chỉ ghi nhận lượt dùng SAU khi provider trả về thành công.
        $credits->record($tenantId, 1);

        if ($text !== '') {
            $att->transcript = $text;
            $att->save();
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('messaging.transcription_failed', ['attachment_id' => $this->attachmentId, 'error' => $e->getMessage()]);
    }
}
