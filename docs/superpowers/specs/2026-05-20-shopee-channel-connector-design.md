# Connector Shopee (Channels) — parity với Lazada & TikTok Shop

- **Trạng thái:** Design draft (2026-05-20)
- **Phase:** Phase 4 — `04-channels/shopee.md` ("Chờ cấp API"), `04-channels/README.md` §5 (Shopee = chờ Phase 4). Connector mới, **không** đụng core.
- **Module backend chính:** `Integrations/Channels/Shopee` (mới) + sửa nhỏ shared OAuth seam (`ChannelConnector` interface, `ChannelConnectionService`, `OAuthCallbackController`, `TokenRefresher`) theo kiểu cộng tham số optional (không phá Lazada/TikTok/Manual).
- **Liên quan:** ADR-0004 (connector registry), `01-architecture/extensibility-rules.md`, `04-channels/README.md` (DTO chuẩn + capability map), `04-channels/shopee.md` (đặc tả sàn), `04-channels/tiktok-shop.md` + `lazada.md` (mẫu), specs `0001-tiktok-order-sync.md` + `0008-lazada-channel.md`, `03-domain/order-sync-pipeline.md`, `03-domain/order-status-state-machine.md` §4, SPEC-0016 (finance/settlement).
- **Nguồn API:** Shopee Open Platform API v2 — production `partner.shopeemobile.com`; sandbox VN/Global `openplatform.sandbox.test-stable.shopee.sg` (xác minh từ docs chính thức, xem `shopee_docs/`). ⚠ `partner.test-stable.shopeemobile.com` (SDK cộng đồng) là SAI.

---

## 0. Bối cảnh & nguyên tắc

`shopee.md` yêu cầu: "Khi có credentials, viết theo **đúng cấu trúc** của `tiktok-shop.md`. Connector implement `ChannelConnector`, trả DTO chuẩn — **không** đụng core." Route `/webhook/shopee` + `/oauth/shopee/callback` + `throttle.shopee` **đã tồn tại sẵn**; dòng đăng ký connector đã được stub comment trong `IntegrationsServiceProvider`.

Connector này được build **faithful theo tài liệu công khai Shopee v2 + contract test (`Http::fake`)**; **PHẢI verify lại trên sandbox Shopee** trước khi bật production (`INTEGRATIONS_CHANNELS` **không** gồm `shopee` mặc định; `finance_enabled` mặc định `false`). Pattern y hệt cách Lazada/TikTok đã được làm trước khi có sandbox thật.

**Quy tắc bất di bất dịch (extensibility-rules §1–6):** mọi cái riêng của Shopee (ký, version, tên trường, mã trạng thái, mã phí, loại push) sống trong `app/Integrations/Channels/Shopee/`. Core chỉ thấy DTO chuẩn. Không `if ($provider === 'shopee')` ở core.

---

## 1. Trong / ngoài phạm vi

### Trong (parity đầy đủ — full)
- **OAuth:** `buildAuthorizationUrl` (`/api/v2/shop/auth_partner`), `exchangeCodeForToken` (`/api/v2/auth/token/get`), `refreshToken` (`/api/v2/auth/access_token/get`), `fetchShopInfo` (`/api/v2/shop/get_shop_info`).
- **Đơn:** `fetchOrders` (`get_order_list` + `get_order_detail`, tự chia cửa sổ ≤15 ngày, cursor), `fetchOrderDetail`, `mapStatus`, `unprocessedRawStatuses`.
- **Webhook:** `verifyWebhookSignature` (Authorization = HMAC(`url|body`)), `parseWebhook` (push `code` → type, giải mã `data` JSON-string).
- **Fulfillment ("luồng A"):** `arrangeShipment` (`get_shipping_parameter` → `ship_order` → `get_tracking_number`), `getShippingDocument` (async: `create` → poll `get_result` → `download`). `pushReadyToShip` = `UnsupportedOperation` (Shopee không có bước RTS riêng).
- **Listings & tồn kho:** `fetchListings` (`get_item_list` + `get_item_base_info` + `get_model_list`), `updateStock` (`update_stock`).
- **Tài chính:** `fetchSettlements` (`get_escrow_detail` per order → gom kỳ → `SettlementDTO`), gate `finance_enabled=false` mặc định.
- **Shared seam:** thêm tham số optional `array $context = []` vào `ChannelConnector::exchangeCodeForToken` + `refreshToken` để thread `shop_id` (Shopee cần). Lazada/TikTok/Manual nhận param nhưng **bỏ qua** ⇒ không đổi hành vi.
- **Config:** khối `shopee` trong `config/integrations.php`. Uncomment đăng ký trong `IntegrationsServiceProvider`.
- **Tests:** `ShopeeConnectorContractTest` + `ShopeeFixtures` + `ShopeeSignerTest` (unit) + 1 feature sync/webhook test (DB).
- **Docs:** cập nhật `04-channels/shopee.md` (điền 1→7), `04-channels/README.md` §5 bảng trạng thái, `03-domain/order-status-state-machine.md` §4 (cột Shopee).

