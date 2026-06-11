<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\Lazada;

use CMBcoreSeller\Integrations\Channels\Contracts\ListingValidator;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;

/**
 * Validates a product listing draft against Lazada CreateProduct rules.
 *
 * Mandatory fields: leaf PrimaryCategory, name ≤255 chars, brand_id, at least
 * one CDN image, per-SKU SellerSku / price > 0 / package_weight / package dims.
 * When there are multiple SKUs every SKU must carry sale_props.
 *
 * Returns an empty array when the draft is fully valid.
 *
 * @see ListingValidator
 */
final class LazadaListingValidator implements ListingValidator
{
    /** @return array<string,string> */
    public function validate(ListingDraftDTO $d): array
    {
        $e = [];

        if (trim($d->title) === '') {
            $e['title'] = 'Tên sản phẩm bắt buộc';
        } elseif (mb_strlen($d->title) > 255) {
            $e['title'] = 'Tên tối đa 255 ký tự';
        }

        if ($d->categoryId === '') {
            $e['categoryId'] = 'Phải chọn danh mục lá';
        }

        if ($d->brandId === null || $d->brandId === '') {
            $e['brandId'] = 'brand_id bắt buộc (dùng id No Brand nếu không có)';
        }

        if (count($d->media) === 0) {
            $e['media'] = 'Cần ít nhất 1 ảnh đã upload lên CDN Lazada';
        }

        if (count($d->skus) > 1) {
            foreach ($d->skus as $i => $s) {
                if (empty($s['sale_props'])) {
                    $e["skus.{$i}.sale_props"] = 'Nhiều SKU phải có sale_props';
                }
            }
        }

        foreach ($d->skus as $i => $s) {
            if (($s['seller_sku'] ?? '') === '') {
                $e["skus.{$i}.seller_sku"] = 'SellerSku bắt buộc';
            }

            if (($s['price'] ?? 0) <= 0) {
                $e["skus.{$i}.price"] = 'Giá > 0';
            }

            if (($s['package_weight'] ?? null) === null) {
                $e["skus.{$i}.package_weight"] = 'package_weight (kg) bắt buộc';
            }

            foreach (['length', 'width', 'height'] as $k) {
                if (! isset($s['package_dims'][$k])) {
                    $e["skus.{$i}.package_{$k}"] = "package_{$k} (cm) bắt buộc";
                }
            }
        }

        return $e;
    }
}
