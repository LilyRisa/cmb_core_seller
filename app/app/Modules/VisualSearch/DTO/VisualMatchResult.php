<?php

namespace CMBcoreSeller\Modules\VisualSearch\DTO;

/**
 * Kết quả tra cứu — tri-state (KHÔNG auto chọn khi điểm sát nhau):
 *   - matched   : 1 item rõ ràng ($item)
 *   - ambiguous : nhiều item sát điểm ($candidates) — UI/AI nên hỏi lại
 *   - not_found : không khớp / tắt / lỗi
 */
final class VisualMatchResult
{
    public const STATUS_MATCHED = 'matched';

    public const STATUS_AMBIGUOUS = 'ambiguous';

    public const STATUS_NOT_FOUND = 'not_found';

    /** @param  list<VisualItemCandidate>  $candidates */
    public function __construct(
        public string $status,
        public ?VisualItemCandidate $item = null,
        public array $candidates = [],
        public string $stage = 'recall',   // 'recall' | 'rerank'
    ) {}

    public static function matched(VisualItemCandidate $item, string $stage = 'recall'): self
    {
        return new self(self::STATUS_MATCHED, $item, [$item], $stage);
    }

    /** @param  list<VisualItemCandidate>  $candidates */
    public static function ambiguous(array $candidates, string $stage = 'recall'): self
    {
        return new self(self::STATUS_AMBIGUOUS, null, $candidates, $stage);
    }

    public static function notFound(string $stage = 'recall'): self
    {
        return new self(self::STATUS_NOT_FOUND, null, [], $stage);
    }

    public function isMatched(): bool
    {
        return $this->status === self::STATUS_MATCHED;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'stage' => $this->stage,
            'item' => $this->item?->toArray(),
            'candidates' => array_map(fn (VisualItemCandidate $c) => $c->toArray(), $this->candidates),
        ];
    }
}
