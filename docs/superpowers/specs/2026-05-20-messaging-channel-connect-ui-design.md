# UI Kết nối & quản lý kênh nhắn tin (Facebook Page + bật chat Lazada/TikTok)

- **Trạng thái:** Design draft (2026-05-20)
- **Phase:** Phase 7.x — bổ khuyết SPEC-0024 (Omnichannel Messaging), slice S2 (Facebook) đã có connector nhưng thiếu lớp UI kết nối/quản lý.
- **Module backend chính:** `Messaging` (endpoint list/disconnect channel) + `Channels` (toggle `messaging_enabled`) + `Tenancy` (permission mới).
- **Module FE chính:** `resources/js` — trang mới `/messaging/channels`, sửa `MessagingSettingsPage`, `MessagingPage`, `ChannelsPage`, `AppLayout`, `MessagingNav`.
- **Liên quan:** `docs/04-channels/facebook-messenger-setup.md`, `docs/04-channels/lazada-chat-setup.md`, `docs/specs/0024-omnichannel-messaging.md`, ADR-0017 (connector registry), ADR-0019 (reuse channel OAuth token). `FacebookOAuthController`, `ChannelAccount` model, `MessagingRegistry`, `ChannelsPage.tsx` (mẫu xử lý OAuth callback + card gian hàng).

---

## 1. Vấn đề & mục tiêu

SPEC-0024 đã code đầy đủ backend OAuth Facebook Page (`start` → Meta dialog → `callback` → `/me/accounts` → upsert `channel_accounts` `messaging_enabled=true` → subscribe webhook) và connector Lazada/TikTok chat (dùng chung token Channels theo ADR-0019). Nhưng lớp **UI kết nối & quản lý** còn nhiều khoảng trống khiến người dùng tưởng tính năng "không có":

- **G1 — Khó tìm:** menu "Tin nhắn" (`AppLayout.tsx:43-47`) chỉ có Hộp thư / Mẫu tin / Tự động trả lời / AI training; **không có** mục kết nối kênh. Nút "Kết nối Facebook Page" nằm ở `/settings/messaging`, chỉ vào được qua segment "Cài đặt AI" của `MessagingNav` — mà `MessagingNav` **không render trên Hộp thư** (`MessagingPage` không import). Người dùng mở Hộp thư không thấy đường nào để cấp quyền page.
- **G2 — Không phản hồi sau cấp quyền:** callback redirect về `/messaging?connected=facebook_page` hoặc `?error=facebook_oauth_*` / `facebook_no_pages` / `facebook_oauth_state` / `facebook_oauth_failed` (`FacebookOAuthController.php:60-113`), nhưng `MessagingPage` **không đọc `useSearchParams`** → không toast/banner. (Đối chiếu `ChannelsPage.tsx:51,101,132` có xử lý `?error=`.)
- **G3 — Không quản lý Page đã kết nối:** không có UI liệt kê page đang kết nối, trạng thái token, **không có nút "Kết nối lại"** (dù tài liệu §9 yêu cầu re-OAuth khi token hết hạn, và scheduler `CheckMessagingTokenHealth` sinh "reconnect notice" không có chỗ bấm), không có ngắt kết nối.
- **G4 — Sai ngữ nghĩa permission:** nút connect gate bằng `messaging.ai.config` (`MessagingSettingsPage.tsx:13,48` + `routes.php:41`) — quyền này theo SPEC §4.7 là "chọn AI provider", không phải kết nối kênh.
- **G5 — Không bật được chat Lazada/TikTok:** `messaging_enabled=true` **chỉ** được set trong callback Facebook (`FacebookOAuthController.php:86`); cột mặc định `false`. Không có code path (UI lẫn BE) bật cờ này cho channel_account Lazada/TikTok đã kết nối → dù ADR-0019 nói "dùng chung token Channels", thực tế **không bật được chat Lazada/TikTok từ UI**.
- **Phụ:** `MessagingPage.tsx:123` dùng emoji `📍` (vi phạm quy ước icon font, SPEC §6.3); `MessagingSettingsPage.tsx:62` dùng `<Select>` cho tập nhỏ AI provider (quy ước ưu tiên `Radio`/`Segmented`).

**Mục tiêu:**

