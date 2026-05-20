<?php

namespace CMBcoreSeller\Integrations\Ai\DTO;

/**
 * Vector embedding của 1 đoạn text. `dimension` phụ thuộc model (OpenAI
 * text-embedding-3-small=1536, Claude=khác). DB lưu pgvector cùng dimension.
 *
 * Provider không hỗ trợ ⇒ {@see \CMBcoreSeller\Integrations\Ai\Exceptions\UnsupportedOperation}.
 */
final readonly class EmbeddingDTO
{
    public function __construct(
        /** @var list<float> */
        public array $vector,
        public int $dimension,
        public string $model,
        public int $tokenCount = 0,
    ) {}
}
