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
            'shipping.arrange' => false,      // Phase 4 fulfillment
            'shipping.document' => false,     // Phase 4 fulfillment (Lazada AWB)
            'shipping.tracking' => false,     // Phase 4 fulfillment
            'listings.fetch' => true,
            'listings.publish' => false,      // Phase 5
            'listings.updateStock' => true,
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

    // --- Fulfillment (Phase 4 — RTS / AWB) -----------------------------------

    public function arrangeShipment(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        throw UnsupportedOperation::for($this->code(), 'arrangeShipment');
    }

    public function getShippingDocument(AuthContext $auth, string $externalOrderId, array $query = []): array
    {
        throw UnsupportedOperation::for($this->code(), 'getShippingDocument');
    }
}
