<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\TikTok;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\Contracts\ProductPublishingConnector;
use CMBcoreSeller\Integrations\Channels\Contracts\PromotionConnector;
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
use CMBcoreSeller\Integrations\Channels\DTO\PromotionDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\PromotionResultDTO;
use CMBcoreSeller\Integrations\Channels\DTO\PromotionSyncDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\MarketplaceApiException;

/**
 * TikTok Shop product-publishing connector (Create Product 202309 + taxonomy + media + status).
 *
 * Implements {@see ProductPublishingConnector}: validates a draft
 * ({@see TikTokListingValidator}), builds the Create Product body
 * ({@see TikTokProductPayload}), and talks to the TikTok Shop Partner Open API via
 * {@see TikTokClient::requestRaw()} so it can inspect the envelope `code` itself and
 * raise a provider-agnostic {@see MarketplaceApiException} on error.
 *
 * Taxonomy/media/status reads use defensive mappings — response shapes are sourced from
 * the TikTok Shop Open Platform docs (category_version=v2) and verified against the
 * sandbox later.
 */
final class TikTokPublisher implements ProductPublishingConnector, PromotionConnector
{
    public function __construct(
        private readonly TikTokClient $client,
        private readonly TikTokListingValidator $validator,
    ) {}

    public function createListing(AuthContext $auth, ListingDraftDTO $draft): ListingResultDTO
    {
        $errors = $this->validator->validate($draft);
        if ($errors !== []) {
            throw MarketplaceApiException::validation('tiktok', $errors);
        }

        // Video do job chuẩn bị trước (upload) và truyền qua DTO.videoExternalId.
        $resp = $this->client->requestRaw('POST', '/product/202309/products', $auth, [], TikTokProductPayload::toBody($draft, 'LISTING', $draft->videoExternalId));
        if (($resp['code'] ?? -1) !== 0) {
            throw MarketplaceApiException::fromTikTok($resp);
        }

        $skuMap = [];
        foreach ((array) ($resp['data']['skus'] ?? []) as $sku) {
            if (! is_array($sku)) {
                continue;
            }
            $sellerSku = (string) ($sku['seller_sku'] ?? '');
            if ($sellerSku !== '') {
                $skuMap[$sellerSku] = (string) ($sku['id'] ?? '');
            }
        }

        return new ListingResultDTO((string) ($resp['data']['product_id'] ?? ''), $skuMap, 'PENDING', $resp);
    }

    /** @return CategoryNodeDTO[] */
    public function getCategoryTree(AuthContext $auth, ?string $parentId = null): array
    {
        $data = $this->client->request('GET', '/product/202309/categories', $auth, ['category_version' => 'v2']);

        $out = [];
        foreach ((array) ($data['categories'] ?? []) as $node) {
            if (! is_array($node)) {
                continue;
            }
            $parent = (string) ($node['parent_id'] ?? '');
            $out[] = new CategoryNodeDTO(
                id: (string) ($node['id'] ?? ''),
                parentId: $parent !== '' ? $parent : null,
                name: (string) ($node['local_name'] ?? ''),
                isLeaf: (bool) ($node['is_leaf'] ?? false),
                raw: $node,
            );
        }

        return $out;
    }

    /** @return ListingAttributeDTO[] */
    public function getCategoryAttributes(AuthContext $auth, string $categoryId): array
    {
        $data = $this->client->request('GET', "/product/202309/categories/{$categoryId}/attributes", $auth, ['category_version' => 'v2']);

        $out = [];
        foreach ((array) ($data['attributes'] ?? []) as $attr) {
            if (! is_array($attr)) {
                continue;
            }
            // Doc has a typo: the field is documented as both `is_required` and `is_requried`.
            $required = (bool) ($attr['is_required'] ?? ($attr['is_requried'] ?? false));

            // `type` = phân lớp thuộc tính (SALES_PROPERTY = phân loại biến thể / PRODUCT_PROPERTY),
            // KHÔNG phải kiểu nhập. Kiểu nhập suy ra: có values? đa chọn? định dạng số tự do?
            $values = [];
            foreach ((array) ($attr['values'] ?? []) as $v) {
                if (! is_array($v)) {
                    continue;
                }
                $values[] = ['id' => (string) ($v['id'] ?? ''), 'name' => (string) ($v['name'] ?? '')];
            }
            if ($values !== []) {
                $inputType = ((bool) ($attr['is_multiple_selection'] ?? false))
                    ? ListingAttributeDTO::INPUT_MULTI_SELECT
                    : ListingAttributeDTO::INPUT_SELECT;
            } elseif (($attr['value_data_format'] ?? '') === 'POSITIVE_INT_OR_DECIMAL') {
                $inputType = ListingAttributeDTO::INPUT_NUMBER;
            } else {
                $inputType = ListingAttributeDTO::INPUT_TEXT;
            }

            $out[] = new ListingAttributeDTO(
                id: (string) ($attr['id'] ?? ''),
                name: (string) ($attr['name'] ?? ''),
                required: $required,
                isSaleProp: (string) ($attr['type'] ?? '') === 'SALES_PROPERTY',
                inputType: $inputType,
                values: $values,
                raw: $attr,
            );
        }

        return $out;
    }