### Ngoài phạm vi (YAGNI / để sau)
- **Returns** (`get_return_list`) — Lazada/TikTok cũng chưa có method `fetchReturns` trong interface ⇒ Shopee parity = chưa làm. Capability `returns.fetch=false`.
- **Listing publish/update price** (`listings.publish`/`updatePrice=false` — như Lazada/TikTok hiện tại).
- **Bật production** — `shopee` không vào `INTEGRATIONS_CHANNELS` mặc định; chỉ bật sau khi verify sandbox.
- **Chat/messaging Shopee** — đã loại ở SPEC-0024 (chờ hạ tầng này); không thuộc spec này.
- **FE** — trang `/channels` đã generic (nút "Kết nối Shopee" hiện disabled "Phase 4"); khi `shopee` được bật trong `INTEGRATIONS_CHANNELS`, `connectable_providers` tự xuất hiện. Không cần code FE mới (đã xác nhận luồng generic ở SPEC trước). Nút disabled "Phase 4" hard-code trong `ChannelsPage` sẽ tự bị thay bằng nút thật khi provider connectable — **không sửa FE trong spec này** (out of scope; nếu muốn gỡ nhãn "Phase 4" thì là 1 dòng, để tuỳ chọn).

---

## 2. Shared seam — thread `shop_id` (không phá luồng cũ)

Shopee cần `shop_id` ở 2 call mà interface hiện không truyền: `exchangeCodeForToken` (lúc connect) và `refreshToken` (lúc làm tươi token). Giải pháp **cộng tham số optional** (đúng tinh thần extensibility-rules §3 "field nullable / optional"):

| File | Thay đổi | Tác động Lazada/TikTok/Manual |
|---|---|---|
| `Contracts/ChannelConnector.php` | `exchangeCodeForToken(string $code, array $context = []): TokenDTO`; `refreshToken(string $refreshToken, array $context = []): TokenDTO` | Không (default `[]`) |
| `Lazada/LazadaConnector.php`, `TikTok/TikTokConnector.php`, `Manual/ManualConnector.php` | Thêm `array $context = []` vào 2 method (bỏ qua) | Không đổi hành vi |
| `Services/ChannelConnectionService.php` | `completeConnect(string $provider, string $code, string $stateValue, array $callbackParams = [])`; gọi `exchangeCodeForToken($code, $callbackParams)` | Lazada/TikTok bỏ qua `$callbackParams` |
| `Http/Controllers/OAuthCallbackController.php` | Truyền `$request->query()` làm `$callbackParams` vào `completeConnect` | Không |
| `Support/TokenRefresher.php` | `refreshToken((string) $account->refresh_token, ['shop_id' => $account->external_shop_id])` | Lazada/TikTok bỏ qua |

> ⚠️ **KHÔNG** đụng `MessagingConnector` interface (riêng biệt; FacebookPage/TikTokChat/LazadaChat dùng nó) — chỉ sửa `ChannelConnector`.

