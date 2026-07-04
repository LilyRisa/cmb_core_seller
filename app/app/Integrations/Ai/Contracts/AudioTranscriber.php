<?php

namespace CMBcoreSeller\Integrations\Ai\Contracts;

use CMBcoreSeller\Integrations\Ai\DTO\AiContext;

/**
 * Năng lực PHÂN TÁCH: transcribe audio → text (STT). Chỉ connector OpenAI-compatible
 * (vd Groq whisper) implement; core kiểm `instanceof AudioTranscriber`. Tách khỏi
 * AiAssistantConnector để không ép mọi connector implement.
 */
interface AudioTranscriber
{
    /**
     * @throws \CMBcoreSeller\Integrations\Ai\Exceptions\TranscriptionFailed khi API lỗi (caller retry).
     */
    public function transcribeAudio(AiContext $ctx, string $bytes, string $mime, ?string $filename = null): string;
}
