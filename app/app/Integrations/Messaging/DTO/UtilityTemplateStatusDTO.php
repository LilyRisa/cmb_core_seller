<?php

namespace CMBcoreSeller\Integrations\Messaging\DTO;

/**
 * Trạng thái duyệt của 1 utility template phía provider — chuẩn hoá về 3 giá trị
 * (Meta dùng nhiều tên: APPROVED/PENDING/REJECTED/PAUSED/DISABLED… connector map về đây).
 */
final readonly class UtilityTemplateStatusDTO
{
    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public function __construct(
        /** Một trong PENDING|APPROVED|REJECTED. */
        public string $status,
        public ?string $reason = null,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}
}
