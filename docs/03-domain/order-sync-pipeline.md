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
- *(Từ 2026-07-14)* dedupe được chốt atomic ở tầng DB bằng unique index `webhook_events(provider, event_type, external_id, external_shop_id, dedupe_status_key)` (bên cạnh fast-path `exists()` cũ, race hiếm giữa 2 request vẫn bị unique constraint chặn — xem `WebhookIngestService`). **Giới hạn**: unique index chuẩn SQL coi NULL khác NULL, nên các event có `external_id` và/hoặc `external_shop_id` = NULL (tuỳ provider) KHÔNG được constraint này bảo vệ — vẫn chỉ dựa vào `exists()` như trước, cho riêng tập con đó.
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
  - **Lazada**: `['pending', 'ready_to_ship']` — **chỉ dùng status filter values mà Lazada `/orders/get` chấp nhận** (theo tài liệu chính thức: `pending | canceled | ready_to_ship | delivered | returned | shipped | failed`). KHÔNG dùng item-level statuses (`topack`/`packed`) làm filter — Lazada reject. `pending` bao trùm `topack`; `ready_to_ship` bao trùm `packed`.
  - **TikTok Shop**: `['ON_HOLD', 'AWAITING_SHIPMENT', 'PARTIALLY_SHIPPING', 'AWAITING_COLLECTION']`. **`ON_HOLD`** = đã thanh toán, chờ fulfillment (buyer còn cancel được — tài liệu TikTok). Đơn PRE_ORDER có thể stuck ở `ON_HOLD` tới 1 ngày trước release ⇒ phải pull về để seller chuẩn bị.
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
6. Token hết hạn giữa chừng → connector tự thử `refreshToken`. **Chỉ đánh dấu `channel_account.status=expired`** (dừng sync + báo user re-connect) khi refresh **bị sàn từ chối thật sự** (connector `isAuthError()` — refresh_token sai/đã thu hồi) **hoặc** `refresh_token_expires_at` đã quá hạn. Lỗi tạm thời (network/5xx/rate-limit/sign/đua xoay-vòng refresh_token) **giữ nguyên `active`** — access_token hiện tại thường còn hạn (Shopee: 4h) nên sync vẫn chạy, lần refresh theo lịch kế tiếp thử lại. (Trước đây expire ngay lần lỗi đầu khiến shop Shopee "rớt sau vài tiếng" dù refresh_token còn 30 ngày.) Refresh được **khoá theo từng account** (`Cache::lock`) + đọc lại token mới nhất vì Shopee/Lazada **xoay-vòng refresh_token mỗi lần refresh** — hai job refresh trùng token sẽ làm job thua cuộc nhận lỗi auth giả. Lịch `channels:refresh-expiring-tokens --within=7200` chạy mỗi 30' (refresh khi còn ~2h, không refresh lại mỗi tick).
7. Một shop lỗi **không** được làm dừng sync các shop khác (cô lập theo job/queue).

## 5. Quan sát & vận hành
- Mỗi `sync_run` & `webhook_event` đều truy được trạng thái trên UI (trang "Nhật ký đồng bộ"): đã xử lý chưa, lỗi gì, re-drive.
- Metric: số đơn đồng bộ/giờ theo sàn, độ trễ webhook→xử lý, tỉ lệ lỗi, số token sắp hết hạn.
- Cảnh báo: shop có `status=expired`, tỉ lệ lỗi sync tăng đột biến, webhook ngừng đến trong X giờ (nghi vấn mất đăng ký webhook → polling vẫn chạy nên không mất đơn, nhưng cần xử lý).
