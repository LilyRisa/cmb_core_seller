<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

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
use Symfony\Component\HttpFoundation\Request;

/**
 * Shopee Open Platform v2 connector. Mirrors Lazada/TikTok. See docs/04-channels/shopee.md
 * + spec docs/superpowers/specs/2026-05-20-shopee-channel-connector-design.md.
 */
class ShopeeConnector implements ChannelConnector
{
    public function __construct(private ShopeeClient $client, private ShopeeWebhookVerifier $verifier = new ShopeeWebhookVerifier()) {}

    public function code(): string
    {
        return 'shopee';
    }

    public function displayName(): string
    {
        return 'Shopee';
    }

    public function capabilities(): array
    {
        $cfg = $this->client->cfg();
        $fulfill = (bool) ($cfg['fulfillment_enabled'] ?? true);

        return [
            'orders.fetch' => true, 'orders.webhook' => true, 'orders.confirm' => false,
            'shipping.arrange' => $fulfill, 'shipping.ready_to_ship' => false,
            'shipping.document' => $fulfill, 'shipping.tracking' => true,
            'listings.fetch' => true, 'listings.publish' => false,
            'listings.updateStock' => true, 'listings.updatePrice' => false,
            'finance.settlements' => (bool) ($cfg['finance_enabled'] ?? false),
            'returns.fetch' => false,
        ];
    }

    public function supports(string $capability): bool
    {
        return (bool) ($this->capabilities()[$capability] ?? false);
    }

    public function buildAuthorizationUrl(string $state, array $opts = []): string
    {
        $redirect = (string) ($opts['redirect_uri'] ?? $this->client->redirectUri());
        $redirect .= (str_contains($redirect, '?') ? '&' : '?').'state='.urlencode($state);

        return $this->client->authorizeUrl($redirect);
    }

    public function exchangeCodeForToken(string $code, array $context = []): TokenDTO
    {
        $shopId = (string) ($context['shop_id'] ?? '');

        return ShopeeMappers::token($this->client->getAccessToken($code, $shopId), $shopId);
    }

    public function refreshToken(string $refreshToken, array $context = []): TokenDTO
    {
        $shopId = (string) ($context['shop_id'] ?? '');

        return ShopeeMappers::token($this->client->refreshAccessToken($refreshToken, $shopId), $shopId);
    }

    public function fetchShopInfo(AuthContext $auth): ShopInfoDTO
    {
        $shopId = (string) ($auth->extra['token_raw']['shop_id'] ?? $auth->externalShopId);
        $shopAuth = new AuthContext(0, 'shopee', $shopId, $auth->accessToken);
        $res = $this->client->shopGet($shopAuth, $this->client->endpoint('shop_info'));

        return ShopeeMappers::shopInfo($res, $shopId);
    }

    public function registerWebhooks(AuthContext $auth): void
    {
        // Shopee push URL is configured once in the app console — nothing to subscribe per-shop.
    }

    public function revoke(AuthContext $auth): void
    {
        // No Shopee API to revoke partner authorization from our side; seller cancels in Seller Center.
    }

    public function fetchOrders(AuthContext $auth, array $query = []): Page
    {
        $cfg = $this->client->cfg();
        $windowDays = (int) ($cfg['order_window_days'] ?? 15);
        $pageSize = min(100, max(1, (int) ($query['pageSize'] ?? 50)));
        $from = $query['updatedFrom'] ?? CarbonImmutable::now()->subDays($windowDays);
        $to = $query['updatedTo'] ?? CarbonImmutable::now();

        // cursor encodes "windowStartUnix:innerCursor"; first call has no cursor.
        [$winStart, $inner] = $this->decodeCursor((string) ($query['cursor'] ?? ''), $from);
        $winEnd = min($to->getTimestamp(), $winStart + $windowDays * 86400);

        $params = [
            'time_range_field' => 'update_time', 'time_from' => $winStart, 'time_to' => $winEnd,
            'page_size' => $pageSize, 'cursor' => $inner !== '' ? $inner : null,
        ];
        if (! empty($query['statuses'])) {
            if (count($query['statuses']) > 1) {
                throw new \InvalidArgumentException('Shopee get_order_list accepts a single order_status per call; pass one status per fetchOrders call.');
            }
            $params['order_status'] = (string) $query['statuses'][0];
        }
        $list = $this->client->shopGet($auth, $this->client->endpoint('order_list'), $params);

        $sns = array_values(array_filter(array_map(fn ($o) => (string) ($o['order_sn'] ?? ''), (array) ($list['order_list'] ?? []))));
        $orders = $sns === [] ? [] : $this->loadDetails($auth, $sns);

        $innerNext = (string) ($list['next_cursor'] ?? '');
        $hasInnerMore = (bool) ($list['more'] ?? false) && $innerNext !== '';
        if ($hasInnerMore) {
            return new Page($orders, $winStart.':'.$innerNext, true);
        }
        if ($winEnd < $to->getTimestamp()) {
            return new Page($orders, ($winEnd + 1).':', true); // +1s: time_from/time_to inclusive — avoid boundary dup
        }

        return new Page($orders, null, false);
    }

