<?php

namespace CMBcoreSeller\Modules\VisualSearch\DTO;

/** Ảnh đầu vào để tra cứu (bytes thô + mime). Caller tự resolve bytes. */
final readonly class VisualImageInput
{
    public function __construct(
        public string $bytes,
        public string $mime,
    ) {}

    public static function fromBinary(string $bytes, string $mime): self
    {
        return new self($bytes, $mime);
    }

    /** Data-URI base64 để gửi cho vision LLM. */
    public function toDataUrl(): string
    {
        return 'data:'.($this->mime ?: 'image/jpeg').';base64,'.base64_encode($this->bytes);
    }
}
