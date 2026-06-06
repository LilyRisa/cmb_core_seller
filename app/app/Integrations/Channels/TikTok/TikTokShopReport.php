<?php

namespace CMBcoreSeller\Integrations\Channels\TikTok;

use CMBcoreSeller\Integrations\Channels\DTO\ShopHealthDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ShopMetricDTO;

/**
 * Map TikTok Shop Analytics `/analytics/{ver}/shop/performance` → ShopHealthDTO (kind='performance').
 * Tài liệu chính thức: partner.tiktokshop.com get-shop-performance-202509.
 *
 * TikTok Partner API KHÔNG có sức khỏe/điểm phạt qua API (chỉ Seller Center UI) ⇒ báo cáo này chỉ là
 * HIỆU SUẤT doanh thu. Mapper tự thích ứng các nhánh dưới `sales` (money nếu có `overall.amount`,
 * number nếu `overall` là số) để không phụ thuộc tên field cứng.
 */
final class TikTokShopReport
{
    private const LABEL = [
        'gmv' => 'GMV (doanh thu)',
        'gross_revenue' => 'Doanh thu gộp',
        'orders' => 'Số đơn',
        'gross_orders' => 'Số đơn gộp',
        'sku_orders' => 'Số đơn SKU',
        'units_sold' => 'Số sản phẩm bán',
        'buyers' => 'Số người mua',
        'page_views' => 'Lượt xem trang',
        'visitors' => 'Lượt truy cập',
    ];

    /**
     * @param  array<string,mixed>  $data  `data` envelope từ TikTokClient::get
     * @param  array<string,mixed>  $meta  thông tin phụ (vd date range) để gắn vào raw
     */
    public static function health(array $data, array $meta = []): ShopHealthDTO
    {
        $intervals = (array) data_get($data, 'performance.intervals', []);
        $interval = is_array($intervals[0] ?? null) ? (array) $intervals[0] : [];
        $sales = (array) ($interval['sales'] ?? []);

        $metrics = [];
        foreach ($sales as $key => $node) {
            if (! is_array($node) || ! array_key_exists('overall', $node)) {
                continue;
            }
            $overall = $node['overall'];
            $name = self::LABEL[$key] ?? ucfirst(str_replace('_', ' ', (string) $key));
            if (is_array($overall) && isset($overall['amount'])) {
                $metrics[] = new ShopMetricDTO(
                    key: (string) $key,
                    name: $name,
                    group: 'sales',
                    value: (float) $overall['amount'],
                    unit: 'money',
                    raw: $node + ['currency' => $overall['currency'] ?? null],
                );
            } elseif (is_numeric($overall)) {
                $metrics[] = new ShopMetricDTO(
                    key: (string) $key,
                    name: $name,
                    group: 'sales',
                    value: (float) $overall,
                    unit: 'number',
                    raw: $node,
                );
            }
        }

        return new ShopHealthDTO(
            provider: 'tiktok',
            kind: 'performance',
            overallRating: null,
            overallLabel: null,
            metrics: $metrics,
            raw: array_filter([
                'start_date' => $interval['start_date'] ?? ($meta['start_date'] ?? null),
                'end_date' => $interval['end_date'] ?? ($meta['end_date'] ?? null),
            ]),
        );
    }
}
