<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;

/**
 * Builds Shopee Open API v2 request bodies for product publishing.
 *
 * add_item   — the initial create-item call (single-SKU: price+stock on root; multi-SKU: no price/stock).
 * tierVariation — the init_tier_variation call that follows add_item for multi-SKU items.
 *
 * Note: tier_variation is NOT part of add_item per Shopee API v2 spec.
 */
final class ShopeeProductPayload
{
    /**
     * Build the body for the add_item API call.
     *
     * @return array<string,mixed>
     */
    public static function addItem(ListingDraftDTO $d): array
    {
        $first = $d->skus[0];

        $body = [
            'category_id' => (int) $d->categoryId,
            'item_name' => $d->title,
            'description' => $d->description,
            'image' => ['image_id_list' => array_map(fn ($m) => $m->ref, $d->media)],
            'logistic_info' => array_map(
                fn ($c) => array_filter([
                    'logistics_channel_id' => $c['logistics_channel_id'],
                    'enabled' => (bool) $c['enabled'],
                    'size_id' => $c['size_id'] ?? null,
                    'shipping_fee' => $c['shipping_fee'] ?? null,
                ], fn ($v) => $v !== null),
                $d->logistics['channels'],
            ),
            'weight' => $d->logistics['weight'] ?? null,
        ];

        if ($d->brandId !== null) {
            $body['brand'] = ['brand_id' => (int) $d->brandId];
        }

        if (! empty($d->attributes['attribute_list'])) {
            $body['attribute_list'] = $d->attributes['attribute_list'];
        }

        if (isset($d->logistics['dimension'])) {
            $body['dimension'] = $d->logistics['dimension'];
        }

        if (count($d->skus) === 1) {
            $body['original_price'] = $first['price'];
            $body['normal_stock'] = (int) $first['stock'];
            if (! empty($first['seller_sku'])) {
                $body['item_sku'] = $first['seller_sku'];
            }
        }

        return array_filter($body, fn ($v) => $v !== null);
    }

    /**
     * Build the body for the init_tier_variation API call (multi-SKU items).
     *
     * @return array<string,mixed>
     */
    public static function tierVariation(int $itemId, ListingDraftDTO $d): array
    {
        [$tiers, $models] = self::buildTiers($d->skus);

        return ['item_id' => $itemId, 'tier_variation' => $tiers, 'model' => $models];
    }

    /**
     * Derive tier_variation + model arrays from the SKU list.
     *
     * @param  array<int,array<string,mixed>>  $skus
     * @return array{0:array<int,mixed>,1:array<int,mixed>}
     */
    private static function buildTiers(array $skus): array
    {
        /** @var string[] $tierNames */
        $tierNames = array_keys($skus[0]['sale_props'] ?? []); // max 2

        /** @var array<string,string[]> $options */
        $options = []; // tierName => ordered unique values

        foreach ($skus as $s) {
            foreach ($tierNames as $t) {
                $val = (string) ($s['sale_props'][$t] ?? '');
                if (! in_array($val, $options[$t] ?? [], true)) {
                    $options[$t][] = $val;
                }
            }
        }

        $tiers = [];
        foreach ($tierNames as $t) {
            $tiers[] = [
                'name' => $t,
                'option_list' => array_map(fn ($v) => ['option' => $v], $options[$t]),
            ];
        }

        $models = [];
        foreach ($skus as $s) {
            $tierIndex = [];
            foreach ($tierNames as $t) {
                $pos = array_search((string) $s['sale_props'][$t], $options[$t], true);
                $tierIndex[] = $pos !== false ? (int) $pos : 0;
            }
            $models[] = [
                'tier_index' => $tierIndex,
                'original_price' => $s['price'],
                'normal_stock' => (int) $s['stock'],
                'model_sku' => $s['seller_sku'] ?? '',
            ];
        }

        return [$tiers, $models];
    }
}
