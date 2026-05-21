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
- Buyer nhắn → Shopee POST code 10 → `/webhook/shopee` → `ShopeeWebhookController`
  demux → pipeline messaging → hộp thư (≤ vài giây).
- NV trả lời → `send_message` (`/api/v2/sellerchat/send_message`, ký HMAC shop).

## 5. Xử lý lỗi thường gặp
| Triệu chứng | Nguyên nhân & cách sửa |
|---|---|
| Không nhận được tin chat | Chưa subscribe **Code 10**; hoặc callback URL ≠ `/webhook/shopee`; hoặc `shopee_chat` chưa trong `INTEGRATIONS_MESSAGING`. |
| Webhook chat 401 | `SHOPEE_PUSH_PARTNER_KEY`/`SHOPEE_PARTNER_KEY` sai (chữ ký push HMAC mismatch). |
| Gửi tin lỗi | Token shop hết hạn → kết nối lại Gian hàng; hoặc Seller Chat API chưa được duyệt cho app. |
| Tin về nhưng không thấy shop | `channel_accounts` provider `shopee` với `external_shop_id` = shop_id chưa tồn tại (chưa OAuth đơn hàng). |

## 6. Giới hạn bản đầu (YAGNI)
Gửi **text + ảnh**. Chưa có: item/order/sticker, read-receipt/typing, polling backup.
Thêm sau theo cùng pattern (sửa connector, không đụng controller/pipeline — ADR-0017).
