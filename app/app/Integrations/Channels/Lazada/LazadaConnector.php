<?php

namespace CMBcoreSeller\Integrations\Channels\Lazada;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\Contracts\ChannelConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\OrderDTO;
use CMBcoreSeller\Integrations\Channels\DTO\Page;
use CMBcoreSeller\Integrations\Channels\DTO\ShopInfoDTO;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Channels\DTO\WebhookEventDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;

/**
 * Lazada Open Platform connector (Vietnam — api.lazada.vn). Orders + auth + listings +
 * stock push + webhook; Lazada's RTS/shipping (arrange shipment, label) lands with the
 * Phase-4 fulfillment work (declared unsupported for now). All Lazada specifics — signing,
 * status & message maps, endpoints — live under app/Integrations/Channels/Lazada/ and
 * config('integrations.lazada'). See docs/04-channels/lazada.md, SPEC 0008.
 */
class LazadaConnector implements ChannelConnector
{
    public function __construct(
        protected LazadaClient $client,
        protected LazadaWebhookVerifier $webhook,
    ) {}

    public function code(): string
    {
        return 'lazada';
    }

    public function displayName(): string
    {
        return 'Lazada';
    }

    public function capabilities(): array
    {
        return [
            'orders.fetch' => true,
            'orders.webhook' => true,
            'orders.confirm' => false,        // Phase 4 fulfillment (RTS / pack)
            // "Luồng A" — Lazada: re-fetch order detail để lấy tracking sàn đã cấp; lấy phiếu giao hàng qua
            // `/order/document/get` (doc_type=shippingLabel). Bật mặc định, tắt bằng
            // INTEGRATIONS_LAZADA_FULFILLMENT=false nếu shop chưa được cấp permission "Fulfillment" trên Open Platform.
            'shipping.arrange' => (bool) config('integrations.lazada.fulfillment_enabled', true),
            'shipping.document' => (bool) config('integrations.lazada.fulfillment_enabled', true),
            'shipping.tracking' => false,     // Phase 4 fulfillment
            'listings.fetch' => true,
            'listings.publish' => false,      // Phase 5
            'listings.updateStock' => true,
            'listings.updatePrice' => false,  // Phase 5
            // Đối soát/Settlement — Phase 6.2. Bật bằng INTEGRATIONS_LAZADA_FINANCE=true sau khi đối chiếu shape
            // `/finance/transaction/details/get` với sandbox thật. SPEC 0016.
            'finance.settlements' => (bool) config('integrations.lazada.finance_enabled', false),
            'returns.fetch' => false,         // Phase 7
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    // --- OAuth / connection --------------------------------------------------

    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        // Truyền `redirect_uri` của route Laravel (`route('oauth.callback', ...)`) — phải khớp đúng URL
        // Callback đã đăng ký trong app console của Lazada. Nếu lệch (host khác / scheme khác / có thêm
        // dấu /), Lazada báo "tham số không hợp lệ" ngay ở bước ủy quyền.
        return $this->client->authorizeUrl($state, isset($opts['redirect_uri']) ? (string) $opts['redirect_uri'] : null);
    }

    public function exchangeCodeForToken(string $code): TokenDTO
    {
        return LazadaMappers::token($this->client->getAccessToken($code));
    }

    public function refreshToken(string $refreshToken): TokenDTO
    {
        return LazadaMappers::token($this->client->refreshAccessToken($refreshToken));
    }

    public function fetchShopInfo(AuthContext $auth): ShopInfoDTO
    {
        // /seller/get (api.lazada.vn/rest, signed, có access_token) trả `data.{ seller_id, name, short_code,
        // location, name_company, ... }` — đây là nguồn chính xác nhất. Trong vài trường hợp (token cấp cho
        // multi-country / tài khoản nhánh) `/seller/get` thiếu `seller_id` — dùng `country_user_info[_list]`
        // từ token làm dự phòng (đã được `ChannelConnectionService` thread qua `extra.token_raw`).
        $seller = $this->client->get('/seller/get', $auth);
        $tokenRaw = (array) ($auth->extra['token_raw'] ?? []);

        return LazadaMappers::shopInfo($seller, $tokenRaw);
    }

    public function registerWebhooks(AuthContext $auth): void
    {
        // Lazada push subscriptions are configured in the Open Platform app console
        // ("App Push" → message types), not via an API call per shop. Nothing to do here.
        Log::info('lazada.webhook.register_noop', ['shop' => $auth->externalShopId]);
    }

    public function revoke(AuthContext $auth): void
    {
        // The seller revokes from their Lazada Seller Center; disconnecting locally stops sync.
        Log::info('lazada.revoke', ['shop' => $auth->externalShopId]);
    }

    // --- Orders --------------------------------------------------------------

    public function fetchOrders(AuthContext $auth, array $query = []): Page
    {
        $limit = min(100, max(1, (int) ($query['pageSize'] ?? 50)));
        $offset = (int) ($query['cursor'] ?? 0);
        $params = [
            'sort_by' => 'updated_at',
            'sort_direction' => 'ASC',
            'offset' => $offset,
            'limit' => $limit,
        ];
        if (! empty($query['updatedFrom'])) {
            $params['update_after'] = $query['updatedFrom']->toIso8601String();
        } else {
            // Lazada requires a window; default to the last 30 days if the caller gives none.
            $params['update_after'] = CarbonImmutable::now()->subDays(30)->toIso8601String();
        }
        if (! empty($query['updatedTo'])) {
            $params['update_before'] = $query['updatedTo']->toIso8601String();
        }
        if (! empty($query['statuses']) && count($query['statuses']) === 1) {
            $params['status'] = (string) $query['statuses'][0];
        }

        $data = $this->client->get('/orders/get', $auth, $params);
        $rawOrders = (array) ($data['orders'] ?? []);
        $items = $this->fetchItemsForOrders($auth, array_values(array_filter(array_map(fn ($o) => (string) ($o['order_id'] ?? ''), $rawOrders), fn ($id) => $id !== '')));

        $orders = array_values(array_map(
            fn (array $o) => LazadaMappers::order($o, $items[(string) ($o['order_id'] ?? '')] ?? []),
            $rawOrders,
        ));

        $count = (int) ($data['count'] ?? $data['countTotal'] ?? 0);
        $hasMore = count($rawOrders) === $limit && ($count === 0 || ($offset + $limit) < $count);

        return new Page(items: $orders, nextCursor: $hasMore ? (string) ($offset + $limit) : null, hasMore: $hasMore);
    }

    public function fetchOrderDetail(AuthContext $auth, string $externalOrderId): OrderDTO
    {
        $order = $this->client->get('/order/get', $auth, ['order_id' => $externalOrderId]);
        // /order/get returns the order object directly in `data`; some sandbox tenants nest it under `orders[0]`.
        if (isset($order['orders']) && is_array($order['orders'])) {
            $order = (array) ($order['orders'][0] ?? $order);
        }
        if (($order['order_id'] ?? null) === null) {
            throw new LazadaApiException("Lazada order {$externalOrderId} not found.", 'OrderNotFound', 404);
        }
        $items = (array) $this->client->get('/order/items/get', $auth, ['order_id' => $externalOrderId]);
        // /order/items/get returns items array directly in `data`.
        $itemList = array_values(array_filter($items, 'is_array'));

        return LazadaMappers::order($order, $itemList);
    }

    /**
     * Batch-fetch items for a page of orders. Lazada's /orders/get doesn't include line items;
     * /orders/items/get?order_ids=[...] returns data[] each {order_id, order_items[]}.
     *
     * @param  list<string>  $orderIds
     * @return array<string, list<array<string,mixed>>> keyed by order_id
     */
    private function fetchItemsForOrders(AuthContext $auth, array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }
        $out = [];
        foreach (array_chunk($orderIds, 50) as $chunk) {
            try {
                $data = $this->client->get('/orders/items/get', $auth, ['order_ids' => json_encode(array_map('intval', $chunk))]);
            } catch (\Throwable $e) {
                Log::info('lazada.orders.items_batch_failed', ['count' => count($chunk), 'error' => class_basename($e)]);

                continue;
            }
            foreach ((array) $data as $row) {
                $oid = (string) (data_get($row, 'order_id') ?? '');
                if ($oid !== '') {
                    $out[$oid] = array_values((array) data_get($row, 'order_items', []));
                }
            }
        }

        return $out;
    }

