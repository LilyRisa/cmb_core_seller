# ADR-0017: Trục mở rộng thứ 4 — `MessagingConnector` + `MessagingRegistry`

- **Trạng thái:** Proposed
- **Ngày:** 2026-05-19
- **Người quyết định:** Team (chờ chủ dự án duyệt SPEC-0024)
- **Liên quan:** SPEC-0024, ADR-0004 (Connector/Registry), ADR-0007 (Webhook + polling), ADR-0018, ADR-0019

## Bối cảnh

SPEC-0024 (Omnichannel Messaging) cần tích hợp chat của 4 nền tảng: Shopee, TikTok Shop, Lazada, Facebook Page Messenger. Câu hỏi kiến trúc: chèn vào `ChannelConnector` hiện có (Channels trục) hay tạo trục mở rộng mới?

**Phương án đã cân nhắc:**

A. **Nhồi messaging API vào `ChannelConnector`** — thêm method `fetchConversations / sendMessage / parseMessagingWebhook` vào interface hiện có.
   - ✗ `ChannelConnector` đang có 20+ method về orders/listings/fulfillment/finance — thêm 10 method messaging = "god interface" 30+ method, vi phạm ISP.
   - ✗ Facebook Page **không có** order/listing/fulfillment — buộc phải throw `UnsupportedOperation` cho ~20 method ⇒ pattern xấu.
   - ✗ Capability flag bùng nổ → khó test, khó document.
   - ✗ Vòng đời / rate limit / window rule messaging rất khác orders (Facebook 24h window, Shopee chat rate khác listing rate).

B. **Tạo trục mở rộng thứ 4: `MessagingConnector` + `MessagingRegistry`** — mirror y nguyên pattern Channels/Carriers/Payments (3 trục đã có).
   - ✓ ISP đúng: interface gọn (~12 method), chỉ method messaging.
   - ✓ Facebook fit tự nhiên (provider không có orders).
   - ✓ Dev mới chỉ học 1 pattern (đã thấy ở 3 trục) — không cần concept mới.
   - ✓ Capability map riêng cho messaging, độc lập với orders capability.

C. **Tách hẳn module + service riêng (microservice messaging)** — out of scope (ADR-0003 quyết modular monolith).

## Quyết định

Chọn **phương án B**.

Tạo trục mở rộng thứ 4:
- `app/Integrations/Messaging/Contracts/MessagingConnector.php` — interface.
- `app/Integrations/Messaging/MessagingRegistry.php` — singleton, `register('<code>', XConnector::class)` + `for($code): MessagingConnector`.
- `app/Integrations/Messaging/DTO/` — DTO chuẩn (`ConversationDTO`, `MessageDTO`, `MessagingWebhookEventDTO`, `SendResultDTO`, `MediaRefDTO`, `OutboundWindowPolicyDTO`, `MessagingAuthContext`).
- `app/Integrations/Messaging/Exceptions/` — `UnsupportedOperation`, `OutboundWindowClosed`, `ConversationClosed`, `TokenExpired` (reuse từ Channels nếu khả thi).
- 4 connector: `Shopee/`, `TikTok/`, `Lazada/`, `Facebook/`.

Interface skeleton:
```php
interface MessagingConnector {
    public function code(): string;              // 'shopee_chat'|'tiktok_chat'|'lazada_chat'|'facebook_page'
    public function displayName(): string;
    public function capabilities(): array;       // 'inbound.webhook','outbound.text','outbound.image','outbound.video','outbound.file','outbound.template','read_receipt','typing'
    public function supports(string $cap): bool;

    // OAuth (Shopee/TikTok/Lazada reuse channel_account token, Facebook = page access token)
    public function buildAuthorizationUrl(string $state, array $opts = []): string;
    public function exchangeCodeForToken(string $code): TokenDTO;
    public function refreshToken(string $refreshToken): TokenDTO;
    public function registerWebhooks(MessagingAuthContext $auth): void;

    // Inbound
    public function parseWebhook(Request $req): MessagingWebhookEventDTO;
    public function verifyWebhookSignature(Request $req): bool;
    public function fetchConversations(MessagingAuthContext $auth, array $query = []): Page;
    public function fetchMessages(MessagingAuthContext $auth, string $conversationId, array $query = []): Page;

    // Outbound — throw UnsupportedOperation nếu không hỗ trợ
    public function sendText(MessagingAuthContext $auth, string $conversationId, string $body, array $opts = []): SendResultDTO;
    public function sendMedia(MessagingAuthContext $auth, string $conversationId, MediaRefDTO $media, array $opts = []): SendResultDTO;
    public function sendTemplate(MessagingAuthContext $auth, string $conversationId, string $templateKey, array $vars = []): SendResultDTO;

    public function outboundWindow(): OutboundWindowPolicyDTO;  // {free_window_hours: 24|null, requires_tag: bool, allowed_tags: [...]}
}
```

Rules áp dụng (mirror ADR-0004):
- **Core (`Modules/Messaging`) không bao giờ biết tên một provider cụ thể.** Cấm `if ($provider === 'facebook_page')` ở core. Khác biệt ⇒ capability map + DTO field nullable + mapping table.
- Thêm provider mới = 1 thư mục connector + 1 dòng `MessagingRegistry::register('<code>', …)` trong `MessagingServiceProvider::boot()` (đọc danh sách bật/tắt từ `config/integrations.php` mục `messaging`) + 1 contract test.
- "manual" KHÔNG cần `ManualMessagingConnector` (không có "chat thủ công" — nếu cần đối thoại nội bộ, là feature khác).

## Hệ quả

**Tích cực:**
- Người mới đã quen Channels/Carriers/Payments → học MessagingConnector trong 5 phút.
- Facebook Page (không orders) fit pattern tự nhiên.
- Khác biệt window/rate per provider gói gọn trong connector.
- Thêm provider mới (Zalo OA, Instagram, WhatsApp) sau = 1 connector + 1 dòng register, không sửa core.
- Contract test pattern giống Channels — copy-paste-modify cho mỗi provider.

**Tiêu cực / đánh đổi:**
- Thêm 1 trục = thêm 1 registry + 1 service provider + 1 set DTO chuẩn để duy trì.
- Khi cần share auth với Channels (Shopee/TikTok/Lazada token chung), MessagingAuthContext sẽ build từ `channel_accounts` row — coupling soft (đọc, không sửa) — xem ADR-0019.

**Việc phải làm theo sau:**
- ADR-0019: chốt việc reuse `channel_accounts` cho Shopee/TikTok/Lazada messaging vs tạo bảng riêng.
- Cập nhật `01-architecture/extensibility-rules.md` thêm row "Messaging" vào bảng §1 + checklist "Thêm 1 nền tảng messaging mới" §6c.
- Cập nhật `config/integrations.php` thêm key `messaging.connectors` + `INTEGRATIONS_MESSAGING` env csv.
- Tạo `docs/04-messaging/README.md` (mirror `04-channels/README.md`) định nghĩa DTO chuẩn + table provider trạng thái.
