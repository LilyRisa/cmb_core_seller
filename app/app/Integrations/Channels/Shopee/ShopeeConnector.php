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
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;

/**
 * Shopee Open Platform v2 connector. Mirrors Lazada/TikTok. See docs/04-channels/shopee.md
 * + spec docs/superpowers/specs/2026-05-20-shopee-channel-connector-design.md.
 */
class ShopeeConnector implements ChannelConnector
{
    public function __construct(private ShopeeClient $client, private ShopeeWebhookVerifier $verifier = new ShopeeWebhookVerifier) {}

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
        // Read finance_enabled live from config so tests and runtime overrides take effect immediately.
        $finance = (bool) config('integrations.shopee.finance_enabled', $cfg['finance_enabled'] ?? false);

        return [
            'orders.fetch' => true, 'orders.webhook' => true, 'orders.confirm' => false,
            'shipping.arrange' => $fulfill, 'shipping.ready_to_ship' => false,
            'shipping.document' => $fulfill, 'shipping.tracking' => true,
            'listings.fetch' => true, 'listings.publish' => false,
            'listings.updateStock' => true, 'listings.updatePrice' => false,
            'finance.settlements' => $finance,
            // After-sales (Hoàn & Hủy) — SPEC 0025. Tắt bằng INTEGRATIONS_SHOPEE_RETURNS=false.
            'returns.fetch' => (bool) config('integrations.shopee.returns_enabled', true),
            'returns.manage' => (bool) config('integrations.shopee.returns_enabled', true),
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
        // Có variant: externalSkuId là model_id. Không variant (externalSkuId == item_id): BỎ model_id
        // (theo body chính thức v2.product.update_stock — shopee_docs/05-stock-and-price.md).
        $sellerStock = ['seller_stock' => [['stock' => max(0, $available)]]];
        $entry = ((string) $externalSkuId === (string) $itemId)
            ? $sellerStock
            : ['model_id' => (int) $externalSkuId] + $sellerStock;
        $this->client->shopPost($auth, $this->client->endpoint('update_stock'), [], [
            'item_id' => $itemId,
            'stock_list' => [$entry],
        ]);
    }

