<?php

namespace CMBcoreSeller\Integrations\Channels\DTO;

/**
 * Everything a connector needs to make an authenticated call for one shop.
 * Built from a channel_accounts row. Region is fixed to 'VN' for now but
 * kept explicit so multi-region is a future extension, not a rewrite.
 */
final readonly class AuthContext
{
    public function __construct(
        public int $channelAccountId,
        public string $provider,
        public string $externalShopId,
        public string $accessToken,
        public string $region = 'VN',
        /** @var array<string, mixed> Extra per-provider fields, e.g. TikTok shop_cipher. */
        public array $extra = [],
    ) {}
}