    public function parseWebhook(Request $request): WebhookEventDTO
    {
        return $this->webhook->parse($request);
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        return $this->webhook->verify($request);
    }

    public function mapStatus(string $rawStatus, array $rawOrder = []): StandardOrderStatus
    {
        // Caller passes either an already-collapsed order-level status or one of Lazada's raw item statuses.
        if (! empty($rawOrder['statuses']) && is_array($rawOrder['statuses'])) {
            $rawStatus = LazadaStatusMap::collapse(array_map('strval', $rawOrder['statuses']));
        }

        return LazadaStatusMap::toStandard($rawStatus);
    }

    // --- Listings / Inventory ------------------------------------------------

    public function fetchListings(AuthContext $auth, array $query = []): Page
    {
        $limit = min(50, max(1, (int) ($query['pageSize'] ?? 50)));
        $offset = (int) ($query['cursor'] ?? 0);
        $data = $this->client->get('/products/get', $auth, ['filter' => 'all', 'offset' => $offset, 'limit' => $limit]);

        $items = [];
        foreach ((array) ($data['products'] ?? []) as $product) {
            foreach (LazadaMappers::listings((array) $product) as $listing) {
                $items[] = $listing;
            }
        }
        $total = (int) ($data['total_products'] ?? 0);
        $hasMore = count((array) ($data['products'] ?? [])) === $limit && ($total === 0 || ($offset + $limit) < $total);

        return new Page(items: $items, nextCursor: $hasMore ? (string) ($offset + $limit) : null, hasMore: $hasMore);
    }

