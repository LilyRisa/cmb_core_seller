# ADR-0019: Messaging tái sử dụng `channel_accounts` cho 3 sàn + Facebook là provider `channel_account` mới (không bảng riêng)

- **Trạng thái:** Proposed
- **Ngày:** 2026-05-19
- **Người quyết định:** Team (chờ duyệt SPEC-0024)
- **Liên quan:** SPEC-0024, ADR-0017, ADR-0019

## Bối cảnh

SPEC-0024 cần lưu credential (OAuth token / page access token) cho 4 nền tảng messaging. Câu hỏi: tạo bảng `messaging_accounts` riêng song song với `channel_accounts`, hay reuse `channel_accounts`?

**Sự thật nền:**
- Shopee Chat / TikTok Shop CS / Lazada IM dùng **chung OAuth token** với API orders/listings/fulfillment — token có scope chung; refresh token, lifecycle, deauthorize event đều dùng chung. Nếu tách 2 bảng ⇒ 2 row riêng cho cùng 1 shop, cùng token → đồng bộ phức tạp, vi phạm "một nguồn sự thật".
- Facebook Page **không có** order/listing/fulfillment. Page Access Token đứng độc lập. Nhưng vẫn là "1 kết nối đến nền tảng ngoài" — về bản chất giống ChannelAccount.
- `ManualConnector` đã chứng minh `channel_accounts` không bắt buộc có order pipeline (manual orders source = `'manual'`, ChannelAccount có thể không tồn tại hoặc tồn tại với capability tối thiểu).

**Phương án đã cân nhắc:**

A. **Bảng `messaging_accounts` riêng, không liên quan `channel_accounts`** — mỗi provider có row riêng.
   - ✗ Shopee/TikTok/Lazada: duplicate token, duplicate refresh logic, dễ trôi.
   - ✗ Disconnect shop ⇒ phải delete 2 row, race condition.
   - ✗ UI "Gian hàng đã kết nối" phải JOIN 2 bảng hoặc UNION → phức tạp.

B. **Reuse `channel_accounts` cho cả 4** — Facebook = `provider='facebook_page'`, không có orders. Bổ sung bảng 1-1 `messaging_account_meta` chứa metadata riêng cho messaging.
   - ✓ 1 nguồn sự thật về kết nối nền tảng.
   - ✓ Disconnect 1 nơi.
   - ✓ Token refresh dùng chung pipeline `RefreshChannelToken` (đã có).
   - ✓ UI "Gian hàng" hiển thị thống nhất, có filter "có messaging".
   - ✗ Facebook Page không phải "shop" → "Gian hàng" mơ hồ về mặt nghĩa.

C. **Reuse `channel_accounts` cho 3 sàn, bảng riêng `social_pages` cho Facebook** — hybrid.
   - ✓ Semantic rõ.
   - ✗ Code phân nhánh: messaging logic phải JOIN 2 nguồn.
   - ✗ Thêm provider social mới (Zalo OA, Instagram) ⇒ lại quyết: thêm `channel_accounts` hay `social_pages`?

## Quyết định

Chọn **phương án B**.

- `channel_accounts.provider` mở rộng tập giá trị: `tiktok | shopee | lazada | manual | facebook_page` (và tương lai `zalo_oa | instagram | whatsapp`). Không thay đổi schema; chỉ thêm value mới.
- `channel_accounts` thêm cột boolean `messaging_enabled` (default `false`; bật khi connect provider có messaging capability hoặc khi seller explicit enable).
- Bảng 1-1 `messaging_account_meta` (xem SPEC-0024 §5.1) chứa metadata riêng messaging (last_inbound_at, outbound window snapshot, AI provider override per shop, ...). Tách bảng vì cột không liên quan tới module Channels — đặt trong module Messaging.
- Đặt lại label UI: sidebar "Gian hàng" giữ nguyên (đã quen); trong trang `/channels` thêm tab/filter "Loại kết nối" (Marketplace / Social / All) để Facebook không lẫn lộn về mặt UX.

