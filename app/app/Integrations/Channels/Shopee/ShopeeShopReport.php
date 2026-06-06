<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\DTO\PenaltyPointDTO;
use CMBcoreSeller\Integrations\Channels\DTO\PunishmentDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ShopHealthDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ShopMetricDTO;

/**
 * Map Shopee AccountHealth (module 103) → DTO chuẩn. Tài liệu chính thức:
 * open.shopee.com /api/v2/account_health/{get_shop_performance,get_penalty_point_history,get_punishment_history}.
 */
final class ShopeeShopReport
{
    private const RATING = [1 => 'Kém', 2 => 'Cần cải thiện', 3 => 'Tốt', 4 => 'Xuất sắc'];

    private const METRIC_GROUP = [1 => 'fulfillment', 2 => 'listing', 3 => 'customer_service'];

    private const UNIT = [1 => 'number', 2 => 'percent', 3 => 'second', 4 => 'day', 5 => 'hour'];

    /**
     * Tên chỉ số tiếng Việt theo `metric_id` (Shopee trả `metric_name` mặc định tiếng Anh).
     * Mã chưa có trong map → dùng `metric_name` của Shopee làm fallback.
     */
    private const METRIC_NAME = [
        1 => 'Tỉ lệ giao hàng trễ',
        3 => 'Tỉ lệ không hoàn tất đơn',
        4 => 'Thời gian chuẩn bị hàng',
        11 => 'Tỉ lệ phản hồi chat',
        12 => '% sản phẩm đặt trước',
        15 => 'Số ngày vi phạm hàng đặt trước',
        21 => 'Thời gian phản hồi',
        22 => 'Đánh giá shop',
        23 => 'Số chat chưa phản hồi',
        25 => 'Tỉ lệ bàn giao nhanh',
        27 => 'Tỉ lệ lấy hàng trễ hẹn',
        28 => 'Giá trị vi phạm lấy hàng trễ',
        29 => 'Thời gian phản hồi trung bình',
        42 => 'Tỉ lệ hủy đơn',
        43 => 'Tỉ lệ trả hàng/hoàn tiền',
        52 => 'Vi phạm sản phẩm nghiêm trọng',
        53 => 'Vi phạm sản phẩm khác',
        54 => 'Sản phẩm bị cấm',
        55 => 'Hàng giả / vi phạm SHTT',
        56 => 'Sản phẩm spam',
        85 => 'Tỉ lệ giao trễ (giao hôm sau)',
        88 => 'Tỉ lệ không hoàn tất (giao hôm sau)',
        91 => 'Tỉ lệ hủy đơn (giao hôm sau)',
        92 => 'Tỉ lệ trả/hoàn (giao hôm sau)',
        95 => 'Mức độ hài lòng khách hàng',
        96 => '% sản phẩm giao trong ngày',
        97 => '% sản phẩm giao hôm sau',
        2001 => 'Tỉ lệ bàn giao nhanh (SLS)',
        2002 => 'Tỉ lệ bàn giao nhanh (FBS)',
        2003 => 'Tỉ lệ bàn giao nhanh (3PF)',
        2011 => 'Sản phẩm kém chất lượng',
        2030 => '% sản phẩm giao hỏa tốc',
        2031 => '% bật miễn phí ship hỏa tốc',
        2032 => 'Giao hàng thứ Bảy',
        2033 => 'Thời gian chuẩn bị (PS)',
    ];

    /** Nhãn loại vi phạm (tiếng Việt) — các mã hay gặp; mã khác fallback "Vi phạm #". */
    private const VIOLATION = [
        5 => 'Tỉ lệ giao trễ cao', 6 => 'Tỉ lệ không hoàn tất đơn cao',
        7 => 'Nhiều đơn không hoàn tất', 8 => 'Nhiều đơn giao trễ',
        9 => 'Sản phẩm cấm đăng', 10 => 'Hàng giả / vi phạm sở hữu trí tuệ',
        11 => 'Spam', 12 => 'Sao chép hình ảnh', 21 => 'Nhiều tin nhắn không phản hồi',
        22 => 'Trả lời chat thô lỗ', 23 => 'Yêu cầu người mua hủy đơn',
        24 => 'Trả lời đánh giá thô lỗ', 25 => 'Vi phạm chính sách Trả hàng/Hoàn tiền',
        101 => 'Lý do theo bậc', 3054 => 'Tự thao túng đơn (order brushing)',
        3060 => 'Tỉ lệ không hoàn tất cực cao', 3145 => 'Tỉ lệ Trả/Hoàn (kênh ngoài)',
    ];

