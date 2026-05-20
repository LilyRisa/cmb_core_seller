# ★ Luật mở rộng — Connector & Registry Pattern

**Status:** Stable · **Cập nhật:** 2026-05-11

> Đây là tài liệu **quan trọng nhất** về "dễ mở rộng". Đọc kỹ trước khi đụng vào tích hợp sàn / ĐVVC. Nguyên tắc tối thượng: **CORE KHÔNG BAO GIỜ BIẾT TÊN MỘT SÀN HAY MỘT ĐVVC CỤ THỂ.** Thêm sàn/ĐVVC mới = thêm **một class** + **một dòng đăng ký**, không sửa core.

## 1. Hai trục mở rộng

| Trục | Interface | Registry | Thêm mới = |
|---|---|---|---|
| **Sàn TMĐT** | `ChannelConnector` | `ChannelRegistry` | 1 thư mục `app/Integrations/Channels/<Tên>/` + 1 dòng `register('<code>', XConnector::class)` |
| **Đơn vị vận chuyển** | `CarrierConnector` | `CarrierRegistry` | 1 thư mục `app/Integrations/Carriers/<Tên>/` + 1 dòng `register('<code>', XConnector::class)` |
| **Cổng thanh toán** *(Phase 6.4 — implemented SPEC-0018)* | `PaymentGatewayConnector` | `PaymentRegistry` | 1 thư mục `app/Integrations/Payments/<Tên>/` + 1 dòng `register('<code>', XConnector::class)` + 1 block trong `config/integrations.php` (`payments.<code>`) + 1 dòng vào `INTEGRATIONS_PAYMENTS` env csv. Đã có: `sepay` (chuyển khoản qua webhook sao kê), `vnpay` (redirect + IPN HMAC-SHA512), `momo` (skeleton — capability=false). |
| **Nền tảng nhắn tin (chat)** *(Phase 7.x đề xuất — SPEC-0024 Draft, ADR-0017)* | `MessagingConnector` | `MessagingRegistry` | 1 thư mục `app/Integrations/Messaging/<Tên>/` + 1 dòng `register('<code>', XConnector::class)` + block `messaging.<code>` trong `config/integrations.php` + `INTEGRATIONS_MESSAGING` env csv. Dự kiến: `facebook_page`, `tiktok_chat`, `shopee_chat`, `lazada_chat`. Capability map riêng (`outbound.text/image/video/file/template`, `inbound.webhook`, `read_receipt`, `typing`). Window rule per provider qua `outboundWindow()`. |
| **AI assistant (LLM)** *(Phase 7.x đề xuất — SPEC-0024 Draft, ADR-0018)* | `AiAssistantConnector` | `AiAssistantRegistry` | 1 thư mục `app/Integrations/Ai/<Tên>/` + 1 dòng `register('<code>', XConnector::class)` + provider config trong `system_settings` group `ai_providers.<code>` (super-admin SPA). Dự kiến: `claude`, `openai`, `gemini`, `local_llm`. **Tenant KHÔNG tự nhập API key** — chỉ chọn 1 trong list `is_active=true`. |
| *(sau)* Hoá đơn điện tử | `EInvoiceConnector` | `EInvoiceRegistry` | tương tự |

## 2. Hợp đồng `ChannelConnector` (định hình — chi tiết DTO ở `04-channels/README.md`)

