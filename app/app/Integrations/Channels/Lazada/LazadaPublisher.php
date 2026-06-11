<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\Lazada;

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

/**
 * Lazada product-publishing connector (CreateProduct + taxonomy + media + status).
 *
 * Implements {@see ProductPublishingConnector}: validates a draft, builds the Lazada
 * CreateProduct XML payload ({@see LazadaProductPayload}), and talks to the Lazada Open
 * Platform via {@see LazadaClient::callRaw()} so it can inspect the envelope `code` itself
 * and raise a provider-agnostic {@see MarketplaceApiException} on error.
 *
 * Taxonomy/media/status reads are best-effort defensive mappings (`?? []` / `?? ''`) —
 * the doc-derived response shapes are verified against the Lazada sandbox later.
 */
final class LazadaPublisher implements ProductPublishingConnector
{
    public function __construct(
        private readonly LazadaClient $client,
        private readonly LazadaListingValidator $validator,
    ) {}

    public function createListing(AuthContext $auth, ListingDraftDTO $draft): ListingResultDTO
    {
        $errors = $this->validator->validate($draft);
        if ($errors !== []) {
            throw MarketplaceApiException::validation('lazada', $errors);
        }

        $payload = LazadaProductPayload::toXml($draft);
        $resp = $this->client->callRaw('/product/create', $auth, ['payload' => $payload]);

        if ((string) ($resp['code'] ?? '') !== '0') {
            throw MarketplaceApiException::fromLazada($resp);
        }

        $skuMap = [];
        foreach ((array) ($resp['data']['sku_list'] ?? []) as $sku) {
            if (! is_array($sku)) {
                continue;
            }
            $sellerSku = (string) ($sku['seller_sku'] ?? '');
            if ($sellerSku !== '') {
                $skuMap[$sellerSku] = (string) ($sku['sku_id'] ?? '');
            }
        }

        return new ListingResultDTO((string) ($resp['data']['item_id'] ?? ''), $skuMap, 'PENDING', $resp);
    }

    /** @return CategoryNodeDTO[] */
    public function getCategoryTree(AuthContext $auth, ?string $parentId = null): array
    {
        $data = $this->client->callRaw('/category/tree/get', $auth, [], 'GET');
        $tree = (array) ($data['data'] ?? []);

        $out = [];
        $this->flattenCategories($tree, null, $out);

        return $out;
    }

    /** @return ListingAttributeDTO[] */
    public function getCategoryAttributes(AuthContext $auth, string $categoryId): array
    {
        $data = $this->client->callRaw('/category/attributes/get', $auth, ['primary_category_id' => $categoryId], 'GET');

        $out = [];
        foreach ((array) ($data['data'] ?? []) as $attr) {
            if (! is_array($attr)) {
                continue;
            }
            $out[] = new ListingAttributeDTO(
                id: (string) ($attr['id'] ?? ($attr['name'] ?? '')),
                name: (string) ($attr['name'] ?? ''),
                required: (bool) ($attr['is_mandatory'] ?? false),
                isSaleProp: (bool) ($attr['is_sale_prop'] ?? false),
                inputType: (string) ($attr['input_type'] ?? ''),
                values: (array) ($attr['options'] ?? []),
                raw: $attr,
            );
        }

        return $out;
    }

    /** @return BrandDTO[] */
    public function getBrands(AuthContext $auth, string $categoryId): array
    {
        $data = $this->client->callRaw('/category/brands/query', $auth, ['startRow' => 0, 'pageSize' => 100], 'GET');

        $out = [];
        foreach ((array) ($data['data']['module'] ?? []) as $brand) {
            if (! is_array($brand)) {
                continue;
            }
            $out[] = new BrandDTO(
                id: (string) ($brand['brand_id'] ?? ($brand['id'] ?? '')),
                name: (string) ($brand['name'] ?? ''),
                mandatory: true,
                raw: $brand,
            );
        }

        return $out;
    }

    public function uploadMedia(AuthContext $auth, string $imageUrlOrPath, string $useCase = 'main'): MediaRefDTO
    {
        $resp = $this->client->callRaw('/image/migrate', $auth, ['url' => $imageUrlOrPath]);

        return new MediaRefDTO((string) ($resp['data']['image']['url'] ?? ''), 'cdn_url', $resp);
    }

    public function getListingStatus(AuthContext $auth, string $externalItemId): ListingStatusDTO
    {
        $data = $this->client->callRaw('/products/get', $auth, ['filter' => 'all'], 'GET');

        $products = (array) ($data['data']['products'] ?? []);
        $first = is_array($products[0] ?? null) ? $products[0] : [];
        $rawStatus = (string) ($first['status'] ?? ($first['Status'] ?? ''));

        return new ListingStatusDTO(
            externalItemId: $externalItemId,
            rawStatus: $rawStatus,
            normalized: $this->normalizeStatus($rawStatus),
            reason: null,
            raw: $data,
        );
    }

    /**
     * Recursively flatten a Lazada category tree into CategoryNodeDTO[]. Each node carries
     * `category_id` / `name` / `children[]`; a node is a leaf when it has no children.
     *
     * @param  array<int|string,mixed>  $nodes
     * @param  CategoryNodeDTO[]  $out
     */
    private function flattenCategories(array $nodes, ?string $parentId, array &$out): void
    {
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }
            $id = (string) ($node['category_id'] ?? '');
            $children = (array) ($node['children'] ?? []);
            $out[] = new CategoryNodeDTO(
                id: $id,
                parentId: $parentId,
                name: (string) ($node['name'] ?? ''),
                isLeaf: $children === [],
                raw: $node,
            );
            if ($children !== []) {
                $this->flattenCategories($children, $id, $out);
            }
        }
    }

    private function normalizeStatus(string $rawStatus): string
    {
        return match (strtoupper($rawStatus)) {
            'PENDING' => 'pending',
            'APPROVED' => 'live',
            'REJECTED' => 'failed',
            default => strtolower($rawStatus),
        };
    }
}
