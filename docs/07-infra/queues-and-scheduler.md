# Hàng đợi & Lịch chạy (Queues & Scheduler)

**Status:** Stable · **Cập nhật:** 2026-05-11

> Mọi I/O ra ngoài (gọi API sàn/ĐVVC, gửi mail, sinh PDF) và mọi việc lâu ⇒ chạy ở job trên hàng đợi Redis, quản lý bằng **Laravel Horizon**. Controller không bao giờ chờ I/O ngoài.

## 1. Queues (Horizon supervisors)
| Queue | Dùng cho | Ưu tiên / scale |
|---|---|---|
| `webhooks` | `ProcessWebhookEvent` (xử lý event từ sàn) | Cao nhất, nhiều worker — phải tiêu hoá nhanh |
| `orders-sync` | `SyncOrdersForShop`, `BackfillOrders`, `FetchOrderDetail` | Trung bình, throttle per (provider,shop) |
| `inventory-push` | `PushStockForSku`, `PushStockToListing` | Trung bình, throttle per (provider,shop), debounce |
| `labels` | `GenerateBulkLabel`, render picking/packing PDF (Gotenberg) | Trung bình; tách riêng vì CPU/IO nặng |
| `listings` | đồng bộ listing, đăng bán đa sàn, đồng bộ category | Thấp |
| `tokens` | `RefreshChannelToken`, `RefreshExpiringTokens` | Cao (token hỏng = sync dừng) |
| `finance` | `FetchSettlements`, tính lợi nhuận, tổng hợp `profit_snapshots` | Thấp, chạy theo kỳ |
| `notifications` | gửi email/in-app/Zalo/Telegram | Trung bình |
| `customers` | *(Phase 2 — SPEC-0002, đã implement)* `LinkOrderToCustomer` (listener), `AnonymizeCustomersForShop` (cả data_deletion & disconnect); commands `customers:recompute-stale` (hằng giờ), `customers:backfill` (one-shot) | Thấp — không chặn order pipeline; race xử lý bằng `lockForUpdate` + unique `(tenant_id,phone_hash)` trong service |
| `default` | còn lại | Trung bình |

- Mỗi queue có số worker riêng (cấu hình theo tải; ~17k đơn/ngày ⇒ vài chục worker process tổng cộng là dư). Worker chạy ở container `worker` tách biệt, scale replica độc lập.
- **Rate limit per (provider, shop)** bằng Redis limiter (`Redis::throttle(...)` / middleware `RateLimited`); tôn trọng 429/`Retry-After` của sàn.
- **Debounce/coalesce**: job đẩy tồn dùng key `push-stock:{tenant}:{sku}` + delay → gom nhiều thay đổi liên tiếp.
- **Retry**: `tries` hợp lý + backoff exponential có jitter + `retryUntil`. Quá hạn ⇒ "failed jobs" + cảnh báo; có UI re-drive cho webhook/sync.
- **Idempotent**: mọi job phải an toàn khi chạy lại (xem các doc domain).
- **Unique job** (`ShouldBeUnique`) cho job không nên chạy song song trùng (vd `SyncOrdersForShop` cho cùng shop).