    public function fetchOrderDetail(AuthContext $auth, string $externalOrderId): OrderDTO
    {
        $orders = $this->loadDetails($auth, [$externalOrderId]);
        if ($orders === []) {
            throw new ShopeeApiException("Shopee order not found: {$externalOrderId}", 'error_not_found');
        }

        return $orders[0];
    }

    public function parseWebhook(Request $request): WebhookEventDTO
    {
        return $this->verifier->parse($request);
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        return $this->verifier->verify($request);
    }

    public function mapStatus(string $rawStatus, array $rawOrder = []): StandardOrderStatus
    {
        return ShopeeStatusMap::toStandard($rawStatus);
    }

    public function unprocessedRawStatuses(): array
    {
        return ['READY_TO_SHIP'];
    }

    public function fetchListings(AuthContext $auth, array $query = []): Page
    {
        $pageSize = min(100, max(1, (int) ($query['pageSize'] ?? 50)));
        $offset = (int) ($query['cursor'] ?? 0);
        $list = $this->client->shopGet($auth, $this->client->endpoint('item_list'), [
            'offset' => $offset, 'page_size' => $pageSize, 'item_status' => 'NORMAL',
        ]);
        $itemIds = array_values(array_filter(array_map(fn ($i) => (int) ($i['item_id'] ?? 0), (array) ($list['item'] ?? []))));
        $items = [];
        if ($itemIds !== []) {
            $base = $this->client->shopGet($auth, $this->client->endpoint('item_base_info'), ['item_id_list' => implode(',', $itemIds)]);
            foreach ((array) ($base['item_list'] ?? []) as $itemBase) {
                $models = $this->client->shopGet($auth, $this->client->endpoint('model_list'), ['item_id' => (int) ($itemBase['item_id'] ?? 0)]);
                foreach (ShopeeMappers::listings((array) $itemBase, $models) as $dto) {
                    $items[] = $dto;
                }
            }
        }
        $hasMore = (bool) ($list['has_next_page'] ?? false);

        return new Page($items, $hasMore ? (string) ((int) ($list['next_offset'] ?? ($offset + $pageSize))) : null, $hasMore);
    }

    public function updateStock(AuthContext $auth, string $externalSkuId, int $available, array $context = []): void
    {
        $itemId = (int) ($context['external_product_id'] ?? 0);
        if ($itemId === 0) {
            throw new ShopeeApiException('Shopee updateStock requires external_product_id (item_id).', 'error_param');
        }
        $this->client->shopPost($auth, $this->client->endpoint('update_stock'), [], [
            'item_id' => $itemId,
            'stock_list' => [[
                'model_id' => (int) $externalSkuId,
                'seller_stock' => [['stock' => max(0, $available)]],
            ]],
        ]);
    }