```
interface ChannelConnector
{
    // --- Định danh ---
    public function code(): string;                 // 'tiktok' | 'shopee' | 'lazada'
    public function displayName(): string;          // 'TikTok Shop'

    // --- Kết nối (OAuth) ---
    public function buildAuthorizationUrl(string $state, array $opts = []): string;
    public function exchangeCodeForToken(string $code): TokenDTO;
    public function refreshToken(string $refreshToken): TokenDTO;
    public function fetchShopInfo(AuthContext $auth): ShopInfoDTO;
    public function registerWebhooks(AuthContext $auth): void;       // nếu sàn hỗ trợ
    public function revoke(AuthContext $auth): void;

    // --- Đơn hàng ---
    public function fetchOrders(AuthContext $auth, OrderQuery $q): Page;   // phân trang bằng cursor
    public function fetchOrderDetail(AuthContext $auth, string $externalOrderId): OrderDTO;
    public function parseWebhook(WebhookRequest $req): WebhookEventDTO;     // → loại event + ids liên quan
    public function mapStatus(string $rawStatus, array $rawOrder = []): StandardOrderStatus; // dùng bảng map

    // --- Sản phẩm / listing ---
    public function fetchListings(AuthContext $auth, ListingQuery $q): Page;
    public function fetchCategories(AuthContext $auth): array;             // cây danh mục
    public function fetchCategoryAttributes(AuthContext $auth, string $catId): array;
    public function publishListing(AuthContext $auth, ListingDraftDTO $d): ListingDTO;
    public function updateListing(AuthContext $auth, string $listingId, array $patch): ListingDTO;
    public function updateStock(AuthContext $auth, string $externalSkuId, int $available): void;
    public function updatePrice(AuthContext $auth, string $externalSkuId, Money $price): void;

    // --- Giao hàng / in ấn (qua logistics của sàn) ---
    public function getShippingOptions(AuthContext $auth, string $externalOrderId): array;
    public function arrangeShipment(AuthContext $auth, ArrangeShipmentDTO $d): ShipmentDTO; // → tracking
    public function getShippingDocument(AuthContext $auth, ShippingDocQuery $q): BinaryFile; // label PDF
    public function getTracking(AuthContext $auth, string $trackingNo): TrackingDTO;

    // --- Tài chính ---
    public function fetchSettlements(AuthContext $auth, DateRange $r): Page;     // → SettlementLineDTO[]
    public function fetchOrderFees(AuthContext $auth, string $externalOrderId): array;

    // --- Hậu mãi ---
    public function fetchReturns(AuthContext $auth, ReturnQuery $q): Page;
}
```

> Một sàn chưa hỗ trợ một method → ném `UnsupportedOperation` (có sẵn). Core kiểm `supports('publishListing')` trước khi gọi (capability map). **Không** vì một sàn thiếu mà thêm `if ($provider === 'shopee')` ở core — thêm vào capability map của connector đó.

## 3. Hợp đồng `CarrierConnector` (cho ĐVVC riêng — đơn manual & đơn tự xử lý)

```
interface CarrierConnector
{
    public function code(): string;                 // 'ghn' | 'ghtk' | 'jt' | 'viettelpost' | ...
    public function displayName(): string;
    public function services(CarrierAccount $acc): array;             // dịch vụ + vùng phủ
    public function quote(CarrierAccount $acc, QuoteRequest $q): array; // phí + thời gian dự kiến
    public function createShipment(CarrierAccount $acc, CreateShipmentDTO $d): ShipmentDTO; // → tracking
    public function getLabel(CarrierAccount $acc, string $trackingNo, LabelFormat $fmt): BinaryFile;
    public function getTracking(CarrierAccount $acc, string $trackingNo): TrackingDTO;
    public function cancel(CarrierAccount $acc, string $trackingNo): void;
    public function parseWebhook(WebhookRequest $req): TrackingEventDTO; // nếu ĐVVC có webhook
}
```

## 4. Registry — đăng ký & lấy connector

```php
// app/Integrations/Channels/ChannelRegistry.php (singleton)
$registry->register('tiktok', TikTokConnector::class);
$registry->register('shopee', ShopeeConnector::class);   // thêm dòng này khi có API
$registry->register('lazada', LazadaConnector::class);   // thêm dòng này khi có API

// Dùng ở module Channels/Orders/Fulfillment...:
$connector = app(ChannelRegistry::class)->for($channelAccount->provider);
$orderDto  = $connector->fetchOrderDetail($channelAccount->authContext(), $externalId);
```

- Đăng ký ở `ChannelServiceProvider::boot()` / `CarrierServiceProvider::boot()` — đọc danh sách từ config `config/integrations.php` để bật/tắt connector mà không sửa code.
- "manual" cũng là một `ChannelConnector` rỗng (`ManualConnector`) để code đối xử mọi nguồn đơn đồng nhất.

## 5. Quy trình "Thêm một sàn mới" (checklist)

1. Tạo `app/Integrations/Channels/<Name>/` với: `XConnector` (implement `ChannelConnector`), `XClient` (HTTP + ký + version), `XStatusMap` (config raw→chuẩn), `XMappers` (raw payload → DTO chuẩn), `XWebhookVerifier`.
2. Viết **contract test** dùng response mẫu (fixtures) — chạy trong CI (xem `09-process/testing-strategy.md`).
3. Thêm `register('<code>', XConnector::class)` + bật trong `config/integrations.php`.
4. Thêm route `/webhook/<code>` (dùng controller webhook chung, chỉ khác verifier) — không thêm logic mới ở core.
5. Thêm doc `docs/04-channels/<code>.md` (auth/ký, endpoint dùng, event webhook, bảng status map, version).
6. Thêm vào capability map (sàn này hỗ trợ những method nào).
7. UI: connector tự khai báo metadata (logo, các trường cấu hình khi connect) → SPA render động, không hard-code từng sàn.

