<?php

namespace CMBcoreSeller\Integrations\Channels\TikTok;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\Contracts\ChannelConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\ChannelListingDTO;
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
 * TikTok Shop connector (API generation "202309"). Orders + auth + webhook for
 * Phase 1; listings/finance/fulfillment land in later phases (declared as
 * unsupported in the capability map so core hides those features rather than
 * branching on the provider name). All TikTok specifics — signing, versions,
 * status & event maps — live under app/Integrations/Channels/TikTok/ and
 * config('integrations.tiktok'). See docs/04-channels/tiktok-shop.md, SPEC 0001.
 */
class TikTokConnector implements ChannelConnector
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
            'shipping.document' => (bool) config('integrations.tiktok.fulfillment_enabled', true),
            'shipping.tracking' => false,     // Phase 3
            'listings.fetch' => true,         // Phase 2 — SPEC 0003 (fetchListings → channel_listings)
            'listings.publish' => false,      // Phase 5
            'listings.updateStock' => true,   // Phase 2 — SPEC 0003
            'listings.updatePrice' => false,  // Phase 5
            // Đối soát/Statements — Phase 6.2. Bật bằng INTEGRATIONS_TIKTOK_FINANCE=true sau khi đã đối chiếu
            // shape `/finance/202309/statements` với sandbox thực; mặc định off (TikTok finance API có thể đổi).
            'finance.settlements' => (bool) config('integrations.tiktok.finance_enabled', false),
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
        // TikTok's service-auth flow ignores any redirect_uri here — the callback URL is
        // configured in the Partner Center app. $opts is accepted to satisfy the interface.
        return $this->client->authorizeUrl($state);
    }

    public function exchangeCodeForToken(string $code): TokenDTO
    {
        return TikTokMappers::token($this->client->getAccessToken($code));
    }

    public function refreshToken(string $refreshToken): TokenDTO
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

        foreach ($events as $event) {
            try {
                $this->client->post("/event/{$ver}/webhooks", $auth, ['event_type' => $event, 'address' => $address]);
            } catch (\Throwable $e) {
                // Many apps configure webhook subscriptions in Partner Center instead — don't fail the connect flow.
                Log::info('tiktok.webhook.subscribe_failed', ['event' => $event, 'shop' => $auth->externalShopId, 'error' => class_basename($e)]);
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
        $ver = $this->client->versionFor('order');
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
            foreach (TikTokMappers::listings((array) $product) as $listing) {
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
    public function arrangeShipment(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        if (! config('integrations.tiktok.fulfillment_enabled')) {
            throw UnsupportedOperation::for($this->code(), 'arrangeShipment');
        }
        $packageId = $this->packageIdFrom($params);
        if ($packageId === '') {
            throw new \RuntimeException('Đơn TikTok chưa có package_id (TikTok tạo package sau khi đơn được thanh toán — thử "Đồng bộ đơn" lại).');
        }
        $body = $this->chooseHandover($auth, $packageId, $externalOrderId, $params);
        $shipPath = $this->fulfillmentPath('ship_package', '/fulfillment/{version}/packages/{package_id}/ship', $packageId, $externalOrderId);
        $this->client->post($shipPath, $auth, $body);

        $tracking = null;
        $carrier = null;
        try {
            $detail = $this->client->get($this->fulfillmentPath('package_detail', '/fulfillment/{version}/packages/{package_id}', $packageId, $externalOrderId), $auth);
            $tracking = data_get($detail, 'tracking_number') ?: data_get($detail, 'package.tracking_number');
            $carrier = data_get($detail, 'shipping_provider_name') ?: data_get($detail, 'package.shipping_provider_name');
        } catch (\Throwable $e) {
            Log::info('tiktok.get_package_detail_failed', ['package' => $packageId, 'error' => class_basename($e)]);
        }

        return ['raw_status' => 'AWAITING_COLLECTION', 'tracking_no' => $tracking, 'carrier' => $carrier, 'package_id' => $packageId];
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
        $ver = $this->client->versionFor('finance') ?: '202309';
        $statementsPath = (string) (config('integrations.tiktok.endpoints.finance_statements') ?? "/finance/{$ver}/statements");
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

    /** @return list<array<string,mixed>> raw transactions trả về theo statement (page 1 only — đủ cho đa số shop). */
    private function fetchStatementTransactions(AuthContext $auth, string $statementId): array
    {
        if ($statementId === '') {
            return [];
        }
        $ver = $this->client->versionFor('finance') ?: '202309';
        $path = (string) (config('integrations.tiktok.endpoints.finance_statement_transactions') ?? "/finance/{$ver}/statements/{$statementId}/statement_transactions");
        $path = str_replace('{statement_id}', $statementId, $path);
        try {
            $resp = $this->client->get($path, $auth, ['page_size' => 100]);
        } catch (\Throwable $e) {
            Log::warning('tiktok.finance.statement_transactions_failed', ['statement' => $statementId, 'error' => $e->getMessage()]);

            return [];
        }
        $tx = (array) (data_get($resp, 'data.statement_transactions') ?? data_get($resp, 'statement_transactions') ?? data_get($resp, 'data.transactions') ?? data_get($resp, 'transactions') ?? []);

        return array_values(array_filter($tx, 'is_array'));
    }
}