`fetchShopInfo`: Shopee đọc `shop_id` từ `$auth->extra['token_raw']['shop_id']` (response `token/get` của Shopee có `shop_id`) — **giống hệt** cách Lazada đọc `country_user_info` từ `token_raw` (xem `completeConnect` line 75-78). Không cần đổi `fetchShopInfo` signature.

---

## 3. Files mới — `app/Integrations/Channels/Shopee/`

### 3.1 `ShopeeSigner.php`
Pure static, HMAC-SHA256 hex thường:
```php
/** Public API (token get/refresh, auth_partner): base = partner_id + path + timestamp. */
public static function signPublic(string $partnerKey, int $partnerId, string $path, int $timestamp): string;
/** Shop API: base = partner_id + path + timestamp + access_token + shop_id. */
public static function signShop(string $partnerKey, int $partnerId, string $path, int $timestamp, string $accessToken, string $shopId): string;
```
Nối chuỗi thuần (không separator). `hash_hmac('sha256', $base, $partnerKey)` → lowercase hex. Có unit test với vector cố định (`ShopeeSignerTest`).

### 3.2 `ShopeeClient.php`
HTTP client ký sẵn cho `config('integrations.shopee.base_url')`:
- `publicPost(string $path, array $body): array` / `publicGet(...)` — ký `signPublic`, không token.
- `shopGet(AuthContext $auth, string $path, array $query = []): array` / `shopPost(...)` — tự nhét `partner_id`, `timestamp`, `sign` (signShop với `$auth->accessToken` + `$auth->externalShopId`), `shop_id`, `access_token` vào **query string**; body JSON cho POST.
- Helper OAuth: `getAccessToken(string $code, string $shopId): array`, `refreshAccessToken(string $refreshToken, string $shopId): array`, `authorizeUrl(string $redirectUri): string`.
- Rate limiter Redis theo `throttle.shopee` (mẫu `LazadaClient`); retry theo `http.retries`/`retry_sleep_ms`; timeout `http.timeout`.
- Parse lỗi: nếu body có `error` (string `error_*`) khác rỗng ⇒ ném `ShopeeApiException($error, $message, $httpStatus)`.
- Tự chia cửa sổ ≤15 ngày là việc của connector (`fetchOrders`), không phải client.

### 3.3 `ShopeeMappers.php`
Toàn bộ field-name knowledge → DTO chuẩn (mẫu `LazadaMappers`):
- `token(array $res): TokenDTO` — `access_token`, `refresh_token`, `expire_in`(s)→`expiresAt`, refresh ~30d→`refreshExpiresAt`, `raw` giữ `shop_id`.
- `shopInfo(array $res, string $shopId): ShopInfoDTO`.
- `order(array $detail): OrderDTO` — map `order_sn`→externalOrderId, `order_status`→rawStatus, `update_time`(unix)→sourceUpdatedAt, buyer (`recipient_address`), địa chỉ VN (full_address/region/state/city/district/town/zipcode), tiền (`total_amount`, `actual_shipping_fee`/`estimated_shipping_fee`, voucher seller/Shopee), `cod` (`payment_method`/`cod`), `item_list`→OrderItemDTO (`item_id`/`model_id`/`model_sku`/`item_sku`), `package_list`→packages (tracking/carrier). Tiền VND = int.
- `listings(array $itemBase, array $models): list<ChannelListingDTO>` — 1 entry/model (variant); item không model ⇒ 1 entry model_id=0.
- `settlement(array $escrowRows, CarbonImmutable $from, CarbonImmutable $to): SettlementDTO` — gom dòng phí.
- `money()`, `time()` helpers.

