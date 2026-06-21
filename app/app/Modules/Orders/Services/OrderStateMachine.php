<?php

namespace CMBcoreSeller\Modules\Orders\Services;

use CMBcoreSeller\Support\Enums\StandardOrderStatus as S;

/**
 * Canonical order status transition graph. See docs/03-domain/order-status-state-machine.md §2-§3.
 *
 * - {@see canTransition()} — whether `$to` is a legal next step from `$from`
 *   ("happy path" + branches). Used to validate user-driven transitions.
 * - {@see isBackwardJump()} / {@see isAbnormalBackwardJump()} — channel data may
 *   report a status earlier in the chain (corrections). That is still recorded,
 *   but an *abnormal* regression (e.g. completed -> processing) flags `has_issue`.
 *
 * Channel-driven updates are NOT gated by canTransition() — the marketplace is
 * the source of truth (rule 1); this class governs *user* transitions and
 * detects anomalies.
 */
final class OrderStateMachine
{
    /** @var array<string, list<string>> from-status => allowed to-statuses (user transitions) */
    private const TRANSITIONS = [
        'unpaid' => ['pending', 'cancelled'],
        'pending' => ['processing', 'ready_to_ship', 'cancelled'],
        'processing' => ['ready_to_ship', 'cancelled'],
        'ready_to_ship' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'delivery_failed', 'returning'],
        'delivery_failed' => ['shipped', 'returning', 'cancelled'],
        'delivered' => ['completed', 'returning'],
        'completed' => ['returning'],
        'returning' => ['returned_refunded'],
        'returned_refunded' => [],
        'cancelled' => [],
    ];

    /** Linear "progress" rank used to detect backward jumps. */
    private const RANK = [
        'unpaid' => 0, 'pending' => 1, 'processing' => 2, 'ready_to_ship' => 3,
        'shipped' => 4, 'delivery_failed' => 4, 'delivered' => 5, 'completed' => 6,
        'returning' => 7, 'returned_refunded' => 8, 'cancelled' => 9,
    ];

    public function canTransition(S $from, S $to): bool
    {
        if ($from === $to) {
            return true; // idempotent no-op
        }

        return in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true);
    }

    /** @return list<S> */
    public function allowedNext(S $from): array
    {
        return array_map(fn (string $v) => S::from($v), self::TRANSITIONS[$from->value] ?? []);
    }

    public function isBackwardJump(S $from, S $to): bool
    {
        if (in_array($to, [S::Cancelled, S::Returning, S::ReturnedRefunded, S::DeliveryFailed], true)) {
            return false; // branches, not regressions
        }

        return self::RANK[$to->value] < self::RANK[$from->value];
    }

    /**
     * Chống kéo lùi khi đồng bộ ngược từ sàn (webhook/poll đến TRỄ hoặc ĐẢO thứ tự). Trả về trạng thái NÊN
     * giữ cho đơn: tiến/bằng rank ⇒ áp dụng `$incoming`; lùi qua mốc lớn ⇒ GIỮ `$current`. Vd Shopee gửi
     * CANCELLED rồi 3s sau gửi IN_CANCEL (→processing) ⇒ guard giữ `cancelled`, không "hồi sinh" đơn đã huỷ.
     * Vẫn cho lùi NHẸ hợp lệ (ready_to_ship→processing). Nhánh cancel/return/refund rank cao ⇒ luôn áp dụng
     * (không bị chặn). docs/03-domain/order-status-state-machine.md §3.
     */
    public function holdAgainstRegression(S $current, S $incoming): S
    {
        if (self::RANK[$incoming->value] >= self::RANK[$current->value]) {
            return $incoming; // tiến hoặc bằng — áp dụng bình thường
        }
        if ($current->isTerminal()) {
            return $current; // (1) huỷ/hoàn-tất/đã-trả-hoàn là CHỐT — update trễ không kéo về trạng thái thấp
        }
        if (self::RANK[$current->value] >= self::RANK[S::Shipped->value] && self::RANK[$incoming->value] <= self::RANK[S::ReadyToShip->value]) {
            return $current; // (3) đã giao/vận chuyển/trả-hoàn không quay về trước-giao-hàng
        }
        if (in_array($current, [S::Processing, S::ReadyToShip], true) && in_array($incoming, [S::Unpaid, S::Pending], true)) {
            return $current; // đã chuẩn bị hàng không về Chờ xử lý (sticky-forward)
        }

        return $incoming; // lùi nhẹ hợp lệ (vd ready_to_ship→processing)
    }

    /** A regression of 2+ ranks, or any regression out of a terminal status. */
    public function isAbnormalBackwardJump(S $from, S $to): bool
    {
        if (! $this->isBackwardJump($from, $to)) {
            return false;
        }
        if ($from->isTerminal()) {
            return true;
        }

        return (self::RANK[$from->value] - self::RANK[$to->value]) >= 2;
    }
}
