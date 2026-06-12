<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\Lazada;

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

    public function getListingDetail(AuthContext $auth, string $externalProductId): ListingDetailDTO
    {
        $data = $this->client->callRaw('/product/item/get', $auth, ['item_id' => $externalProductId], 'GET');
        if ((string) ($data['code'] ?? '') !== '0') {
            throw MarketplaceApiException::fromLazada($data);
        }

        $item = (array) ($data['data'] ?? []);
        $attrs = (array) ($item['attributes'] ?? []);

        $skus = [];
        foreach ((array) ($item['skus'] ?? []) as $sku) {
            if (! is_array($sku)) {
                continue;
            }
            $skus[] = [
                'external_sku_id' => (string) ($sku['SkuId'] ?? ''),
                'seller_sku' => (string) ($sku['SellerSku'] ?? ''),
                'price' => (int) round((float) ($sku['price'] ?? 0)),
            ];
        }

        return new ListingDetailDTO(
            externalProductId: $externalProductId,
            title: (string) ($attrs['name'] ?? ''),
            description: (string) ($attrs['description'] ?? ''),
            images: array_values(array_filter(array_map('strval', (array) ($item['images'] ?? [])))),
            skus: $skus,
            raw: $data,
        );
    }

    public function updateListing(AuthContext $auth, string $externalProductId, ListingEditDTO $edit): ListingResultDTO
    {
        if ($edit->hasInfo()) {
            // Lazada attaches images at the SKU level — resolve current SkuIds, then
            // migrate the source URLs to Lazada CDN and apply to every SKU.
            $skuIds = array_values(array_filter(array_map(
                fn ($s) => (string) $s['external_sku_id'],
                $this->getListingDetail($auth, $externalProductId)->skus,
            )));
            $imageUrls = array_map(fn ($u) => $this->uploadMedia($auth, (string) $u)->ref, $edit->images ?? []);

            $resp = $this->client->callRaw('/product/update', $auth, [
                'payload' => $this->buildUpdateXml($externalProductId, $edit, $skuIds, $imageUrls),
            ]);
            if ((string) ($resp['code'] ?? '') !== '0') {
                throw MarketplaceApiException::fromLazada($resp);
            }
        }

        if ($edit->hasPrices()) {
            $resp = $this->client->callRaw('/product/price_quantity/update', $auth, [
                'payload' => $this->buildPriceXml($externalProductId, $edit->prices ?? []),
            ]);
            if ((string) ($resp['code'] ?? '') !== '0') {
                throw MarketplaceApiException::fromLazada($resp);
            }
        }

        return new ListingResultDTO($externalProductId, [], 'live');
    }

    /**
     * Build the `/product/update` XML for title/description + (optionally) images.
     * Images, when given, are written to every SKU (Lazada has no product-level image slot).
     *
     * @param  string[]  $skuIds
     * @param  string[]  $imageUrls
     */
    private function buildUpdateXml(string $itemId, ListingEditDTO $edit, array $skuIds, array $imageUrls): string
    {
        $esc = fn (string $s) => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $attrs = '';
        if ($edit->title !== null) {
            $attrs .= '<name>'.$esc($edit->title).'</name>';
        }
        if ($edit->description !== null) {
            $attrs .= '<description>'.$esc($edit->description).'</description>';
        }

        $skusXml = '';
        if ($imageUrls !== [] && $skuIds !== []) {
            $imagesXml = '<Images>'.implode('', array_map(fn ($u) => '<Image>'.$esc($u).'</Image>', $imageUrls)).'</Images>';
            foreach ($skuIds as $skuId) {
                $skusXml .= '<Sku><SkuId>'.$esc($skuId).'</SkuId>'.$imagesXml.'</Sku>';
            }
        }

        return '<Request><Product><ItemId>'.$esc($itemId).'</ItemId>'
            .($attrs !== '' ? '<Attributes>'.$attrs.'</Attributes>' : '')
            .($skusXml !== '' ? '<Skus>'.$skusXml.'</Skus>' : '')
            .'</Product></Request>';
    }

    /**
     * Build the `/product/price_quantity/update` XML for per-SKU price.
     *
     * @param  array<int,array{external_sku_id:string,price:int}>  $prices
     */
    private function buildPriceXml(string $itemId, array $prices): string
    {
        $esc = fn (string $s) => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $skus = '';
        foreach ($prices as $p) {
            $skus .= '<Sku><ItemId>'.$esc($itemId).'</ItemId>'
                .'<SkuId>'.$esc((string) $p['external_sku_id']).'</SkuId>'
                .'<Price>'.(int) $p['price'].'</Price></Sku>';
        }

        return '<Request><Product><Skus>'.$skus.'</Skus></Product></Request>';
    }

    public function getShippingOptions(AuthContext $auth): array
    {
        // Lazada không chọn kênh lúc tạo SP: vận chuyển dựa trên KL/kích thước kiện ở
        // cấp SKU; tuỳ chọn `delivery_option_sof` (giao bởi người bán). Doc /product/create.
        return [
            'mode' => 'package',
            'notes' => 'Lazada tính vận chuyển theo khối lượng/kích thước kiện (nhập ở từng SKU). Bật "Giao bởi người bán (SOF)" nếu tự giao.',
        ];
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