    /** Nhãn loại hình phạt (tiếng Việt). */
    private const PUNISHMENT = [
        103 => 'Ẩn listing khỏi duyệt danh mục', 104 => 'Ẩn listing khỏi tìm kiếm',
        105 => 'Không thể tạo listing mới', 106 => 'Không thể sửa listing',
        107 => 'Không thể tham gia chương trình marketing', 108 => 'Mất trợ giá vận chuyển',
        109 => 'Tài khoản bị treo', 600 => 'Ẩn listing khỏi tìm kiếm',
        601 => 'Ẩn listing khỏi gợi ý', 602 => 'Ẩn listing khỏi duyệt danh mục',
        1109 => 'Giảm giới hạn listing', 1110 => 'Giảm giới hạn listing',
        1111 => 'Giảm giới hạn listing', 1112 => 'Giảm giới hạn listing', 2008 => 'Giới hạn số đơn',
    ];

    /** @param array<string,mixed> $res `response` từ ShopeeClient::shopGet */
    public static function health(array $res): ShopHealthDTO
    {
        $overall = (array) ($res['overall_performance'] ?? []);
        $rating = isset($overall['rating']) ? (int) $overall['rating'] : null;

        $metrics = [];
        foreach ((array) ($res['metric_list'] ?? []) as $m) {
            if (! is_array($m)) {
                continue;
            }
            $metricId = (int) ($m['metric_id'] ?? 0);
            if ($metricId < 0) {   // metric_id < 0 = nhóm chỉ số (không phải giá trị thật) → bỏ
                continue;
            }
            $target = (array) ($m['target'] ?? []);
            $value = isset($m['current_period']) ? (float) $m['current_period'] : null;
            $targetVal = isset($target['value']) ? (float) $target['value'] : null;
            $comparator = isset($target['comparator']) ? (string) $target['comparator'] : null;
            $metrics[] = new ShopMetricDTO(
                key: 'metric_'.$metricId,
                name: self::METRIC_NAME[$metricId] ?? (string) ($m['metric_name'] ?? ('Chỉ số #'.$metricId)),
                group: self::METRIC_GROUP[(int) ($m['metric_type'] ?? 0)] ?? 'other',
                value: $value,
                unit: self::UNIT[(int) ($m['unit'] ?? 0)] ?? 'number',
                target: $targetVal,
                comparator: $comparator,
                passed: self::compare($value, $comparator, $targetVal),
                raw: $m,
            );
        }

        return new ShopHealthDTO(
            provider: 'shopee',
            kind: 'health',
            overallRating: $rating,
            overallLabel: $rating !== null ? (self::RATING[$rating] ?? null) : null,
            metrics: $metrics,
            raw: $overall,
        );
    }

    /**
     * @param  array<string,mixed>  $res
     * @return list<PenaltyPointDTO>
     */
    public static function penalties(array $res): array
    {
        $out = [];
        foreach ((array) ($res['penalty_point_list'] ?? []) as $p) {
            if (! is_array($p)) {
                continue;
            }
            $vt = isset($p['violation_type']) ? (int) $p['violation_type'] : null;
            $out[] = new PenaltyPointDTO(
                points: (int) ($p['latest_point_num'] ?? 0),
                violationType: $vt,
                violationLabel: $vt !== null ? (self::VIOLATION[$vt] ?? ('Vi phạm #'.$vt)) : null,
                issuedAt: self::ts($p['issue_time'] ?? null),
                referenceId: isset($p['reference_id']) ? (string) $p['reference_id'] : null,
                raw: $p,
            );
        }

        return $out;
    }

    /**
     * @param  array<string,mixed>  $res
     * @return list<PunishmentDTO>
     */
    public static function punishments(array $res): array
    {
        $out = [];
        foreach ((array) ($res['punishment_list'] ?? []) as $p) {
            if (! is_array($p)) {
                continue;
            }
            $type = isset($p['punishment_type']) ? (int) $p['punishment_type'] : null;
            $reason = isset($p['reason']) ? (int) $p['reason'] : null;
            $out[] = new PunishmentDTO(
                type: $type,
                typeLabel: $type !== null ? (self::PUNISHMENT[$type] ?? ('Hình phạt #'.$type)) : null,
                tier: ($reason !== null && $reason >= 1 && $reason <= 5) ? $reason : null,
                startAt: self::ts($p['start_time'] ?? null),
                endAt: self::ts($p['end_time'] ?? null),
                ongoing: true,
                raw: $p,
            );
        }

        return $out;
    }

    /** Nhãn loại vi phạm (tiếng Việt) — public để webhook điểm phạt (code 28) dùng chung map. */
    public static function violationLabel(?int $type): ?string
    {
        return $type === null ? null : (self::VIOLATION[$type] ?? ('Vi phạm #'.$type));
    }

    /** So sánh value với target theo comparator Shopee (<, <=, >, >=, =). */
    private static function compare(?float $value, ?string $comparator, ?float $target): ?bool
    {
        if ($value === null || $target === null || $comparator === null || $comparator === '') {
            return null;
        }

        return match ($comparator) {
            '<' => $value < $target,
            '<=' => $value <= $target,
            '>' => $value > $target,
            '>=' => $value >= $target,
            '=' => $value === $target,
            default => null,
        };
    }

    private static function ts(mixed $v): ?CarbonImmutable
    {
        $n = (int) $v;

        return $n > 0 ? CarbonImmutable::createFromTimestamp($n) : null;
    }
}
