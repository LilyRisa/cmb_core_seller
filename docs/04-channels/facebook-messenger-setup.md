# Cấu hình Facebook Page Messenger (SPEC-0024 / ADR-0017, ADR-0019)

Hướng dẫn cấu hình **Facebook Developer** để bật tính năng nhắn tin Messenger của
Facebook Page trong CMBcoreSeller. Đối tượng: super-admin/dev cấu hình app lần đầu.

> Tham chiếu: [Meta Messenger Platform — Webhooks](https://developers.facebook.com/docs/messenger-platform/webhooks),
> [Send API](https://developers.facebook.com/docs/messenger-platform/reference/send-api),
> [Page Access Tokens](https://developers.facebook.com/docs/pages/access-tokens),
> [24-hour messaging window & tags](https://developers.facebook.com/docs/messenger-platform/policy/policy-overview).

---

## 0. Kiến trúc tích hợp (đã code sẵn — chỉ cần cấu hình)

| Thành phần | Vị trí | Vai trò |
|---|---|---|
| `FacebookPageConnector` | `app/Integrations/Messaging/Facebook/` | verify chữ ký, parse webhook (batch), Send API, 24h window |
| Webhook nhận | `POST /webhook/messaging/facebook` | sàn đẩy tin → verify HMAC → store → xử lý async |
| Webhook verify | `GET  /webhook/messaging/facebook` | trả `hub.challenge` lúc Meta setup |
| OAuth connect | `POST /api/v1/messaging/facebook/connect` → callback `GET /oauth/facebook_page/callback` | đổi code → page token → tạo `channel_accounts` |

`external_conversation_id` = **PSID** (Page-Scoped ID của buyer); `external_shop_id` = **page id**.

---

## 1. Tạo Facebook App

1. Vào <https://developers.facebook.com/apps> → **Create App**.
2. Use case: chọn **Other** → loại **Business**.
3. Sau khi tạo, vào **App settings → Basic**, lấy:
   - **App ID** → `MESSAGING_FACEBOOK_APP_ID`
   - **App Secret** → `MESSAGING_FACEBOOK_APP_SECRET` (dùng để verify chữ ký webhook HMAC-SHA256).
4. Thêm sản phẩm **Messenger** (Add Product → Messenger → Set Up).

---

## 2. Cấu hình Webhook

Trong **Messenger → Settings → Webhooks** (hoặc App → Webhooks):

1. **Callback URL**: `https://<APP_DOMAIN>/webhook/messaging/facebook`
   (vd `https://app.cmbcore.vn/webhook/messaging/facebook`). Phải là HTTPS, public.
2. **Verify Token**: nhập 1 chuỗi bí mật bất kỳ → đặt **GIỐNG** vào
   `MESSAGING_FACEBOOK_VERIFY_TOKEN`. Khi bấm Verify, Meta gọi `GET` với
   `hub.mode=subscribe&hub.verify_token=...&hub.challenge=...`; hệ thống so khớp
   token (so sánh hằng-thời-gian) và echo lại `hub.challenge`.
3. **Subscribe fields**: tick tối thiểu `messages`. Nên thêm `messaging_postbacks`,
   `message_deliveries`, `message_reads` (connector đã parse các loại này).

> Sau khi connect 1 page (bước 4), hệ thống tự gọi `POST /{page_id}/subscribed_apps`
> để subscribe page vào app cho các field trên (xem `FacebookPageConnector::registerWebhooks`).

---

## 3. Quyền (Permissions) & App Review

Để gửi/nhận tin trên page thật, app cần các quyền:

| Quyền | Mục đích |
|---|---|
| `pages_messaging` | gửi/nhận tin nhắn Messenger thay mặt page |
| `pages_manage_metadata` | subscribe page vào webhook |
| `pages_show_list` | liệt kê page user quản lý (lúc OAuth) |
| `pages_read_engagement` | đọc thông tin page |

- **Development mode**: chỉ admin/developer/tester của app nhắn được — đủ để test.
- **Production**: phải qua **App Review** cho `pages_messaging` (+ Business Verification).
  Chuẩn bị video demo luồng: buyer nhắn page → app nhận → NV trả lời.

---

## 4. Luồng kết nối Page (OAuth) — phía người dùng

1. Tenant (Owner/Admin) vào **Cài đặt → Tin nhắn** → bấm **Kết nối Facebook Page**.
   - FE gọi `POST /api/v1/messaging/facebook/connect` → nhận `authorize_url` → redirect sang Meta.
2. User đăng nhập FB, chọn page cho phép → Meta redirect về
   `GET /oauth/facebook_page/callback?code=...&state=...`.
3. Hệ thống: verify `state` (bảng `oauth_states`) → đổi `code` lấy **user token** →
   gọi `/me/accounts` lấy **page access token** từng page → upsert `channel_accounts`
   (`provider=facebook_page`, `messaging_enabled=true`) → subscribe webhook → về `/messaging`.

> **Redirect URI** đăng ký trong **Facebook Login → Settings → Valid OAuth Redirect URIs**
> phải đúng: `https://<APP_DOMAIN>/oauth/facebook_page/callback`.

---

## 5. Biến môi trường (`.env`)

```dotenv
# Bật connector trong registry (CSV nhiều provider)
INTEGRATIONS_MESSAGING=facebook_page

# Facebook App
MESSAGING_FACEBOOK_APP_ID=xxxxxxxxxxxx
MESSAGING_FACEBOOK_APP_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
MESSAGING_FACEBOOK_VERIFY_TOKEN=mot-chuoi-bi-mat-tu-dat
MESSAGING_FACEBOOK_GRAPH_VERSION=v19.0
```

App secret/token KHÔNG commit; page access token lưu **mã hoá** trong `channel_accounts.access_token`.

---

## 6. Cửa sổ 24 giờ (quan trọng về nghiệp vụ)

Messenger chỉ cho gửi tin **tự do trong 24h** kể từ tin cuối của buyer. Quá 24h chỉ
gửi được tin có **message tag** hợp lệ (`CONFIRMED_EVENT_UPDATE`, `POST_PURCHASE_UPDATE`,
`ACCOUNT_UPDATE`). Hệ thống tự chặn ở `OutboundWindowGuard` → trả `422 OUTBOUND_WINDOW_CLOSED`;
muốn gửi ngoài window phải đính `message_tag`. (Vi phạm → Meta có thể hạn chế page.)

---

## 7. Kiểm thử nhanh

1. **Verify webhook**: bấm Verify trong dashboard → phải xanh (echo challenge).
2. **Nhận tin**: dùng tài khoản tester nhắn vào page → kiểm `messages` tin về
   `/messaging` ≤ 10s (hoặc check bảng `webhook_events` provider `messaging.facebook_page`).
3. **Gửi tin**: trong inbox bấm trả lời → buyer nhận được.
4. **Batch**: Meta đôi khi gộp nhiều tin/1 POST — hệ thống fan-out từng tin (test
   `MessagingFacebookWebhookTest::test_post_batch_fans_out_each_message`).

---

## 8. Mở rộng sang sàn khác

Connector khác (TikTok/Lazada chat) cùng pattern `MessagingConnector`: chỉ khác
`verifyWebhookSignature` + `parseWebhookEvents` + endpoint Send. **Không sửa controller
/ pipeline** khi thêm sàn (ADR-0017). Thêm provider = 1 class + 1 dòng register +
1 mục `INTEGRATIONS_MESSAGING` + doc này tương ứng.
