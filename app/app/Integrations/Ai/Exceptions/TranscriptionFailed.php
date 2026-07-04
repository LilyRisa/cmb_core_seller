<?php

namespace CMBcoreSeller\Integrations\Ai\Exceptions;

use RuntimeException;

/** Transcription (STT) thất bại — job retry, hết lần thì bỏ qua. */
class TranscriptionFailed extends RuntimeException
{
    public static function http(string $providerCode, int $status): self
    {
        return new self("AI provider [{$providerCode}] transcription HTTP {$status}.");
    }
}
