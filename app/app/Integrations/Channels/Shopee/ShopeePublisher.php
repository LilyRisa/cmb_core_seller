<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use CMBcoreSeller\Integrations\Channels\Contracts\ProductPublishingConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\BrandDTO;
use CMBcoreSeller\Integrations\Channels\DTO\CategoryNodeDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingAttributeDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
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

        $resp = $this->client->shopPostEnvelope($auth, '/api/v2/product/add_item', [], ShopeeProductPayload::addItem($draft));
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
            $out[] = new CategoryNodeDTO(
                id: (string) ($node['category_id'] ?? ''),
                parentId: (string) ($node['parent_category_id'] ?? ''),
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