    /** @return BrandDTO[] */
    public function getBrands(AuthContext $auth, string $categoryId): array
    {
        $out = [];
        $pageToken = '';

        // page_size is REQUIRED by /product/202309/brands (valid range 1-100); paginate
        // through next_page_token so categories with >100 brands return the full list.
        do {
            $query = ['category_id' => $categoryId, 'page_size' => 100];
            if ($pageToken !== '') {
                $query['page_token'] = $pageToken;
            }

            $data = $this->client->request('GET', '/product/202309/brands', $auth, $query);

            foreach ((array) ($data['brands'] ?? []) as $brand) {
                if (! is_array($brand)) {
                    continue;
                }
                $out[] = new BrandDTO(
                    id: (string) ($brand['id'] ?? ''),
                    name: (string) ($brand['name'] ?? ''),
                    mandatory: false,
                    raw: $brand,
                );
            }

            $pageToken = (string) ($data['next_page_token'] ?? '');
        } while ($pageToken !== '');

        return $out;
    }

    /** Bắt đầu (và hoàn tất) upload video → trả file id. TikTok dùng được ngay (không transcode async). */
    public function startVideoUpload(AuthContext $auth, ListingDraftDTO $draft): string
    {
        return $this->uploadVideo($auth, (string) $draft->videoRef);
    }

    /** TikTok không có bước transcode bất đồng bộ — id dùng được ngay. */
    public function videoUploadStatus(AuthContext $auth, string $videoId): string
    {
        return 'ready';
    }

    /**
     * Upload a product video (non-image file) to TikTok Shop and return its file id,
     * used as `video.id` in create/edit product. Endpoint: upload-product-file-202309.
     */
    public function uploadVideo(AuthContext $auth, string $videoUrlOrPath): string
    {
        $name = basename((string) (parse_url($videoUrlOrPath, PHP_URL_PATH) ?: 'video.mp4')) ?: 'video.mp4';
        $resp = $this->client->uploadMultipart('/product/202309/files/upload', $auth, 'data', $videoUrlOrPath, ['name' => $name]);
        if (($resp['code'] ?? -1) !== 0) {
            throw MarketplaceApiException::fromTikTok($resp);
        }

        return (string) ($resp['data']['id'] ?? '');
    }

    public function uploadMedia(AuthContext $auth, string $imageUrlOrPath, string $useCase = 'main'): MediaRefDTO
    {
        $resp = $this->client->uploadMultipart(
            '/product/202309/images/upload',
            $auth,
            'data',
            $imageUrlOrPath,
            ['use_case' => $useCase === 'main' ? 'MAIN_IMAGE' : 'DESCRIPTION_IMAGE'],
        );

        if (($resp['code'] ?? -1) !== 0) {
            throw MarketplaceApiException::fromTikTok($resp);
        }

        return new MediaRefDTO((string) ($resp['data']['uri'] ?? ''), 'uri', $resp);
    }