    /**
     * Push the available stock of one Lazada SKU. Lazada's UpdatePriceQuantity takes a
     * `payload` param (XML in the legacy API, JSON in newer). Kept config-able via
     * `integrations.lazada.endpoints.update_stock` + `update_stock_format` ('json'|'xml').
     *
     * @param  array{external_product_id?:string|null,seller_sku?:string|null}  $context
     */
    public function updateStock(AuthContext $auth, string $externalSkuId, int $available, array $context = []): void
    {
        $path = (string) (config('integrations.lazada.endpoints.update_stock') ?? '/product/price_quantity/update');
        $sellerSku = $context['seller_sku'] ?? $externalSkuId;
        $itemId = $context['external_product_id'] ?? null;
        $format = strtolower((string) (config('integrations.lazada.endpoints.update_stock_format') ?? 'json'));

        if ($format === 'xml') {
            $itemXml = $itemId !== null ? '<ItemId>'.htmlspecialchars((string) $itemId).'</ItemId>' : '';
            $payload = '<Request><Product><Skus><Sku>'.$itemXml
                .'<SellerSku>'.htmlspecialchars((string) $sellerSku).'</SellerSku>'
                .'<Quantity>'.$available.'</Quantity></Sku></Skus></Product></Request>';
            $this->client->post($path, $auth, ['payload' => $payload]);

            return;
        }

        $sku = array_filter(['ItemId' => $itemId, 'SellerSku' => (string) $sellerSku, 'Quantity' => $available], fn ($v) => $v !== null);
        $this->client->post($path, $auth, ['payload' => json_encode(['Request' => ['Product' => ['Skus' => ['Sku' => [$sku]]]]], JSON_UNESCAPED_SLASHES)]);
    }

    // --- Fulfillment (Phase 4 — pack → RTS → AWB) ----------------------------

    /**
     * Cache `/shipment/providers/get` trong cùng request — Lazada throttle khá chặt cho endpoint này
     * (~1 call/s/shop), và provider list hiếm khi đổi trong vòng 1 thao tác user.
     *
     * @var array<int, list<array<string,mixed>>>
     */
    private array $providerCache = [];

