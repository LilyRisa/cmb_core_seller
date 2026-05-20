<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

/**
 * Kết quả `PiiRedactor::redact()`. Counts dùng để ghi `ai_assistant_runs.meta.redacted_count`
 * (08-security-and-privacy §6b §3).
 */
final readonly class RedactResult
{
    public function __construct(
        public string $redacted,
        /** @var array<string, string> placeholder → original */
        public array $mapping,
        /** @var array<string, int> kind → count */
        public array $counts,
    ) {}

    public function hasAnyPii(): bool
    {
        return array_sum($this->counts) > 0;
    }

    /** Restore placeholder → original (nếu cần — vd hiện draft cho NV duyệt). */
    public function restore(string $text): string
    {
        return str_replace(array_keys($this->mapping), array_values($this->mapping), $text);
    }
}