    public function arrangeShipment(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        $cfg = $this->client->cfg();
        $packageNumber = (string) ($params['packages'][0]['externalPackageId'] ?? '');

        if ((string) ($cfg['fulfillment_mode'] ?? 'auto') !== 'refetch_only') {
            $param = $this->client->shopGet($auth, $this->client->endpoint('shipping_parameter'), array_filter(['order_sn' => $externalOrderId, 'package_number' => $packageNumber ?: null]));
            $body = ['order_sn' => $externalOrderId];
            if ($packageNumber !== '') {
                $body['package_number'] = $packageNumber;
            }
            // Prefer dropoff when offered, else pickup with first available slot.
            if (! empty($param['dropoff'])) {
                $body['dropoff'] = (object) [];
            } else {
                $addr = (array) ($param['pickup']['address_list'][0] ?? []);
                $body['pickup'] = array_filter([
                    'address_id' => $addr['address_id'] ?? null,
                    'pickup_time_id' => $addr['time_slot_list'][0]['pickup_time_id'] ?? null,
                ]);
            }
            $this->client->shopPost($auth, $this->client->endpoint('ship_order'), [], $body);
        }

        $track = $this->client->shopGet($auth, $this->client->endpoint('tracking_number'), array_filter([
            'order_sn' => $externalOrderId, 'package_number' => $packageNumber ?: null,
        ]));

        return [
            'tracking_no' => ($track['tracking_number'] ?? null) ? (string) $track['tracking_number'] : null,
            'carrier' => null,
            'raw_status' => 'PROCESSED',
            'package_id' => $packageNumber ?: $externalOrderId,
        ];
    }

    public function pushReadyToShip(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        throw UnsupportedOperation::for($this->code(), 'pushReadyToShip'); // Shopee has no separate RTS step
    }

    public function getShippingDocument(AuthContext $auth, string $externalOrderId, array $query = []): array
    {
        $cfg = $this->client->cfg();
        $packageNumber = (string) ($query['externalPackageId'] ?? '');
        $docType = (string) ($cfg['document_type'] ?? 'NORMAL_AIR_WAYBILL');
        $orderEntry = array_filter(['order_sn' => $externalOrderId, 'package_number' => $packageNumber ?: null, 'shipping_document_type' => $docType]);

        $this->client->shopPost($auth, $this->client->endpoint('create_document'), [], ['order_list' => [$orderEntry]]);

        $attempts = (int) ($cfg['document_poll_attempts'] ?? 6);
        $sleepMs = (int) ($cfg['document_poll_sleep_ms'] ?? 1000);
        $ready = false;
        for ($i = 0; $i < $attempts; $i++) {
            $res = $this->client->shopPost($auth, $this->client->endpoint('get_document_result'), [], [
                'order_list' => [array_filter(['order_sn' => $externalOrderId, 'package_number' => $packageNumber ?: null])],
            ]);
            $status = (string) ($res['result_list'][0]['status'] ?? 'PROCESSING');
            if ($status === 'READY') {
                $ready = true;
                break;
            }
            if ($status === 'FAILED') {
                throw new ShopeeApiException("Shopee shipping document FAILED for {$externalOrderId}", 'document_failed');
            }
            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }
        if (! $ready) {
            throw new ShopeeApiException("Shopee shipping document not ready for {$externalOrderId} after {$attempts} attempts", 'document_timeout');
        }

        $bytes = $this->client->shopPostRaw($auth, $this->client->endpoint('download_document'), [
            'order_list' => [array_filter(['order_sn' => $externalOrderId, 'package_number' => $packageNumber ?: null, 'shipping_document_type' => $docType])],
        ]);

        return ['filename' => 'shopee-'.$externalOrderId.'.pdf', 'mime' => 'application/pdf', 'bytes' => $bytes];
    }

    public function fetchSettlements(AuthContext $auth, array $query = []): Page
    {
        throw UnsupportedOperation::for($this->code(), 'fetchSettlements'); // Task 8
    }

    /**
     * @param  list<string>  $sns
     * @return list<OrderDTO>
     */
    private function loadDetails(AuthContext $auth, array $sns): array
    {
        $out = [];
        foreach (array_chunk($sns, 50) as $chunk) {
            $res = $this->client->shopGet($auth, $this->client->endpoint('order_detail'), [
                'order_sn_list' => implode(',', $chunk),
                'response_optional_fields' => 'buyer_username,recipient_address,item_list,package_list,pay_time,total_amount,actual_shipping_fee,estimated_shipping_fee,cod,order_status,update_time,create_time',
            ]);
            foreach ((array) ($res['order_list'] ?? []) as $row) {
                $out[] = ShopeeMappers::order((array) $row);
            }
        }

        return $out;
    }

    /**
     * @return array{0:int,1:string} [windowStartUnix, innerCursor]
     */
    private function decodeCursor(string $cursor, CarbonImmutable $from): array
    {
        if ($cursor === '') {
            return [$from->getTimestamp(), ''];
        }
        $parts = explode(':', $cursor, 2);

        return [(int) $parts[0], $parts[1] ?? ''];
    }
}
