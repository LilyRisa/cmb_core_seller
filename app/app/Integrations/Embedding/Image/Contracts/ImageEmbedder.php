<?php

namespace CMBcoreSeller\Integrations\Embedding\Image\Contracts;

use CMBcoreSeller\Integrations\Embedding\Image\DTO\ImageVectorDTO;

/**
 * Embedding ẢNH vendor-agnostic. CLIP/SigLIP hôm nay (self-host sidecar); thêm
 * Cohere/Voyage sau = 1 connector mới. `modelKey()` định danh collection + cột
 * `model` ⇒ chạy song song nhiều model không xung đột.
 */
interface ImageEmbedder
{
    public function enabled(): bool;

    /** @throws \RuntimeException khi sidecar lỗi (caller bắt & đánh status=failed). */
    public function embedImage(string $bytes, string $mime): ImageVectorDTO;

    public function modelKey(): string;

    public function dimension(): int;
}
