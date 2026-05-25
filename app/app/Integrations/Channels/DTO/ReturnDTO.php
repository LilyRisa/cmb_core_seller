<?php

namespace CMBcoreSeller\Integrations\Channels\DTO;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Support\Enums\AfterSalesStatus;

/**
 * Normalized after-sales record (cancel / return / refund) from any channel.
 * Connectors map their raw return/cancel payload onto this DTO; the canonical
 * {@see AfterSalesStatus} is already resolved by the connector's return-status map.
 * Money is bigint VND đồng. See SPEC 0025.
 */
final readonly class ReturnDTO
{
    public const KIND_CANCEL = 'cancel';

    public const KIND_RETURN = 'return';

    public const KIND_REFUND = 'refund';

    /**
     * @param  list<array<string,mixed>>  $items
     * @param  array<string,mixed>  $raw
     */
    public function __construct(
        public string $externalReturnId,
        public string $source,
        public string $kind,
        public AfterSalesStatus $status,
        public string $rawStatus,
        public ?string $externalOrderId = null,
        public ?string $reason = null,
        public int $refundAmount = 0,
        public string $currency = 'VND',
        public array $items = [],
        public ?CarbonImmutable $requestedAt = null,
        public ?CarbonImmutable $decidedAt = null,
        public ?CarbonImmutable $sourceUpdatedAt = null,
        public array $raw = [],
    ) {}
}
