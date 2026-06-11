<?php

namespace CMBcoreSeller\Integrations\Channels\Shopee;

use Carbon\CarbonImmutable;
use CMBcoreSeller\Integrations\Channels\Contracts\ChannelConnector;
use CMBcoreSeller\Integrations\Channels\Contracts\PenaltyWebhookConnector;
use CMBcoreSeller\Integrations\Channels\Contracts\ShopReportConnector;
use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\DTO\OrderDTO;
use CMBcoreSeller\Integrations\Channels\DTO\Page;
use CMBcoreSeller\Integrations\Channels\DTO\PenaltyEventDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ShopHealthDTO;
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
class ShopeeConnector implements ChannelConnector, PenaltyWebhookConnector, ShopReportConnector
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
            // Shopee cấp mã vận đơn ASYNC (3PL), nhưng AWB/tem là bước create_shipping_document ĐỘC LẬP, lấy
            // được ngay khi đơn đã arrange (doc order-management §8) ⇒ KHÔNG chờ tracking mới kéo tem. TikTok/
            // Lazada KHÔNG khai cờ này (giữ luồng cũ: chỉ lấy tem sau khi đã có tracking).
            'shipping.document_before_tracking' => $fulfill,
            'listings.fetch' => true, 'listings.publish' => true,
            'listings.taxonomy' => true, 'listings.media' => true, 'listings.statusRead' => true,
            'listings.updateStock' => true, 'listings.updatePrice' => false,
            'finance.settlements' => $finance,
            // After-sales (Hoàn & Hủy) — SPEC 0025. Tắt bằng INTEGRATIONS_SHOPEE_RETURNS=false.
            'returns.fetch' => (bool) config('integrations.shopee.returns_enabled', true),
            'returns.manage' => (bool) config('integrations.shopee.returns_enabled', true),
            // Báo cáo sàn — AccountHealth (module 103): sức khỏe + điểm phạt + hình phạt.
            // Cần Shopee cấp quyền module 103; nếu chưa, API trả error_api_permission (xử lý ở service).
            'report.health' => true,
            'report.penalty' => true,
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
        $winEnd = $this->shopeeWindowEnd($winStart, $to->getTimestamp(), $windowDays);
        if ($winEnd <= $winStart) {
            return new Page([], null, false); // cửa sổ rỗng (winStart ≥ to) — Shopee từ chối khi time_from ≥ time_to
        }

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

    /**
     * Order status đã qua bước arrange (Shopee: get_shipping_parameter/ship_order chỉ hợp lệ ở READY_TO_SHIP;
     * gọi ở các trạng thái này ⇒ `error_param "...only...when package is ready to be shipped"`).
     */
    private const ALREADY_ARRANGED_STATUSES = ['PROCESSED', 'RETRY_SHIP', 'SHIPPED', 'TO_CONFIRM_RECEIVE', 'COMPLETED'];

    public function arrangeShipment(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        $cfg = $this->client->cfg();
        $packageNumber = (string) ($params['packages'][0]['externalPackageId'] ?? '');

        // Idempotency + precondition dựa trên ORDER_STATUS, KHÔNG dựa trên tracking. CỐT LÕI: Shopee
        // **pre-assign `tracking_number` ngay ở READY_TO_SHIP** (trước khi seller ship_order) — nó nằm trong
        // get_order_detail.package_list lẫn get_tracking_number. Nếu short-circuit theo tracking (như trước),
        // đơn chưa thực sự arrange ⇒ `create_shipping_document` báo `logistics.tracking_number_invalid`. Vì vậy:
        //  - PROCESSED+ (đã arrange thật): KHÔNG ship lại; trả tracking đang có (nếu Shopee đã cấp hợp lệ).
        //  - chưa tới READY_TO_SHIP (UNPAID/IN_CANCEL/CANCELLED/TO_RETURN): báo lỗi rõ ràng.
        //  - READY_TO_SHIP (hoặc '' do đọc detail lỗi → degrade): LUÔN ship_order (không tin tracking pre-assigned).
        $status = $this->currentOrderStatus($auth, $externalOrderId);
        if (in_array($status, self::ALREADY_ARRANGED_STATUSES, true)) {
            return ['tracking_no' => $this->safeTracking($auth, $externalOrderId, $packageNumber), 'carrier' => null, 'raw_status' => $status, 'package_id' => $packageNumber ?: $externalOrderId];
        }
        if ($status !== '' && $status !== 'READY_TO_SHIP') {
            throw new ShopeeApiException(
                "Shopee đơn {$externalOrderId} không ở trạng thái READY_TO_SHIP (hiện tại: {$status}) — chưa thể tạo phiếu giao hàng.",
                'error_param',
            );
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
                    // Thông báo tiếng Việt, KHÔNG lộ tên biến env lên UI.
                    throw new ShopeeApiException("Shopee chưa có địa chỉ lấy hàng (pickup) cho đơn {$externalOrderId}. Kiểm tra cấu hình lấy hàng/giao hàng trên Shopee Seller Center, hoặc chuyển sang gửi tại bưu cục.", 'error_param');
                }
            }
            $this->client->shopPost($auth, $this->client->endpoint('ship_order'), [], $body);
        }

        return [
            'tracking_no' => $this->safeTracking($auth, $externalOrderId, $packageNumber),
            'carrier' => null,
            'raw_status' => 'PROCESSED',
            'package_id' => $packageNumber ?: $externalOrderId,
        ];
    }

    /**
     * Đọc mã vận đơn qua get_tracking_number (doc v2.logistics.get_tracking_number). Nuốt lỗi + coi mã rỗng
     * là "chưa cấp" ⇒ trả null (3PL cấp trễ — doc cho phép poll trong ~5'; backfill job retry tiếp).
     *
     * `response_optional_fields`: BẮT BUỘC để lấy first_mile/last_mile/plp — đơn Cross-Border KHÔNG có
     * `tracking_number` mà mã nằm ở `first_mile_tracking_number` (doc FAQ order-management §8 + response spec).
     */
    private function safeTracking(AuthContext $auth, string $externalOrderId, string $packageNumber): ?string
    {
        try {
            $track = $this->client->shopGet($auth, $this->client->endpoint('tracking_number'), array_filter([
                'order_sn' => $externalOrderId,
                'package_number' => $packageNumber ?: null,
                'response_optional_fields' => 'first_mile_tracking_number,last_mile_tracking_number,plp_number',
            ]));
        } catch (\Throwable) {
            return null;
        }

        // Ưu tiên tracking_number; fallback các trường Cross-Border. (hint giải thích vì sao rỗng, vd CVS đóng.)
        foreach (['tracking_number', 'first_mile_tracking_number', 'last_mile_tracking_number', 'plp_number'] as $field) {
            $v = trim((string) ($track[$field] ?? ''));
            if ($v !== '') {
                return $v;
            }
        }

        return null;
    }

    /**
     * Đọc order_status hiện tại (UPPERCASE) qua get_order_detail. Tolerant: trả '' khi lỗi/không thấy đơn ⇒
     * caller degrade an toàn (không chặn arrange). Match theo order_sn (response có thể chứa nhiều đơn).
     */
    private function currentOrderStatus(AuthContext $auth, string $externalOrderId): string
    {
        try {
            $res = $this->client->shopGet($auth, $this->client->endpoint('order_detail'), [
                'order_sn_list' => $externalOrderId,
                'response_optional_fields' => 'order_status',
            ]);
        } catch (\Throwable) {
            return '';
        }
        $rows = (array) ($res['order_list'] ?? []);
        foreach ($rows as $row) {
            if (is_array($row) && (string) ($row['order_sn'] ?? '') === $externalOrderId) {
                return strtoupper((string) ($row['order_status'] ?? ''));
            }
        }

        return strtoupper((string) (($rows[0]['order_status'] ?? '')));
    }

    public function pushReadyToShip(AuthContext $auth, string $externalOrderId, array $params = []): array
    {
        throw UnsupportedOperation::for($this->code(), 'pushReadyToShip'); // Shopee has no separate RTS step
    }

    public function getShippingDocument(AuthContext $auth, string $externalOrderId, array $query = []): array
    {
        $cfg = $this->client->cfg();
        $packageNumber = (string) ($query['externalPackageId'] ?? '');

        // create_shipping_document YÊU CẦU `tracking_number` cho kênh tích hợp (SPX...) — THIẾU ⇒
        // `logistics.tracking_number_invalid` (chính là lý do tem Shopee không bao giờ lấy được). Ưu tiên mã
        // do caller (shipment) truyền vào; fallback get_tracking_number. Doc v2.logistics.create_shipping_document.
        $trackingNo = trim((string) ($query['tracking_no'] ?? ''));
        if ($trackingNo === '') {
            $trackingNo = (string) ($this->safeTracking($auth, $externalOrderId, $packageNumber) ?? '');
        }

        // Loại tem theo kênh: suggest_shipping_document_type (vd SPX gợi ý THERMAL_AIR_WAYBILL; có kênh chỉ cho
        // THERMAL). create/result/download PHẢI cùng MỘT loại. Fallback config nếu không lấy được.
        $docType = $this->resolveDocumentType($auth, $externalOrderId, $packageNumber)
            ?: (string) ($cfg['document_type'] ?? 'NORMAL_AIR_WAYBILL');

        $orderEntry = array_filter([
            'order_sn' => $externalOrderId,
            'package_number' => $packageNumber ?: null,
            'tracking_number' => $trackingNo ?: null,
            'shipping_document_type' => $docType,
        ]);

        try {
            $this->client->shopPost($auth, $this->client->endpoint('create_document'), [], ['order_list' => [$orderEntry]]);
        } catch (ShopeeApiException $e) {
            // `common.batch_api_all_failed` giấu lý do thật trong result_list[].fail_error/fail_message — bóc ra
            // để log/báo cho user biết vì sao (vd package chưa sẵn sàng, sai shipping_document_type).
            $reason = $this->batchFailReason($e->response);
            if ($reason !== '') {
                throw new ShopeeApiException("Shopee tạo tem thất bại cho đơn {$externalOrderId}: {$reason}", $e->shopeeError, $e->httpStatus, $e->response);
            }
            throw $e;
        }

        $attempts = (int) ($cfg['document_poll_attempts'] ?? 6);
        $sleepMs = (int) ($cfg['document_poll_sleep_ms'] ?? 1000);
        $ready = false;
        for ($i = 0; $i < $attempts; $i++) {
            $res = $this->client->shopPost($auth, $this->client->endpoint('get_document_result'), [], [
                'order_list' => [array_filter(['order_sn' => $externalOrderId, 'package_number' => $packageNumber ?: null, 'shipping_document_type' => $docType])],
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

    /**
     * Loại tem sàn gợi ý cho đơn (get_shipping_document_parameter → suggest_shipping_document_type).
     * Null nếu không lấy được (endpoint chưa cấu hình / lỗi) ⇒ caller fallback config.document_type.
     */
    private function resolveDocumentType(AuthContext $auth, string $externalOrderId, string $packageNumber): ?string
    {
        try {
            $r = $this->client->shopPost($auth, $this->client->endpoint('document_parameter'), [], [
                'order_list' => [array_filter(['order_sn' => $externalOrderId, 'package_number' => $packageNumber ?: null])],
            ]);
            $suggest = trim((string) ($r['result_list'][0]['suggest_shipping_document_type'] ?? ''));

            return $suggest !== '' ? $suggest : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Bóc lý do thật của batch error Shopee (`create_shipping_document` → `common.batch_api_all_failed`):
     * lấy `fail_error`/`fail_message` của item đầu tiên trong `result_list`. Rỗng ⇒ caller giữ lỗi gốc.
     *
     * @param  array<string,mixed>|null  $response
     */
    private function batchFailReason(?array $response): string
    {
        foreach ((array) ($response['result_list'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $err = trim((string) ($row['fail_error'] ?? ''));
            $msg = trim((string) ($row['fail_message'] ?? ''));
            if ($err !== '' || $msg !== '') {
                return trim($err.($err !== '' && $msg !== '' ? ': ' : '').$msg);
            }
        }

        return '';
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
            // Shopee v2 get_escrow_list trả `escrow_list[]` (mỗi item là object có `order_sn`),
            // KHÔNG phải `order_sn_list[]`. Đọc sai field ⇒ 0 order_sn ⇒ settlement 0 line (bug đối soát).
            // Vẫn nhận `order_sn_list`/chuỗi phẳng để an toàn ngược với mọi shape cũ.
            foreach ((array) ($list['escrow_list'] ?? []) as $row) {
                $sns[] = is_array($row) ? (string) ($row['order_sn'] ?? '') : (string) $row;
            }
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
        $cfg = $this->client->cfg();
        $windowDays = (int) ($cfg['order_window_days'] ?? 15);
        $pageSize = min(100, max(1, (int) ($query['pageSize'] ?? 50)));
        $from = $query['updatedFrom'] ?? CarbonImmutable::now()->subDays($windowDays);
        $to = $query['updatedTo'] ?? CarbonImmutable::now();

        // get_return_list giới hạn create_time_from..create_time_to ≤ 15 ngày ⇒ chia cửa sổ như fetchOrders.
        // cursor mã hoá "windowStartUnix:pageNo" (page_no 0-based của get_return_list trong cùng cửa sổ).
        [$winStart, $inner] = $this->decodeCursor((string) ($query['cursor'] ?? ''), $from);
        $winEnd = $this->shopeeWindowEnd($winStart, $to->getTimestamp(), $windowDays);
        if ($winEnd <= $winStart) {
            return new Page([], null, false); // cửa sổ rỗng — tránh create_time_from ≥ create_time_to
        }
        $pageNo = $inner !== '' ? (int) $inner : 0;

        $params = [
            'page_no' => $pageNo, 'page_size' => $pageSize,
            'create_time_from' => $winStart, 'create_time_to' => $winEnd,
        ];
        $res = $this->client->shopGet($auth, $this->client->endpoint('return_list'), $params);
        $items = array_values(array_map(fn ($r) => ShopeeMappers::returnRecord((array) $r), (array) ($res['return'] ?? [])));
        $more = (bool) ($res['more'] ?? false);

        if ($more) {
            return new Page($items, $winStart.':'.($pageNo + 1), true);   // còn trang trong cùng cửa sổ
        }
        if ($winEnd < $to->getTimestamp()) {
            return new Page($items, ($winEnd + 1).':', true);             // sang cửa sổ kế (page_no reset về 0)
        }

        return new Page($items, null, false);
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

    /**
     * Span tối đa (giây) cho một cửa sổ `get_order_list` / `get_return_list`: Shopee chặn
     * `order.order_list_invalid_time` khi `time_to − time_from` ≥ 15 ngày. Để margin 1s để
     * span LUÔN < 15 ngày, tránh bị từ chối ngay ở biên (đúng 15×86400).
     */
    private const SHOPEE_MAX_WINDOW_SECONDS = 15 * 86400 - 1;

    /** Cận trên (unix) an toàn của cửa sổ: không vượt $toTs và span < 15 ngày kể từ $winStart. */
    private function shopeeWindowEnd(int $winStart, int $toTs, int $windowDays): int
    {
        $span = min(max(1, $windowDays) * 86400, self::SHOPEE_MAX_WINDOW_SECONDS);

        return min($toTs, $winStart + $span);
    }

    // --- Báo cáo sàn (read-only) — AccountHealth module 103, SPEC 2026-06-06 ---

    public function fetchShopHealth(AuthContext $auth): ShopHealthDTO
    {
        $res = $this->client->shopGet($auth, '/api/v2/account_health/get_shop_performance');

        return ShopeeShopReport::health($res);
    }

    public function fetchPenaltyPoints(AuthContext $auth): array
    {
        $res = $this->client->shopGet($auth, '/api/v2/account_health/get_penalty_point_history', [
            'page_no' => 1,
            'page_size' => 100,
        ]);

        return ShopeeShopReport::penalties($res);
    }

    public function fetchPunishments(AuthContext $auth): array
    {
        // punishment_status = 1 (Ongoing): chỉ lấy hình phạt đang hiệu lực.
        $res = $this->client->shopGet($auth, '/api/v2/account_health/get_punishment_history', [
            'page_no' => 1,
            'page_size' => 100,
            'punishment_status' => 1,
        ]);

        return ShopeeShopReport::punishments($res);
    }

    /**
     * Bóc push điểm phạt Shopee → PenaltyEventDTO:
     *  - code 28 `shop_penalty_update_push`: action 1=cấp điểm, 2=gỡ điểm, 3=đổi bậc phạt.
     *  - code 16 `violation_item_push`: listing bị BANNED/deboost.
     */
    public function parsePenaltyWebhook(array $rawPush): array
    {
        $code = (int) ($rawPush['code'] ?? 0);
        $data = (array) ($rawPush['data'] ?? []);
        $at = isset($rawPush['timestamp']) ? CarbonImmutable::createFromTimestamp((int) $rawPush['timestamp']) : null;
        $occurred = isset($data['update_time']) ? CarbonImmutable::createFromTimestamp((int) $data['update_time']) : $at;

        if ($code === 28) {
            $action = (int) ($data['action_type'] ?? 0);
            if ($action === 1) {
                $d = (array) ($data['points_issued_data'] ?? []);
                $vt = isset($d['violation_type']) ? (int) $d['violation_type'] : null;

                return [new PenaltyEventDTO(kind: 'penalty_issued', points: (int) ($d['issued_points'] ?? 0),
                    violationType: $vt, violationLabel: ShopeeShopReport::violationLabel($vt), occurredAt: $occurred, raw: $data)];
            }
            if ($action === 2) {
                $d = (array) ($data['points_removed_data'] ?? []);
                $vt = isset($d['violation_type']) ? (int) $d['violation_type'] : null;

                return [new PenaltyEventDTO(kind: 'penalty_removed', points: (int) ($d['removed_points'] ?? 0),
                    violationType: $vt, violationLabel: ShopeeShopReport::violationLabel($vt), occurredAt: $occurred, raw: $data)];
            }
            if ($action === 3) {
                $d = (array) ($data['tier_update_data'] ?? []);

                return [new PenaltyEventDTO(kind: 'tier_update', tier: isset($d['new_tier']) ? (int) $d['new_tier'] : null,
                    violationLabel: 'Cập nhật bậc phạt', occurredAt: $occurred, raw: $data)];
            }

            return [];
        }

        if ($code === 16) {
            return [new PenaltyEventDTO(
                kind: 'listing_violation',
                violationLabel: (string) ($data['violation_reason'] ?? $data['violation_type'] ?? 'Vi phạm listing'),
                itemId: isset($data['item_id']) ? (string) $data['item_id'] : null,
                itemName: isset($data['item_name']) ? (string) $data['item_name'] : null,
                occurredAt: $occurred,
                raw: $data,
            )];
        }

        return [];
    }
}