    public function getListingStatus(AuthContext $auth, string $externalItemId): ListingStatusDTO
    {
        $data = $this->client->request('GET', "/product/202309/products/{$externalItemId}", $auth);

        $rawStatus = (string) ($data['status'] ?? '');

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
        $data = $this->client->request('GET', "/product/202309/products/{$externalProductId}", $auth);

        $images = [];
        foreach ((array) ($data['main_images'] ?? []) as $img) {
            if (! is_array($img)) {
                continue;
            }
            $url = (string) (($img['url_list'][0] ?? '') ?: ($img['uri'] ?? ''));
            if ($url !== '') {
                $images[] = $url;
            }
        }

        $skus = [];
        foreach ((array) ($data['skus'] ?? []) as $sku) {
            if (! is_array($sku)) {
                continue;
            }
            $price = (array) ($sku['price'] ?? []);
            $skus[] = [
                'external_sku_id' => (string) ($sku['id'] ?? ''),
                'seller_sku' => (string) ($sku['seller_sku'] ?? ''),
                'price' => (int) round((float) ($price['sale_price'] ?? ($price['tax_exclusive_price'] ?? 0))),
            ];
        }

        // Category leaf = phần tử cuối của category_chains (is_leaf).
        $chains = (array) ($data['category_chains'] ?? []);
        $leaf = '';
        foreach ($chains as $c) {
            if (is_array($c) && ($c['is_leaf'] ?? false)) {
                $leaf = (string) ($c['id'] ?? '');
            }
        }
        if ($leaf === '' && $chains !== []) {
            $last = end($chains);
            $leaf = is_array($last) ? (string) ($last['id'] ?? '') : '';
        }

        return new ListingDetailDTO(
            externalProductId: $externalProductId,
            title: (string) ($data['title'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            images: $images,
            skus: $skus,
            raw: $data,
            categoryId: $leaf !== '' ? $leaf : null,
            brandId: ($b = (string) ($data['brand']['id'] ?? '')) !== '' ? $b : null,
            attributes: is_array($data['product_attributes'] ?? null) ? $data['product_attributes'] : [],
        );
    }

    public function updateListing(AuthContext $auth, string $externalProductId, ListingEditDTO $edit): ListingResultDTO
    {
        if ($edit->hasInfo()) {
            $body = [];
            if ($edit->title !== null) {
                $body['title'] = $edit->title;
            }
            if ($edit->description !== null) {
                $body['description'] = $edit->description;
            }
            if ($edit->images !== null) {
                // TikTok references images by uploaded uri.
                $body['main_images'] = array_map(fn ($u) => ['uri' => $this->uploadMedia($auth, (string) $u)->ref], $edit->images);
            }
            $resp = $this->client->requestRaw('POST', "/product/202309/products/{$externalProductId}/partial_edit", $auth, [], $body);
            if (($resp['code'] ?? -1) !== 0) {
                throw MarketplaceApiException::fromTikTok($resp);
            }
        }

        if ($edit->hasPrices()) {
            $skus = array_map(fn ($p) => [
                'id' => (string) $p['external_sku_id'],
                'price' => ['amount' => (string) (int) $p['price'], 'currency' => 'VND'],
            ], $edit->prices ?? []);

            $resp = $this->client->requestRaw('POST', "/product/202309/products/{$externalProductId}/prices/update", $auth, [], ['skus' => $skus]);
            if (($resp['code'] ?? -1) !== 0) {
                throw MarketplaceApiException::fromTikTok($resp);
            }
        }

        return new ListingResultDTO($externalProductId, [], 'live');
    }

    public function getShippingOptions(AuthContext $auth): array
    {
        $wh = $this->client->request('GET', '/logistics/202309/warehouses', $auth);
        $warehouses = [];
        foreach ((array) ($wh['warehouses'] ?? []) as $w) {
            if (! is_array($w)) {
                continue;
            }
            $warehouses[] = [
                'id' => (string) ($w['id'] ?? ''),
                'name' => (string) ($w['name'] ?? ''),
                'is_default' => (bool) ($w['is_default'] ?? false),
            ];
        }

        // Delivery options gắn theo warehouse — lấy theo kho mặc định (hoặc kho đầu).
        $primary = null;
        foreach ($warehouses as $w) {
            if ($w['is_default']) {
                $primary = $w['id'];
                break;
            }
        }
        $primary ??= $warehouses[0]['id'] ?? null;

        $deliveryOptions = [];
        if ($primary !== null && $primary !== '') {
            $do = $this->client->request('GET', "/logistics/202309/warehouses/{$primary}/delivery_options", $auth, ['scope' => 'PRODUCT']);
            foreach ((array) ($do['delivery_options'] ?? []) as $d) {
                if (! is_array($d)) {
                    continue;
                }
                $deliveryOptions[] = ['id' => (string) ($d['id'] ?? ''), 'name' => (string) ($d['name'] ?? '')];
            }
        }

        return ['mode' => 'warehouse_delivery', 'warehouses' => $warehouses, 'delivery_options' => $deliveryOptions];
    }

    private function normalizeStatus(string $rawStatus): string
    {
        return match (strtoupper($rawStatus)) {
            'DRAFT' => 'draft',
            'PENDING' => 'pending',
            'ACTIVATE' => 'live',
            'FAILED' => 'failed',
            default => strtolower($rawStatus),
        };
    }

    public function promotionCapabilities(): array
    {
        return [
            'max_items_per_call' => 300,
            'supports_percent' => true,
            'has_program_object' => true,
            'supports_time_of_day' => true,
        ];
    }

    public function createPromotion(AuthContext $auth, PromotionDraftDTO $draft): PromotionResultDTO
    {
        $body = [
            'title' => $draft->title,
            'activity_type' => $draft->discountType === 'percent' ? 'DIRECT_DISCOUNT' : 'FIXED_PRICE',
            'product_level' => 'VARIATION',
            'duration_type' => 'NORMAL',
            'begin_time' => $draft->startAt->getTimestamp(),
            'end_time' => $draft->endAt->getTimestamp(),
        ];

        $data = $this->client->post('/promotion/202309/activities', $auth, $body);

        return new PromotionResultDTO(
            (string) ($data['activity_id'] ?? ''),
            (string) ($data['status'] ?? ''),
        );
    }

    public function putPromotionItems(AuthContext $auth, ?string $externalPromotionId, PromotionDraftDTO $campaign, array $itemsBatch): void
    {
        if ($externalPromotionId === null || $externalPromotionId === '') {
            return;
        }

        // Gom theo external product id → mỗi product có nhiều sku.
        $byProduct = [];
        foreach ($itemsBatch as $item) {
            $sku = ['id' => $item->externalSkuId];
            if ($campaign->discountType === 'percent') {
                $sku['discount'] = (string) $item->discountValue;
            } else {
                $sku['activity_price_amount'] = (string) $item->salePrice;
            }
            $byProduct[$item->externalProductId][] = $sku;
        }

        $products = [];
        foreach ($byProduct as $productId => $skus) {
            $products[] = ['id' => (string) $productId, 'skus' => $skus];
        }

        $this->client->request('PUT', "/promotion/202309/activities/{$externalPromotionId}/products", $auth, [], ['products' => $products]);
    }

    public function endPromotion(AuthContext $auth, ?string $externalPromotionId, PromotionDraftDTO $campaign, array $items = []): void
    {
        if ($externalPromotionId === null || $externalPromotionId === '') {
            return;
        }

        $this->client->request('DELETE', "/promotion/202309/activities/{$externalPromotionId}", $auth);
    }

    public function listPromotions(AuthContext $auth): array
    {
        $out = [];

        try {
            $activities = [];
            // TikTok lọc theo 1 trạng thái/lần — gộp ongoing + chưa bắt đầu. Liệt kê hoạt động qua POST
            // /activities/search (doc 202309 — GET /activities trả HTTP 405). Body: {status, page_size}.
            foreach (['ONGOING', 'NOT_START'] as $status) {
                $data = $this->client->request('POST', '/promotion/202309/activities/search', $auth, [], ['status' => $status, 'page_size' => 100]);
                foreach ((array) ($data['activities'] ?? []) as $a) {
                    if (! is_array($a)) {
                        continue;
                    }
                    $id = (string) ($a['id'] ?? ($a['activity_id'] ?? ''));
                    if ($id !== '') {
                        $activities[$id] = $a;
                    }
                }
            }

            foreach (array_slice($activities, 0, 50, true) as $id => $a) {
                try {
                    $detail = $this->client->request('GET', "/promotion/202309/activities/{$id}", $auth);
                } catch (\Throwable) {
                    $detail = $a;
                }

                $rawStatus = strtoupper((string) ($detail['status'] ?? ($a['status'] ?? '')));
                $normalized = match ($rawStatus) {
                    'ONGOING' => 'ongoing',
                    'NOT_START' => 'upcoming',
                    default => 'ended',
                };

                $begin = $detail['begin_time'] ?? ($a['begin_time'] ?? null);
                $end = $detail['end_time'] ?? ($a['end_time'] ?? null);

                $items = [];
                foreach ((array) ($detail['products'] ?? []) as $product) {
                    if (! is_array($product)) {
                        continue;
                    }
                    $productId = (string) ($product['id'] ?? '');
                    $skus = (array) ($product['skus'] ?? []);
                    // Khuyến mãi cấp SẢN PHẨM (product_level=PRODUCT) KHÔNG có mảng skus ⇒ khóa theo product_id.
                    if ($skus === []) {
                        $items[] = [
                            'external_product_id' => $productId,
                            'external_sku_id' => '',
                            'sale_price' => TikTokMappers::money(data_get($product, 'activity_price.amount')),
                        ];

                        continue;
                    }
                    foreach ($skus as $sku) {
                        if (! is_array($sku)) {
                            continue;
                        }
                        $items[] = [
                            'external_product_id' => $productId,
                            'external_sku_id' => (string) ($sku['id'] ?? ''),
                            // Giá KM nằm ở activity_price.amount (chuỗi) cho FIXED_PRICE/FLASHSALE; DIRECT_DISCOUNT
                            // chỉ có % (trường discount) ⇒ không có giá tuyệt đối ⇒ 0 (vẫn tô xám "bận").
                            'sale_price' => TikTokMappers::money(data_get($sku, 'activity_price.amount')),
                        ];
                    }
                }

                $out[] = new PromotionSyncDTO(
                    externalPromotionId: (string) $id,
                    title: (string) ($detail['title'] ?? ($a['title'] ?? '')),
                    status: $normalized,
                    startAt: $begin !== null ? CarbonImmutable::createFromTimestamp((int) $begin) : null,
                    endAt: $end !== null ? CarbonImmutable::createFromTimestamp((int) $end) : null,
                    items: $items,
                );
            }
        } catch (\Throwable) {
            return [];
        }

        return $out;
    }
}
