<?php

namespace CMBcoreSeller\Modules\Inventory\Support;

/**
 * Canonical form of a SKU code for matching `channel_listing.seller_sku` against
 * `skus.sku_code`: trim, uppercase, strip internal whitespace. See SPEC 0003 §4.
 */
final class SkuCodeNormalizer
{
    public static function normalize(?string $code): string
    {
        if ($code === null) {
            return '';
        }

        return preg_replace('/\s+/u', '', mb_strtoupper(trim($code))) ?? '';
    }

    public static function matches(?string $a, ?string $b): bool
    {
        $a = self::normalize($a);

        return $a !== '' && $a === self::normalize($b);
    }
}
