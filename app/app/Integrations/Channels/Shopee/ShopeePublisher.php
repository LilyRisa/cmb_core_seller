<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use CMBcoreSeller\Integrations\Channels\Contracts\ProductPublishingConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\BrandDTO;
use CMBcoreSeller\Integrations\Channels\DTO\CategoryNodeDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingAttributeDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDetailDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingEditDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingResultDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingStatusDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\MarketplaceApiException;
use Illuminate\Support\Sleep;

/**
 * Shopee product-publishing connector (add_item + init_tier_variation + taxonomy + media + status).
 *
 * Implements {@see ProductPublishingConnector}. Shopee item creation is multi-step: add_item creates
 * the (single-SKU priced) item, then for multi-SKU items init_tier_variation defines the tier matrix.
 * Talks to Shopee via the envelope helpers on {@see ShopeeClient} so it can inspect the `error` field
 * itself and raise a provider-agnostic {@see MarketplaceApiException} via {@see MarketplaceApiException::fromShopee}.
 *
 * Taxonomy/media/status reads are defensive mappings against the documented Shopee Open API v2 shapes.
 */
final class ShopeePublisher implements ProductPublishingConnector
{
    public function __construct(
        private readonly ShopeeClient $client,
        private readonly ShopeeListingValidator $validator,
    ) {}

    public function createListing(AuthContext $auth, ListingDraftDTO $draft): ListingResultDTO
    {
        $errors = $this->validator->validate($draft);
        if ($errors !== []) {
            throw MarketplaceApiException::validation('shopee', $errors);
        }

        // Video đã được job chuẩn bị trước (upload + chờ transcode qua re-queue) và truyền
        // vào DTO dưới dạng videoExternalId — gắn thẳng vào add_item, KHÔNG upload đồng bộ ở đây.
        $resp = $this->client->shopPostEnvelope($auth, '/api/v2/product/add_item', [], ShopeeProductPayload::addItem($draft, $draft->videoExternalId));
        if ((string) ($resp['error'] ?? '') !== '') {
            throw MarketplaceApiException::fromShopee($resp);
        }

        $itemId = (int) ($resp['item_id'] ?? $resp['response']['item_id'] ?? 0);
        $raw = ['add_item' => $resp];

        if (count($draft->skus) > 1) {
            Sleep::for(5)->seconds();
            $tv = $this->client->shopPostEnvelope($auth, '/api/v2/product/init_tier_variation', [], ShopeeProductPayload::tierVariation($itemId, $draft));
            if ((string) ($tv['error'] ?? '') !== '') {
                // NOTE (known limitation, S-R2): add_item already created the item on
                // Shopee but WITHOUT variations. We surface external_item_id in the
                // exception context so it is recorded for manual recovery. A naive
                // re-push would call add_item again and DUPLICATE the item — a full
                // resume requires splitting create/variation into separate steps.
                // Shopee stays sandbox-gated (S-R1) before prod use.
                throw new MarketplaceApiException(
                    'Shopee: tạo biến thể thất bại sau khi tạo sản phẩm (item_id='.$itemId.')',
                    'shopee',
                    ['response' => $tv, 'external_item_id' => (string) $itemId],
                    false,
                );
            }
            $raw['tier'] = $tv;
        }

        return new ListingResultDTO((string) $itemId, [], 'NORMAL', $raw);
    }

    /** Bắt đầu upload video (không chờ transcode) → trả video_upload_id. Job sẽ poll trạng thái. */
    public function startVideoUpload(AuthContext $auth, ListingDraftDTO $draft): string
    {
        return $this->client->startVideoUpload($auth, (string) $draft->videoRef);
    }

    /** Trạng thái video: 'ready' | 'processing' | 'failed'. */
    public function videoUploadStatus(AuthContext $auth, string $videoId): string
    {
        return $this->client->videoUploadStatus($auth, $videoId);
    }

    public function uploadMedia(AuthContext $auth, string $imageUrlOrPath, string $useCase = 'main'): MediaRefDTO
    {
        $scene = $useCase === 'main' ? 'normal' : 'desc';
        $resp = $this->client->uploadImage($auth, $imageUrlOrPath, $scene);

        return new MediaRefDTO((string) ($resp['response']['image_info']['image_id'] ?? ''), 'image_id', $resp);
    }

