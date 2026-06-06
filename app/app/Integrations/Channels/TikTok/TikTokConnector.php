<?php

namespace CMBcoreSeller\Integrations\Channels\TikTok;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\Contracts\ChannelConnector;
use CMBcoreSeller\Integrations\Channels\Contracts\ShopReportConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\ChannelListingDTO;
use CMBcoreSeller\Integrations\Channels\DTO\OrderDTO;
use CMBcoreSeller\Integrations\Channels\DTO\Page;
use CMBcoreSeller\Integrations\Channels\DTO\ReturnDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ShopHealthDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ShopInfoDTO;
use CMBcoreSeller\Integrations\Channels\DTO\TokenDTO;
use CMBcoreSeller\Integrations\Channels\DTO\WebhookEventDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;

/**
 * TikTok Shop connector (API generation "202309"). Orders + auth + webhook for
 * Phase 1; listings/finance/fulfillment land in later phases (declared as
 * unsupported in the capability map so core hides those features rather than
 * branching on the provider name). All TikTok specifics — signing, versions,
 * status & event maps — live under app/Integrations/Channels/TikTok/ and
 * config('integrations.tiktok'). See docs/04-channels/tiktok-shop.md, SPEC 0001.
 */
class TikTokConnector implements ChannelConnector, ShopReportConnector
{
    public function __construct(
        protected TikTokClient $client,
        protected TikTokWebhookVerifier $webhook,
    ) {}

    public function code(): string
    {
        return 'tiktok';
    }

    public function displayName(): string
    {
        return 'TikTok Shop';
    }