✅ **Không** được làm khi thêm sàn: sửa `OrderUpsertService`, sửa state machine core, thêm `if/switch` theo tên sàn ở module nghiệp vụ, đụng vào DTO chuẩn theo kiểu "chỉ sàn này mới có field X" (nếu thực sự cần field mới → thêm vào DTO chuẩn + tất cả connector khác trả `null`, và ghi ADR).

## 6. Quy trình "Thêm một ĐVVC mới" (checklist)
1. `app/Integrations/Carriers/<Name>/` với `XCarrierConnector`, `XClient`, mappers.
2. Contract test với fixtures.
3. `CarrierRegistry::register('<code>', XCarrierConnector::class)` + bật trong config.
4. Doc `docs/03-domain/carriers.md` cập nhật (hoặc file riêng nếu dài).
5. UI cấu hình tài khoản ĐVVC render động từ metadata connector.

## 6b. Quy trình "Thêm một cổng thanh toán mới" (Phase 6.4 / SPEC 0018)

Mirror pattern của Channels/Carriers — `app/Integrations/Payments/` đã có:
- `Contracts/PaymentGatewayConnector.php` — interface chung (capabilities: `checkout`/`webhook`/`refund`/`query`; method: `code`, `displayName`, `method`, `checkout`, `verifyWebhookSignature`, `parseWebhook`, `queryStatus`, `assertConfigured`).
- `Contracts/DTOs/CheckoutRequest`, `CheckoutSession`, `PaymentNotification` — DTO chuẩn (không tied tới gateway nào).
- `Exceptions/UnsupportedOperation`, `GatewayNotConfigured`.
- `PaymentRegistry.php` — singleton, register by code, resolve `for($code)`.

Hai biến thể UX:
- `method='bank_transfer'` — SePay (FE hiện QR + memo, poll `payment-status`).
- `method='redirect'` — VNPay/MoMo (FE redirect tới `redirectUrl`).

Checklist thêm cổng mới:
1. Tạo `app/Integrations/Payments/<Name>/` với `XConnector` + `XSigner`/`XWebhookVerifier` nếu cần.
2. Contract test (`Tests/Feature/Billing/<Name>Test.php`) — signer deterministic + webhook valid/invalid + parse payload fixture.
3. Thêm vào `$paymentConnectors` ở `IntegrationsServiceProvider` + `app->bind` để inject config.
4. Thêm block `payments.<code>` vào `config/integrations.php` + `.env.example` cập nhật.
5. Bật trong `INTEGRATIONS_PAYMENTS` env csv khi đi production.

**KHÔNG** sửa `BillingController`/`PaymentService`/`ProcessPaymentWebhook` để thêm cổng — chúng đã gateway-agnostic qua `PaymentRegistry`.

## 6c. Quy trình "Thêm một nền tảng nhắn tin mới" (SPEC-0024 / ADR-0017)

Mirror pattern Channels/Carriers/Payments — `app/Integrations/Messaging/` sẽ có:
- `Contracts/MessagingConnector.php` — interface chung (capabilities: `inbound.webhook`, `outbound.text/image/video/file/template`, `read_receipt`, `typing`; method: `code`, `displayName`, `buildAuthorizationUrl`, `exchangeCodeForToken`, `refreshToken`, `registerWebhooks`, `parseWebhook`, `verifyWebhookSignature`, `fetchConversations`, `fetchMessages`, `sendText`, `sendMedia`, `sendTemplate`, `outboundWindow`).
- `Contracts/DTOs/`: `MessagingAuthContext`, `ConversationDTO`, `MessageDTO`, `MessagingWebhookEventDTO`, `SendResultDTO`, `MediaRefDTO`, `OutboundWindowPolicyDTO`.
- `Exceptions/`: `UnsupportedOperation`, `OutboundWindowClosed`, `ConversationClosed`, `TokenExpired`.
- `MessagingRegistry.php` — singleton.

