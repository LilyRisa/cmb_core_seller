<?php

namespace CMBcoreSeller\Integrations\Channels\Lazada;

use CMBcoreSeller\Integrations\Channels\DTO\ShopHealthDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ShopMetricDTO;

/**
 * Map Lazada `/seller/performance/get` (indicators[]) → ShopHealthDTO chuẩn.
 * Tài liệu chính thức: open.lazada.com /seller/performance/get (Category: Seller API).
 * Lazada không có "rating tổng" ⇒ overallRating=null; mỗi indicator có target + target_respected.
 */
final class LazadaShopReport
{
    /** Nhóm chỉ số suy từ `type` của Lazada (best-effort; mặc định 'other'). */
    private const GROUP = [
        'POSITIVE_SELLER_RATING' => 'rating',
        'PRODUCT_RATING_COVERAGE' => 'rating',
        'SHIP_ON_TIME' => 'fulfillment',
        'SHIP_ON_TIME_RATE' => 'fulfillment',
        'CANCELLATION_RATE' => 'fulfillment',
        'RETURN_RATE' => 'fulfillment',
        'FAST_HANDOVER_RATE' => 'fulfillment',
        'RESPONSE_RATE' => 'customer_service',
        'RESPONSE_TIME' => 'customer_service',
        'NEGATIVE_RATING' => 'rating',
    ];

    /** @param array<string,mixed> $data `data` envelope từ LazadaClient::get */
    public static function health(array $data): ShopHealthDTO
    {
        $metrics = [];
        foreach ((array) ($data['indicators'] ?? []) as $ind) {
            if (! is_array($ind)) {
                continue;
            }
            $type = (string) ($ind['type'] ?? '');
            $score = $ind['score'] ?? null;
            $target = $ind['target'] ?? null;
            $comparator = self::comparator((string) ($ind['target_format'] ?? ''));
            $metrics[] = new ShopMetricDTO(
                key: $type !== '' ? strtolower($type) : 'metric_'.count($metrics),
                name: (string) ($ind['name'] ?? $type),
                group: self::GROUP[$type] ?? 'other',
                value: ($score === null || $score === '' || $score === '-') ? null : (float) $score,
                unit: self::unit((string) ($ind['score_format'] ?? '')),
                target: ($target === null || $target === '') ? null : (float) $target,
                comparator: $comparator,
                passed: self::passed($ind['target_respected'] ?? null),
                raw: $ind,
            );
        }

        return new ShopHealthDTO(
            provider: 'lazada',
            kind: 'health',
            overallRating: null,
            overallLabel: null,
            metrics: $metrics,
            raw: ['main_category_name' => $data['main_category_name'] ?? null, 'seller_id' => $data['seller_id'] ?? null],
        );
    }

    private static function unit(string $scoreFormat): string
    {
        return match (strtoupper($scoreFormat)) {
            'PERCENTAGE' => 'percent',
            'MINUTES' => 'minute',
            'HOURS' => 'hour',
            default => 'number',     // INTEGER | DOUBLE
        };
    }

    private static function comparator(string $targetFormat): ?string
    {
        $f = strtoupper($targetFormat);

        return match (true) {
            str_starts_with($f, 'GREATER_THAN') => '>=',
            str_starts_with($f, 'STRICTLY_LOWER_THAN') => '<',
            str_starts_with($f, 'LOWER_THAN') => '<=',
            str_starts_with($f, 'EQUALS_TO') => '=',
            default => null,
        };
    }

    /** Lazada trả `target_respected` dạng bool hoặc chuỗi 'true'/'false'. */
    private static function passed(mixed $v): ?bool
    {
        if (is_bool($v)) {
            return $v;
        }
        if (is_string($v) && $v !== '') {
            return strtolower($v) === 'true';
        }

        return null;
    }
}