    /**
     * Lazada "luồng A" — đẩy đơn từ `pending` → `ready_to_ship` trên Lazada & lấy tracking thật:
     *
     *   1. `fetchOrderDetail` để xem item-level status (idempotent: đã `packed`/`ready_to_ship`/`shipped`
     *       ⇒ trả tracking ngay, KHÔNG pack/rts lại — chống double-arrange).
     *   2. Resolve `shipment_provider`: `$params['shipment_provider']` → config
     *       `default_shipment_provider` → `/shipment/providers/get` (pick `is_default=true`/đầu).
     *   3. `POST /order/pack` (`delivery_type=dropship`, `shipment_provider`, `order_items=[id,...]`) ⇒
     *       Lazada cấp `tracking_number` + `package_id`.
     *   4. `POST /order/rts` (`tracking_number` từ pack, `order_item_ids=[id,...]`) ⇒ Lazada chuyển sang
     *       `ready_to_ship`. **Bắt buộc** — bỏ qua thì AWB không hợp lệ & seller bị phạt.
     *   5. Trả `tracking_no` + `carrier` + `package_id` + `raw_status='ready_to_ship'`.
     *
     * Mode `LAZADA_FULFILLMENT_MODE=refetch_only` (legacy): bỏ bước 2–4, chỉ re-fetch — cho shop không
     * có permission "Fulfillment" pack thủ công ngoài Seller Center.
     *
     * Lỗi ở bất kỳ bước nào ⇒ ném `LazadaApiException` lên — `ShipmentService::arrangeOnChannel` catch &
     * gắn cờ `has_issue` để user "Nhận phiếu giao hàng" thử lại sau.
     *
     * @return array{tracking_no:?string,carrier:?string,raw_status:?string,package_id:?string}
     */
    public function arrangeShipment(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        if (! config('integrations.lazada.fulfillment_enabled')) {
            throw UnsupportedOperation::for($this->code(), 'arrangeShipment');
        }

        // (1) idempotent re-fetch — đã có tracking thì khỏi pack/rts lại
        $existing = $this->extractExistingShipment($auth, $externalOrderId, $params);
        if ($existing !== null) {
            return $existing;
        }

        $mode = strtolower((string) (config('integrations.lazada.fulfillment_mode') ?? 'auto'));
        if ($mode !== 'auto') {
            // refetch_only: shop tự pack ngoài app — chờ tracking xuất hiện ở re-fetch sau
            return ['tracking_no' => null, 'carrier' => null, 'raw_status' => null, 'package_id' => null];
        }

        // (2) resolve order_item_ids + shipment_provider + delivery_type
        $itemIds = $this->resolveOrderItemIds($auth, $externalOrderId, $params);
        if ($itemIds === []) {
            throw new LazadaApiException(
                "Lazada arrange shipment: không tìm thấy order_item_id nào cho đơn {$externalOrderId}. Hãy đồng bộ lại đơn.",
                'NoOrderItems', 422,
            );
        }
        $deliveryType = (string) ($params['delivery_type'] ?? config('integrations.lazada.default_delivery_type') ?? 'dropship');
        $shipmentProvider = (string) ($params['shipment_provider']
            ?? config('integrations.lazada.default_shipment_provider')
            ?? $this->pickDefaultShipmentProvider($auth, $deliveryType));
        if ($shipmentProvider === '') {
            throw new LazadaApiException(
                'Lazada arrange shipment: chưa resolve được shipment_provider. Đặt LAZADA_DEFAULT_SHIPMENT_PROVIDER hoặc gọi /shipment/providers/get.',
                'NoShipmentProvider', 422,
            );
        }

        // (3) /order/pack — Lazada gán tracking
        $packPath = (string) (config('integrations.lazada.endpoints.order_pack') ?? '/order/pack');
        $packResp = $this->client->post($packPath, $auth, [
            'delivery_type' => $deliveryType,
            'shipment_provider' => $shipmentProvider,
            'order_items' => json_encode($itemIds, JSON_UNESCAPED_SLASHES),
        ]);
        $pack = LazadaMappers::packResponse($packResp);
        $trackingNo = $pack['tracking_no'];
        $packageId = $pack['package_id'];
        // Lazada đôi khi map sang provider khác (vd nhập "GHN" → trả "Giao Hang Nhanh Vietnam"); dùng tên
        // Lazada trả ra để bước RTS không bị "ShipmentProviderMismatch".
        $finalProvider = $pack['shipment_provider'] !== null && $pack['shipment_provider'] !== ''
            ? $pack['shipment_provider'] : $shipmentProvider;
        if ($trackingNo === null) {
            // Một số sandbox cấp tracking async — vẫn gọi RTS với chuỗi rỗng sẽ bị reject. Bắt buộc fail
            // sớm để ShipmentService gắn issue rõ.
            throw new LazadaApiException(
                "Lazada /order/pack không trả tracking_number cho đơn {$externalOrderId} — provider [{$shipmentProvider}] có thể chưa cấu hình cho shop.",
                'PackNoTracking', 502,
            );
        }

        // (4) /order/rts — bắt buộc để Lazada chuyển sang ready_to_ship
        $rtsPath = (string) (config('integrations.lazada.endpoints.order_rts') ?? '/order/rts');
        $this->client->post($rtsPath, $auth, [
            'delivery_type' => $deliveryType,
            'shipment_provider' => $finalProvider,
            'tracking_number' => $trackingNo,
            'order_item_ids' => json_encode($itemIds, JSON_UNESCAPED_SLASHES),
        ]);

        Log::info('lazada.arrange_shipment_ok', [
            'order' => $externalOrderId, 'items' => count($itemIds),
            'provider' => $finalProvider, 'has_package_id' => $packageId !== null,
        ]);

        // `$finalProvider` đã được đảm bảo non-empty: `$shipmentProvider === ''` đã throw ở trên, branch
        // pack-response chỉ ghi đè khi giá trị mới non-empty.
        return [
            'tracking_no' => $trackingNo,
            'carrier' => $finalProvider,
            'raw_status' => 'ready_to_ship',
            'package_id' => $packageId,
        ];
    }