    /** @return CategoryNodeDTO[] */
    public function getCategoryTree(AuthContext $auth, ?string $parentId = null): array
    {
        $resp = $this->client->shopGetEnvelope($auth, '/api/v2/product/get_category', ['language' => 'vi']);

        $out = [];
        foreach ((array) ($resp['response']['category_list'] ?? []) as $node) {
            if (! is_array($node)) {
                continue;
            }
            $parent = (string) ($node['parent_category_id'] ?? '');
            $out[] = new CategoryNodeDTO(
                id: (string) ($node['category_id'] ?? ''),
                parentId: ($parent === '' || $parent === '0') ? null : $parent,
                name: (string) ($node['display_category_name'] ?? ''),
                isLeaf: ! ($node['has_children'] ?? false),
                raw: $node,
            );
        }

        return $out;
    }

    /** @return ListingAttributeDTO[] */
    public function getCategoryAttributes(AuthContext $auth, string $categoryId): array
    {
        $resp = $this->client->shopGetEnvelope($auth, '/api/v2/product/get_attribute_tree', [
            'category_id_list' => $categoryId,
            'language' => 'vi',
        ]);

        $tree = (array) (($resp['response']['list'][0]['attribute_tree'] ?? []));

        $out = [];
        foreach ($tree as $attr) {
            if (! is_array($attr)) {
                continue;
            }
            $out[] = new ListingAttributeDTO(
                id: (string) ($attr['attribute_id'] ?? ''),
                name: (string) ($attr['original_attribute_name'] ?? ''),
                required: (bool) ($attr['mandatory'] ?? false),
                isSaleProp: false,
                inputType: (string) ($attr['input_type'] ?? ''),
                raw: $attr,
            );
        }

        return $out;
    }

    /** @return BrandDTO[] */
    public function getBrands(AuthContext $auth, string $categoryId): array
    {
        $resp = $this->client->shopGetEnvelope($auth, '/api/v2/product/get_brand_list', [
            'category_id' => (int) $categoryId,
            'page_size' => 100,
            'offset' => 0,
            'status' => 1,
        ]);

        $out = [];
        foreach ((array) ($resp['response']['brand_list'] ?? []) as $brand) {
            if (! is_array($brand)) {
                continue;
            }
            $out[] = new BrandDTO(
                id: (string) ($brand['brand_id'] ?? ''),
                name: (string) ($brand['original_brand_name'] ?? ''),
                raw: $brand,
            );
        }

        return $out;
    }

    public function getListingStatus(AuthContext $auth, string $externalItemId): ListingStatusDTO
    {
        $resp = $this->client->shopGetEnvelope($auth, '/api/v2/product/get_item_base_info', [
            'item_id_list' => $externalItemId,
        ]);

        $item = (array) (($resp['response']['item_list'][0] ?? []));
        $rawStatus = (string) ($item['item_status'] ?? '');

        return new ListingStatusDTO(
            externalItemId: $externalItemId,
            rawStatus: $rawStatus,
            normalized: $this->normalizeStatus($rawStatus),
            reason: null,
            raw: $resp,
        );
    }

    public function getListingDetail(AuthContext $auth, string $externalProductId): ListingDetailDTO
    {
        $resp = $this->client->shopGetEnvelope($auth, '/api/v2/product/get_item_base_info', [
            'item_id_list' => $externalProductId,
        ]);
        if ((string) ($resp['error'] ?? '') !== '') {
            throw MarketplaceApiException::fromShopee($resp);
        }

        $item = (array) (($resp['response']['item_list'][0] ?? []));
        $hasModel = (bool) ($item['has_model'] ?? false);

        $skus = [];
        if ($hasModel) {
            $models = $this->client->shopGetEnvelope($auth, '/api/v2/product/get_model_list', ['item_id' => $externalProductId]);
            foreach ((array) ($models['response']['model'] ?? []) as $m) {
                if (! is_array($m)) {
                    continue;
                }
                $skus[] = [
                    'external_sku_id' => (string) ($m['model_id'] ?? ''),
                    'seller_sku' => (string) ($m['model_sku'] ?? ''),
                    'price' => $this->currentPrice($m['price_info'] ?? []),
                ];
            }
        } else {
            $skus[] = [
                'external_sku_id' => $externalProductId,
                'seller_sku' => (string) ($item['item_sku'] ?? ''),
                'price' => $this->currentPrice($item['price_info'] ?? []),
            ];
        }

        return new ListingDetailDTO(
            externalProductId: $externalProductId,
            title: (string) ($item['item_name'] ?? ''),
            description: (string) ($item['description'] ?? ''),
            images: array_values(array_filter(array_map('strval', (array) ($item['image']['image_url_list'] ?? [])))),
            skus: $skus,
            raw: $resp,
            categoryId: ($cat = (string) ($item['category_id'] ?? '')) !== '' ? $cat : null,
            brandId: ($b = (string) ($item['brand']['brand_id'] ?? '')) !== '' && $b !== '0' ? $b : null,
            attributes: is_array($item['attribute_list'] ?? null) ? $item['attribute_list'] : [],
        );
    }