Checklist thêm provider mới:
1. Tạo `app/Integrations/Messaging/<Name>/` với `XConnector` + `XClient` + `XWebhookVerifier` + `XMappers` (raw → DTO chuẩn).
2. Contract test (`Tests/Feature/Messaging/<Name>ConnectorTest.php`) — fixtures `Http::fake` cho mỗi message kind + verify signature happy/unhappy + window guard.
3. Thêm `MessagingRegistry::register('<code>', XConnector::class)` trong `MessagingServiceProvider::boot()`.
4. Thêm block `messaging.<code>` vào `config/integrations.php` + `.env.example` (key/secret/webhook_token) + `INTEGRATIONS_MESSAGING` env csv.
5. Thêm doc `docs/04-messaging/<code>.md` (auth, endpoint, event webhook, window rule, status map).
6. Capability map khai báo (provider hỗ trợ kind nào, có read_receipt không, …).
7. UI: connector tự khai báo metadata (logo, OAuth scope) → SPA render dynamic.

**KHÔNG** sửa `MessageIngestionService`/`MessageSendService`/`AutoReplyEngine` để thêm provider — chúng phải provider-agnostic qua `MessagingRegistry`. **Không** thêm `if ($provider === 'facebook_page')` ở core; khác biệt ⇒ capability map + DTO field nullable + connector-internal mapping.

## 6d. Quy trình "Thêm một AI provider mới" (SPEC-0024 / ADR-0018)

Mirror cùng pattern — `app/Integrations/Ai/`:
- `Contracts/AiAssistantConnector.php` — interface (capabilities: `reply.suggest`, `reply.auto`, `intent.classify`, `rag.training`, `embedding`).
- `Contracts/DTOs/`: `AiContext`, `ConversationSnapshot`, `KnowledgeBase`, `AiReplyDTO`, `IntentDTO`, `EmbeddingDTO`.
- `AiAssistantRegistry.php` — singleton.

Khác Messaging/Channels/Carriers/Payments: **config sống ở DB**, không phải `config/integrations.php`:
- `system_settings` group `ai_providers.<code>` (key `is_active`, `api_key🔒`, `base_url`, `default_model`, `embedding_model?`, `pricing` jsonb micro_vnd/1k tokens, `capabilities` jsonb).
- Super-admin CRUD qua `/admin/ai-providers` (SPEC-0024 §6.1).
- Tenant chọn 1: `tenant_settings.messaging.ai_provider_code` ∈ list `is_active=true`.

Checklist thêm provider mới:
1. Tạo `app/Integrations/Ai/<Name>/` với `XConnector` (implement `AiAssistantConnector`) + `XClient` (HTTP client).
2. Contract test với mock LLM response (xác nhận DTO output đúng, error/timeout handling, redact PII trước khi gửi).
3. Thêm `AiAssistantRegistry::register('<code>', XConnector::class)` trong service provider.
4. Super-admin add provider vào `system_settings` (UI hoặc seeder dev).
5. Tenant chọn ở `/settings/messaging`.

**KHÔNG** sửa `AiReplyOrchestrator`/`IntentClassifier`/`KnowledgeIndexer` để thêm provider — chúng provider-agnostic.

## 7. Những điểm khác đã thiết kế để dễ mở rộng / bảo trì
- **Trạng thái đơn**: state machine + bảng mapping config (không hard-code) — xem `03-domain/order-status-state-machine.md`.
- **Template in**: layout lưu dạng dữ liệu (JSON/HTML), render bằng Gotenberg — thêm khổ giấy/mẫu mới không cần deploy.
- **Quy tắc tự động**: rules engine (điều kiện → hành động) — thêm hành động mới = thêm 1 "action handler".
- **Thông báo**: `NotificationChannel` adapter (email/in-app/Zalo OA/Telegram...) — thêm kênh = thêm 1 adapter.
- **DTO chuẩn ở giữa**: core ↔ DTO ↔ connector. Đổi API ngoài chỉ ảnh hưởng tầng mapper của connector đó.
- **API versioned** (`/api/v1`): đổi breaking → `/api/v2`, giữ `v1` một thời gian.
- **Migration partition theo tháng**: tự tạo partition tháng kế bằng job định kỳ; archive partition cũ — không phải đụng schema.
- **Config `config/integrations.php`**: bật/tắt sàn, ĐVVC, ĐVVC mặc định, throttle per sàn — đổi vận hành không sửa code.
- **i18n & money/address VN gói trong `app/Support`**: muốn thêm quốc gia sau = mở rộng support layer, không rải khắp nơi.
