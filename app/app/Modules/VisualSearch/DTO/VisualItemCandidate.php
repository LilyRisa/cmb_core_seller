<?php

namespace CMBcoreSeller\Modules\VisualSearch\DTO;

/** Một item ứng viên + điểm tin cậy (cosine recall hoặc do re-rank quyết). */
final class VisualItemCandidate
{
    /** @param  array<string,mixed>  $attributes */
    public function __construct(
        public int $itemId,
        public string $name,
        public ?string $description,
        public array $attributes,
        public float $confidence,
    ) {}

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'item_id' => $this->itemId,
            'name' => $this->name,
            'description' => $this->description,
            'attributes' => $this->attributes,
            'confidence' => round($this->confidence, 4),
        ];
    }
}
