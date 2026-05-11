<?php

namespace CMBcoreSeller\Integrations\Channels\DTO;

final readonly class ShopInfoDTO
{
    public function __construct(
        public string $externalShopId,
        public string $name,
        public string $region = 'VN',
        public ?string $sellerType = null,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}
}
