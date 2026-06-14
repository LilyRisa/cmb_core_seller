<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\TikTok;

use CMBcoreSeller\Integrations\Channels\Contracts\ListingValidator;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;

/**
 * Validates a product listing draft against TikTok Shop Create Product rules for Vietnam.
 * Rules sourced from TikTok Shop Open Platform — Create Product API docs (category_version=v2).
 *
 * @see ListingValidator
 */
final class TikTokListingValidator implements ListingValidator
{
    /**
     * {@inheritDoc}
     *
     * Enforced rules (VN):
     * - title: 25–255 characters
     * - description: ≤10 000 characters
     * - categoryId: non-empty (leaf category, category_version=v2)
     * - media: 1–9 main images
     * - skus: ≤100 SKUs; each must have warehouse_id and price > 0
     * - logistics.package_weight: > 0
     *
     * @return array<string, string>
     */
    public function validate(ListingDraftDTO $draft): array
    {
        $errors = [];

        $titleLen = mb_strlen($draft->title);
        if ($titleLen < 25 || $titleLen > 255) {
            $errors['title'] = 'VN: tiêu đề 25–255 ký tự';
        }

        if (mb_strlen($draft->description) > 10000) {
            $errors['description'] = 'Mô tả ≤10.000 ký tự';
        }

        if ($draft->categoryId === '') {
            $errors['categoryId'] = 'Phải chọn danh mục lá (category_version=v2)';
        }

        $maxImages = (int) config('integrations.listing_limits.tiktok.max_images', 9);
        $mediaCount = count($draft->media);
        if ($mediaCount === 0) {
            $errors['media'] = 'Cần ≥1 ảnh (uri)';
        } elseif ($mediaCount > $maxImages) {
            $errors['media'] = "Tối đa $maxImages ảnh";
        }

        if (count($draft->skus) > 100) {
            $errors['skus'] = 'Tối đa 100 SKU';
        }

        if ((float) ($draft->logistics['package_weight'] ?? 0) <= 0) {
            $errors['logistics.package_weight'] = 'package_weight > 0';
        }

        foreach ($draft->skus as $i => $sku) {
            if (empty($sku['warehouse_id'])) {
                $errors["skus.{$i}.warehouse_id"] = 'Mỗi SKU phải có warehouse_id';
            }

            if (($sku['price'] ?? 0) <= 0) {
                $errors["skus.{$i}.price"] = 'Giá > 0';
            }
        }

        return $errors;
    }
}