    /**
     * Đọc tracking từ order detail (nếu Lazada đã cấp do user/Lazada pack trước đó hoặc do pack vừa
     * xong từ bước (3) chạy lại). Trả null khi chưa có ⇒ caller tự pack tiếp.
     *
     * @return array{tracking_no:string,carrier:?string,raw_status:?string,package_id:?string}|null
     */
    private function extractExistingShipment(AuthContext $auth, string $externalOrderId, array $params): ?array
    {
        $detail = $this->fetchOrderDetail($auth, $externalOrderId);
        foreach ($detail->packages as $pkg) {
            if (! empty($pkg['trackingNo'])) {
                return [
                    'tracking_no' => (string) $pkg['trackingNo'],
                    'carrier' => isset($pkg['carrier']) ? (string) $pkg['carrier'] : null,
                    'raw_status' => isset($pkg['status']) ? (string) $pkg['status'] : null,
                    'package_id' => isset($pkg['externalPackageId']) ? (string) $pkg['externalPackageId'] : null,
                ];
            }
        }

        return null;
    }

    /**
     * Lấy danh sách order_item_id cho đơn:
     *  - caller truyền `order_item_ids` hoặc `items` ⇒ ưu tiên (tránh fetch lại);
     *  - hoặc `packages[].items[].order_item_id` (đã có trong order DTO);
     *  - fallback: gọi `/order/items/get?order_id=` (1 lượt extra, idempotent).
     *
     * @return list<int>
     */
    private function resolveOrderItemIds(AuthContext $auth, string $externalOrderId, array $params): array
    {
        $direct = $this->itemIdsFromParams($params);
        if ($direct !== []) {
            return $direct;
        }
        // packages[] mang theo từ ShipmentService::prepareChannelOrder — đôi khi đã có order_item_id
        foreach ((array) ($params['packages'] ?? []) as $pkg) {
            $pkgItems = $this->itemIdsFromParams(['items' => (array) ($pkg['items'] ?? [])]);
            if ($pkgItems !== []) {
                return $pkgItems;
            }
        }
        try {
            $items = (array) $this->client->get('/order/items/get', $auth, ['order_id' => $externalOrderId]);
            $out = [];
            foreach ($items as $it) {
                if (! is_array($it)) {
                    continue;
                }
                $id = (int) ($it['order_item_id'] ?? 0);
                if ($id > 0) {
                    $out[] = $id;
                }
            }

            return array_values(array_unique($out));
        } catch (\Throwable $e) {
            Log::info('lazada.resolve_order_items_failed', ['order' => $externalOrderId, 'error' => class_basename($e)]);

            return [];
        }
    }