    public function arrangeShipment(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        $cfg = $this->client->cfg();
        $packageNumber = (string) ($params['packages'][0]['externalPackageId'] ?? '');

        // Idempotency: đơn đã ship (đã có tracking) ⇒ trả luôn, KHÔNG gọi ship_order lại — re-call ship_order
        // trên đơn đã PROCESSED sẽ lỗi "logistic status not ready to ship". get_tracking_number trên đơn CHƯA
        // ship trả rỗng/lỗi ⇒ coi như chưa ship, tiến hành ship bình thường.
        $existingTrack = $this->safeTracking($auth, $externalOrderId, $packageNumber);
        if ($existingTrack !== null) {
            return ['tracking_no' => $existingTrack, 'carrier' => null, 'raw_status' => 'PROCESSED', 'package_id' => $packageNumber ?: $externalOrderId];
        }

        if ((string) ($cfg['fulfillment_mode'] ?? 'auto') !== 'refetch_only') {
            $param = $this->client->shopGet($auth, $this->client->endpoint('shipping_parameter'), array_filter(['order_sn' => $externalOrderId, 'package_number' => $packageNumber ?: null]));
            $body = ['order_sn' => $externalOrderId];
            // Shopee chỉ chấp nhận package_number ở ship_order khi đơn ĐÃ được tách (split) thành ≥2 kiện.
            // get_order_detail.package_list vẫn trả package_number cho cả đơn 1 kiện (chưa tách), nên phải xét
            // SỐ KIỆN chứ không xét chuỗi rỗng — gửi package_number cho đơn chưa tách ⇒ lỗi
            // `logistics.ship_order_not_need_pacakge_number`. Doc Shopee §8.3.2: ship_order chỉ cần order_sn.
            if ($packageNumber !== '' && count((array) ($params['packages'] ?? [])) > 1) {
                $body['package_number'] = $packageNumber;
            }
            $method = (string) ($cfg['ship_method'] ?? 'auto');
            // 'auto': ưu tiên dropoff khi sàn có khai báo dropoff cho đơn này; else pickup. Verify trên sandbox.
            $useDropoff = $method === 'dropoff' || ($method === 'auto' && array_key_exists('dropoff', $param) && $param['dropoff'] !== null && $param['dropoff'] !== []);
            if ($useDropoff) {
                $body['dropoff'] = (object) [];
            } else {
                $addr = (array) ($param['pickup']['address_list'][0] ?? []);
                $body['pickup'] = array_filter([
                    'address_id' => $addr['address_id'] ?? null,
                    'pickup_time_id' => $addr['time_slot_list'][0]['pickup_time_id'] ?? null,
                ]);
                if (empty($body['pickup']['address_id'])) {
                    throw new ShopeeApiException("Shopee get_shipping_parameter returned no pickup address for order {$externalOrderId}; set SHOPEE_SHIP_METHOD=dropoff or verify the shipping parameter response.", 'error_param');
                }
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

    /** get_tracking_number, nuốt lỗi + coi mã rỗng là "chưa ship" ⇒ trả null. Dùng cho idempotency arrange. */
    private function safeTracking(AuthContext $auth, string $externalOrderId, string $packageNumber): ?string
    {
        try {
            $track = $this->client->shopGet($auth, $this->client->endpoint('tracking_number'), array_filter([
                'order_sn' => $externalOrderId, 'package_number' => $packageNumber ?: null,
            ]));
        } catch (\Throwable) {
            return null;
        }
        $t = (string) ($track['tracking_number'] ?? '');

        return $t !== '' ? $t : null;
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
        if (! $this->supports('finance.settlements')) {
            throw UnsupportedOperation::for($this->code(), 'fetchSettlements');
        }
        $from = $query['from'] ?? CarbonImmutable::now()->subDays(15);
        $to = $query['to'] ?? CarbonImmutable::now();
        $sns = [];
        $pageNo = 1;
        for ($i = 0; $i < 100; $i++) { // safety cap 100 pages
            if ($i === 99) {
                Log::warning('shopee.settlement.page_cap_reached', ['from' => $from->toIso8601String(), 'to' => $to->toIso8601String(), 'sns_so_far' => count($sns)]);
            }
            $list = $this->client->shopGet($auth, $this->client->endpoint('escrow_list'), [
                'release_time_from' => $from->getTimestamp(), 'release_time_to' => $to->getTimestamp(),
                'page_size' => 100, 'page_no' => $pageNo,
            ]);
            foreach ((array) ($list['order_sn_list'] ?? []) as $sn) {
                $sns[] = (string) $sn;
            }
            if (! (bool) ($list['more'] ?? false)) {
                break;
            }
            $pageNo++;
        }
        $sns = array_values(array_unique(array_filter($sns)));
        $escrows = [];
        foreach ($sns as $sn) {
            $detail = $this->client->shopGet($auth, $this->client->endpoint('escrow_detail'), ['order_sn' => $sn]);
            $escrows[] = $detail;
        }
        $settlement = ShopeeMappers::settlement($escrows, $from, $to);

        return new Page([$settlement], null, false);
    }

    // --- After-sales (Hoàn & Hủy) — SPEC 0025. Field/endpoint đối chiếu doc 227; verify sandbox. ---

    public function fetchReturns(AuthContext $auth, array $query = []): Page
    {
        if (! $this->supports('returns.fetch')) {
            throw UnsupportedOperation::for($this->code(), 'fetchReturns');
        }
        $pageSize = min(100, max(1, (int) ($query['pageSize'] ?? 50)));
        $pageNo = (int) ($query['cursor'] ?? 0);   // get_return_list dùng page_no (0-based)
        $params = ['page_no' => $pageNo, 'page_size' => $pageSize];
        if (! empty($query['updatedFrom'])) {
            $params['create_time_from'] = $query['updatedFrom']->getTimestamp();
            $params['create_time_to'] = CarbonImmutable::now()->getTimestamp();
        }
        $res = $this->client->shopGet($auth, $this->client->endpoint('return_list'), $params);
        $items = array_values(array_map(fn ($r) => ShopeeMappers::returnRecord((array) $r), (array) ($res['return'] ?? [])));
        $more = (bool) ($res['more'] ?? false);

        return new Page($items, $more ? (string) ($pageNo + 1) : null, $more);
    }

    public function fetchCancellations(AuthContext $auth, array $query = []): Page
    {
        // Shopee không có API "cancel list" riêng — hủy hiển thị qua order status (IN_CANCEL/CANCELLED) đã
        // đồng bộ ở orders. Trả Page rỗng để job after-sales no-op cho nhánh cancel. SPEC 0025 §12.
        return new Page([], null, false);
    }

    public function decideReturn(AuthContext $auth, string $externalReturnId, string $action, array $params = []): array
    {
        if (! $this->supports('returns.manage')) {
            throw UnsupportedOperation::for($this->code(), 'decideReturn');
        }
        // confirm = đồng ý hoàn (Accepted); dispute = phản đối yêu cầu của buyer.
        $endpoint = strtolower($action) === 'reject' ? 'return_dispute' : 'return_confirm';
        $body = array_filter(['return_sn' => $externalReturnId, 'email' => $params['comment'] ?? null], fn ($v) => $v !== null && $v !== '');

        return $this->client->shopPost($auth, $this->client->endpoint($endpoint), [], $body);
    }

    public function decideCancellation(AuthContext $auth, string $externalCancelId, string $action, array $params = []): array
    {
        if (! $this->supports('returns.manage')) {
            throw UnsupportedOperation::for($this->code(), 'decideCancellation');
        }
        // handle_buyer_cancellation: ACCEPT (duyệt hủy) | REJECT. $externalCancelId = order_sn.
        $operation = strtolower($action) === 'reject' ? 'REJECT' : 'ACCEPT';

        return $this->client->shopPost($auth, $this->client->endpoint('handle_cancellation'), [], ['order_sn' => $externalCancelId, 'operation' => $operation]);
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
