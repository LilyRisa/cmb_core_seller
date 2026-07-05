<?php

namespace CMBcoreSeller\Modules\VisualSearch\DTO;

/** Ảnh của 1 training item để gửi cho khách (bytes + mime). */
final class VisualItemImage
{
    public function __construct(
        public string $mime,
        public string $bytes,
    ) {}

    /** Đuôi file suy từ mime (mặc định jpg). */
    public function ext(): string
    {
        return match ($this->mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
    }
}
