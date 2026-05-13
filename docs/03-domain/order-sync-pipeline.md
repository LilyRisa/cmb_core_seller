# Pipeline đồng bộ đơn hàng

**Status:** Stable · **Cập nhật:** 2026-05-11

> Nguyên tắc: **webhook (đẩy) + polling (kéo) bổ trợ nhau; mọi job idempotent; webhook không bao giờ được tin là đủ.**

## 1. Toàn cảnh

```
A) PUSH (webhook):
   Sàn ──POST /webhook/{provider}──▶ [verify chữ ký] ──▶ ghi webhook_events(pending) ──▶ HTTP 200 ngay
                                                          │
                                                          └─dispatch─▶ ProcessWebhookEvent (queue: webhooks)
B) PULL (polling):
   Scheduler mỗi 5–15' ──▶ với mỗi channel_account active: SyncOrdersForShop (queue: orders-sync)

Cả hai đổ về cùng một chỗ:
   ProcessWebhookEvent / SyncOrdersForShop
        │ resolve tenant + channel_account (qua shop id)
        │ connector.parseWebhook() / connector.fetchOrders(since,cursor)
        │ với mỗi đơn cần cập nhật:
        ▼
   connector.fetchOrderDetail(externalOrderId)        ← luôn lấy detail, không tin payload webhook
        ▼
   OrderUpsertService.upsert(OrderDTO)                 ← idempotent
        │  - unique (source, channel_account_id, external_order_id)
        │  - nếu DTO.source_updated_at <= order.source_updated_at hiện có ⇒ bỏ qua (đến trễ)
        │  - map raw_status → status chuẩn (connector.mapStatus / XStatusMap)
        │  - ghi order_status_history nếu status đổi
        │  - upsert order_items; thử auto-map sku (seller_sku → sku_code)
        ▼
   fire OrderUpserted / OrderStatusChanged (domain event)
        ├─▶ InventoryListener: reserve/release tồn theo trạng thái (+ ghi inventory_movements)
        │       └─▶ fire InventoryChanged ─▶ debounce PushStockToChannel (xem inventory doc)
        ├─▶ AutomationListener: chạy automation_rules khớp (vd auto-confirm)
        └─▶ NotificationListener: thông báo "đơn mới" / "đơn lỗi" nếu cấu hình
```

## 2. Webhook receiver — yêu cầu

- Route `/webhook/{provider}` (không CSRF, không auth). Middleware `verify-webhook:{provider}` gọi `XWebhookVerifier` của connector → sai chữ ký ⇒ `401`, **không** ghi gì.
- **Trả `200` trong < ~3s** dù xử lý chưa xong → chỉ ghi `webhook_events` rồi dispatch job. Không gọi API sàn trong request webhook.
- Chống replay: dedupe theo `(provider, external_id, event_type[, timestamp])`; nếu đã có `processed` ⇒ bỏ qua. Bỏ event quá cũ (ngoài cửa sổ chấp nhận).
- Mọi event lưu nguyên `payload jsonb` để có thể tái xử lý (re-drive) khi sửa bug mapping.
- Loại event tối thiểu cần xử lý: `order_status_update`, `order_create`, `order_cancel`, (sau) `return_status_update`, `settlement_available`, `product_status_update`, và **`data_deletion`/`shop_deauthorized`** (xem `08-security-and-privacy.md`).

## 3. Polling — yêu cầu

Ba mode `sync_runs.type` (đều dùng cùng job `SyncOrdersForShop`):

### 3.1 `poll` (thời gian) — mặc định mỗi 10'
- Gọi `connector.fetchOrders(OrderQuery{ updatedFrom: last_synced_at − overlap(vài phút), cursor })`, lặp phân trang tới hết; với mỗi đơn → `fetchOrderDetail` → `OrderUpsertService.upsert`.
- Lưu tiến độ vào `sync_runs` (cursor, stats: fetched/created/updated/errors). Lỗi giữa chừng → lần sau tiếp tục từ cursor.
- Cập nhật `channel_account.last_synced_at` = thời điểm bắt đầu run (không phải kết thúc) trừ overlap → không bỏ sót đơn cập nhật trong lúc chạy.
- Rải job theo shop để không dồn cùng lúc.

