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
- *Còn lại:* rate-limit Redis throttle per `(provider,shop)` đang best-effort trong `TikTokClient::throttle()` — hoàn thiện + xử lý 429/`Retry-After` kỹ hơn; UI re-drive cho `webhook_events`/`sync_runs`.

## 4. RULES
1. Không gọi API ngoài / sinh PDF trong request HTTP — luôn dispatch job.
2. Mọi job idempotent + có retry/backoff hợp lý.
3. Job đụng API sàn phải đi qua rate limiter per (provider, shop).
4. Một shop/tenant lỗi không được làm nghẽn queue chung — cô lập bằng throttle + dead-letter.
5. Thêm job mới nặng/định kỳ ⇒ cập nhật bảng ở file này.