### 3.4 `ShopeeStatusMap.php`
`toStandard(string $raw): StandardOrderStatus` đọc `config('integrations.shopee.status_map')`. Mapping (verify sandbox):
| Shopee raw | StandardOrderStatus | Ghi chú |
|---|---|---|
| `UNPAID` | `unpaid` | |
| `READY_TO_SHIP` | `pending` | Đã thanh toán, chưa arrange → "Chờ xử lý" |
| `PROCESSED` | `processing` | Đã arrange/ship_order, chờ pickup → "Đang xử lý/Chờ bàn giao" |
| `RETRY_SHIP` | `processing` | |
| `SHIPPED` | `shipped` | |
| `TO_CONFIRM_RECEIVE` | `delivered` | Đã giao, chờ buyer xác nhận |
| `COMPLETED` | `completed` | |
| `IN_CANCEL` | `processing` | Buyer xin huỷ, chưa chốt — giữ active (verify) |
| `CANCELLED` | `cancelled` | |
| `TO_RETURN` | `returning` | |

`unprocessedRawStatuses()` = `['READY_TO_SHIP']` (đơn chưa bàn giao; dùng cho sync mode `unprocessed`).

### 3.5 `ShopeeWebhookVerifier.php`
- `verify(Request $request): bool` — header `Authorization` == `hash_hmac('sha256', $pushUrl.'|'.$rawBody, $partnerKey)` (lowercase hex). `$pushUrl` = `config('integrations.shopee.push_url')` (mặc định `APP_URL.'/webhook/shopee'`). `webhook_verify_mode` `strict`(default)/`lenient` (mẫu Lazada).
- `parse(Request $request): WebhookEventDTO` — đọc `code`(int) → type qua `config('integrations.shopee.webhook_event_types')`; `shop_id`→externalShopId; `data` là **JSON-string** ⇒ `json_decode` lần 2 để lấy `ordersn`/`status`. `occurredAt` từ `timestamp`(unix).

### 3.6 `ShopeeApiException.php`
`RuntimeException` + `public string $shopeeError` (vd `error_auth`,`error_sign`,`error_param`,`error_permission`) + `?int $httpStatus`. `isAuthError()` (`error_auth`), `isRateLimited()` (`error_busy`/HTTP 429). `OAuthCallbackController` thêm 1 nhánh `$e instanceof ShopeeApiException => 'shopee_api_error'` (+ `sp_code`) để surface lỗi (mẫu Lazada/TikTok).

### 3.7 `ShopeeConnector.php`
Implement `ChannelConnector` (~22 method). Inject `ShopeeClient`. `capabilities()`:
```
orders.fetch=true, orders.webhook=true, orders.confirm=false,
shipping.arrange=true (gate fulfillment_enabled), shipping.ready_to_ship=false,
shipping.document=true (gate fulfillment_enabled), shipping.tracking=true,
listings.fetch=true, listings.publish=false, listings.updateStock=true, listings.updatePrice=false,
finance.settlements=<finance_enabled>, returns.fetch=false
```
- `arrangeShipment`: short-circuit nếu order đã có tracking; `get_shipping_parameter` để biết `pickup`/`dropoff`; `ship_order` (ưu tiên dropoff nếu sàn cho, else pickup slot đầu); `get_tracking_number`. Trả `{tracking_no, carrier, raw_status:'PROCESSED', package_id:order_sn}`. Mode `refetch_only` (config) ⇒ chỉ refetch lấy tracking.
- `getShippingDocument`: `create_shipping_document` → poll `get_shipping_document_result` (tối đa `document_poll_attempts`×`document_poll_sleep_ms`, mặc định 6×1000ms) tới `READY` → `download_shipping_document` → `{filename, mime:'application/pdf', bytes}`. Quá hạn/`FAILED` ⇒ ném `ShopeeApiException`.
- `pushReadyToShip` ⇒ `UnsupportedOperation::for('shopee','pushReadyToShip')`.
- `fetchSettlements` ⇒ gate `finance.settlements`; chưa bật ⇒ vẫn implement (đọc cờ), method gọi escrow.

---

## 4. Config block (`config/integrations.php`)

