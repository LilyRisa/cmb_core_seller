<?php

namespace CMBcoreSeller\Integrations\Channels\Lazada;

use CMBcoreSeller\Support\Enums\StandardOrderStatus;

/**
 * The single place Lazada's order status strings are translated to the canonical
 * {@see StandardOrderStatus}. Backed by config('integrations.lazada.status_map').
 *
 * Lazada is item-level: an order's `statuses` is an array (one per order-item, with
 * dedup). We collapse that list to the "most advanced reverse-flow / least advanced
 * forward-flow" status — i.e. if any item is `returned`/`canceled` we don't override a
 * shipped/delivered majority; the connector passes the chosen string here. See
 * docs/03-domain/order-status-state-machine.md §4, docs/04-channels/lazada.md.
 */
final class LazadaStatusMap
{
    /** @return array<string,string> */
    public static function table(): array
    {
        return (array) config('integrations.lazada.status_map', []);
    }

    public static function toStandard(string $rawStatus): StandardOrderStatus
    {
        $key = strtolower(trim(str_replace([' ', '-'], '_', $rawStatus)));
        $mapped = self::table()[$key] ?? null;
        if ($mapped !== null) {
            return StandardOrderStatus::tryFrom($mapped) ?? StandardOrderStatus::Pending;
        }

        return match (true) {
            str_contains($key, 'cancel') => StandardOrderStatus::Cancelled,
            str_contains($key, 'return') || str_contains($key, 'refund') => StandardOrderStatus::Returning,
            str_contains($key, 'failed') && str_contains($key, 'delivery') => StandardOrderStatus::DeliveryFailed,
            str_contains($key, 'delivered') => StandardOrderStatus::Delivered,
            str_contains($key, 'shipped') || str_contains($key, 'transit') => StandardOrderStatus::Shipped,
            str_contains($key, 'ready_to_ship') || str_contains($key, 'packed') => StandardOrderStatus::ReadyToShip,
            str_contains($key, 'unpaid') || str_contains($key, 'pending') => StandardOrderStatus::Pending,
            default => StandardOrderStatus::Pending,
        };
    }

    /**
     * Pick the order-level status from Lazada's per-item `statuses` list. Reverse-flow
     * states (canceled/returned/refund) only "win" if ALL items are in that state;
     * otherwise pick the least-advanced forward status (so an order with one item still
     * `pending` is `pending`).
     *
     * @param  list<string>  $statuses
     */
    public static function collapse(array $statuses): string
    {
        $statuses = array_values(array_filter(array_map(fn ($s) => (string) $s, $statuses), fn ($s) => $s !== ''));
        if ($statuses === []) {
            return 'pending';
        }
        // Rank để chọn "least advanced forward" khi 1 đơn có items ở nhiều trạng thái khác nhau. KHỚP với
        // status_map ở config: paid/pending/topack = "Chờ xử lý" (1) → packed = "Đang xử lý" (2) →
        // ready_to_ship/toship = "Chờ bàn giao" (3) → shipped (4) → delivered (5) → confirmed (6).
        // Reverse-flow (canceled/returned/shipped_back) rank cao để chỉ "thắng" khi MỌI item đều reverse.
        $rank = [
            'unpaid' => 0,
            'paid' => 1, 'pending' => 1, 'topack' => 1,
            'packed' => 2,
            'ready_to_ship' => 3, 'ready_to_ship_pending' => 3, 'toship' => 3,
            'shipped' => 4,
            'delivered' => 5,
            'confirmed' => 6,
            'failed' => 4, 'failed_delivered' => 4, 'lost' => 4, 'damaged' => 4,
            'shipped_back' => 7, 'shipped_back_failed' => 7,
            'returned' => 8, 'return_to_seller' => 8, 'rtm_init' => 8,
            'canceled' => 9, 'cancelled' => 9,
        ];
        $norm = fn ($s) => strtolower(str_replace([' ', '-'], '_', $s));
        $reverse = ['canceled', 'returned', 'shipped_back', 'shipped_back_failed'];
        $allReverse = array_diff(array_map($norm, $statuses), $reverse) === [];
        if ($allReverse) {
            // most "final" reverse status
            usort($statuses, fn ($a, $b) => ($rank[$norm($b)] ?? 0) <=> ($rank[$norm($a)] ?? 0));

            return $statuses[0];
        }
        // forward statuses only — least advanced wins
        $forward = array_values(array_filter($statuses, fn ($s) => ! in_array($norm($s), $reverse, true)));
        usort($forward, fn ($a, $b) => ($rank[$norm($a)] ?? 99) <=> ($rank[$norm($b)] ?? 99));

        return $forward[0] ?? $statuses[0];
    }
}
