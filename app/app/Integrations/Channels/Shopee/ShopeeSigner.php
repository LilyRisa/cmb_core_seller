<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

/**
 * Shopee Open Platform API v2 request signing.
 *
 * sign = HMAC-SHA256(partner_key, base_string) hex lowercase, where:
 *   - Public API (token get/refresh, auth_partner): base = partner_id . api_path . timestamp
 *   - Shop API:                                       base = partner_id . api_path . timestamp . access_token . shop_id
 * Pure concatenation, no separators. Pure & deterministic. See docs/04-channels/shopee.md §2.
 */
final class ShopeeSigner
{
    public static function signPublic(string $partnerKey, int $partnerId, string $apiPath, int $timestamp): string
    {
        return hash_hmac('sha256', $partnerId.$apiPath.$timestamp, $partnerKey);
    }

    public static function signShop(string $partnerKey, int $partnerId, string $apiPath, int $timestamp, string $accessToken, string $shopId): string
    {
        return hash_hmac('sha256', $partnerId.$apiPath.$timestamp.$accessToken.$shopId, $partnerKey);
    }
}