```php
'shopee' => [
    'partner_id'   => env('SHOPEE_PARTNER_ID'),            // int
    'partner_key'  => env('SHOPEE_PARTNER_KEY'),           // bí mật — ký HMAC
    'sandbox'      => (bool) env('SHOPEE_SANDBOX', true),
    'base_url'     => env('SHOPEE_API_BASE_URL', 'https://partner.shopeemobile.com'),
    'redirect_uri' => env('SHOPEE_REDIRECT_URI'),          // default url('/oauth/shopee/callback')
    'push_url'     => env('SHOPEE_PUSH_URL'),               // default url('/webhook/shopee') — để verify chữ ký push
    'http'         => ['timeout' => 20, 'retries' => 2, 'retry_sleep_ms' => 500],
    'webhook_verify_mode' => env('SHOPEE_WEBHOOK_VERIFY_MODE', 'strict'),
    'order_window_days'   => 15,                            // max get_order_list window
    'fulfillment_enabled' => (bool) env('INTEGRATIONS_SHOPEE_FULFILLMENT', true),
    'fulfillment_mode'    => env('SHOPEE_FULFILLMENT_MODE', 'auto'),  // 'auto' | 'refetch_only'
    'finance_enabled'     => (bool) env('INTEGRATIONS_SHOPEE_FINANCE', false),
    'endpoints' => [
        'auth_partner'              => '/api/v2/shop/auth_partner',
        'token_get'                 => '/api/v2/auth/token/get',
        'token_refresh'             => '/api/v2/auth/access_token/get',
        'shop_info'                 => '/api/v2/shop/get_shop_info',
        'order_list'                => '/api/v2/order/get_order_list',
        'order_detail'              => '/api/v2/order/get_order_detail',
        'shipping_parameter'        => '/api/v2/logistics/get_shipping_parameter',
        'ship_order'                => '/api/v2/logistics/ship_order',
        'tracking_number'           => '/api/v2/logistics/get_tracking_number',
        'create_document'           => '/api/v2/logistics/create_shipping_document',
        'get_document_result'       => '/api/v2/logistics/get_shipping_document_result',
        'download_document'         => '/api/v2/logistics/download_shipping_document',
        'item_list'                 => '/api/v2/product/get_item_list',
        'item_base_info'            => '/api/v2/product/get_item_base_info',
        'model_list'                => '/api/v2/product/get_model_list',
        'update_stock'              => '/api/v2/product/update_stock',
        'escrow_detail'             => '/api/v2/payment/get_escrow_detail',
        'escrow_list'               => '/api/v2/payment/get_escrow_list',
    ],
    'document_type'        => env('SHOPEE_DOCUMENT_TYPE', 'NORMAL_AIR_WAYBILL'),
    'document_poll_attempts' => (int) env('SHOPEE_DOC_POLL_ATTEMPTS', 6),
    'document_poll_sleep_ms' => (int) env('SHOPEE_DOC_POLL_SLEEP_MS', 1000),
    'status_map' => [ /* xem §3.4 */ ],
    'webhook_event_types' => [
        1 => 'shop_deauthorized',   // shop authorization/deauthorization (partner-level)
        3 => 'order_status_update', // order status (data.ordersn, data.status)
        4 => 'product_update',      // item update (verify)
        6 => 'order_status_update', // tracking number update → re-fetch order
        // 2,5,7..13: verify sandbox trước khi dùng — default 'unknown'
    ],
],
```
`THROTTLE_SHOPEE_PER_MIN` (đã có `integrations.throttle.shopee`). `INTEGRATIONS_CHANNELS` **không** thêm `shopee` mặc định.

Đăng ký: trong `IntegrationsServiceProvider::$channelConnectors` đổi dòng comment thành:
```php
'shopee' => \CMBcoreSeller\Integrations\Channels\Shopee\ShopeeConnector::class,
```

---

## 5. Luồng dữ liệu (đối chiếu pipeline hiện có — không đổi core)