### 3.2 `backfill` (khi mới kết nối) — 1 lần
- Job riêng `BackfillOrders(channel_account, days=90)` chạy theo batch, throttle, ghi `sync_runs(type=backfill)`. Có thể yêu cầu backfill lại bằng tay.
- Watermark thời gian dùng `now − backfill_days` (mặc định 90). Lazada cap `update_after` thực tế khá ngắn ⇒ backfill có thể không lấy đủ đơn cũ.

### 3.3 `unprocessed` (status-based) — mỗi 30' + manual trigger
- **Lý do tồn tại**: Đơn đặt từ lâu nhưng chưa rời kho (sàn nhận, ĐVVC chưa cầm hàng) — `pending`/`ready_to_ship`/`packed` — không nằm trong cửa sổ thời gian gần đây ⇒ poll thời gian KHÔNG kéo về. User vẫn cần xử lý các đơn này.
- **Cách hoạt động**: Không dùng `updatedFrom` (không giới hạn thời gian). Iterate qua từng raw status trong `connector.unprocessedRawStatuses()` — với mỗi status, gọi `fetchOrders(statuses=[status], cursor)` page hết → upsert.
- Connector khai báo các "trạng thái chưa xử lý" qua method `unprocessedRawStatuses(): list<string>`:
  - **Lazada**: `['pending', 'topack', 'ready_to_ship', 'packed']` — item-level status, đơn chưa rời kho.
  - **TikTok Shop**: `['AWAITING_SHIPMENT', 'AWAITING_COLLECTION', 'PARTIALLY_SHIPPING']`.
  - **Manual**: `[]` (đơn tự tạo, không có sàn để pull).
- Trigger: scheduler mỗi 30' + endpoint `POST /channel-accounts/{id}/resync-unprocessed` cho user manual.
- **Không** cập nhật `last_synced_at` (vì không phải time-window sync) — không nhiễu vào polling thường.
- Unique guard theo `(account, type=unprocessed)` — không conflict với poll/backfill.

## 4. Idempotency & nhất quán (RULES)

1. Khoá chống trùng: `orders(source, channel_account_id, external_order_id)`; `webhook_events(provider, external_id, event_type)`.
2. So `source_updated_at` trước khi ghi đè — chỉ ghi nếu mới hơn (xử lý đến trễ / out-of-order).
3. Upsert đơn + cập nhật tồn nằm trong **một transaction**; phát domain event **sau commit** (`afterCommit`).
4. Job có `tries` + backoff (exponential, có jitter) + `retryUntil`; quá hạn → vào "dead letter" (đánh dấu `webhook_events.status=failed`, `sync_runs` lỗi) + cảnh báo, có UI re-drive.
5. Rate-limit per `(provider, shop)` bằng Redis limiter; tôn trọng `Retry-After` / mã 429 của sàn.
6. Token hết hạn giữa chừng → connector tự thử `refreshToken`; nếu không được → đánh dấu `channel_account.status=expired`, dừng sync shop đó, thông báo user re-connect.
7. Một shop lỗi **không** được làm dừng sync các shop khác (cô lập theo job/queue).

## 5. Quan sát & vận hành
- Mỗi `sync_run` & `webhook_event` đều truy được trạng thái trên UI (trang "Nhật ký đồng bộ"): đã xử lý chưa, lỗi gì, re-drive.
- Metric: số đơn đồng bộ/giờ theo sàn, độ trễ webhook→xử lý, tỉ lệ lỗi, số token sắp hết hạn.
- Cảnh báo: shop có `status=expired`, tỉ lệ lỗi sync tăng đột biến, webhook ngừng đến trong X giờ (nghi vấn mất đăng ký webhook → polling vẫn chạy nên không mất đơn, nhưng cần xử lý).
