<?php

namespace CMBcoreSeller\Integrations\Channels\DTO;

final readonly class OrderItemDTO
{
    public function __construct(
        public string $externalItemId,
        public ?string $externalProductId,
        public ?string $externalSkuId,
        public ?string $sellerSku,
        public string $name,
        public ?string $variation,
        public int $quantity,
        /** Money in VND minor unit (= đồng, no decimals). */
        public int $unitPrice,
        public int $discount = 0,
        public ?string $image = null,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}
}
