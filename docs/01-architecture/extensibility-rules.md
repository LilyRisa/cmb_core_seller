# ★ Luật mở rộng — Connector & Registry Pattern

**Status:** Stable · **Cập nhật:** 2026-05-11

> Đây là tài liệu **quan trọng nhất** về "dễ mở rộng". Đọc kỹ trước khi đụng vào tích hợp sàn / ĐVVC. Nguyên tắc tối thượng: **CORE KHÔNG BAO GIỜ BIẾT TÊN MỘT SÀN HAY MỘT ĐVVC CỤ THỂ.** Thêm sàn/ĐVVC mới = thêm **một class** + **một dòng đăng ký**, không sửa core.

## 1. Hai trục mở rộng

| Trục | Interface | Registry | Thêm mới = |
|---|---|---|---|
| **Sàn TMĐT** | `ChannelConnector` | `ChannelRegistry` | 1 thư mục `app/Integrations/Channels/<Tên>/` + 1 dòng `register('<code>', XConnector::class)` |
| **Đơn vị vận chuyển** | `CarrierConnector` | `CarrierRegistry` | 1 thư mục `app/Integrations/Carriers/<Tên>/` + 1 dòng `register('<code>', XConnector::class)` |
| *(sau)* Cổng thanh toán | `PaymentGatewayConnector` | `PaymentRegistry` | tương tự |
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
