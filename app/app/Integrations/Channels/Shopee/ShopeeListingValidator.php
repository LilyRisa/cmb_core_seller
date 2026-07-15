<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use CMBcoreSeller\Integrations\Channels\Contracts\ListingValidator;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;

/**
 * Validates a listing draft against Shopee Open API v2 add_item rules.
 *
 * Rules enforced:
 * - name required (non-blank)
 * - name ≤ title_max_length (config, mặc định 100)
 * - leaf category required (non-empty string)
 * - ≥1 image_id (already uploaded via upload_image)
 * - logistic_info needs ≥1 channel with enabled=true
 * - weight required when any chosen channel has fee_type === 'SIZE_INPUT'
 * - days_to_ship in 7..30 when pre-order (hàng đặt trước) is enabled
 * - price > 0 per SKU
 */
final class ShopeeListingValidator implements ListingValidator
{
    /** @return array<string,string> */
    public function validate(ListingDraftDTO $d): array
    {
        $e = [];

        $titleMax = (int) config('integrations.listing_limits.shopee.title_max_length', 100);
        if (trim($d->title) === '') {
            $e['title'] = 'Tên bắt buộc';
        } elseif (mb_strlen($d->title) > $titleMax) {
            $e['title'] = "Tên tối đa $titleMax ký tự";
        }

        if ($d->categoryId === '') {
            $e['categoryId'] = 'Phải chọn danh mục lá';
        }

        if ($d->brandId === null || $d->brandId === '') {
            $e['brandId'] = 'Phải chọn thương hiệu (dùng id No Brand nếu không có)';
        }

        $maxImages = (int) config('integrations.listing_limits.shopee.max_images', 9);
        if (count($d->media) === 0) {
            $e['media'] = 'Cần ≥1 image_id (đã upload_image)';
        } elseif (count($d->media) > $maxImages) {
            $e['media'] = "Tối đa $maxImages ảnh";
        }

        $channels = $d->logistics['channels'] ?? [];

        if (! collect($channels)->contains(fn ($c) => ! empty($c['enabled']))) {
            $e['logistics.channels'] = 'Cần ≥1 logistics channel enabled';
        }

        $needsSize = collect($channels)->contains(fn ($c) => ($c['fee_type'] ?? '') === 'SIZE_INPUT');

        if ($needsSize && ($d->logistics['weight'] ?? null) === null) {
            $e['logistics.weight'] = 'weight bắt buộc với SIZE_INPUT';
        }

        $preOrder = $d->logistics['pre_order'] ?? null;

        if (is_array($preOrder) && ! empty($preOrder['is_pre_order'])) {
            $days = (int) ($preOrder['days_to_ship'] ?? 0);
            if ($days < 7 || $days > 30) {
                $e['logistics.pre_order.days_to_ship'] = 'Hàng đặt trước: số ngày chuẩn bị phải từ 7–30';
            }
        }

        foreach ($d->skus as $i => $s) {
            if (($s['price'] ?? 0) <= 0) {
                $e["skus.$i.price"] = 'Giá > 0';
            }
        }

        return $e;
    }
}
