# Webhook & OAuth callback

**Status:** Stable · **Cập nhật:** 2026-05-11

> Đây là 2 nhóm route "không phải `/api`" mà vẫn ở phía Laravel (ngoài catch-all SPA).

## 1. Webhook `/webhook/{provider}`

- Route: `POST /webhook/{provider}` với `provider ∈ {tiktok, shopee, lazada, ...}` (và `/webhook/{carrier}` cho ĐVVC có webhook). **Không CSRF, không auth session.**
- Middleware `verify-webhook:{provider}` gọi `XWebhookVerifier` của connector (kiểm chữ ký bằng secret + raw body). Sai ⇒ `401`, **không ghi gì, không xử lý**.
- Xử lý trong request (nhanh, < ~3s):
  1. Đọc raw body + headers.
  2. (Đã verify) ghi `webhook_events`: `provider`, `event_type` (sơ bộ), `external_id` (đơn/shop/...), `signature`, `payload jsonb`, `received_at`, `status=pending`.
  3. Dedupe: nếu `(provider, external_id, event_type)` đã `processed` ⇒ trả `200` (idempotent), không dispatch lại.
  4. `dispatch(ProcessWebhookEvent::class)` lên queue `webhooks`.
  5. Trả `200 OK` (body trống hoặc `{"ok":true}`).
- **Không** gọi API sàn / DB ghi nặng trong request webhook. Mọi việc thật ⇒ trong job.
- `ProcessWebhookEvent`: resolve tenant (qua shop id → `channel_account`) → theo `type`:
  - `order_*` ⇒ (1) **fast-path** — nếu push mang theo trạng thái đơn (`webhook_events.order_raw_status`, do `parseWebhook` trích từ `data.order_status`) và đơn đã có trong DB ⇒ `OrderUpsertService.applyStatusFromWebhook` cập nhật ngay trạng thái (không bump `source_updated_at`); (2) `fetchOrderDetail` → `OrderUpsertService.upsert` để làm giàu (tạo đơn nếu chưa có) — nếu re-fetch lỗi mà fast-path đã áp ⇒ log & dừng (polling sẽ bù phần còn lại). Xem `03-domain/order-sync-pipeline.md`.
  - `return_update` ⇒ cập nhật `return_requests` + trạng thái đơn.
  - `settlement_available` ⇒ enqueue `FetchSettlements` (Phase 6).
  - `product_update` ⇒ enqueue refresh listing.
  - `shop_deauthorized` ⇒ `channel_account.status=revoked`, dừng sync, thông báo user.
  - `data_deletion` ⇒ enqueue job ẩn danh hoá dữ liệu buyer (xem `08-security-and-privacy.md`).
  - không nhận diện được ⇒ `webhook_events.status=ignored` + log (để bổ sung mapping sau).
- Hoàn tất ⇒ `webhook_events.processed_at`, `status=processed`. Lỗi ⇒ retry/backoff; quá hạn ⇒ `status=failed` + cảnh báo; UI "Nhật ký đồng bộ" cho **re-drive** (chạy lại từ `payload` đã lưu).
- Mỗi sàn liệt kê loại event nó gửi & cách verify trong `04-channels/<provider>.md`.

## 2. OAuth callback `/oauth/{provider}/callback`

- **Bắt đầu kết nối** (qua API, không phải route web): `POST /api/v1/channel-accounts/{provider}/connect` (auth + tenant) → tạo `oauth_states(state, provider, tenant_id, expires_at)` → trả `{ "auth_url": "..." }` (do `connector.buildAuthorizationUrl(state)`). SPA redirect user tới `auth_url`.
- **Callback** (route web, public): `GET /oauth/{provider}/callback?code=...&state=...`:
  1. Tra `oauth_states` theo `state` → hết hạn/không có ⇒ trang lỗi. Lấy `tenant_id` từ đó (vì callback không có session đáng tin).
  2. `connector.exchangeCodeForToken(code)` → `TokenDTO`.
  3. `connector.fetchShopInfo(...)` → tạo/cập nhật `channel_account` (tenant_id, provider, external_shop_id, shop_name, token🔒, expires...). Nếu shop đã kết nối ở tenant khác ⇒ báo lỗi rõ ràng.
  4. `connector.registerWebhooks(...)` (nếu sàn hỗ trợ).
  5. Enqueue `BackfillOrders(channel_account, 90 days)`.
  6. Xóa `oauth_states`. Redirect về SPA: `/{...}/channels?connected={provider}` (trang "Kết nối thành công").
- Lỗi giữa chừng ⇒ trang lỗi thân thiện + log + không tạo `channel_account` nửa vời.
- **Disconnect**: `DELETE /api/v1/channel-accounts/{id}` → `connector.revoke()` (best-effort) → `status=revoked` (giữ lịch sử đơn) hoặc xoá mềm tuỳ chọn → dừng mọi job của shop đó → (tuỳ chính sách) ẩn danh hoá dữ liệu buyer.

## 3. RULES
1. Webhook: verify trước, trả 200 nhanh, xử lý async, idempotent, lưu payload thô để re-drive.
2. OAuth: dùng `state` chống CSRF; resolve tenant từ `oauth_states` chứ không từ session ở callback.
3. Một controller chung cho mọi sàn — khác biệt nằm trong connector (verifier, buildAuthorizationUrl, exchangeCodeForToken, parseWebhook).
4. Mọi token lưu mã hoá; không log token/secret.
5. Payload webhook không phải dữ liệu cuối — `fetchOrderDetail` để lấy bản chuẩn (items/địa chỉ/phí…). Ngoại lệ được phép: trạng thái đơn trong push có thể áp ngay vào đơn **đã tồn tại** (`applyStatusFromWebhook`, không bump `source_updated_at`) để webhook vẫn "chạy" khi API tạm lỗi; lần re-fetch sau vẫn ghi đè/làm giàu.