1. Một **trang riêng** "Kết nối kênh" (`/messaging/channels`) là nơi kết nối Facebook Page + xem/quản lý/ngắt page đã kết nối, có trên menu chính và `MessagingNav`.
2. Phản hồi rõ ràng (toast/banner) sau khi cấp quyền OAuth (thành công/lỗi).
3. Bật/tắt nhắn tin cho kênh Lazada/TikTok ngay trên trang Gian hàng (`/channels`).
4. Tách quyền kết nối kênh khỏi quyền cấu hình AI.

---

## 2. Trong / ngoài phạm vi

**Trong:**

- Permission mới `messaging.connect` (Owner/Admin tự có qua `*`).
- 2 endpoint Messaging mới: `GET /messaging/channels` (list page Facebook), `DELETE /messaging/channels/{id}` (ngắt kết nối = xoá hẳn + cascade).
- 1 endpoint Channels mới: `PATCH /channel-accounts/{id}/messaging` (bật/tắt `messaging_enabled` cho lazada/tiktok).
- Đổi đích redirect callback Facebook sang `/messaging/channels`.
- Trang FE mới `MessagingChannelsPage` + route + mục menu + option MessagingNav.
- Sửa `MessagingSettingsPage` (bỏ card kết nối, đổi Select→Radio), `MessagingPage` (bỏ emoji, thêm nav), `ChannelsPage` (switch bật nhắn tin).
- Hooks FE: `useMessagingChannels`, `useDisconnectFacebookPage`, `useSetChannelMessaging`.
- Test BE (feature) + FE.

**Ngoài phạm vi (YAGNI):**

- Chọn từng page khi OAuth — vẫn kết nối **tất cả** page từ `/me/accounts` (giữ nguyên callback hiện tại).
- Auto-refresh token (page token dài hạn — reconnect thủ công, đúng `FacebookPageConnector::refreshToken` ném `UnsupportedOperation`).
- Shopee chat (chưa có hạ tầng Channels Shopee — `shopee_chat` còn comment trong registry).
- Upload file cho AI knowledge (đã ghi nhận follow-up riêng).
- Polling backup `/im/*/list` (connector chưa bật `inbound.polling`).

---

## 3. Phân quyền (G4)

`Modules/Tenancy/Enums/Role.php`:

- Thêm permission string `messaging.connect` vào tập khái niệm. Owner = `['*']`, Admin = `['*', ...exclusions]` ⇒ **tự có**. Không thêm vào `staff_cs`, `staff_order` (kết nối/ngắt kênh là việc của Owner/Admin). Không cần migration (permission tính từ enum trong code qua `Gate::before`).
- Đổi `FacebookOAuthController::start`: `Gate::authorize('messaging.ai.config')` → `Gate::authorize('messaging.connect')`.

| Quyền | Trước | Sau |
|---|---|---|
| Kết nối Facebook Page (`facebook/connect`) | `messaging.ai.config` | `messaging.connect` |
| List channel nhắn tin (`GET /messaging/channels`) | — | `messaging.view` |
| Ngắt kết nối page (`DELETE /messaging/channels/{id}`) | — | `messaging.connect` |
| Bật/tắt nhắn tin Lazada/TikTok (`PATCH .../messaging`) | — | `messaging.connect` |
| Chọn AI provider (`/messaging/settings`) | `messaging.ai.config` | `messaging.ai.config` (giữ nguyên) |

---

## 4. Backend — endpoint

### 4.1 Messaging: list & disconnect (`Modules/Messaging/Http/routes.php`)

Thêm vào group `api/v1/messaging` (đã có middleware Sanctum + tenant + `plan.feature:messaging_inbox`):

```php
// --- Kết nối & quản lý kênh nhắn tin (UI /messaging/channels) ---
Route::get('channels', [MessagingChannelController::class, 'index'])
    ->name('messaging.channels.index');                       // messaging.view
Route::delete('channels/{id}', [MessagingChannelController::class, 'destroy'])
    ->whereNumber('id')->name('messaging.channels.destroy');  // messaging.connect
```

Controller mới `MessagingChannelController`:

- **`index`** (`Gate::authorize('messaging.view')`): trả danh sách `channel_accounts` `provider=facebook_page` của tenant. Shape mỗi item:
  ```
  { id, provider, shop_name, external_shop_id, status,
    messaging_enabled, token_expired: bool, connected_at }
  ```
  `token_expired` suy từ `channel_accounts` (status `expired` hoặc cờ token health nếu có). Không trả `access_token`.
