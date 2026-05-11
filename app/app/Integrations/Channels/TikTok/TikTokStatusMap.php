<?php

namespace CMBcoreSeller\Integrations\Channels\TikTok;

use CMBcoreSeller\Support\Enums\StandardOrderStatus;

/**
 * The single place TikTok's raw order status strings are translated to the
 * canonical {@see StandardOrderStatus}. Backed by config('integrations.tiktok.status_map')
 * so the table can be tuned without code changes. See docs/03-domain/order-status-state-machine.md §4.
 */
final class TikTokStatusMap
{
    /** @return array<string,string> */
    public static function table(): array
    {
        return (array) config('integrations.tiktok.status_map', []);
    }

    /** @param array<string,mixed> $rawOrder used to disambiguate (e.g. AWAITING_SHIPMENT with packages -> processing) */
    public static function toStandard(string $rawStatus, array $rawOrder = []): StandardOrderStatus
    {
        $key = strtoupper(trim($rawStatus));
        $mapped = self::table()[$key] ?? null;

        // AWAITING_SHIPMENT: pending before any package, processing once a package exists.
        if ($key === 'AWAITING_SHIPMENT' && ! empty($rawOrder['packages'])) {
            return StandardOrderStatus::Processing;
        }

        if ($mapped !== null) {
            return StandardOrderStatus::tryFrom($mapped) ?? StandardOrderStatus::Pending;
        }

        // Conservative fallbacks for statuses not yet in the map.
        return match (true) {
            str_contains($key, 'CANCEL') => StandardOrderStatus::Cancelled,
            str_contains($key, 'RETURN') || str_contains($key, 'REFUND') => StandardOrderStatus::Returning,
            str_contains($key, 'DELIVER') => StandardOrderStatus::Delivered,
            str_contains($key, 'TRANSIT') || str_contains($key, 'SHIP') => StandardOrderStatus::Shipped,
            str_contains($key, 'UNPAID') => StandardOrderStatus::Unpaid,
            str_contains($key, 'COMPLETE') => StandardOrderStatus::Completed,
            default => StandardOrderStatus::Pending,
        };
    }
}