    public function capabilities(): array
    {
        return [
            'orders.fetch' => true,
            'orders.webhook' => true,
            'orders.confirm' => false,        // Phase 3 (arrange shipment flow)
            // "luồng A" (arrange ship + lấy tem PDF thật) — endpoint theo SDK chính thức fulfillment 202309;
            // bật mặc định, tắt bằng INTEGRATIONS_TIKTOK_FULFILLMENT=false nếu shop cần handover_method khác /
            // lỗi gọi sàn (lỗi vẫn được bắt & gắn cờ has_issue, không chặn). SPEC 0013/0014.
            'shipping.arrange' => (bool) config('integrations.tiktok.fulfillment_enabled', true),
            // TikTok không có bước riêng kiểu Lazada /order/rts — sau arrange (POST /packages/{id}/ship) đơn
            // đã ở `AWAITING_COLLECTION`; "Đã gói & sẵn sàng bàn giao" là thao tác nội bộ. SPEC 0001.
            'shipping.ready_to_ship' => false,
            'shipping.document' => (bool) config('integrations.tiktok.fulfillment_enabled', true),
            'shipping.tracking' => false,     // Phase 3
            'listings.fetch' => true,         // Phase 2 — SPEC 0003 (fetchListings → channel_listings)
            'listings.publish' => false,      // Phase 5
            'listings.updateStock' => true,   // Phase 2 — SPEC 0003
            'listings.updatePrice' => false,  // Phase 5
            // Đối soát/Statements — Phase 6.2. Bật bằng INTEGRATIONS_TIKTOK_FINANCE=true sau khi đã đối chiếu
            // shape `/finance/202309/statements` với sandbox thực; mặc định off (TikTok finance API có thể đổi).
            'finance.settlements' => (bool) config('integrations.tiktok.finance_enabled', false),
            // After-sales (Hoàn & Hủy) — SPEC 0025. API return_refund 202309. Tắt bằng INTEGRATIONS_TIKTOK_RETURNS=false.
            'returns.fetch' => (bool) config('integrations.tiktok.returns_enabled', true),
            'returns.manage' => (bool) config('integrations.tiktok.returns_enabled', true),
            // Báo cáo sàn — chỉ HIỆU SUẤT (analytics shop performance). TikTok KHÔNG có API
            // sức khỏe/điểm phạt (chỉ Seller Center UI) ⇒ report.penalty=false.
            'report.health' => true,
            'report.penalty' => false,
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    // --- OAuth / connection --------------------------------------------------

    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        // TikTok's service-auth flow ignores any redirect_uri here — the callback URL is
        // configured in the Partner Center app. $opts is accepted to satisfy the interface.
        return $this->client->authorizeUrl($state);
    }

    public function exchangeCodeForToken(string $code, array $context = []): TokenDTO
    {
        return TikTokMappers::token($this->client->getAccessToken($code));
    }

    public function refreshToken(string $refreshToken, array $context = []): TokenDTO
    {
        return TikTokMappers::token($this->client->refreshAccessToken($refreshToken));
    }

    public function fetchShopInfo(AuthContext $auth): ShopInfoDTO
    {
        $ver = $this->client->versionFor('authorization');
        $data = $this->client->get("/authorization/{$ver}/shops", $auth, shopScoped: false);
        $shops = (array) ($data['shops'] ?? []);
        if ($shops === []) {
            throw new TikTokApiException('TikTok returned no authorized shops for this token.', 0, 200);
        }
        // First active shop. (Multi-shop tokens are rare for sandbox; refine if needed.)
        $shop = $shops[0];

        // Surface the shop_cipher to the caller via the DTO's raw payload (OAuthService stores it in meta).
        return TikTokMappers::shopInfo($shop);
    }

    public function registerWebhooks(AuthContext $auth): void
    {
        $events = (array) config('integrations.tiktok.subscribe_events', []);
        if ($events === []) {
            return;
        }
        $ver = $this->client->versionFor('event');
        $address = url('/webhook/tiktok');

        // TikTok `UpdateShopWebhook` API là **PUT** (idempotent create-or-update) — POST trả HTTP 405
        // "Method Not Allowed" (code 36009010). Đối chiếu SDK chính thức `sdk_tiktok_seller/api/eventV202309Api.ts`
        // `WebhooksPut()` ở line 267: method='PUT', path='/event/202309/webhooks', body=`{address,event_type}`.
        // Endpoints khác cùng path: WebhooksGet=GET, WebhooksDelete=DELETE.
        foreach ($events as $event) {
            try {
                $this->client->put("/event/{$ver}/webhooks", $auth, ['event_type' => $event, 'address' => $address]);
            } catch (\Throwable $e) {
                // Many apps configure webhook subscriptions in Partner Center instead — don't fail the connect flow.
                Log::info('tiktok.webhook.subscribe_failed', ['event' => $event, 'shop' => $auth->externalShopId, 'error' => $e->getMessage()]);
            }
        }
    }

    public function revoke(AuthContext $auth): void
    {
        // The seller revokes authorization from their side; there is no reliable
        // "revoke my own token" endpoint. Disconnecting locally is enough — sync stops.
        Log::info('tiktok.revoke', ['shop' => $auth->externalShopId]);
    }

    // --- Orders --------------------------------------------------------------

    public function fetchOrders(AuthContext $auth, array $query = []): Page
    {
        $ver = $this->client->versionFor('order');
        $q = [
            'page_size' => (string) min(100, max(1, (int) ($query['pageSize'] ?? 50))),
            'sort_field' => 'update_time',
            'sort_order' => 'ASC',
        ];
        if (! empty($query['cursor'])) {
            $q['page_token'] = (string) $query['cursor'];
        }

        $body = [];
        if (! empty($query['updatedFrom'])) {
            $body['update_time_ge'] = $query['updatedFrom']->getTimestamp();
        }
        if (! empty($query['updatedTo'])) {
            $body['update_time_lt'] = $query['updatedTo']->getTimestamp();
        }
        if (! empty($query['statuses']) && count($query['statuses']) === 1) {
            $body['order_status'] = (string) $query['statuses'][0];
        }

        $data = $this->client->post("/order/{$ver}/orders/search", $auth, $body, $q);
        $orders = array_values(array_map(fn ($o) => TikTokMappers::order((array) $o), (array) ($data['orders'] ?? [])));
        $next = $data['next_page_token'] ?? null;

        return new Page(items: $orders, nextCursor: $next ?: null, hasMore: ! empty($next));
    }

    public function fetchOrderDetail(AuthContext $auth, string $externalOrderId): OrderDTO
    {
        // Get Order Detail dùng generation riêng (mặc định 202507) — tách khỏi list (202309/orders/search).
        $ver = $this->client->versionFor('order_detail');
        $data = $this->client->get("/order/{$ver}/orders", $auth, ['ids' => $externalOrderId]);
        $orders = (array) ($data['orders'] ?? []);
        if ($orders === []) {
            throw new TikTokApiException("TikTok order {$externalOrderId} not found.", 0, 404);
        }

        return TikTokMappers::order((array) $orders[0]);
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
        return TikTokStatusMap::toStandard($rawStatus, $rawOrder);
    }

    /**
     * TikTok raw statuses coi là "đơn chưa bàn giao ĐVVC" — đối chiếu enum trong SDK chính thức
     * `sdk_tiktok_seller/model/order/V202309/GetOrderListRequestBody.ts`:
     *   - `ON_HOLD` — đã thanh toán, chờ fulfillment (buyer còn cancel được). PRE_ORDER có thể
     *     stuck ở đây cho tới 1 ngày trước release ⇒ phải pull về để seller chuẩn bị.
     *   - `AWAITING_SHIPMENT` — sẵn sàng ship, chưa item nào shipped.
     *   - `PARTIALLY_SHIPPING` — một số item đã shipped; phần còn lại vẫn cần xử lý.
     *   - `AWAITING_COLLECTION` — đã arrange, chờ ĐVVC tới lấy.
     * Loại `UNPAID` (sàn không cho ship), `IN_TRANSIT`/`DELIVERED`/`COMPLETED`/`CANCELLED` (đã rời
     * kho hoặc terminal). Config-able qua `integrations.tiktok.unprocessed_raw_statuses`. Xem
     * docs/03-domain/order-sync-pipeline.md §3.3.
     */
    public function unprocessedRawStatuses(): array
    {
        $cfg = (array) config('integrations.tiktok.unprocessed_raw_statuses', []);

        return $cfg !== []
            ? array_values(array_filter(array_map('strval', $cfg), fn ($s) => $s !== ''))
            : ['ON_HOLD', 'AWAITING_SHIPMENT', 'PARTIALLY_SHIPPING', 'AWAITING_COLLECTION'];
    }

    // --- Listings / Inventory (Phase 2) --------------------------------------

    /**
     * Page through the shop's products and flatten each product's SKUs into one
     * ChannelListingDTO per SKU. Endpoint shape to be confirmed against the Partner
     * API / sandbox; kept config-able via `integrations.tiktok.endpoints.product_search`.
     *
     * @param  array{cursor?:string,pageSize?:int}  $query
     * @return Page<ChannelListingDTO>
     */
    public function fetchListings(AuthContext $auth, array $query = []): Page
    {
        $version = $this->client->versionFor('product');
        $path = (string) (config('integrations.tiktok.endpoints.product_search') ?? "/product/{$version}/products/search");
        $path = str_replace(['{version}'], [$version], $path);

        $q = ['page_size' => (string) min(100, max(1, (int) ($query['pageSize'] ?? 50)))];
        if (! empty($query['cursor'])) {
            $q['page_token'] = (string) $query['cursor'];
        }
        $data = $this->client->post($path, $auth, ['status' => 'ALL'], $q);

        $items = [];
        foreach ((array) ($data['products'] ?? []) as $product) {
            $product = (array) $product;
            // `products/search` KHÔNG trả ảnh (chỉ id/skus/price/inventory). Lấy `main_images` qua
            // GetProduct detail để listing có ảnh sản phẩm. Best-effort: lỗi / không có ảnh → bỏ qua
            // (image=null), KHÔNG chặn đồng bộ. Guard `empty(main_images)` để khỏi gọi thừa nếu đã có.
            $pid = (string) ($product['id'] ?? '');
            if ($pid !== '' && empty($product['main_images'])) {
                try {
                    $detailPath = (string) (config('integrations.tiktok.endpoints.product_detail') ?? "/product/{$version}/products/{$pid}");
                    $detailPath = str_replace(['{version}', '{product_id}'], [$version, $pid], $detailPath);
                    $detail = $this->client->get($detailPath, $auth);
                    if (! empty($detail['main_images'])) {
                        $product['main_images'] = $detail['main_images'];
                    }
                } catch (\Throwable $e) {
                    Log::info('tiktok.listing_image_fetch_failed', ['product' => $pid, 'error' => class_basename($e)]);
                }
            }
            foreach (TikTokMappers::listings($product) as $listing) {
                $items[] = $listing;
            }
        }
        $next = $data['next_page_token'] ?? null;

        return new Page(items: $items, nextCursor: $next ?: null, hasMore: ! empty($next));
    }

    /**
     * Push the available stock of one SKU to TikTok. TikTok's inventory-update
     * endpoint is keyed by product id (must come in $context). Exact request shape
     * to be confirmed against the Partner API docs / sandbox — kept config-able via
     * `integrations.tiktok.endpoints.update_inventory`.
     *
     * @param  array{external_product_id?:string|null,warehouse_id?:string|int|null}  $context
     */
    public function updateStock(AuthContext $auth, string $externalSkuId, int $available, array $context = []): void
    {
        $productId = $context['external_product_id'] ?? null;
        if (! $productId) {
            throw UnsupportedOperation::for($this->code(), 'updateStock requires external_product_id');
        }
        $version = $this->client->versionFor('product');
        $path = (string) (config('integrations.tiktok.endpoints.update_inventory')
            ?? "/product/{$version}/products/{productId}/inventory/update");
        $path = str_replace(['{version}', '{productId}', '{product_id}'], [$version, (string) $productId, (string) $productId], $path);

        $inventory = array_filter(['warehouse_id' => $context['warehouse_id'] ?? null, 'quantity' => $available], fn ($v) => $v !== null);
        $this->client->post($path, $auth, ['skus' => [['id' => $externalSkuId, 'inventory' => [$inventory]]]]);
    }

    // --- Fulfillment (Phase 3) -----------------------------------------------

    /** Thay {version}/{package_id}/{order_id} trong đường dẫn endpoint fulfillment (cấu hình ở config). */
    private function fulfillmentPath(string $configKey, string $default, string $packageId = '', string $orderId = ''): string
    {
        $ver = $this->client->versionFor('fulfillment');
        $path = (string) (config("integrations.tiktok.endpoints.$configKey") ?: $default);

        return str_replace(['{version}', '{package_id}', '{order_id}', '{orderId}'], [$ver, $packageId, $orderId, $orderId], $path);
    }

    /** package_id từ `$params['packages']` (mapper đặt key `externalPackageId`). */
    private function packageIdFrom(array $params): string
    {
        foreach ((array) ($params['packages'] ?? []) as $p) {
            $id = is_array($p) ? data_get($p, 'externalPackageId', data_get($p, 'id', data_get($p, 'package_id'))) : null;
            if ($id) {
                return (string) $id;
            }
        }

        return '';
    }

    /**
     * Chọn `handover_method` + `pickup_slot` cho package: gọi `GET .../packages/{package_id}/handover_time_slots`
     * (SDK 202309 — `can_drop_off`/`can_pickup` + `pickup_slots[{start_time,end_time,avaliable}]`). Ưu tiên
     * DROP_OFF; nếu chỉ PICKUP ⇒ chọn slot khả dụng đầu tiên. Lỗi ⇒ mặc định DROP_OFF (best-effort).
     *
     * @return array<string,mixed> body cho POST .../ship
     */
    private function chooseHandover(AuthContext $auth, string $packageId, string $externalOrderId, array $override): array
    {
        if (! empty($override['handover_method'])) {
            return array_filter(['handover_method' => $override['handover_method'], 'pickup_slot' => $override['pickup_slot'] ?? null, 'self_shipment' => $override['self_shipment'] ?? null], fn ($v) => $v !== null && $v !== []);
        }
        try {
            $slots = $this->client->get($this->fulfillmentPath('handover_time_slots', '/fulfillment/{version}/packages/{package_id}/handover_time_slots', $packageId, $externalOrderId), $auth);
        } catch (\Throwable $e) {
            Log::info('tiktok.handover_time_slots_failed', ['package' => $packageId, 'error' => class_basename($e)]);

            return ['handover_method' => 'DROP_OFF'];
        }
        if (data_get($slots, 'can_drop_off')) {
            return ['handover_method' => 'DROP_OFF'];
        }
        $slot = collect((array) data_get($slots, 'pickup_slots', []))->first(fn ($s) => data_get($s, 'avaliable', data_get($s, 'available', true)));
        if (data_get($slots, 'can_pickup') && is_array($slot)) {
            return ['handover_method' => 'PICKUP', 'pickup_slot' => ['start_time' => (int) data_get($slot, 'start_time'), 'end_time' => (int) data_get($slot, 'end_time')]];
        }

        return ['handover_method' => 'DROP_OFF'];   // fallback
    }

    /**
     * "Luồng A" — TikTok "sắp xếp vận chuyển" cho gói (theo SDK fulfillment 202309):
     * 1) chọn handover (DROP_OFF / PICKUP+slot) qua `.../handover_time_slots`;
     * 2) `POST /fulfillment/{ver}/packages/{package_id}/ship` với body `{handover_method[, pickup_slot, self_shipment]}`
     *    ⇒ TikTok gán ĐVVC + tracking, đơn chuyển `AWAITING_COLLECTION`;
     * 3) `GET /fulfillment/{ver}/packages/{package_id}` ⇒ `tracking_number` + `shipping_provider_name`.
     * `$params` có thể chứa `handover_method`/`pickup_slot`/`self_shipment` để ép. Trả `['raw_status','tracking_no','carrier','package_id']`.
     *
     * @return array<string,mixed>
     */
    /**
     * TikTok không có bước /order/rts riêng — capability `shipping.ready_to_ship`=false ⇒
     * `ShipmentService::markPacked` không gọi method này. Khai báo để contract thoả mãn + safety guard.
     */
    public function pushReadyToShip(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        throw UnsupportedOperation::for($this->code(), 'pushReadyToShip');
    }

    public function arrangeShipment(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        if (! config('integrations.tiktok.fulfillment_enabled')) {
            throw UnsupportedOperation::for($this->code(), 'arrangeShipment');
        }
        $packageId = $this->packageIdFrom($params);
        if ($packageId === '') {
            throw new \RuntimeException('Đơn TikTok chưa có package_id (TikTok tạo package sau khi đơn được thanh toán — thử "Đồng bộ đơn" lại).');
        }

        // Idempotency: nếu package đã được ship trước đó (đã có tracking_number) ⇒ trả luôn, KHÔNG gọi
        // `/ship` lại — re-call ship trên package đã ship sẽ lỗi. Cho phép "Nhận phiếu giao hàng" gọi lại
        // arrange an toàn (tương tự `extractExistingShipmentFromDetail` của Lazada). SPEC 0014.
        $existing = $this->packageTracking($auth, $packageId, $externalOrderId);
        if ($existing['tracking_no'] !== null) {
            return ['raw_status' => 'AWAITING_COLLECTION', 'tracking_no' => $existing['tracking_no'], 'carrier' => $existing['carrier'], 'package_id' => $packageId];
        }

        $body = $this->chooseHandover($auth, $packageId, $externalOrderId, $params);
        $shipPath = $this->fulfillmentPath('ship_package', '/fulfillment/{version}/packages/{package_id}/ship', $packageId, $externalOrderId);
        $this->client->post($shipPath, $auth, $body);

        $after = $this->packageTracking($auth, $packageId, $externalOrderId);
        if ($after['tracking_no'] === null) {
            Log::warning('tiktok.arrange_shipment_no_tracking', [
                'package' => $packageId, 'order' => $externalOrderId,
                'note' => 'Ship POST thành công nhưng tracking_number chưa có — shipment sẽ thiếu mã vận đơn (BackfillChannelTracking sẽ kéo lại)',
            ]);
        }

        return ['raw_status' => 'AWAITING_COLLECTION', 'tracking_no' => $after['tracking_no'], 'carrier' => $after['carrier'], 'package_id' => $packageId];
    }

    /**
     * `GET /fulfillment/{ver}/packages/{package_id}` → `['tracking_no'=>?string, 'carrier'=>?string]`.
     * Tracking rỗng/chưa có hoặc gọi lỗi ⇒ `tracking_no=null` (đơn chưa ship hoặc sàn cấp trễ).
     *
     * @return array{tracking_no:?string,carrier:?string}
     */
    private function packageTracking(AuthContext $auth, string $packageId, string $externalOrderId): array
    {
        try {
            $detail = $this->client->get($this->fulfillmentPath('package_detail', '/fulfillment/{version}/packages/{package_id}', $packageId, $externalOrderId), $auth);
        } catch (\Throwable $e) {
            Log::warning('tiktok.get_package_detail_failed', ['package' => $packageId, 'error' => class_basename($e)]);

            return ['tracking_no' => null, 'carrier' => null];
        }
        $tracking = data_get($detail, 'tracking_number') ?: data_get($detail, 'package.tracking_number');

        return [
            'tracking_no' => ($tracking !== null && $tracking !== '') ? (string) $tracking : null,
            'carrier' => data_get($detail, 'shipping_provider_name') ?: data_get($detail, 'package.shipping_provider_name'),
        ];
    }

    /**
     * Lấy tem/AWB **thật** của TikTok (PDF bytes) — `GET /fulfillment/{ver}/packages/{package_id}/shipping_documents`
     * (`document_type` ∈ SHIPPING_LABEL | PACKING_SLIP | SHIPPING_LABEL_AND_PACKING_SLIP | …; `document_size`
     * A6/A5). Response `data.doc_url` (valid 24h) ⇒ tải về bytes. Cần `externalPackageId`. Theo SDK 202309. SPEC 0006 §9.1.
     *
     * @param  array{type?:string,format?:string,externalPackageId?:string}  $query
     * @return array{filename:string,mime:string,bytes:string}
     */
    public function getShippingDocument(AuthContext $auth, string $externalOrderId, array $query = []): array
    {
        if (! config('integrations.tiktok.fulfillment_enabled')) {
            throw UnsupportedOperation::for($this->code(), 'getShippingDocument');
        }
        $packageId = trim((string) ($query['externalPackageId'] ?? ''));
        if ($packageId === '') {
            throw UnsupportedOperation::for($this->code(), 'getShippingDocument requires externalPackageId');
        }
        $docType = strtoupper((string) ($query['type'] ?? 'SHIPPING_LABEL'));
        $q = ['document_type' => $docType];
        $size = strtoupper((string) (data_get($query, 'size') ?? ''));
        if ($size === 'A5' || $size === 'A6') {
            $q['document_size'] = $size;
        }
        $path = $this->fulfillmentPath('shipping_documents', '/fulfillment/{version}/packages/{package_id}/shipping_documents', $packageId, $externalOrderId);
        $data = $this->client->get($path, $auth, $q);
        $url = (string) (data_get($data, 'doc_url') ?? data_get($data, 'data.doc_url') ?? '');
        $bytes = '';
        if ($url !== '') {
            $resp = Http::timeout(30)->get($url);
            $bytes = $resp->successful() ? $resp->body() : '';
        }
        if ($bytes === '') {
            throw new \RuntimeException('TikTok không trả về tệp tem cho package '.$packageId.'.');
        }

        return ['filename' => "tiktok-label-{$packageId}.pdf", 'mime' => 'application/pdf', 'bytes' => $bytes];
    }

    // --- Finance / Settlements ----------------------------------------------

    /**
     * Đối soát/Statements TikTok (Finance 202309) — kéo các statement trong khoảng thời gian, mỗi statement
     * có nhiều `transactions` (đơn) với từng dòng phí (commission, payment fee, ship, voucher, adjustment).
     * Path mặc định khớp SDK `sdk_tiktok_seller/api/financeV202309Api.ts` (`GET /finance/{ver}/statements` +
     * `GET /finance/{ver}/statements/{id}/transactions`). SPEC 0016.
     *
     * Gated bởi cờ `INTEGRATIONS_TIKTOK_FINANCE` (mặc định off) — cần đối chiếu sandbox thật để chốt shape.
     *
     * @param  array{from?:CarbonImmutable,to?:CarbonImmutable,cursor?:string,pageSize?:int}  $query
     */
    public function fetchSettlements(AuthContext $auth, array $query = []): Page
    {
        if (! config('integrations.tiktok.finance_enabled')) {
            throw UnsupportedOperation::for($this->code(), 'fetchSettlements (đặt INTEGRATIONS_TIKTOK_FINANCE=true để bật)');
        }
        $ver = $this->client->versionFor('finance_statements') ?: '202309';
        // BUG-fix: thay {version} trong path (config trả về placeholder, trước đây bị bỏ sót ⇒ gọi sai path).
        $statementsPath = str_replace('{version}', $ver, (string) (config('integrations.tiktok.endpoints.finance_statements') ?? '/finance/{version}/statements'));
        $params = [
            'page_size' => max(1, min(100, (int) ($query['pageSize'] ?? 50))),
            'sort_field' => 'statement_time', 'sort_order' => 'ASC',
        ];
        if (! empty($query['cursor'])) {
            $params['page_token'] = (string) $query['cursor'];
        }
        if (! empty($query['from'])) {
            $params['statement_time_ge'] = $query['from']->getTimestamp();
        }
        if (! empty($query['to'])) {
            $params['statement_time_lt'] = $query['to']->getTimestamp();
        }

        $resp = $this->client->get($statementsPath, $auth, $params);
        $items = [];
        foreach ((array) (data_get($resp, 'data.statements') ?? data_get($resp, 'statements') ?? []) as $st) {
            $items[] = TikTokMappers::settlement((array) $st, $this->fetchStatementTransactions($auth, (string) ($st['id'] ?? '')));
        }
        $next = (string) (data_get($resp, 'data.next_page_token') ?? data_get($resp, 'next_page_token') ?? '');

        return new Page(items: $items, nextCursor: $next ?: null, hasMore: $next !== '');
    }

    /** @return list<array<string,mixed>> Lấy toàn bộ transactions của statement, paginate đến hết (safety cap 2000). */
    private function fetchStatementTransactions(AuthContext $auth, string $statementId): array
    {
        if ($statementId === '') {
            return [];
        }
        $ver = $this->client->versionFor('finance_transactions') ?: '202309';
        // BUG-fix: thay CẢ {version} lẫn {statement_id} (trước đây chỉ thay {statement_id} ⇒ kẹt literal {version}).
        $path = str_replace(['{version}', '{statement_id}'], [$ver, $statementId], (string) (config('integrations.tiktok.endpoints.finance_statement_transactions') ?? '/finance/{version}/statements/{statement_id}/statement_transactions'));

        $allTx = [];
        $cursor = null;
        do {
            // BUG-fix: `sort_field` là BẮT BUỘC (chỉ nhận `order_create_time`) — thiếu sẽ bị API từ chối.
            $params = ['page_size' => 100, 'sort_field' => 'order_create_time', 'sort_order' => 'ASC'];
            if ($cursor !== null) {
                $params['page_token'] = $cursor;
            }
            try {
                $resp = $this->client->get($path, $auth, $params);
            } catch (\Throwable $e) {
                Log::warning('tiktok.finance.statement_transactions_failed', ['statement' => $statementId, 'error' => $e->getMessage()]);
                break;
            }
            $tx = (array) (data_get($resp, 'data.statement_transactions') ?? data_get($resp, 'statement_transactions') ?? data_get($resp, 'data.transactions') ?? data_get($resp, 'transactions') ?? []);
            $allTx = array_merge($allTx, array_values(array_filter($tx, 'is_array')));
            $cursor = (string) (data_get($resp, 'data.next_page_token') ?? data_get($resp, 'next_page_token') ?? '');
        } while ($cursor !== '' && count($allTx) < 2000);

        return $allTx;
    }

    // --- After-sales (Hoàn & Hủy) — SPEC 0025 --------------------------------

    public function fetchReturns(AuthContext $auth, array $query = []): Page
    {
        return $this->searchAfterSales($auth, $query, ReturnDTO::KIND_RETURN);
    }

    public function fetchCancellations(AuthContext $auth, array $query = []): Page
    {
        return $this->searchAfterSales($auth, $query, ReturnDTO::KIND_CANCEL);
    }

    /**
     * `POST /return_refund/{ver}/returns/search` | `.../cancellations/search` — paginate theo update_time ASC.
     * Body lọc `update_time_ge` (window) + tùy chọn `return_status`/`cancel_status` (list). SDK returnRefundV202309.
     *
     * @param  array<string,mixed>  $query
     */
    private function searchAfterSales(AuthContext $auth, array $query, string $kind): Page
    {
        if (! config('integrations.tiktok.returns_enabled')) {
            throw UnsupportedOperation::for($this->code(), $kind === 'cancel' ? 'fetchCancellations' : 'fetchReturns');
        }
        $isCancel = $kind === ReturnDTO::KIND_CANCEL;
        $ver = $this->client->versionFor('return_refund') ?: '202309';
        $path = $isCancel ? "/return_refund/{$ver}/cancellations/search" : "/return_refund/{$ver}/returns/search";

        $q = [
            'page_size' => (string) min(100, max(1, (int) ($query['pageSize'] ?? 50))),
            'sort_field' => 'update_time',
            'sort_order' => 'ASC',
        ];
        if (! empty($query['cursor'])) {
            $q['page_token'] = (string) $query['cursor'];
        }
        $body = [];
        if (! empty($query['updatedFrom'])) {
            $body['update_time_ge'] = $query['updatedFrom']->getTimestamp();
        }
        if (! empty($query['statuses'])) {
            $body[$isCancel ? 'cancel_status' : 'return_status'] = array_values(array_map('strval', (array) $query['statuses']));
        }

        $data = $this->client->post($path, $auth, $body, $q);
        $listKey = $isCancel ? 'cancellations' : 'return_orders';
        $items = array_values(array_map(
            fn ($r) => TikTokMappers::returnRecord((array) $r, $kind),
            (array) ($data[$listKey] ?? []),
        ));
        $next = $data['next_page_token'] ?? null;

        return new Page(items: $items, nextCursor: $next ?: null, hasMore: ! empty($next));
    }

    public function decideReturn(AuthContext $auth, string $externalReturnId, string $action, array $params = []): array
    {
        return $this->decideAfterSales($auth, 'returns', $externalReturnId, $action, $params);
    }

    public function decideCancellation(AuthContext $auth, string $externalCancelId, string $action, array $params = []): array
    {
        return $this->decideAfterSales($auth, 'cancellations', $externalCancelId, $action, $params);
    }

    /**
     * `POST /return_refund/{ver}/{returns|cancellations}/{id}/{approve|reject}`. `$action` ∈ approve|reject.
     *
     * @param  array<string,mixed>  $params
     * @return array<string,mixed>
     */
    private function decideAfterSales(AuthContext $auth, string $resource, string $id, string $action, array $params): array
    {
        if (! config('integrations.tiktok.returns_enabled')) {
            throw UnsupportedOperation::for($this->code(), $resource === 'cancellations' ? 'decideCancellation' : 'decideReturn');
        }
        $op = strtolower($action) === 'reject' ? 'reject' : 'approve';
        $ver = $this->client->versionFor('return_refund') ?: '202309';
        $body = array_filter([
            'decision_role' => $params['decision_role'] ?? null,
            'comment' => $params['comment'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        return $this->client->post("/return_refund/{$ver}/{$resource}/{$id}/{$op}", $auth, $body);
    }

    // --- Báo cáo sàn (read-only) — Analytics shop performance, SPEC 2026-06-06 ---

    /**
     * Hiệu suất gian hàng TikTok — `/analytics/{ver}/shop/performance` (GMV, đơn, sản phẩm bán) cho
     * 7 ngày gần nhất, gộp (granularity=ALL), tiền tệ LOCAL. shop_cipher đính qua shopScoped=true.
     * Đây KHÔNG phải sức khỏe/điểm phạt (TikTok không có API cho phần đó).
     */
    public function fetchShopHealth(AuthContext $auth): ShopHealthDTO
    {
        $ver = (string) (config('integrations.tiktok.version.analytics') ?? '202509');
        $end = CarbonImmutable::now()->addDay();          // end_date_lt là exclusive
        $start = $end->subDays(8);
        $startStr = $start->format('Y-m-d');
        $endStr = $end->format('Y-m-d');

        $data = $this->client->get("/analytics/{$ver}/shop/performance", $auth, [
            'start_date_ge' => $startStr,
            'end_date_lt' => $endStr,
            'granularity' => 'ALL',
            'currency' => 'LOCAL',
        ]);

        return TikTokShopReport::health($data, ['start_date' => $startStr, 'end_date' => $endStr]);
    }

    public function fetchPenaltyPoints(AuthContext $auth): array
    {
        throw UnsupportedOperation::for($this->code(), 'fetchPenaltyPoints');
    }

    public function fetchPunishments(AuthContext $auth): array
    {
        throw UnsupportedOperation::for($this->code(), 'fetchPunishments');
    }
}