- **`destroy`** (`Gate::authorize('messaging.connect')`): ngắt kết nối 1 page.
  - Chặn nếu account không thuộc tenant / không phải `facebook_page` ⇒ `404`.
  - Best-effort unsubscribe webhook Meta: `DELETE https://graph.facebook.com/{version}/{page_id}/subscribed_apps` (lỗi chỉ log, không chặn).
  - **Xoá cascade trong transaction:** message_attachments (+ xoá file MinIO best-effort) → messages → conversations của `channel_account_id` này → `messaging_account_meta` (nếu có) → `channel_accounts` row.
  - Audit `messaging.facebook.disconnected` (kèm `external_shop_id`, số conversation đã xoá).
  - Trả `{ ok: true }`.

> **Lưu ý FK (xác minh khi viết plan):** `conversations.channel_account_id` / `messages` partition. Xoá theo thứ tự con→cha trong transaction để không vướng FK. Nếu DB đã đặt `ON DELETE CASCADE` thì chỉ cần xoá `channel_accounts` — plan sẽ kiểm migration `conversations`/`messages` để chọn cách an toàn nhất. Mặc định spec: **xoá tường minh ở service layer** (không phụ thuộc cascade DB) để kiểm soát việc dọn file MinIO.

- **Kết nối lại** không cần endpoint mới: FE gọi lại `POST /messaging/facebook/connect`; callback `updateOrCreate` (`FacebookOAuthController.php:80`) làm tươi token cho page trùng `external_shop_id`.

### 4.2 Channels: toggle messaging (`Modules/Channels/Http/...`)

```php
Route::patch('channel-accounts/{id}/messaging', [ChannelAccountController::class, 'setMessaging'])
    ->whereNumber('id')->name('channel-accounts.messaging');  // messaging.connect
```

`ChannelAccountController::setMessaging`:

- `Gate::authorize('messaging.connect')`.
- Validate `{ messaging_enabled: bool }`.
- Chỉ cho provider **có messaging connector đang bật**: map `lazada→lazada_chat`, `tiktok→tiktok_chat`; kiểm `MessagingRegistry::supports($code)` (tức `INTEGRATIONS_MESSAGING` có chứa) — nếu không ⇒ `422 MESSAGING_NOT_AVAILABLE`.
- Set `channel_accounts.messaging_enabled`, audit `messaging.channel.toggle`.
- Trả account đã cập nhật.

> Facebook không dùng endpoint này (đã bật trong OAuth callback; tắt = ngắt kết nối ở 4.1).

### 4.3 Redirect callback (G2) — `FacebookOAuthController`

Đổi đích redirect từ `/messaging?...` sang `/messaging/channels?...`:

- `start` line 44: `OAuthState::issue(..., '/messaging/channels?connected=facebook_page')`.
- callback lines 61, 66, 75, 110: `/messaging/channels?error=...`.
- callback line 113 fallback: `/messaging/channels?connected=facebook_page`.

Mã lỗi giữ nguyên (`facebook_oauth_<x>`, `facebook_oauth_state`, `facebook_no_pages`, `facebook_oauth_failed`).

---

## 5. Frontend

### 5.1 Trang mới `MessagingChannelsPage.tsx` → `/messaging/channels` (G1+G2+G3)

- Render `<MessagingNav />` ở đầu.
- **Callback feedback (G2):** `useSearchParams` đọc `connected` / `error`; map `error` theo bảng thân thiện (mẫu `ChannelsPage.tsx`):
  | mã | thông điệp |
  |---|---|
  | `facebook_no_pages` | "Tài khoản chưa quản lý Page nào hoặc chưa cấp quyền Page." |
  | `facebook_oauth_state` | "Phiên kết nối hết hạn, vui lòng thử lại." |
  | `facebook_oauth_failed` | "Kết nối Facebook thất bại, thử lại sau." |
  | `facebook_oauth_*` khác | "Bạn đã huỷ hoặc Facebook từ chối cấp quyền." |
  `connected` → `message.success`. Sau khi hiện, xoá param khỏi URL (`setParams`).
