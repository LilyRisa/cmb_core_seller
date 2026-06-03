# Cấu hình Shopee Chat (Seller Chat) — SPEC-0024 / ADR-0017, ADR-0019

Bật nhận & trả lời tin nhắn Shopee Chat ngay trong hộp thư hợp nhất. Shopee Chat
**dùng chung kết nối với Gian hàng Shopee** (ADR-0019) — KHÔNG đăng nhập riêng.

> ⚠️ Connector ở mức **shape-tested** (đúng tài liệu + unit-test), **chưa verify
> sandbox thật**. Test kỹ trên 1 shop trước khi dùng rộng.

## 0. Điều kiện tiên quyết
- Shop Shopee đã kết nối cho **đơn hàng**: `shopee` có trong `INTEGRATIONS_CHANNELS`
  + đã OAuth (`/oauth/shopee/callback`) → có `channel_accounts` (provider `shopee`).
- Push key đã cấu hình: `SHOPEE_PUSH_PARTNER_KEY` (hoặc fallback `SHOPEE_PARTNER_KEY`).

## 1. Bật connector
`INTEGRATIONS_MESSAGING` (CSV) thêm `shopee_chat`. Ví dụ:

```dotenv
INTEGRATIONS_MESSAGING=facebook_page,shopee_chat
```

## 2. Push Mechanism (Shopee Console)
1. Console → **Push Mechanism** → chọn App → **Set Push**.
2. **Callback URL**: `https://app.cmbcore.com/webhook/shopee` (DÙNG CHUNG với đơn hàng —
   Shopee chỉ 1 URL/app; app tự demux code 10 sang hộp thư).
3. Subscribe thêm **Code 10 — Webchat Push** (ngoài các code đơn hàng 1/2/3/4/15…).

## 3. Bật nhắn tin cho shop
App → trang **Gian hàng** → shop Shopee → bật **"nhắn tin"**
(`PATCH /api/v1/channel-accounts/{id}/messaging`).

## 4. Luồng (đã code)
- **Realtime (chính):** Buyer nhắn → Shopee POST code 10 → `/webhook/shopee` →
  `ShopeeWebhookController` demux → pipeline messaging → hộp thư (≤ vài giây).
- **Polling (backfill + lưới an toàn):** job `SyncConversationsForShop` kéo qua
  `sellerchat/get_conversation_list` + `get_message` — chạy tự động mỗi 5 phút cho
  shop `active` + đã bật nhắn tin, và thủ công qua `POST /channel-accounts/{id}/resync-chat`.
  Vá tin webhook lọt + lấy hội thoại cũ. Ingest idempotent theo `(conversation, message_id)`
  ⇒ webhook & poll trùng tin là vô hại.
  > ⚠️ Endpoint `get_*` của sellerchat là **endpoint cộng đồng** (tài liệu chính thức Shopee
  > không nêu chi tiết — như `send_message`): parse phòng thủ, **PHẢI verify sandbox thật**.
  > Thứ tự kiểm (quan trọng → ít):
  > 1. **Tên trường timestamp hội thoại** (`last_message_timestamp`): nếu sai tên ⇒ mọi
  >    `lastMessageAt = null` ⇒ since-stop KHÔNG kích hoạt ⇒ mỗi lần poll quét tới cap 50 trang
  >    (~1250 hội thoại) trên cùng rate-bucket với đồng bộ đơn. Tốn (không vô hạn — có cap + ingest
  >    idempotent) nhưng phải xác nhận TRƯỚC khi để scheduler chạy với shop nhiều hội thoại.
  > 2. Shape phân trang `page_result` (`next_cursor`/`next_offset`/`has_next_page`).
  > 3. Đơn vị timestamp (giây/mili/micro/nano — đã chuẩn hoá theo độ lớn).
  > 4. Direction qua `from_shop_id` so với `shop_id`.
- NV trả lời → `send_message` (`/api/v2/sellerchat/send_message`, ký HMAC shop).

## 5. Xử lý lỗi thường gặp
| Triệu chứng | Nguyên nhân & cách sửa |
|---|---|
| Không nhận được tin chat | Chưa subscribe **Code 10**; hoặc callback URL ≠ `/webhook/shopee`; hoặc `shopee_chat` chưa trong `INTEGRATIONS_MESSAGING`. |
| Webhook chat 401 | `SHOPEE_PUSH_PARTNER_KEY`/`SHOPEE_PARTNER_KEY` sai (chữ ký push HMAC mismatch). |
| Gửi tin lỗi | Token shop hết hạn → kết nối lại Gian hàng; hoặc Seller Chat API chưa được duyệt cho app. |
| Tin về nhưng không thấy shop | `channel_accounts` provider `shopee` với `external_shop_id` = shop_id chưa tồn tại (chưa OAuth đơn hàng). |

## 6. Giới hạn bản đầu (YAGNI)
Gửi **text + ảnh**. Nhận: webhook code 10 (realtime) **+ polling backfill** (mục 4).
Chưa có: gửi item/order/sticker, read-receipt/typing. Thêm sau theo cùng pattern
(sửa connector, không đụng controller/pipeline — ADR-0017).