    public function updateListing(AuthContext $auth, string $externalProductId, ListingEditDTO $edit): ListingResultDTO
    {
        if ($edit->hasInfo()) {
            $body = ['item_id' => (int) $externalProductId];
            if ($edit->title !== null) {
                $body['item_name'] = $edit->title;
            }
            if ($edit->description !== null) {
                $body['description'] = $edit->description;
            }
            if ($edit->images !== null) {
                // Shopee references images by uploaded image_id, not URL.
                $body['image'] = ['image_id_list' => array_map(fn ($u) => $this->uploadMedia($auth, (string) $u)->ref, $edit->images)];
            }
            $resp = $this->client->shopPostEnvelope($auth, '/api/v2/product/update_item', [], $body);
            if ((string) ($resp['error'] ?? '') !== '') {
                throw MarketplaceApiException::fromShopee($resp);
            }
        }

        if ($edit->hasPrices()) {
            $priceList = [];
            foreach ($edit->prices ?? [] as $p) {
                $sku = (string) $p['external_sku_id'];
                $entry = ['original_price' => (int) $p['price']];
                if ($sku !== '' && $sku !== $externalProductId) {
                    $entry['model_id'] = (int) $sku;   // has-variant item: price is per model
                }
                $priceList[] = $entry;
            }
            $resp = $this->client->shopPostEnvelope($auth, '/api/v2/product/update_price', [], [
                'item_id' => (int) $externalProductId,
                'price_list' => $priceList,
            ]);
            if ((string) ($resp['error'] ?? '') !== '') {
                throw MarketplaceApiException::fromShopee($resp);
            }
        }

        return new ListingResultDTO($externalProductId, [], 'live');
    }

    public function getShippingOptions(AuthContext $auth): array
    {
        $resp = $this->client->shopGetEnvelope($auth, '/api/v2/logistics/get_channel_list', []);

        $channels = [];
        foreach ((array) ($resp['response']['logistics_channel_list'] ?? []) as $c) {
            if (! is_array($c)) {
                continue;
            }
            // Chỉ kênh đang bật & dùng được để tạo SP (mask_channel_id=0). Doc 209/211.
            if (($c['enabled'] ?? false) && (int) ($c['mask_channel_id'] ?? 0) === 0) {
                $channels[] = [
                    'id' => (string) ($c['logistics_channel_id'] ?? ''),
                    'name' => (string) ($c['logistics_channel_name'] ?? ($c['name'] ?? '')),
                    'fee_type' => (string) ($c['fee_type'] ?? ''),
                ];
            }
        }

        return ['mode' => 'channels', 'channels' => $channels];
    }

    /**
     * Pull the current price (integer VND) from a Shopee price_info block.
     */
    private function currentPrice(mixed $priceInfo): int
    {
        $first = is_array($priceInfo) ? (array) ($priceInfo[0] ?? []) : [];

        return (int) round((float) ($first['current_price'] ?? ($first['original_price'] ?? 0)));
    }

    private function normalizeStatus(string $rawStatus): string
    {
        return match (strtoupper($rawStatus)) {
            'NORMAL' => 'live',
            'UNLIST' => 'unlisted',
            'BANNED' => 'failed',
            'DELETED' => 'deleted',
            default => strtolower($rawStatus),
        };
    }
}