    /**
     * Pick `shipment_provider` mặc định cho `delivery_type` (gần như chỉ `dropship`):
     * gọi `/shipment/providers/get`, ưu tiên element có `is_default=true` (hoặc `default=true`); fallback element đầu.
     * Cache trong `$this->providerCache[$channelAccountId]`.
     */
    private function pickDefaultShipmentProvider(AuthContext $auth, string $deliveryType): string
    {
        $providers = $this->providerCache[$auth->channelAccountId] ?? null;
        if ($providers === null) {
            $path = (string) (config('integrations.lazada.endpoints.shipment_providers') ?? '/shipment/providers/get');
            try {
                $resp = $this->client->get($path, $auth);
            } catch (\Throwable $e) {
                Log::warning('lazada.shipment_providers_fetch_failed', ['error' => class_basename($e)]);
                $this->providerCache[$auth->channelAccountId] = [];

                return '';
            }
            // Response shape thay đổi giữa các region:
            //   data.shipment_providers[] | data.shipping_providers[] | data[]
            // Mỗi element thường có `name` + `is_default`/`default` + có khi `delivery_type` (filter theo
            // nếu Lazada cấp).
            $list = (array) ($resp['shipment_providers'] ?? $resp['shipping_providers'] ?? $resp['providers'] ?? $resp);
            $providers = array_values(array_filter($list, 'is_array'));
            $this->providerCache[$auth->channelAccountId] = $providers;
        }
        if ($providers === []) {
            return '';
        }
        $matchesDelivery = function (array $p) use ($deliveryType): bool {
            $dt = strtolower((string) ($p['delivery_type'] ?? $p['deliveryType'] ?? ''));

            return $dt === '' || $dt === strtolower($deliveryType);
        };
        // Ưu tiên: default = true & match delivery_type
        foreach ($providers as $p) {
            if ((bool) ($p['is_default'] ?? $p['default'] ?? false) && $matchesDelivery($p)) {
                return (string) ($p['name'] ?? '');
            }
        }
        // Fallback: element đầu match delivery_type
        foreach ($providers as $p) {
            if ($matchesDelivery($p) && ! empty($p['name'])) {
                return (string) $p['name'];
            }
        }

        // Ultimate fallback: element đầu (kể cả khi delivery_type không khớp — let Lazada decide)
        return (string) ($providers[0]['name'] ?? '');
    }

    /**
     * Lazada `/order/document/get` — lấy `shippingLabel` (PDF) cho `order_item_ids`. Theo tài liệu
     * Open Platform (https://open.lazada.com/apps/doc/api?path=/order/document/get) response trả
     * `document: { file: <base64 PDF | URL>, doc_type, expire_time }`. Tem PDF chỉ dùng được sau khi
     * Lazada đã cấp tracking (status ≥ packed). Trước đó endpoint trả error.
     *
     * `$query` keys: `type` (SHIPPING_LABEL* | INVOICE | CARRIER_MANIFEST — sẽ map về tên Lazada),
     * `order_item_ids` (tuỳ chọn — nếu trống, tự fetch từ `/order/items/get`).
     *
     * @return array{filename:string,mime:string,bytes:string}
     */
    public function getShippingDocument(AuthContext $auth, string $externalOrderId, array $query = []): array
    {
        if (! config('integrations.lazada.fulfillment_enabled')) {
            throw UnsupportedOperation::for($this->code(), 'getShippingDocument');
        }
        // Map tên type chung của core về tên Lazada (snake-case / camelCase tuỳ phiên bản — dùng camelCase
        // theo tài liệu hiện hành; nếu sandbox của shop khác, có thể đè qua config).
        $typeIn = strtoupper((string) ($query['type'] ?? 'SHIPPING_LABEL'));
        $docType = (string) (config('integrations.lazada.endpoints.doc_type_map.'.$typeIn)
            ?? match ($typeIn) {
                'SHIPPING_LABEL', 'SHIPPING_LABEL_AND_PACKING_SLIP' => 'shippingLabel',
                'INVOICE' => 'invoice',
                'CARRIER_MANIFEST' => 'carrierManifest',
                'PICKLIST' => 'pickList',
                default => 'shippingLabel',
            });

        $itemIds = array_values(array_filter(array_map('intval', (array) ($query['order_item_ids'] ?? [])), fn ($v) => $v > 0));
        if ($itemIds === []) {
            // Fallback: hỏi Lazada items hiện tại cho đơn. Một lượt gọi thêm; chỉ chạy khi caller không truyền.
            try {
                $items = (array) $this->client->get('/order/items/get', $auth, ['order_id' => $externalOrderId]);
                foreach ($items as $it) {
                    if (! is_array($it)) {
                        continue;
                    }
                    $id = (int) ($it['order_item_id'] ?? 0);
                    if ($id > 0) {
                        $itemIds[] = $id;
                    }
                }
                $itemIds = array_values(array_unique($itemIds));
            } catch (\Throwable $e) {
                Log::info('lazada.document_get_items_failed', ['order' => $externalOrderId, 'error' => class_basename($e)]);
            }
        }
        if ($itemIds === []) {
            throw new \RuntimeException('Không có order_item_id để lấy phiếu giao hàng từ Lazada (đơn '.$externalOrderId.').');
        }

        $endpoint = (string) (config('integrations.lazada.endpoints.document_get') ?? '/order/document/get');
        $data = $this->client->get($endpoint, $auth, [
            'doc_type' => $docType,
            'order_item_ids' => json_encode($itemIds, JSON_UNESCAPED_SLASHES),
        ]);

        // Response shape: `document.file` (string — có thể là base64 PDF hoặc URL có TTL ngắn) hoặc
        // `document.url`. Một số shop trả ngay `data.file` không bọc thêm — defensive đọc cả hai.
        $document = is_array($data['document'] ?? null) ? (array) $data['document'] : (array) $data;
        $file = (string) ($document['file'] ?? $document['url'] ?? '');
        $bytes = '';
        if ($file !== '') {
            if (preg_match('#^https?://#i', $file)) {
                $resp = Http::timeout(30)->get($file);
                $bytes = $resp->successful() ? $resp->body() : '';
            } else {
                $decoded = base64_decode($file, true);
                $bytes = $decoded !== false ? $decoded : '';
            }
        }
        if ($bytes === '') {
            throw new \RuntimeException('Lazada không trả về tệp '.$docType.' cho đơn '.$externalOrderId.'. Có thể đơn chưa được cấp tracking (Lazada chỉ cấp PDF sau khi đơn ở trạng thái "packed/ready_to_ship").');
        }

        return ['filename' => "lazada-{$docType}-{$externalOrderId}.pdf", 'mime' => 'application/pdf', 'bytes' => $bytes];
    }

