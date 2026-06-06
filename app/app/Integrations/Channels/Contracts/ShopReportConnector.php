<?php

namespace CMBcoreSeller\Integrations\Channels\Contracts;

use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\PenaltyPointDTO;
use CMBcoreSeller\Integrations\Channels\DTO\PunishmentDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ShopHealthDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;

/**
 * Năng lực "Báo cáo sàn" (read-only) — segregated capability interface (mirror
 * {@see ListsPostsConnector}). Chỉ connector hỗ trợ mới implement; core kiểm
 * `instanceof ShopReportConnector` + `supports('report.*')` TRƯỚC khi gọi.
 *
 * Mỗi sàn lộ dữ liệu khác nhau: Lazada/Shopee có "sức khỏe shop", TikTok chỉ có
 * "hiệu suất" (analytics). Điểm phạt/trừng phạt hiện chỉ Shopee có API; sàn khác
 * ném {@see UnsupportedOperation}. Xem docs/superpowers/research/2026-06-06-bao-cao-san-api-feasibility.md.
 */
interface ShopReportConnector
{
    /** Sức khỏe/hiệu suất gian hàng (scorecard). */
    public function fetchShopHealth(AuthContext $auth): ShopHealthDTO;

    /**
     * Lịch sử điểm phạt ("sao quả tạ"). Sàn không hỗ trợ → throw {@see UnsupportedOperation}.
     *
     * @return list<PenaltyPointDTO>
     */
    public function fetchPenaltyPoints(AuthContext $auth): array;

    /**
     * Các hình phạt đang/đã áp dụng. Sàn không hỗ trợ → throw {@see UnsupportedOperation}.
     *
     * @return list<PunishmentDTO>
     */
    public function fetchPunishments(AuthContext $auth): array;
}