## 2. Scheduler (`app/Console/Kernel::schedule`)
| Tần suất | Job | Mục đích |
|---|---|---|
| mỗi ~10' | `SyncOrdersForShop` cho từng `channel_account` active (rải đều) | Polling đơn (backup webhook) |
| mỗi 5–10' | quét listing chưa ghép SKU / cảnh báo | Nhắc ghép SKU |
| mỗi 30' | `RefreshExpiringTokens` | Refresh token sắp hết hạn |
| mỗi giờ | `ReconcileInventory` (đối chiếu tồn sàn ↔ master, cảnh báo lệch) | Phát hiện trôi tồn |
| mỗi giờ | `RetryFailedStockPushes` | Đẩy lại tồn các listing `sync_status=error` |
| hằng ngày | `BackfillRecentOrders` (vài ngày gần, an toàn) | Lưới an toàn |
| hằng ngày | `FetchSettlements` (Phase 6) | Kéo đối soát |
| hằng ngày | `RebuildProfitSnapshots` (Phase 6) | Tổng hợp báo cáo |
| hằng ngày | `PruneOldWebhookEvents` / archive partition cũ | Giữ DB gọn |
| mỗi giờ | *(Phase 2)* `customers:recompute-stale` | Recompute stats cho khách có đơn updated trong giờ qua — phòng `LinkOrderToCustomer` miss event (lưới an toàn idempotent) |
| hằng ngày | *(Phase 2)* `AnonymizeCustomersForShop` cho các shop disconnect quá 90 ngày | Ẩn danh hoá theo SPEC-0002 §7.2 |
| hằng ngày | `PrunePrintDocuments` | Xoá file phiếu in (vận đơn/picking/packing PDF) quá 90 ngày, giữ metadata — xem `docs/03-domain/fulfillment-and-printing.md` §8 |
| hằng ngày | `CreateNextMonthPartitions` | Tạo trước partition tháng kế cho bảng lớn |
| hằng ngày | `SendDigestNotifications` (tuỳ cấu hình tenant) | Tóm tắt đơn/cảnh báo |
| hằng tuần | `ReconcileShippingFees` (Phase 6) | Đối soát phí ship ước tính vs thực tế |
| hằng tuần | `CheckWebhookHeartbeat` | Cảnh báo nếu sàn ngừng gửi webhook (polling vẫn chạy nên không mất đơn) |

- Scheduler chạy ở container `scheduler` (một instance để tránh chạy trùng), hoặc dùng `withoutOverlapping()`.
- Job định kỳ nặng (`SyncOrdersForShop` cho N shop) ⇒ dispatch các job con lên queue, không tự chạy đồng bộ trong scheduler tick.

## 3. Quan sát
- Horizon dashboard: throughput, thời gian chờ, jobs failed theo queue.
- Cảnh báo: queue tồn đọng quá ngưỡng, tỉ lệ fail tăng, `tokens` queue có job (token sắp/đã hỏng), `labels` queue chậm.
- Metric nghiệp vụ (xem `order-sync-pipeline.md` §5).

## 3b. Đã implement (Phase 1 — TikTok, xem `docs/specs/0001-tiktok-order-sync.md`)
- Queue `webhooks`: `Channels\Jobs\ProcessWebhookEvent` (tries 5, backoff 10/30/60/300/900s) — resolve shop, re-fetch order detail, upsert idempotent.
- Queue `orders-sync`: `Channels\Jobs\SyncOrdersForShop` (tries 3, `ShouldBeUnique` theo `(shop,type)`; dùng chung cho `poll` & `backfill`) — phân trang `connector.fetchOrders`, upsert, ghi `sync_runs`, advance `last_synced_at`. *(Không cần job `FetchOrderDetail` riêng cho polling — `orders/search` của TikTok trả đủ detail; chỉ webhook mới `fetchOrderDetail`.)*
- Queue `tokens`: `Channels\Jobs\RefreshChannelToken` (tries 3, `ShouldBeUnique` theo account).
- Scheduler (`routes/console.php`): mỗi 10' dispatch `SyncOrdersForShop` cho từng `channel_account` active (`Schedule::call`, `withoutOverlapping`); mỗi 30' `channels:refresh-expiring-tokens`; hằng ngày `SyncOrdersForShop(since=now-3d)` cho từng shop active (backfill an toàn) + `model:prune` (dọn `oauth_states` hết hạn); `horizon:snapshot` mỗi 5' & `db:partitions:ensure` hằng ngày (Phase 0).
- UI **Nhật ký đồng bộ** (`/sync-runs`, `/webhook-events` + re-drive) đã có (xem `docs/05-api/endpoints.md`).
- *Còn lại:* rate-limit Redis throttle per `(provider,shop)` đang best-effort trong `TikTokClient::throttle()` — hoàn thiện + xử lý 429/`Retry-After` kỹ hơn.

