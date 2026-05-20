<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\ShopInfoDTO;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;

/** Shopee v2 JSON -> standard DTOs. The ONLY place Shopee field names live (besides StatusMap/Verifier). */
final class ShopeeMappers
{
    /** @param array<string,mixed> $res token/get|refresh response @param string $shopId from context */
    public static function token(array $res, string $shopId): TokenDTO
    {
        $expireIn = (int) ($res['expire_in'] ?? 14400);
        $raw = $res;
        $raw['shop_id'] = $res['shop_id'] ?? $shopId;

        return new TokenDTO(
            accessToken: (string) ($res['access_token'] ?? ''),
            refreshToken: ($res['refresh_token'] ?? null) ? (string) $res['refresh_token'] : null,
            expiresAt: CarbonImmutable::now()->addSeconds($expireIn),
            refreshExpiresAt: CarbonImmutable::now()->addDays(30), // Shopee refresh ~30d
            scope: null,
            raw: $raw,
        );
    }

    /** @param array<string,mixed> $res get_shop_info `response` */
    public static function shopInfo(array $res, string $shopId): ShopInfoDTO
    {
        return new ShopInfoDTO(
            externalShopId: $shopId,
            name: (string) ($res['shop_name'] ?? ('Shopee '.$shopId)),
            region: (string) ($res['region'] ?? 'VN'),
            sellerType: isset($res['shop_type']) ? (string) $res['shop_type'] : null,
            raw: $res,
        );
    }
}