Quy ước module:
- **Channels** sở hữu `channel_accounts` + `messaging_enabled` column.
- **Messaging** sở hữu `messaging_account_meta` (1-1, FK cascade).
- Messaging KHÔNG sửa `channel_accounts` trực tiếp — đổi `messaging_enabled` qua `MessagingAccountService` gọi qua interface `MessagingEnablementContract` đặt ở `Channels/Contracts/` (Channels module owns the write).
- `ChannelAccount` model thêm helper `messagingCapable(): bool` (lookup `MessagingRegistry::has($this->provider)`); UI dùng để show toggle.

## Hệ quả

**Tích cực:**
- 1 row / 1 kết nối → disconnect / token refresh / `shop_deauthorized` / `data_deletion` chạy chung pipeline.
- Tiết kiệm migration: không tạo `messaging_accounts` mới.
- `RefreshChannelToken` job tự động phục vụ Facebook page access token (token lifecycle khác, nhưng cùng pattern; có thể cần `RefreshFacebookPageToken` extension nhỏ).
- Thêm social provider mới (Zalo OA) = thêm 1 dòng vào enum provider + 1 MessagingConnector + (optional) ChannelConnector rỗng.

**Tiêu cực / đánh đổi:**
- Semantic "channel_accounts" mở rộng từ "shop sàn TMĐT" sang "kết nối nền tảng ngoài bất kỳ". Cần đổi mô tả trong `02-data-model/overview.md` và `glossary.md`.
- ChannelConnector cho `facebook_page` = stub gần như rỗng (mọi method order/listing throw `UnsupportedOperation`). Cần thêm 1 `FacebookPageChannelConnector` minimal, hoặc cơ chế đặc biệt cho "messaging-only providers" trong `ChannelRegistry`.
- Schema `channel_accounts` có vài cột (`external_shop_id`, `shop_name`, `seller_type`) tên hơi forced với Facebook ("page_id", "page_name"). Mitigation: dùng `meta jsonb` (đã có) cho field đặc thù; `display_name` đã có.

**Giải pháp cho "messaging-only providers":**

Thêm capability flag `channel.kind` vào `ChannelConnector::capabilities()`:
```
'channel.kind' => 'marketplace'   // Shopee/TikTok/Lazada/Manual
'channel.kind' => 'social'        // Facebook Page / Zalo OA / Instagram (Phase sau)
```
Core module Orders/Inventory/Fulfillment chỉ liệt kê accounts `kind=marketplace`. Module Messaging liệt kê `MessagingRegistry::has($provider)`. UI dùng để phân tab Marketplace / Social.

`FacebookPageChannelConnector` (`app/Integrations/Channels/FacebookPage/`):
- `code() = 'facebook_page'`, `displayName() = 'Facebook Page'`.
- `capabilities()`: chỉ `channel.kind=social`; tất cả orders/listings/fulfillment/finance = false.
- `buildAuthorizationUrl`/`exchangeCodeForToken`/`refreshToken`/`fetchShopInfo`/`registerWebhooks` — implement (cùng OAuth flow Facebook).
- `fetchOrders`, `fetchOrderDetail`, `fetchListings`, `updateStock`, … — throw `UnsupportedOperation`.

`FacebookPageMessagingConnector` (`app/Integrations/Messaging/Facebook/`) — implement messaging methods, share `FacebookClient` HTTP với Channels nếu có chung endpoint.

**Việc phải làm theo sau:**
- Migration: ALTER TABLE `channel_accounts` ADD COLUMN `messaging_enabled bool default false`.
- Tạo `messaging_account_meta` table (Messaging migration).
- Tạo `FacebookPageChannelConnector` (skeleton) + register vào `ChannelRegistry`.
- Tạo `MessagingEnablementContract` ở `Modules/Channels/Contracts/` + impl.
- Cập nhật `02-data-model/overview.md` mục Channels: "provider mở rộng cho social".
- Cập nhật `glossary.md` định nghĩa "ChannelAccount" (đã không còn = shop sàn; = kết nối tới nền tảng ngoài).
- Cập nhật UI `/channels` thêm tab/filter Marketplace/Social.