- **Connect:** FE `/channels` → `POST /channel-accounts/shopee/connect` → `startConnect` → `buildAuthorizationUrl` (auth_partner) → seller đồng ý → `GET /oauth/shopee/callback?code&shop_id&state` → `OAuthCallbackController` truyền query → `completeConnect($provider,$code,$state,$query)` → `exchangeCodeForToken($code, ['shop_id'=>...])` → `fetchShopInfo` (shop_id từ token_raw) → upsert `channel_accounts` → `registerWebhooks` (no-op/log; Shopee cấu hình push URL ở console) → `SyncOrdersForShop` backfill.
- **Đồng bộ đơn:** `SyncOrdersForShop` (poll/backfill/unprocessed) → `fetchOrders` (chia ≤15 ngày) → `mapStatus` → `OrderUpsertService`. Không đổi job.
- **Webhook:** `POST /webhook/shopee` → `WebhookController@handle` → `WebhookIngestService` → `verifyWebhookSignature` + `parseWebhook` → dedupe → `ProcessWebhookEvent` (order_status_update → re-fetch detail; shop_deauthorized → revoke). Không đổi controller/service.
- **Fulfillment:** `ShipmentService` (luồng A) gọi `arrangeShipment` (capability check) + `getShippingDocument`. Không đổi service.
- **Refresh token:** `TokenRefresher` (scheduler/khi 401) → `refreshToken($rt, ['shop_id'=>external_shop_id])`.
- **Token refresh tự động:** Shopee access 4h ⇒ `TokenRefresher` đã có cơ chế refresh trước hạn (token_expires_at) — chỉ cần connector trả `expiresAt` đúng.

---

## 6. Edge case & lỗi

| Tình huống | Xử lý |
|---|---|
| Cửa sổ `get_order_list` > 15 ngày (backfill 90 ngày) | Connector chia thành các đoạn ≤15 ngày, gọi lặp + gộp; cursor trong mỗi đoạn |
| `data` push là JSON-string | `parseWebhook` json_decode 2 lần; lỗi parse ⇒ type `unknown`, vẫn ack 200 |
| Chữ ký push sai | `verify` false ⇒ 401 (strict); `lenient` ⇒ true + log (chỉ dev) |
| Token access hết hạn (4h) | `ShopeeApiException::isAuthError` → `TokenRefresher` refresh bằng refresh_token + shop_id; hết refresh (30d) ⇒ `status=expired` + reconnect |
| Document chưa `READY` sau poll | Ném `ShopeeApiException` → job in/AWB retry sau (không tạo file rỗng) |
| Đơn nhiều kiện / SPX mặc định | `package_list`/`package_number`; arrange theo package; carrier từ tracking response |
| `error_sign` | Lệch timestamp/sai base string ⇒ exception rõ; log path + tên tham số (không log secret) |
| Shop bị ban (code 2) | (nếu xác nhận) map `shop_deauthorized` → revoke; mặc định để `unknown` tới khi verify |
| Rate limit 429 / `error_busy` | Retry backoff (client) |

---

## 7. Bảo mật
- `partner_key` + access/refresh token: **không log**, mã hoá khi lưu (`channel_accounts.access_token/refresh_token` đã `encrypted` cast), đọc từ config.
- `verifyWebhookSignature` sai ⇒ 401, không lưu payload.
- Log chỉ tên tham số/path, không giá trị token/sign (mẫu `LazadaClient` `log_requests`).

---

## 8. Kiểm thử (bắt buộc — extensibility-rules §5)

### 8.1 Unit
- `ShopeeSignerTest`: `signPublic`/`signShop` xác định + vector cố định (giống `TikTokSignerTest`).

