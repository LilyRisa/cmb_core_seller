# ADR-0007: Đồng bộ đơn = webhook + polling backup; mọi job idempotent; không tin payload webhook làm dữ liệu cuối

- **Trạng thái:** Accepted
- **Ngày:** 2026-05-11
- **Người quyết định:** Team

## Bối cảnh

Sàn TMĐT đẩy webhook nhưng webhook **không đáng tin** (mất, trễ, trùng, đôi khi sai). Nếu chỉ dựa vào webhook ⇒ mất đơn. Nếu chỉ polling ⇒ trễ + tốn rate limit. Cần "đơn mới tự về trong vài phút" mà vẫn an toàn khi webhook hỏng.

## Quyết định

- **Webhook + polling backup**, cả hai đổ vào cùng một `OrderUpsertService`:
  - Webhook `/webhook/{provider}`: verify chữ ký → ghi `webhook_events` (lưu payload thô) → trả `200` nhanh → `dispatch(ProcessWebhookEvent)` lên queue `webhooks`. Dedupe theo `(provider, external_id, event_type)`.
  - Polling: scheduler chạy `SyncOrdersForShop` mỗi ~5–15' cho mỗi channel account active (rải đều, throttle per (provider, shop)); backfill 90 ngày khi kết nối; `BackfillRecentOrders` hằng ngày làm lưới an toàn.
- **Không tin payload webhook làm dữ liệu cuối** — luôn `fetchOrderDetail()` để lấy bản chuẩn, rồi upsert.
- **Idempotent mọi nơi**: `webhook_events`/`orders` có unique chống trùng; chỉ ghi nếu `source_updated_at` mới hơn; chạy lại job 2 lần = kết quả như 1 lần (không tạo dòng thừa, không trừ tồn 2 lần). Trạng thái lùi (sàn báo lùi) ⇒ ghi nhận + đánh dấu `has_issue`, không ghi đè im lặng. Webhook mất → polling vẫn về đơn ⇒ không mất; UI "Nhật ký đồng bộ" cho re-drive từ `payload` đã lưu. `CheckWebhookHeartbeat` cảnh báo nếu một sàn ngừng gửi webhook.

## Hệ quả

- Tích cực: nhanh khi webhook chạy, an toàn khi không; tái dựng được từ `webhook_events` + cursor; an toàn khi retry.
- Đánh đổi: thêm tải polling + thêm lần gọi `fetchOrderDetail`; phải tôn trọng 429/`Retry-After`, throttle per (provider, shop) để một shop lỗi không nghẽn queue chung.
- Liên quan: `03-domain/order-sync-pipeline.md`, `03-domain/order-status-state-machine.md`, `05-api/webhooks-and-oauth.md`, `07-infra/queues-and-scheduler.md`.
