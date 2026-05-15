<?php

namespace CMBcoreSeller\Modules\Accounting\DTO;

use Illuminate\Support\Carbon;

/**
 * DTO cho 1 bút toán (giá trị, không persist).
 */
final class JournalEntryDTO
{
    /**
     * @param  list<JournalLineDTO>  $lines
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly Carbon $postedAt,
        public readonly string $sourceModule,
        public readonly string $sourceType,
        public readonly ?int $sourceId,
        public readonly string $idempotencyKey,
        public readonly array $lines,
        public readonly ?string $narration = null,
        public readonly ?int $createdBy = null,
        public readonly bool $isAdjustment = false,
        public readonly ?int $isReversalOfId = null,
        public readonly ?int $adjustedPeriodId = null,
    ) {}
}
