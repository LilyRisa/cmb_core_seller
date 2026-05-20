<?php

namespace CMBcoreSeller\Integrations\Messaging\DTO;

/**
 * Auth context cho 1 cuộc gọi messaging — built từ `channel_accounts` row
 * + `messaging_account_meta` 1-1. Provider Shopee/TikTok/Lazada reuse access
 * token chung với Channels; Facebook page = page access token độc lập.
 *
 * Region cố định 'VN' (vision-and-scope §4) — `extra` cho per-provider field
 * (vd Facebook page_id, TikTok shop_cipher).
 */
final readonly class MessagingAuthContext
{
    public function __construct(
        public int $channelAccountId,
        public string $provider,
        public string $externalShopId,
        public string $accessToken,
        public string $region = 'VN',
        /** @var array<string, mixed> */
        public array $extra = [],
    ) {}
}
