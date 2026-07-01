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

        // Fallback phòng thủ (status không có trong config map) — THỨ TỰ khớp quan trọng: nhánh reverse/lost
        // phải xét TRƯỚC 'shipped'/'delivered' vì mã reverse chứa cả 'shipped' (shipped_back) / 'delivered'
        // (failed_delivered). Đối chiếu sơ đồ Order Status Flow chính chủ Lazada.
        return match (true) {
            str_contains($key, 'cancel') => StandardOrderStatus::Cancelled,
            str_contains($key, 'scrapped') => StandardOrderStatus::ReturnedRefunded,            // package_scrapped — chốt trả/hoàn
            str_contains($key, 'shipped_back') || str_contains($key, 'return') || str_contains($key, 'refund') || str_contains($key, 'rtm') => StandardOrderStatus::Returning,
            str_contains($key, 'lost') || str_contains($key, 'damaged') => StandardOrderStatus::DeliveryFailed,   // lost_by_3pl / damaged_by_3pl
            str_contains($key, 'failed') => StandardOrderStatus::DeliveryFailed,                // failed_delivery / failed_delivered
            str_contains($key, 'delivered') => StandardOrderStatus::Delivered,
            // "…to_ship" (ready_to_ship / transit_to_ship / toship) = sau RTS, chờ 3PL lấy ⇒ "Chờ bàn giao".
            // PHẢI xét TRƯỚC 'shipped'/'transit' vì `transit_to_ship` chứa cả 'transit' — không thì map nhầm
            // sang "Đang giao" ngay khi vừa quét/RTS (chỉ `shipped`/`in_transit` thật mới là "Đang giao").
            str_contains($key, 'to_ship') || str_contains($key, 'toship') => StandardOrderStatus::ReadyToShip,
            str_contains($key, 'shipped') || str_contains($key, 'transit') => StandardOrderStatus::Shipped,
            str_contains($key, 'repack') || str_contains($key, 'packed') => StandardOrderStatus::Processing,   // packed/repacked → Đang xử lý (KHÔNG ready_to_ship)
            str_contains($key, 'confirmed') => StandardOrderStatus::Completed,
            str_contains($key, 'unpaid') => StandardOrderStatus::Unpaid,
            default => StandardOrderStatus::Pending,                                            // pending/paid/topack/...
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
            'packed' => 2, 'repacked' => 2,
            'ready_to_ship' => 3, 'ready_to_ship_pending' => 3, 'toship' => 3, 'transit_to_ship' => 3,
            'shipped' => 4,
            'delivered' => 5,
            'confirmed' => 6,
            'failed' => 4, 'failed_delivered' => 4, 'failed_delivery' => 4,
            'lost' => 4, 'lost_by_3pl' => 4, 'damaged' => 4, 'damaged_by_3pl' => 4,
            'shipped_back' => 7, 'shipped_back_failed' => 7,
            'returned' => 8, 'return_to_seller' => 8, 'rtm_init' => 8, 'shipped_back_success' => 8, 'package_scrapped' => 8,
            'canceled' => 9, 'cancelled' => 9,
        ];
        $norm = fn ($s) => strtolower(str_replace([' ', '-'], '_', $s));
        $reverse = ['canceled', 'returned', 'shipped_back', 'shipped_back_failed', 'shipped_back_success', 'package_scrapped'];
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