- **Kết nối:** nút "Kết nối Facebook Page" (`<FacebookFilled/>`) gọi `useConnectFacebook` (giữ hook cũ), `disabled` khi không có `messaging.connect`.
- **Danh sách (G3):** card mỗi page (mẫu card `ChannelsPage`): tên page, `<ChannelBadge provider="facebook_page"/>`, tag trạng thái (Active/Hết hạn token), nút **"Kết nối lại"** (gọi lại connect) hiện rõ khi `token_expired`, nút **"Ngắt kết nối"** trong `<Popconfirm>` cảnh báo "Sẽ gỡ Page và xoá toàn bộ hội thoại liên quan, không khôi phục được."
- Empty state khi chưa có page.
- Ghi chú dưới trang: "Lazada/TikTok dùng chung kết nối với Gian hàng — bật nhắn tin tại trang Gian hàng" + `<Link to="/channels">`.

### 5.2 Điều hướng (G1)

- `app.tsx`: thêm `<Route path="messaging/channels" element={<MessagingChannelsPage />} />`.
- `AppLayout.tsx` menu "Tin nhắn": thêm con `{ key: '/messaging/channels', label: <Link>Kết nối kênh</Link> }` (đặt ngay sau "Hộp thư").
- `MessagingNav.tsx`: thêm option `{ label: 'Kết nối kênh', value: '/messaging/channels' }` (sau "Hộp thư"). Đổi label `'Cài đặt AI'` giữ nguyên.
- `MessagingPage.tsx`: import & render `<MessagingNav />` ở đầu (để Hộp thư cũng có thanh điều hướng).

### 5.3 Sửa các trang hiện có

- **`MessagingSettingsPage.tsx`:** bỏ card "Kết nối kênh nhắn tin" (lines 44-53) — chuyển sang trang mới. Giữ card AI. Đổi `<Select>` AI provider (line 62) → `<Radio.Group>` (nếu `providers.length` lớn vẫn dùng Select — nhưng tập provider nhỏ, mặc định Radio). Bỏ import `useConnectFacebook`, `FacebookFilled`.
- **`MessagingPage.tsx`:** thay emoji `📍` (line 123) bằng `<ShopOutlined/>` (hoặc `<EnvironmentOutlined/>`) từ `@ant-design/icons`.
- **`ChannelsPage.tsx`** (G5): trong card mỗi gian hàng, nếu provider ∈ {lazada, tiktok} **và** messaging connector của provider đó đang bật → hiện `<Switch>` "Bật nhắn tin" (checked = `account.messaging_enabled`), gọi `useSetChannelMessaging`. Việc biết connector nào bật: BE trả thêm cờ `messaging_available` per account trong `GET /channel-accounts` (mở rộng resource), hoặc FE đọc từ 1 nguồn config. **Chọn:** mở rộng `ChannelAccountResource` thêm `messaging_available: bool` + `messaging_enabled: bool`.

### 5.4 Hooks mới

- `messagingConfig.tsx`: `useMessagingChannels()` (GET list), `useDisconnectFacebookPage()` (DELETE + invalidate list).
- `channels.tsx`: `useSetChannelMessaging()` (PATCH `/channel-accounts/{id}/messaging` + invalidate channel list).

---

## 6. Data flow

- **Kết nối:** nút → `POST /messaging/facebook/connect` → redirect Meta dialog (scope `pages_messaging,...`) → user cấp quyền → callback đổi code → `/me/accounts` → upsert channel_accounts (`messaging_enabled=true`) → subscribe webhook → redirect `/messaging/channels?connected=facebook_page` → toast + list refresh.
- **Kết nối lại:** giống trên (token được làm tươi qua `updateOrCreate`).
- **Ngắt kết nối:** nút → Popconfirm → `DELETE /messaging/channels/{id}` → unsubscribe webhook (best-effort) → xoá cascade trong transaction → audit → list refresh.
- **Bật/tắt nhắn tin Lazada/TikTok:** Switch trên `/channels` → `PATCH /channel-accounts/{id}/messaging {messaging_enabled}` → set cờ → invalidate.

---

## 7. Edge case & lỗi

