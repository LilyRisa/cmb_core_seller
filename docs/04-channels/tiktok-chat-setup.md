# Cấu hình TikTok Shop Chat (Customer Service IM) — SPEC-0024 / ADR-0017, ADR-0019

Bật nhận & trả lời tin nhắn TikTok Shop ngay trong hộp thư hợp nhất. TikTok chat
**dùng chung kết nối với Gian hàng TikTok** (ADR-0019) — KHÔNG đăng nhập riêng.

> ⚠️ Connector ở mức **shape-tested** (đúng tài liệu + unit-test), **chưa verify
> sandbox thật**. TikTok IM **giới hạn vùng** và cần **TikTok Shop API approval**
> cho phạm vi Customer Service. Test kỹ trên 1 shop trước khi dùng rộng.

## 0. Điều kiện tiên quyết
- Shop TikTok đã kết nối cho **đơn hàng**: `tiktok` có trong `INTEGRATIONS_CHANNELS`
  (mặc định đã có) + đã OAuth → có `channel_accounts` (provider `tiktok`).
- App TikTok đã được duyệt scope Customer Service / Message.

## 1. Bật connector
`INTEGRATIONS_MESSAGING` (CSV) gồm `tiktok_chat` (đã có trong default prod).

```dotenv
INTEGRATIONS_MESSAGING=tiktok_chat,lazada_chat,shopee_chat
```

## 2. Webhook (TikTok Partner Center)
Đăng ký webhook IM ở Partner Center trỏ về:
`https://app.cmbcore.com/webhook/messaging/tiktok_chat`
(verify chữ ký dùng chung `TIKTOK_APP_SECRET` với orders — header `Authorization` =
HMAC-SHA256(app_secret, app_key + raw_body)).

## 3. Bật nhắn tin cho shop
App → trang **Gian hàng** → shop TikTok → bật **"nhắn tin"**
(`PATCH /api/v1/channel-accounts/{id}/messaging`).

## 4. Luồng (đã code)
- Buyer nhắn → TikTok push IM → `/webhook/messaging/tiktok_chat` → pipeline messaging
  → hộp thư.
- NV trả lời → `/customer_service/202309/conversations/{id}/messages` (ký `TikTokSigner`).

## 5. Xử lý lỗi thường gặp
| Triệu chứng | Nguyên nhân & cách sửa |
|---|---|
| Không nhận được tin | Webhook IM chưa đăng ký ở Partner Center; hoặc `tiktok_chat` chưa trong `INTEGRATIONS_MESSAGING`; hoặc app chưa được duyệt scope IM/region. |
| Webhook 401 | `TIKTOK_APP_SECRET`/`TIKTOK_APP_KEY` không khớp app. |
| Gửi tin lỗi | Token shop hết hạn → kết nối lại Gian hàng; hoặc thiếu `shop_cipher` trong auth context. |
| Tin về nhưng không thấy shop | `channel_accounts` provider `tiktok` với `external_shop_id` = shop_id chưa tồn tại (chưa OAuth đơn hàng). |

## 6. Giới hạn bản đầu (YAGNI)
Gửi **text + ảnh**. Chưa có: video/file, template, polling backup. Mở rộng theo cùng
pattern (sửa connector, không đụng controller/pipeline — ADR-0017).