    /** @return list<int> */
    private function itemIdsFromParams(array $params): array
    {
        $out = [];
        foreach ((array) ($params['order_item_ids'] ?? $params['items'] ?? []) as $v) {
            if (is_array($v) && isset($v['order_item_id'])) {
                $v = $v['order_item_id'];
            }
            $id = (int) $v;
            if ($id > 0) {
                $out[] = $id;
            }
        }

        return array_values(array_unique($out));
    }

    // --- Finance / Settlements ----------------------------------------------

    /**
     * Đối soát Lazada — `GET /finance/transaction/details/get` (start_time/end_time, offset/limit). Lazada không
     * group sẵn thành statement; ta gom toàn bộ transactions trong khoảng `[from, to]` thành **một** SettlementDTO
     * (1 statement / 1 fetch). Gated bởi cờ `INTEGRATIONS_LAZADA_FINANCE` (mặc định off). SPEC 0016.
     *
     * @param  array{from?:CarbonImmutable,to?:CarbonImmutable,cursor?:string,pageSize?:int}  $query
     */
    public function fetchSettlements(AuthContext $auth, array $query = []): Page
    {
        if (! config('integrations.lazada.finance_enabled')) {
            throw UnsupportedOperation::for($this->code(), 'fetchSettlements (đặt INTEGRATIONS_LAZADA_FINANCE=true để bật)');
        }
        $from = $query['from'] ?? CarbonImmutable::now()->subDays(30);
        $to = $query['to'] ?? CarbonImmutable::now();
        $limit = max(1, min(500, (int) ($query['pageSize'] ?? 100)));
        $offset = (int) ($query['cursor'] ?? 0);
        $path = (string) (config('integrations.lazada.endpoints.transaction_details') ?? '/finance/transaction/details/get');

        $rows = [];
        do {
            $resp = $this->client->get($path, $auth, [
                'start_time' => $from->format('Y-m-d'), 'end_time' => $to->format('Y-m-d'),
                'limit' => $limit, 'offset' => $offset,
            ]);
            foreach ((array) ($resp['data'] ?? $resp) as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $rows[] = $row;
            }
            $got = count((array) ($resp['data'] ?? $resp));
            $offset += $got;
            if ($got < $limit) {
                break;
            }
            if (count($rows) >= 2000) {
                break;   // safety cap — fetch tiếp ở lần gọi sau qua cursor.
            }
        } while (true);

        $settlement = LazadaMappers::settlement($rows, $from, $to);

        return new Page(items: [$settlement], nextCursor: null, hasMore: false);
    }
}
