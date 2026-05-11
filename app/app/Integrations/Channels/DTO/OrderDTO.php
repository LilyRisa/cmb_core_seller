<?php

namespace CMBcoreSeller\Integrations\Channels\DTO;

use Carbon\CarbonImmutable;

/**
 * Normalized order as produced by any ChannelConnector. Core code (the
 * Orders module) only ever sees this — never a marketplace's raw JSON.
 * All money fields are integers in VND đồng (no decimals).
 */
final readonly class OrderDTO
{
    public function __construct(
        public string $externalOrderId,
        public string $source,                 // 'tiktok' | 'shopee' | 'lazada' | 'manual'
        public string $rawStatus,
        public CarbonImmutable $sourceUpdatedAt,
        public ?string $orderNumber = null,
        public ?string $paymentStatus = null,
        public ?CarbonImmutable $placedAt = null,
        public ?CarbonImmutable $paidAt = null,
        public ?CarbonImmutable $shippedAt = null,
        public ?CarbonImmutable $deliveredAt = null,
        public ?CarbonImmutable $completedAt = null,
        public ?CarbonImmutable $cancelledAt = null,
        public ?string $cancelReason = null,
        /** @var array{name?:string,phone?:string,email?:string} */
        public array $buyer = [],
        /** @var array{fullName?:string,phone?:string,line1?:string,ward?:string,district?:string,province?:string,country?:string,zip?:string,note?:string} */
        public array $shippingAddress = [],
        public string $currency = 'VND',
        public int $itemTotal = 0,
        public int $shippingFee = 0,
        public int $platformDiscount = 0,
        public int $sellerDiscount = 0,
        public int $tax = 0,
        public int $codAmount = 0,
        public int $grandTotal = 0,
        public bool $isCod = false,
        public ?string $fulfillmentType = null,   // marketplace fulfillment program, e.g. TikTok 'FULFILLMENT_BY_SELLER' / 'FULFILLMENT_BY_TIKTOK'
        /** @var list<OrderItemDTO> */
        public array $items = [],
        /** @var list<array{externalPackageId:?string,trackingNo:?string,carrier:?string,status:?string}> */
        public array $packages = [],
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}
}
