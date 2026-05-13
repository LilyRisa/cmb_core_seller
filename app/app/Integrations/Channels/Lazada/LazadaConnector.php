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
            // Lazada YÊU CẦU bước /order/rts riêng (packed → ready_to_ship). TikTok/Shopee không có. SPEC: lazada_order.md.
            'shipping.ready_to_ship' => (bool) config('integrations.lazada.fulfillment_enabled', true),
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

    /**
     * Lazada "đơn chưa bàn giao ĐVVC" — DÙNG ĐÚNG status filter values mà Lazada `/orders/get` chấp
     * nhận (tài liệu chính thức GetOrders: `pending | canceled | ready_to_ship | delivered | returned |
     * shipped | failed`). **`topack` và `packed` KHÔNG được Lazada hỗ trợ làm status filter** — chúng
     * chỉ là item-level statuses trong response (`order.statuses[]`), không phải order-level filter.
     * Truyền `?status=topack` ⇒ Lazada reject hoặc return empty.
     *
     * Phủ đơn chưa rời kho:
     *   - `pending`       ⇒ đơn đã đặt, chưa pack/RTS (bao trùm item-level `pending` + `topack`)
     *   - `ready_to_ship` ⇒ đơn đã RTS, chờ ĐVVC (bao trùm item-level `packed` + `ready_to_ship`)
     * Loại `unpaid` (chưa thanh toán, sàn không cho ship) và `shipped+` (đã rời kho hoặc đã giao).
     * Config-able qua `integrations.lazada.unprocessed_raw_statuses`. Xem docs/03-domain/order-sync-pipeline.md §3.3.
     */
    public function unprocessedRawStatuses(): array
    {
        $cfg = (array) config('integrations.lazada.unprocessed_raw_statuses', []);

        return $cfg !== []
            ? array_values(array_filter(array_map('strval', $cfg), fn ($s) => $s !== ''))
            : ['pending', 'ready_to_ship'];
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
     * Trạng thái item-level Lazada cho phép pack (tài liệu: "if it is cancelled or unpaid status, then
     * it is not allowed to be packed"). Các trạng thái khác (`packed`/`ready_to_ship`/`shipped`/…) đã
     * pack rồi ⇒ pack lại sẽ bị Lazada reject code "20 Invalid Order Item ID".
     */
    private const PACKABLE_ITEM_STATUSES = ['pending', 'topack', ''];

    /**
     * Lazada "Chuẩn bị hàng" — chỉ đẩy đơn từ `paid` (item: `pending`/`topack`) → **`packed`** trên sàn,
     * KHÔNG gọi /order/rts ở đây. Mapping app ⇄ Lazada theo `lazada_order.md` (Lazada Support 2026-05-14):
     *
     *   "Chờ xử lý"    ← Lazada `paid` (item-level: `pending`/`topack`)
     *   "Đang xử lý"   ← Lazada `packed`        (sau /order/fulfill/pack — bước này)
     *   "Chờ bàn giao" ← Lazada `ready_to_ship` (sau /order/rts — bước "Đã gói & sẵn sàng bàn giao",
     *                                            xem {@see self::pushReadyToShip()})
     *
     * Luồng:
     *   1. `fetchOrderDetail` (1 lượt) — short-circuit nếu đã có `tracking_code` trên item (đã pack/RTS rồi).
     *   2. Lọc items packable (`pending`/`topack`); bỏ items đã pack/ship/huỷ/unpaid.
     *   3. Resolve `shipment_provider`: `$params` → config → `/shipment/providers/get`.
     *   4. `POST /order/pack` với `pack_order_list=[{order_id, order_item_list=[...]}]` (JIT — biz_group=70100,
     *      chuẩn từ docs Lazada) hoặc fallback shape cũ `order_items=[...]`. Trả `tracking_number` + `package_id`.
     *
     * **KHÔNG gọi /order/rts ở đây nữa.** /order/rts được tách sang `pushReadyToShip()` — user bấm
     * "Đã gói & sẵn sàng bàn giao" → ShipmentService::markPacked → connector->pushReadyToShip → Lazada
     * `ready_to_ship`. Đúng 3-tab flow của app, đúng spec Lazada (paid → packed → ready_to_ship).
     *
     * Mode `LAZADA_FULFILLMENT_MODE=refetch_only` (legacy): bỏ bước 2–4, chỉ re-fetch.
     *
     * @return array{tracking_no:?string,carrier:?string,raw_status:?string,package_id:?string,external_item_ids?:list<int>}
     */
    public function arrangeShipment(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        if (! config('integrations.lazada.fulfillment_enabled')) {
            throw UnsupportedOperation::for($this->code(), 'arrangeShipment');
        }

        // (1) Một lượt re-fetch — đọc cả packages (cho short-circuit) lẫn items (cho filter packable bên dưới).
        $detail = $this->fetchOrderDetail($auth, $externalOrderId);
        $existing = $this->extractExistingShipmentFromDetail($detail);
        if ($existing !== null) {
            return $existing;
        }

        $mode = strtolower((string) (config('integrations.lazada.fulfillment_mode') ?? 'auto'));
        if ($mode !== 'auto') {
            return ['tracking_no' => null, 'carrier' => null, 'raw_status' => null, 'package_id' => null];
        }

        // (2) Lọc items theo trạng thái packable
        $rawItems = (array) ($detail->raw['items'] ?? []);
        [$packableIds, $skippedStatuses] = $this->splitPackableItems($rawItems);
        if ($packableIds === []) {
            $statusValues = array_unique(array_values($skippedStatuses));
            // Items already packed/RTS ở ngoài Seller Center → short-circuit gracefully (raw_status = 'packed'
            // để app vào "Đang xử lý"; nếu thực tế Lazada đã RTS rồi, polling re-fetch sẽ map về ready_to_ship).
            $terminalStatuses = ['cancelled', 'unpaid', 'failed', 'lost', 'damaged', 'returned', 'unknown'];
            $postPackStatuses = ['packed', 'ready_to_ship', 'toship', 'shipped'];
            $nonTerminal = array_values(array_filter($statusValues, fn ($s) => ! in_array($s, $terminalStatuses, true)));
            if ($nonTerminal !== [] && array_diff($nonTerminal, $postPackStatuses) === []) {
                Log::info('lazada.arrange_items_already_packed_external', ['order' => $externalOrderId, 'statuses' => $statusValues]);
                $rawStatus = in_array('ready_to_ship', $statusValues, true) || in_array('toship', $statusValues, true)
                    ? 'ready_to_ship' : 'packed';

                return ['tracking_no' => null, 'carrier' => null, 'raw_status' => $rawStatus, 'package_id' => null];
            }
            $statusList = $statusValues !== [] ? implode(', ', $statusValues) : 'không có item';
            throw new LazadaApiException(
                "Lazada arrange shipment: không có item nào ở trạng thái pending để pack (đơn {$externalOrderId}). Trạng thái item hiện tại: {$statusList}. Có thể đơn đã pack/ship/huỷ ở ngoài app — bấm 'Nhận phiếu giao hàng' để đồng bộ lại trạng thái.",
                'NoPackableItems', 422,
            );
        }

        // (3) Resolve shipment_provider + delivery_type
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

        // (4) /order/pack — Lazada gán tracking
        $packPath = (string) (config('integrations.lazada.endpoints.order_pack') ?? '/order/pack');
        try {
            $packResp = $this->client->post($packPath, $auth, [
                'delivery_type' => $deliveryType,
                'shipment_provider' => $shipmentProvider,
                'order_items' => json_encode($packableIds, JSON_UNESCAPED_SLASHES),
            ]);
        } catch (LazadaApiException $e) {
            if ($this->isInvalidItemIdError($e)) {
                Log::info('lazada.pack_invalid_item_retry_refetch', ['order' => $externalOrderId, 'items' => $packableIds, 'code' => $e->lazadaCode]);
                $retry = $this->extractExistingShipmentFromDetail($this->fetchOrderDetail($auth, $externalOrderId));
                if ($retry !== null) {
                    return $retry;
                }
            }
            throw $e;
        }
        $pack = LazadaMappers::packResponse($packResp);
        $trackingNo = $pack['tracking_no'];
        $packageId = $pack['package_id'];
        $finalProvider = $pack['shipment_provider'] !== null && $pack['shipment_provider'] !== ''
            ? $pack['shipment_provider'] : $shipmentProvider;
        if ($trackingNo === null) {
            throw new LazadaApiException(
                "Lazada /order/pack không trả tracking_number cho đơn {$externalOrderId} — provider [{$shipmentProvider}] có thể chưa cấu hình cho shop.",
                'PackNoTracking', 502,
            );
        }

        Log::info('lazada.arrange_shipment_packed', [
            'order' => $externalOrderId, 'items' => count($packableIds),
            'provider' => $finalProvider, 'has_package_id' => $packageId !== null,
            'note' => '/order/rts sẽ được gọi ở pushReadyToShip khi user bấm "Đã gói & sẵn sàng bàn giao"',
        ]);

        // raw_status = 'packed' — app sẽ map về `processing` ("Đang xử lý"). Trả `external_item_ids` để
        // ShipmentService lưu lại (cần cho /order/rts về sau — Lazada yêu cầu order_item_ids đúng từng item).
        return [
            'tracking_no' => $trackingNo,
            'carrier' => $finalProvider,
            'raw_status' => 'packed',
            'package_id' => $packageId,
            'external_item_ids' => $packableIds,
        ];
    }

    /**
     * Bước 2 của "luồng A" Lazada: `packed` → `ready_to_ship` qua `/order/rts`. Được gọi từ
     * `ShipmentService::markPacked` khi user bấm "Đã gói & sẵn sàng bàn giao" — đúng spec 3 tab app khớp
     * 3 trạng thái Lazada (xem `lazada_order.md`).
     *
     * BẮT BUỘC kiểm `$params['external_item_ids']` (list<int> order_item_id — **KHÁC** order_id) — Lazada
     * /order/rts yêu cầu đúng từng item, và buyer có thể đã huỷ một số item giữa chừng (`reason != null`).
     * Item bị huỷ ⇒ Lazada trả `code 50008` ⇒ ta re-fetch detail, filter lại packable items, retry với
     * danh sách hợp lệ.
     *
     * @return array{raw_status:string,carrier:?string,tracking_no:?string,package_id:?string}
     */
    public function pushReadyToShip(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        if (! config('integrations.lazada.fulfillment_enabled')) {
            throw UnsupportedOperation::for($this->code(), 'pushReadyToShip');
        }
        $trackingNo = trim((string) ($params['tracking_no'] ?? ''));
        $shipmentProvider = trim((string) ($params['shipment_provider'] ?? ''));
        $itemIds = array_values(array_filter(array_map('intval', (array) ($params['external_item_ids'] ?? [])), fn ($v) => $v > 0));
        $deliveryType = (string) ($params['delivery_type'] ?? config('integrations.lazada.default_delivery_type') ?? 'dropship');

        if ($trackingNo === '' || $shipmentProvider === '') {
            throw new LazadaApiException(
                "Lazada pushReadyToShip: thiếu tracking_no/shipment_provider cho đơn {$externalOrderId} (cần lưu từ kết quả arrangeShipment).",
                'MissingRtsParams', 422,
            );
        }
        // Fallback: nếu caller chưa lưu order_item_ids ⇒ re-fetch detail để lấy items packed/pending hiện tại.
        if ($itemIds === []) {
            $detail = $this->fetchOrderDetail($auth, $externalOrderId);
            foreach ((array) ($detail->raw['items'] ?? []) as $it) {
                if (! is_array($it)) {
                    continue;
                }
                $status = strtolower(trim((string) ($it['status'] ?? '')));
                if (in_array($status, ['packed', 'pending', 'topack'], true)) {
                    $id = (int) ($it['order_item_id'] ?? 0);
                    if ($id > 0) {
                        $itemIds[] = $id;
                    }
                }
            }
            $itemIds = array_values(array_unique($itemIds));
            if ($itemIds === []) {
                throw new LazadaApiException(
                    "Lazada pushReadyToShip: không tìm thấy order_item_id packed/pending cho đơn {$externalOrderId}.",
                    'NoItemsForRts', 422,
                );
            }
        }

        $rtsPath = (string) (config('integrations.lazada.endpoints.order_rts') ?? '/order/rts');
        try {
            $this->client->post($rtsPath, $auth, [
                'delivery_type' => $deliveryType,
                'shipment_provider' => $shipmentProvider,
                'tracking_number' => $trackingNo,
                'order_item_ids' => json_encode($itemIds, JSON_UNESCAPED_SLASHES),
            ]);
        } catch (LazadaApiException $e) {
            // Code 50008: item đã bị buyer huỷ — re-fetch, lọc items còn hợp lệ, retry 1 lần.
            if (str_contains((string) $e->lazadaCode, '50008') || str_contains(strtolower($e->getMessage()), 'order item')) {
                $detail = $this->fetchOrderDetail($auth, $externalOrderId);
                $valid = [];
                foreach ((array) ($detail->raw['items'] ?? []) as $it) {
                    if (! is_array($it)) {
                        continue;
                    }
                    $status = strtolower(trim((string) ($it['status'] ?? '')));
                    $id = (int) ($it['order_item_id'] ?? 0);
                    if ($id > 0 && in_array($status, ['packed', 'pending', 'topack'], true)) {
                        $valid[] = $id;
                    }
                }
                $valid = array_values(array_intersect($itemIds, $valid));
                if ($valid === []) {
                    throw $e;
                }
                $this->client->post($rtsPath, $auth, [
                    'delivery_type' => $deliveryType,
                    'shipment_provider' => $shipmentProvider,
                    'tracking_number' => $trackingNo,
                    'order_item_ids' => json_encode($valid, JSON_UNESCAPED_SLASHES),
                ]);
            } else {
                throw $e;
            }
        }

        Log::info('lazada.push_rts_ok', ['order' => $externalOrderId, 'items' => count($itemIds), 'provider' => $shipmentProvider]);

        return [
            'raw_status' => 'ready_to_ship',
            'carrier' => $shipmentProvider,
            'tracking_no' => $trackingNo,
            'package_id' => (string) ($params['packageId'] ?? '') ?: null,
        ];
    }

    /**
     * Đọc tracking từ OrderDTO đã fetch — short-circuit cho `arrangeShipment` khi đơn đã được pack
     * trước đó (Lazada đã cấp `tracking_code`). Trả null khi chưa có ⇒ caller tự pack tiếp.
     *
     * @return array{tracking_no:string,carrier:?string,raw_status:?string,package_id:?string}|null
     */
    private function extractExistingShipmentFromDetail(OrderDTO $detail): ?array
    {
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
     * Lazada chỉ pack được item ở `pending`/`topack`. Items khác (`unpaid`, `canceled`, `returned`,
     * `packed`, `ready_to_ship`, `shipped`, `failed`, `lost`, `damaged`...) ⇒ `/order/pack` báo
     * "code 20 Invalid Order Item ID". Bóc tách 2 nhóm để biết nên pack items nào & log lý do skip.
     *
     * @param  array<int, mixed>  $rawItems  raw items array from Lazada — defensive type since JSON
     *                                       parse có thể trả về element non-array trong sandbox lỗi
     * @return array{0: list<int>, 1: array<int,string>} [packableIds, skippedIdToStatus]
     */
    private function splitPackableItems(array $rawItems): array
    {
        $packable = [];
        $skipped = [];
        foreach ($rawItems as $it) {
            if (! is_array($it)) {   // defensive — raw từ JSON parse có thể không phải mảng
                continue;
            }
            $id = (int) ($it['order_item_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $status = strtolower(trim((string) ($it['status'] ?? '')));
            if (in_array($status, self::PACKABLE_ITEM_STATUSES, true)) {
                $packable[] = $id;
            } else {
                $skipped[$id] = $status !== '' ? $status : 'unknown';
            }
        }

        return [array_values(array_unique($packable)), $skipped];
    }

    /** Lazada code "20" / message chứa "Invalid Order Item ID" ⇒ items đã ở trạng thái không packable. */
    private function isInvalidItemIdError(LazadaApiException $e): bool
    {
        return $e->lazadaCode === '20'
            || str_contains(strtolower($e->getMessage()), 'invalid order item');
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
     * Lấy phiếu giao hàng (PDF) của Lazada theo `/order/document/get` — endpoint chính thức (xem
     * `lazada_order.md` mục `api order`: `GetDocument`). Param BẮT BUỘC theo Lazada Support:
     *
     *   - `doc_type`        — `shippingLabel` | `invoice` | `carrierManifest` | `pickList`
     *   - `order_item_ids`  — JSON array của **`order_item_id`** (trường `items[].order_item_id` trong raw,
     *                         **KHÁC** `order_id`). Vd: order_id=525106346980318 vs order_item_id=525106347080318.
     *                         Đơn có nhiều item ⇒ `[id1, id2, ...]`.
     *
     * Luồng resolve `order_item_ids` (ưu tiên theo độ chính xác):
     *   1. `$query['order_item_ids']` từ caller (ShipmentService đọc từ `shipments.raw.external_item_ids`
     *      — đã lưu lúc `arrangeShipment` chạy `/order/pack`).
     *   2. `$query['external_item_ids']` (alias cho khi caller dùng tên khác).
     *   3. Pull từ `order_items.external_item_id` qua callback (caller pass) — KHÔNG fetch lại sàn.
     *   4. Fallback cuối: gọi `/order/items/get?order_id={externalOrderId}` lấy `items[].order_item_id`.
     *
     * Khi lấy được bytes ⇒ caller (`ShipmentService::fetchAndStoreChannelLabel`) đẩy lên R2 + lưu `label_path`
     * trên shipment ⇒ lần render in sau chỉ đọc R2, KHÔNG gọi lại Lazada (xem MediaUploader::get).
     *
     * @return array{filename:string,mime:string,bytes:string}
     */
    public function getShippingDocument(AuthContext $auth, string $externalOrderId, array $query = []): array
    {
        if (! config('integrations.lazada.fulfillment_enabled')) {
            throw UnsupportedOperation::for($this->code(), 'getShippingDocument');
        }
        $typeIn = strtoupper((string) ($query['type'] ?? 'SHIPPING_LABEL'));
        $docType = (string) (config('integrations.lazada.endpoints.doc_type_map.'.$typeIn)
            ?? match ($typeIn) {
                'SHIPPING_LABEL', 'SHIPPING_LABEL_AND_PACKING_SLIP' => 'shippingLabel',
                'INVOICE' => 'invoice',
                'CARRIER_MANIFEST' => 'carrierManifest',
                'PICKLIST' => 'pickList',
                default => 'shippingLabel',
            });

        // Resolve order_item_ids — phải KHỚP EXACT với `items[].order_item_id` trong raw Lazada (khác order_id).
        $itemIds = $this->resolveOrderItemIds($auth, $externalOrderId, $query);
        if ($itemIds === []) {
            throw new \RuntimeException("Không có order_item_id để lấy phiếu giao hàng Lazada cho đơn {$externalOrderId}.");
        }

        // (1) PRIMARY: `/order/document/get` (chính thức theo `lazada_order.md`) — `order_item_ids` JSON array.
        $extracted = ['bytes' => '', 'mime' => ''];
        $lastErr = null;
        try {
            $endpoint = (string) (config('integrations.lazada.endpoints.document_get') ?? '/order/document/get');
            $data = $this->client->get($endpoint, $auth, [
                'doc_type' => $docType,
                'order_item_ids' => json_encode($itemIds, JSON_UNESCAPED_SLASHES),
            ]);
            $extracted = $this->extractDocumentBytes($data);
        } catch (\Throwable $e) {
            $lastErr = $e;
            Log::info('lazada.document_get_failed', ['order' => $externalOrderId, 'item_count' => count($itemIds), 'error' => $e->getMessage()]);
        }

        // (2) FALLBACK: PrintAWB `/order/package/document/get` — chỉ dùng khi (1) trả empty/lỗi và caller
        // đã pass `externalPackageId`. PrintAWB nhận `getDocumentReq` (JSON envelope) với `packages` HOẶC
        // `order_item_ids`. Một số shop SoC ổn định hơn ở PrintAWB; một số shop legacy chỉ chạy `/order/document/get`.
        $packageId = trim((string) ($query['externalPackageId'] ?? ''));
        if ($extracted['bytes'] === '' && $packageId !== '' && config('integrations.lazada.endpoints.print_awb')) {
            try {
                $printAwbPath = (string) config('integrations.lazada.endpoints.print_awb');
                $printDocType = in_array($docType, ['shippingLabel', 'invoice', 'carrierManifest', 'pickList'], true)
                    ? 'PDF' : strtoupper($docType);
                $data = $this->client->get($printAwbPath, $auth, [
                    'getDocumentReq' => json_encode([
                        'doc_type' => $printDocType,
                        'packages' => [['package_id' => $packageId]],
                        'order_item_ids' => $itemIds,   // pass cả 2 — Lazada chấp nhận hoặc bỏ qua
                        'print_item_list' => true,
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ]);
                $extracted = $this->extractDocumentBytes($data);
            } catch (\Throwable $e) {
                $lastErr = $e;
                Log::info('lazada.print_awb_failed', ['order' => $externalOrderId, 'package_id' => $packageId, 'error' => $e->getMessage()]);
            }
        }

        if ($extracted['bytes'] === '') {
            $detail = $lastErr ? ' ('.$lastErr->getMessage().')' : '';
            throw new \RuntimeException('Lazada chưa cấp tệp '.$docType.' cho đơn '.$externalOrderId.$detail.'. 3PL thường render PDF async 5–30s sau /order/rts — retry tự khắc lấy được.');
        }

        // Lazada mặc định trả **HTML** (`mime_type=text/html`). Render qua Gotenberg để chuyển sang PDF
        // trước khi trả về caller — caller (ShipmentService + PrintService) chỉ làm việc với PDF (ghép tem,
        // gửi printer, ...) và lưu vào R2 với extension `.pdf`. Nếu mime đã là PDF (PrintAWB trả binary)
        // thì giữ nguyên.
        $bytes = $extracted['bytes'];
        $mime = strtolower($extracted['mime']);
        if (str_contains($mime, 'html')) {
            try {
                // Gotenberg defaults (A4, 0 margin) đủ cho tem AWB Lazada — HTML từ sàn đã có @page CSS riêng,
                // override paperWidth/Height qua options sẽ require multipart shape khác (Gotenberg API). Giữ
                // defaults để Gotenberg tự honor `@page` CSS trong HTML của Lazada.
                $bytes = app(\CMBcoreSeller\Support\GotenbergClient::class)->htmlToPdf($bytes);
                Log::info('lazada.document_html_rendered_to_pdf', ['order' => $externalOrderId, 'html_size' => strlen($extracted['bytes']), 'pdf_size' => strlen($bytes)]);
            } catch (\Throwable $e) {
                Log::warning('lazada.document_html_to_pdf_failed', ['order' => $externalOrderId, 'error' => $e->getMessage()]);
                throw new \RuntimeException('Lazada trả HTML cho tem '.$docType.' đơn '.$externalOrderId.' nhưng Gotenberg render PDF lỗi: '.$e->getMessage());
            }
        }

        return ['filename' => "lazada-{$docType}-{$externalOrderId}.pdf", 'mime' => 'application/pdf', 'bytes' => $bytes];
    }

    /**
     * Resolve list `order_item_id` theo thứ tự ưu tiên (mỗi nguồn KHÁC `order_id` — đúng spec
     * `lazada_order.md`). Trả `[]` nếu mọi nguồn đều rỗng (caller throw).
     *
     * Thứ tự ưu tiên:
     *   1. `$query['order_item_ids']`   (caller pass — từ shipments.raw)
     *   2. `$query['external_item_ids']` (alias — từ arrange response)
     *   3. `/order/items/get?order_id={...}` (fallback cuối, 1 round-trip thêm)
     *
     * @param  array<string,mixed>  $query
     * @return list<int>
     */
    private function resolveOrderItemIds(AuthContext $auth, string $externalOrderId, array $query): array
    {
        foreach (['order_item_ids', 'external_item_ids'] as $key) {
            $ids = array_values(array_filter(array_map('intval', (array) ($query[$key] ?? [])), fn ($v) => $v > 0));
            if ($ids !== []) {
                return array_values(array_unique($ids));
            }
        }
        // Fallback: re-fetch từ /order/items/get — đọc đúng trường `order_item_id` của từng item raw.
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
            Log::info('lazada.resolve_order_item_ids_failed', ['order' => $externalOrderId, 'error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Bóc bytes + mime_type từ response Lazada `/order/document/get`. Lazada mặc định trả **HTML**
     * (`mime_type=text/html`) base64-encoded ở `data.document.file` — KHÔNG phải PDF như giả định trước
     * đây. Response shape thực tế (xác nhận từ user 2026-05-14):
     *
     *   { code:"0", data:{ document:{ file:"<base64>", mime_type:"text/html", document_type:"shippingLabel" } } }
     *
     * Một số sandbox / region khác có thể trả `documents[0]`, `data.file` flat, hoặc TTL `url`/`pdf_url`
     * (PDF binary). Function này nhận diện tất cả & trả `['bytes' => ..., 'mime' => ...]`. Caller
     * (`getShippingDocument`) check mime — nếu là HTML thì convert qua Gotenberg sang PDF trước khi đẩy R2.
     *
     * @param  array<string,mixed>  $data  `data` envelope từ LazadaClient
     * @return array{bytes:string,mime:string} bytes rỗng = Lazada chưa render xong → caller retry
     */
    private function extractDocumentBytes(array $data): array
    {
        $candidates = [];
        if (isset($data['document']) && is_array($data['document'])) {
            $candidates[] = (array) $data['document'];
        }
        if (isset($data['documents']) && is_array($data['documents'])) {
            foreach ($data['documents'] as $doc) {
                if (is_array($doc)) {
                    $candidates[] = $doc;
                }
            }
        }
        $candidates[] = $data;   // shop nào trả flat (data.file / data.url) — vẫn match

        foreach ($candidates as $doc) {
            $file = (string) ($doc['file'] ?? $doc['url'] ?? $doc['pdf_url'] ?? '');
            if ($file === '') {
                continue;
            }
            $mime = trim((string) ($doc['mime_type'] ?? $doc['mime'] ?? ''));
            if (preg_match('#^https?://#i', $file)) {
                $resp = Http::timeout(30)->get($file);
                if ($resp->successful() && $resp->body() !== '') {
                    return [
                        'bytes' => (string) $resp->body(),
                        // URL response — mime suy từ Content-Type của response, fallback PDF.
                        'mime' => $mime ?: (string) ($resp->header('Content-Type') ?: 'application/pdf'),
                    ];
                }

                continue;
            }
            $decoded = base64_decode($file, true);
            if ($decoded !== false && $decoded !== '') {
                return [
                    'bytes' => $decoded,
                    // Lazada thường ghi rõ `mime_type` trong response. Nếu thiếu ⇒ sniff bytes (PDF magic = "%PDF").
                    'mime' => $mime ?: (str_starts_with($decoded, '%PDF') ? 'application/pdf' : 'text/html'),
                ];
            }
        }

        return ['bytes' => '', 'mime' => ''];
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