| Tình huống | Xử lý |
|---|---|
| User huỷ ở Meta dialog | callback `?error` → `/messaging/channels?error=facebook_oauth_*` → banner thân thiện |
| Tài khoản không quản lý page | `facebook_no_pages` → banner hướng dẫn |
| Ngắt kết nối page có nhiều hội thoại | Popconfirm cảnh báo; xoá cascade trong transaction; nếu xoá file MinIO lỗi → log, vẫn xoá DB |
| Unsubscribe webhook Meta lỗi | Log, không chặn việc xoá (page có thể đã bị thu hồi token) |
| Toggle messaging cho provider không có connector bật | `422 MESSAGING_NOT_AVAILABLE`, FE ẩn switch sẵn |
| staff_cs/staff_order mở trang channels | Thấy danh sách (`messaging.view`) nhưng nút kết nối/ngắt/switch `disabled` (`messaging.connect`) |
| Token page hết hạn | List hiện tag "Hết hạn token" + nút "Kết nối lại" nổi bật |
| Xoá page rồi webhook cũ vẫn đẩy tin | `MessagingWebhookController` không tìm thấy channel_account active ⇒ bỏ qua (đã có pattern resolve) |

---

## 8. Bảo mật

- `GET /messaging/channels` **không** trả `access_token`.
- Mọi mutating action (`connect`/`disconnect`/`toggle`) ghi `audit_logs` prefix `messaging.*` (đúng SPEC §8.7).
- Ngắt kết nối xoá cả file đính kèm MinIO (PII ảnh) — nhất quán SPEC §8.3.
- Endpoint mutating gate `messaging.connect`; chỉ Owner/Admin.

---

## 9. Kiểm thử

**BE feature:**
- `connect` gate `messaging.connect`: Owner OK; `staff_cs` 403.
- `GET /messaging/channels`: trả đúng page facebook của tenant, không lộ token, không thấy page tenant khác.
- `DELETE /messaging/channels/{id}`: xoá channel + conversations + messages + attachments; audit ghi; account provider≠facebook_page ⇒ 404; account tenant khác ⇒ 404.
- `PATCH /channel-accounts/{id}/messaging`: set cờ đúng; provider không hỗ trợ ⇒ 422; gate `messaging.connect`.
- Callback redirect tới `/messaging/channels?connected=...` / `?error=...`.

**FE (Vitest):**
- `MessagingChannelsPage` render list + empty state.
- Toast theo `?connected` / banner theo `?error=facebook_*`, param được dọn.
- Popconfirm ngắt kết nối gọi đúng hook.
- `ChannelsPage` switch hiện đúng điều kiện + gọi PATCH.
- Nút disabled khi thiếu `messaging.connect`.

---

## 10. Tiêu chí hoàn thành

- [ ] Permission `messaging.connect` thêm vào Role enum; connect gate đổi sang nó.
- [ ] `GET /messaging/channels` + `DELETE /messaging/channels/{id}` hoạt động + test xanh.
- [ ] `PATCH /channel-accounts/{id}/messaging` hoạt động + `ChannelAccountResource` có `messaging_available`/`messaging_enabled`.
- [ ] Callback redirect sang `/messaging/channels`.
- [ ] Trang `/messaging/channels` có trên menu + MessagingNav (cả Hộp thư); kết nối/kết nối lại/ngắt kết nối hoạt động; toast/banner đúng.
- [ ] Switch "Bật nhắn tin" Lazada/TikTok trên `/channels` set được cờ.
- [ ] `MessagingSettingsPage` bỏ card kết nối + Select→Radio; `MessagingPage` bỏ emoji `📍`.
- [ ] Audit log mọi action mutating.
- [ ] Test BE + FE đạt ngưỡng coverage chung.

---

## 11. Lộ trình triển khai (slice nhỏ)

1. **BE-perm:** thêm `messaging.connect`, đổi gate connect. (test)
2. **BE-list/disconnect:** `MessagingChannelController` + routes + service xoá cascade + unsubscribe. (test)
3. **BE-toggle:** `setMessaging` + route + resource field. (test)
4. **BE-redirect:** đổi đích callback. (test)
5. **FE-nav:** route + AppLayout menu + MessagingNav option + render nav trên inbox.
6. **FE-page:** `MessagingChannelsPage` (connect + list + disconnect + callback feedback) + hooks.
7. **FE-channels:** switch bật nhắn tin trên ChannelsPage + hook.
8. **FE-cleanup:** sửa MessagingSettingsPage (bỏ card, Select→Radio) + bỏ emoji MessagingPage.
9. **Test FE** + chạy toàn bộ.
