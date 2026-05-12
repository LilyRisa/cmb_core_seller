<?php

namespace CMBcoreSeller\Integrations\Channels\TikTok;

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
            // "luồng A" (arrange ship + lấy tem) — TẮT mặc định; bật INTEGRATIONS_TIKTOK_FULFILLMENT=true sau
            // khi đã chỉnh `endpoints.ship_package` cho khớp Partner docs (lỗi gọi sàn khi bật vẫn được bắt &
            // gắn cờ has_issue trên đơn, không chặn — vẫn tạo vận đơn cục bộ). SPEC 0013/0014.
            'shipping.arrange' => (bool) config('integrations.tiktok.fulfillment_enabled', false),
            'shipping.document' => (bool) config('integrations.tiktok.fulfillment_enabled', false),
            'shipping.tracking' => false,     // Phase 3
            'listings.fetch' => true,         // Phase 2 — SPEC 0003 (fetchListings → channel_listings)
            'listings.publish' => false,      // Phase 5
            'listings.updateStock' => true,   // Phase 2 — SPEC 0003
            'listings.updatePrice' => false,  // Phase 5
            'finance.settlements' => false,   // Phase 6
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

    /**
     * "Luồng A" — TikTok "sắp xếp vận chuyển" cho gói: `POST {endpoints.ship_package}` (mặc định
     * `/fulfillment/{ver}/packages/{package_id}/ship`). ⚠️ Đường dẫn cần đối chiếu Partner docs/sandbox —
     * tắt mặc định (`INTEGRATIONS_TIKTOK_FULFILLMENT=false`); lỗi được {@see ShipmentService} bắt & gắn cờ
     * has_issue (không chặn). Trả `['raw_status','tracking_no','carrier','package_id']`. SPEC 0013/0014.
     *
     * @return array<string,mixed>
     */
    public function arrangeShipment(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        if (! config('integrations.tiktok.fulfillment_enabled')) {
            throw UnsupportedOperation::for($this->code(), 'arrangeShipment (đặt INTEGRATIONS_TIKTOK_FULFILLMENT=true để bật "luồng A")');
        }
        $pkgIds = array_values(array_filter(array_map(
            fn ($p) => is_array($p) ? data_get($p, 'externalPackageId', data_get($p, 'id', data_get($p, 'package_id'))) : null,
            (array) ($params['packages'] ?? []),
        )));
        $packageId = (string) ($pkgIds[0] ?? '');
        if ($packageId === '') {
            throw new \RuntimeException('Đơn TikTok chưa có package_id (đồng bộ đơn lại / kiểm tra trên app sàn). Không thể gọi arrange shipment.');
        }
        $path = $this->fulfillmentPath('ship_package', '/fulfillment/{version}/packages/{package_id}/ship', $packageId, $externalOrderId);
        $data = $this->client->post($path, $auth, []);
        $pkg = (array) data_get($data, 'packages.0', data_get($data, 'package', $data));

        return [
            'raw_status' => 'AWAITING_COLLECTION',
            'tracking_no' => data_get($pkg, 'tracking_number', data_get($data, 'tracking_number')),
            'carrier' => data_get($pkg, 'shipping_provider_name', data_get($data, 'shipping_provider_name')),
            'package_id' => $packageId,
        ];
    }

    /**
     * Lấy tem/AWB **thật** của TikTok (PDF bytes): `GET {endpoints.shipping_documents}` (mặc định
     * `/fulfillment/{ver}/packages/{package_id}/shipping_documents`). Trả bytes (tải `doc_url` nếu có, hoặc
     * decode base64 inline). Cần `externalPackageId`. SPEC 0006 §9.1.
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
        $path = $this->fulfillmentPath('shipping_documents', '/fulfillment/{version}/packages/{package_id}/shipping_documents', $packageId, $externalOrderId);
        $data = $this->client->get($path, $auth, ['document_type' => $docType]);
        $url = (string) (data_get($data, 'documents.0.doc_url') ?? data_get($data, 'doc_url') ?? '');
        $inline = (string) (data_get($data, 'documents.0.data') ?? data_get($data, 'data') ?? data_get($data, 'documents.0.file') ?? '');
        $bytes = '';
        if ($url !== '') {
            $resp = Http::timeout(30)->get($url);
            $bytes = $resp->successful() ? $resp->body() : '';
        } elseif ($inline !== '') {
            $bytes = base64_decode($inline, true) ?: $inline;
        }
        if ($bytes === '') {
            throw new \RuntimeException('TikTok không trả về tệp tem cho package '.$packageId.'.');
        }

        return ['filename' => "tiktok-label-{$packageId}.pdf", 'mime' => 'application/pdf', 'bytes' => $bytes];
    }
}
