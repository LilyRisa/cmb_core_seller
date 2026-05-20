<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use CMBcoreSeller\Support\Enums\StandardOrderStatus;

/** Shopee raw order status -> canonical. Source of truth = config('integrations.shopee.status_map'). */
final class ShopeeStatusMap
{
    public static function toStandard(string $raw): StandardOrderStatus
    {
        $map = (array) config('integrations.shopee.status_map', []);
        $val = $map[$raw] ?? $map[strtoupper($raw)] ?? 'processing';

        return StandardOrderStatus::from((string) $val);
    }
}