## 3c. Đã implement (Phase 2 — Customers, xem `docs/specs/0002-customer-registry-and-buyer-reputation.md`)
- Queue `customers` (thêm vào `supervisor-default` của Horizon, `waits` 300s):
  - `Customers\Listeners\LinkOrderToCustomer` (listen `OrderUpserted`, tries 3) — khớp/tạo khách theo `phone_hash`, set `orders.customer_id`, recompute `lifetime_stats` + reputation + auto-notes (idempotent: đọc thẳng từ `orders`; race xử lý bằng `lockForUpdate` + unique `(tenant_id, phone_hash)`).
  - `Customers\Jobs\AnonymizeCustomersForShop` (tries 3) — clear PII của khách "single-shop" thuộc shop bị data_deletion / disconnect; giữ `phone_hash` + `lifetime_stats`. Trigger: listener `OnDataDeletionRequested` (event `Channels\Events\DataDeletionRequested`, ngay) và `OnChannelAccountRevoked` (event `ChannelAccountRevoked`, `->delay(now()->addDays(config('customers.anonymize_after_days')))`).
- Scheduler (`routes/console.php`): mỗi giờ `customers:recompute-stale --hours=2` (lưới an toàn). One-shot: `customers:backfill`.

## 3d. Đã implement (Phase 2 — Tồn kho & đẩy tồn, xem `docs/specs/0003-products-skus-inventory-manual-orders.md`)
- Queue mặc định (listener): `Inventory\Listeners\ApplyOrderInventoryEffects` (listen `OrderUpserted`, tries 3) — resolve `order_items.sku_id` (mapping/auto-match) + áp tồn theo vòng đời (reserve/ship/release/return) qua `InventoryLedgerService` (idempotent per `(order_item,sku,type)`); set/clear `order.has_issue='SKU chưa ghép'`. Manual order create/cancel/edit cũng fire `OrderUpserted` ⇒ cùng listener (+ `LinkOrderToCustomer`).
- Queue `inventory-push` (đã có trong supervisor-sync): `Inventory\Jobs\PushStockForSku` (`ShouldBeUnique` per `(tenant,sku)` 30s — debounce; tính `available` tổng → mỗi `channel_listing` ghép: `desired = single⇒floor(avail/qty)`, `bundle⇒min(floor(avail_i/qty_i))`; khác `channel_stock` ⇒ dispatch tiếp) → `Inventory\Jobs\PushStockToListing` (tries 4, backoff 30/120/600s — gọi `connector.updateStock`; `UnsupportedOperation`/listing `is_stock_locked` ⇒ `sync_status=error`/skip, không retry; lỗi khác ⇒ retry, quá hạn ⇒ `sync_status=error` giữ desired). Trigger: listener `PushStockOnInventoryChange` (listen `InventoryChanged`) → `PushStockForSku::dispatch(...)->delay(10s)`.
- Queue `listings` (đã có trong supervisor-sync): `Channels\Jobs\FetchChannelListings` (`ShouldBeUnique` per shop 30', backoff 60/300/900s) — phân trang `connector.fetchListings` → upsert `channel_listings` by `(channel_account_id, external_sku_id)` (giữ `sync_status`/`is_stock_locked`) → `SkuMappingService::autoMatchUnmapped`. Trigger: `POST /channel-accounts/{id}/resync-listings`, `POST /channel-listings/sync`, và scheduler hằng ngày `03:30` cho mọi shop active hỗ trợ `listings.fetch`. Command bổ trợ `inventory:resync-order-skus` (re-resolve `order_items.sku_id` cho đơn còn `has_issue='SKU chưa ghép'` sau khi có mapping mới — chạy tay/sau khi sync listing).
- *Chưa làm:* đồng bộ ngược tồn (đối chiếu `channel_stock` thực) — Phase 5.

## 4. RULES
1. Không gọi API ngoài / sinh PDF trong request HTTP — luôn dispatch job.
2. Mọi job idempotent + có retry/backoff hợp lý.
3. Job đụng API sàn phải đi qua rate limiter per (provider, shop).
4. Một shop/tenant lỗi không được làm nghẽn queue chung — cô lập bằng throttle + dead-letter.
5. Thêm job mới nặng/định kỳ ⇒ cập nhật bảng ở file này.
