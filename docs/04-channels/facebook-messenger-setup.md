# Cấu hình Facebook Page Messenger (chi tiết) — SPEC-0024 / ADR-0017, ADR-0019

Hướng dẫn **từng bước** cấu hình Facebook Developer để bật nhắn tin Messenger của
Facebook Page trong CMBcoreSeller. Có cả phần **đăng nhập Facebook (OAuth)** — điểm
nhiều người vướng. Đối tượng: super-admin/dev cấu hình lần đầu.

> Tài liệu Meta: [Messenger Webhooks](https://developers.facebook.com/docs/messenger-platform/webhooks) ·
> [Send API](https://developers.facebook.com/docs/messenger-platform/reference/send-api) ·
> [Page Access Tokens](https://developers.facebook.com/docs/pages/access-tokens) ·
> [Facebook Login — manual flow](https://developers.facebook.com/docs/facebook-login/guides/advanced/manual-flow) ·
> [Messaging policy & 24h window](https://developers.facebook.com/docs/messenger-platform/policy/policy-overview).

---

## 0. App đã code sẵn 2 URL — ghi nhớ để dán vào Meta

Thay `app.cmbcore.com` bằng domain thật của bạn (`APP_URL`):

| Mục đích | URL (dán vào Meta) |
|---|---|
| **Webhook callback** (Messenger) | `https://app.cmbcore.com/webhook/messaging/facebook` |
| **OAuth Redirect URI** (Facebook Login) | `https://app.cmbcore.com/oauth/facebook_page/callback` |

> Redirect URI được app suy ra từ `APP_URL` (hoặc `MESSAGING_FACEBOOK_REDIRECT_URI` nếu
> đặt). App dùng **đúng 1 URI này** cho cả lúc bấm "Đăng nhập" lẫn lúc đổi code lấy token —
> nên trong Meta phải đăng ký **chính xác** chuỗi này (sai 1 ký tự ⇒ lỗi mismatch).

---

## 1. Tạo Facebook App

1. Vào <https://developers.facebook.com/apps> → **Create app**.
2. **Use cases**: chọn **Other** → **Next**.
3. **App type**: chọn **Business** → **Next**.
4. Nhập **App name** (vd "CMBcoreSeller"), **App contact email** → **Create app**
   (xác nhận mật khẩu nếu được hỏi).

## 2. Lấy App ID / App Secret (Settings → Basic)

1. Sidebar trái: **App settings → Basic**.
2. Copy:
   - **App ID** → biến `MESSAGING_FACEBOOK_APP_ID`.
   - **App secret** (bấm **Show**) → biến `MESSAGING_FACEBOOK_APP_SECRET`.
3. Điền các trường cần để lên Live: **Privacy Policy URL**, **App domains**
   (thêm `app.cmbcore.com`), **Category**. Bấm **Save changes**.

---

## 3. ⭐ Cấu hình ĐĂNG NHẬP Facebook (Facebook Login) — phần hay vướng

App dùng OAuth server-side cổ điển (`/dialog/oauth` → redirect về callback của ta → đổi
`code` lấy token). Cần thêm sản phẩm **Facebook Login** và khai báo Redirect URI.

1. Sidebar trái → **Add product** (dấu +) → tìm **Facebook Login** → **Set up**.
   (Nếu Meta gợi ý **Facebook Login for Business** cũng được — khai báo Redirect URI giống nhau.)
2. Vào **Facebook Login → Settings**, mục **Client OAuth Settings**, bật/điền:
   - **Client OAuth Login**: **Yes (On)**.
   - **Web OAuth Login**: **Yes (On)**.
   - **Enforce HTTPS**: **Yes** (production bắt buộc HTTPS).
   - **Valid OAuth Redirect URIs**: dán **chính xác**
     `https://app.cmbcore.com/oauth/facebook_page/callback` → Enter → **Save changes**.
     - ⚠️ Khớp tuyệt đối với redirect URI app gửi (§0). Khác scheme (`http`↔`https`),
       thiếu/thừa `/`, sai domain → Meta báo **"URL Blocked / redirect_uri isn't allowed"**.
3. (Nếu domain dev khác prod) thêm cả URI dev, vd `https://dev.cmbcore.com/oauth/facebook_page/callback`.

**Luồng app thực hiện (đã code — chỉ để hiểu):**

```
1) FE bấm "Kết nối Facebook Page"  → POST /api/v1/messaging/facebook/connect
2) BE trả authorize_url:
   https://www.facebook.com/v19.0/dialog/oauth?client_id=<APP_ID>
     &redirect_uri=https://app.cmbcore.com/oauth/facebook_page/callback
     &state=<csrf>&response_type=code
     &scope=pages_messaging,pages_manage_metadata,pages_read_engagement,pages_show_list
3) User đăng nhập FB, chọn Page, đồng ý quyền  → Meta redirect:
   GET /oauth/facebook_page/callback?code=...&state=...
4) BE: verify state → đổi code lấy USER token (kèm redirect_uri GIỐNG HỆT)
   → gọi /me/accounts lấy PAGE access token từng page
   → tạo channel_accounts (provider=facebook_page, messaging_enabled=true) → subscribe webhook
```

---

## 4. Cấu hình Webhook (Messenger)

1. **Add product → Messenger → Set up**.
2. **Messenger → Settings** (hoặc *Messenger API Settings*) → mục **Webhooks** →
   **Configure / Edit callback URL**:
   - **Callback URL**: `https://app.cmbcore.com/webhook/messaging/facebook`
   - **Verify Token**: tự đặt 1 chuỗi bí mật → nhập **GIỐNG HỆT** vào
     `MESSAGING_FACEBOOK_VERIFY_TOKEN`.
   - Bấm **Verify and Save**. Meta gọi `GET` với `hub.mode=subscribe&hub.verify_token=...
     &hub.challenge=...`; app so token (hằng-thời-gian) và echo `hub.challenge` → phải xanh.
3. **Subscription fields**: tick tối thiểu `messages`. Nên thêm `messaging_postbacks`,
   `message_deliveries`, `message_reads` (connector đã parse).

> Sau khi connect page (§3), app tự `POST /{page_id}/subscribed_apps` để gắn page vào app
> cho các field trên (`FacebookPageConnector::registerWebhooks`).

---

## 5. Quyền & App Review (để chạy với page của người khác)

| Quyền | Mục đích |
|---|---|
| `pages_messaging` | gửi/nhận tin Messenger thay mặt page |
| `pages_manage_metadata` | subscribe page vào webhook |
| `pages_show_list` | liệt kê page user quản lý (lúc OAuth) |
| `pages_read_engagement` | đọc thông tin page |

- **Development mode** (mặc định): chỉ tài khoản có **vai trò** trong app
  (Admin/Developer/Tester) mới đăng nhập & nhắn được — **đủ để test ngay**.
- **Production/Live**: cần **App Review → Advanced Access** cho `pages_messaging` (+ các
  quyền trên) và **Business Verification**. Chuẩn bị video demo luồng nhắn tin.
- Thêm người test: **App roles → Roles → Add People** (Tester/Developer) → người đó vào
  <https://developers.facebook.com/requests> chấp nhận.

---

## 6. Biến môi trường (đối chiếu Meta ↔ env ↔ docker)

| Biến env | Lấy từ đâu | Ghi chú |
|---|---|---|
| `INTEGRATIONS_MESSAGING` | (tự đặt) | thêm `facebook_page` (CSV). Vd `facebook_page,lazada_chat` |
| `MESSAGING_FACEBOOK_APP_ID` | Settings → Basic → App ID | |
| `MESSAGING_FACEBOOK_APP_SECRET` | Settings → Basic → App secret | **bí mật** |
| `MESSAGING_FACEBOOK_VERIFY_TOKEN` | tự đặt, dán vào Messenger → Webhooks | **bí mật** |
| `MESSAGING_FACEBOOK_GRAPH_VERSION` | mặc định `v19.0` | đổi khi nâng Graph API |
| `MESSAGING_FACEBOOK_REDIRECT_URI` | mặc định = `APP_URL` + `/oauth/facebook_page/callback` | chỉ đặt khi domain callback ≠ APP_URL |

**`.env` (CLI / dev):**

```dotenv
INTEGRATIONS_MESSAGING=facebook_page
MESSAGING_FACEBOOK_APP_ID=xxxxxxxxxxxx
MESSAGING_FACEBOOK_APP_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
MESSAGING_FACEBOOK_VERIFY_TOKEN=mot-chuoi-bi-mat-tu-dat
MESSAGING_FACEBOOK_GRAPH_VERSION=v19.0
# MESSAGING_FACEBOOK_REDIRECT_URI=   # bỏ trống = suy từ APP_URL
```

**Production (`docker-compose.prod.yml`)** — đã khai báo sẵn trong khối `x-app-env`
(`MESSAGING_FACEBOOK_*` + `INTEGRATIONS_MESSAGING` + `MESSAGING_MEDIA_DISK`…). Quy ước file:
**biến phải được liệt kê trong `x-app-env` mới vào container**; trên Portainer còn phải
**nhập lại** biến đó ở mục *Environment variables* (Portainer chỉ thay `${VAR}`, không tự
inject vào container).

> ⚠️ Cú pháp default phải là `${VAR:-giá_trị}` — **KHÔNG có khoảng trắng**: `${VAR: -x}` là
> SAI (compose lấy default lệch thành ` -x`). App secret / verify token **nên để trống
> default** trong file và nhập ở Portainer/`.env` để tránh commit bí mật vào repo.

---

## 7. Cửa sổ 24 giờ (nghiệp vụ quan trọng)

Messenger cho gửi **tự do trong 24h** kể từ tin cuối của buyer. Quá 24h chỉ gửi được tin có
**message tag** hợp lệ (`CONFIRMED_EVENT_UPDATE`, `POST_PURCHASE_UPDATE`, `ACCOUNT_UPDATE`).
App tự chặn ở `OutboundWindowGuard` → `422 OUTBOUND_WINDOW_CLOSED`; ngoài window phải đính
`message_tag`. (Vi phạm chính sách → Meta có thể hạn chế page.)

---

## 8. Kiểm thử nhanh (theo thứ tự)

1. **Webhook verify**: bấm *Verify and Save* (Messenger → Webhooks) → xanh.
2. **Kết nối page**: app → **Tin nhắn → Kết nối kênh → Kết nối Facebook Page** → đăng nhập → chọn page.
   - Thành công → redirect `/messaging/channels?connected=facebook_page`; có row `channel_accounts`
     (`provider=facebook_page`, `messaging_enabled=true`).
3. **Nhận tin**: tài khoản Tester nhắn vào page → tin về `/messaging` ≤ 10s (hoặc check
   `webhook_events` provider `messaging.facebook_page`).
4. **Gửi tin**: trả lời trong inbox → buyer nhận được.

---

## 9. Xử lý lỗi thường gặp

| Triệu chứng | Nguyên nhân & cách sửa |
|---|---|
| **"URL Blocked / redirect_uri isn't allowed"** | Redirect URI gửi đi ≠ *Valid OAuth Redirect URIs*. Kiểm `APP_URL`/`MESSAGING_FACEBOOK_REDIRECT_URI`, dán đúng `https://<domain>/oauth/facebook_page/callback`. |
| **Webhook verify đỏ** | `MESSAGING_FACEBOOK_VERIFY_TOKEN` ≠ token trên Meta; hoặc Callback URL sai/không HTTPS/không public. |
| **Đăng nhập xong không thấy page** | Tài khoản không quản lý page, hoặc thiếu `pages_show_list`/chưa cấp quyền page. Dev mode: tài khoản phải có vai trò trong app. |
| **401 ở webhook POST** | Chữ ký `X-Hub-Signature-256` sai → `MESSAGING_FACEBOOK_APP_SECRET` không khớp app. |
| **Gửi lỗi ngoài 24h** | `422 OUTBOUND_WINDOW_CLOSED` — dùng template có `message_tag` hợp lệ. |
| **Token page hết hạn / bị thu hồi** | Kết nối lại (re-OAuth); connector `refreshToken` cố ý không hỗ trợ (page token dài hạn). |
| **"App đang Development / chỉ admin nhắn được"** | Thêm người vào App roles, hoặc đưa app lên Live sau App Review. |

---

## 10. Mở rộng sang sàn khác

Connector khác (TikTok/Lazada chat) cùng pattern `MessagingConnector`: chỉ khác
`verifyWebhookSignature` + `parseWebhookEvents` + endpoint Send. **Không sửa controller/
pipeline** khi thêm sàn (ADR-0017). Thêm provider = 1 class + 1 dòng register + 1 mục
`INTEGRATIONS_MESSAGING` + doc tương ứng (vd `lazada-chat-setup.md`).