### 8.2 Contract (`ShopeeConnectorContractTest`, `Http::fake`, không DB)
- `exchangeCodeForToken` → TokenDTO (access/refresh/expiry; raw có shop_id).
- `refreshToken($rt, ['shop_id'=>...])` → TokenDTO; assert body có shop_id + sign public.
- `fetchShopInfo` đọc shop_id từ token_raw → ShopInfoDTO.
- `fetchOrders`: chia cửa sổ ≤15 ngày (assert ≥2 call khi range 90 ngày); get_order_detail theo lô; → OrderDTO chuẩn (tiền VND int, items, địa chỉ).
- `mapStatus` đủ bảng §3.4.
- `verifyWebhookSignature` happy/unhappy (Authorization = HMAC(url|body)); `parseWebhook` code 3 → order_status_update + externalOrderId từ data JSON-string; code 1 → shop_deauthorized.
- `arrangeShipment` (fake get_shipping_parameter+ship_order+tracking) → tracking/carrier.
- `getShippingDocument` async: create→result(READY)→download → bytes; result FAILED ⇒ exception.
- `fetchListings` → ChannelListingDTO/model; `updateStock` body shape.
- `fetchSettlements` (escrow) → SettlementDTO (gate finance_enabled=true trong test).
- `pushReadyToShip` ⇒ UnsupportedOperation.
- Fixtures: `tests/fixtures/Channels/shopee/ShopeeFixtures.php` (mẫu `TikTokFixtures`): `configure()`, `tokenGet()`, `orderList()`, `orderDetail()`, `shippingParameter()`, `shipOrder()`, `trackingNumber()`, `createDocument()`, `documentResult()`, `itemList()`, `modelList()`, `escrowDetail()`, `pushOrderStatus()`.

### 8.3 Feature (DB, `RefreshDatabase`)
- `ShopeeSyncTest`: tạo `ChannelAccount(provider=shopee)` → dispatch `SyncOrdersForShop` (Http::fake) → assert `Order` upsert đúng status; dispatch `ProcessWebhookEvent` (push code 3) → re-fetch + cập nhật.

---

## 9. Tiêu chí hoàn thành (Acceptance)
- [ ] 7 file Shopee tạo đủ; `ShopeeConnector` implement đủ interface (method không hỗ trợ ⇒ `UnsupportedOperation`).
- [ ] Shared seam: interface + 3 connector + `completeConnect` + `OAuthCallbackController` + `TokenRefresher` cập nhật; **Lazada/TikTok test cũ vẫn xanh** (no regression).
- [ ] Khối config `shopee` + uncomment đăng ký.
- [ ] `OAuthCallbackController` thêm nhánh lỗi `shopee_api_error`.
- [ ] Unit + contract + feature test xanh; contract test phủ mọi capability=true.
- [ ] Docs cập nhật: `shopee.md` (điền), `README.md` §5 (Shopee = Implemented faithful, chờ verify sandbox), `order-status-state-machine.md` §4 (cột Shopee).
- [ ] `INTEGRATIONS_CHANNELS` **không** auto-bật shopee; `finance_enabled` mặc định false. `.env.example` thêm `SHOPEE_*` (commented/empty).
- [ ] Không có `if ($provider==='shopee')` ở core; core diff = 0 (chỉ seam optional-param).

---

## 10. Lộ trình triển khai (slice — cho implementation plan)
1. **Seam**: interface optional `$context` + 3 connector + completeConnect + controller + TokenRefresher; chạy lại test Lazada/TikTok (no regression). (test)
2. **Signer + Client + Exception** + `ShopeeSignerTest`. (test)
3. **OAuth**: connector OAuth methods + Mappers.token/shopInfo + config block + đăng ký; contract test OAuth. (test)
4. **Orders**: fetchOrders (chia cửa sổ) + fetchOrderDetail + Mappers.order + StatusMap; contract test. (test)
5. **Webhook**: WebhookVerifier + parse + config event map; contract test. (test)
6. **Fulfillment**: arrangeShipment + getShippingDocument (async) + pushReadyToShip(unsupported); contract test. (test)
7. **Listings & stock**: fetchListings + updateStock; contract test. (test)
8. **Finance**: fetchSettlements (escrow) + Mappers.settlement; contract test (gate on). (test)
9. **Feature DB test** `ShopeeSyncTest` + fixtures hoàn chỉnh. (test)
10. **Docs** + `.env.example`; chạy toàn bộ suite Channels (no regression).
