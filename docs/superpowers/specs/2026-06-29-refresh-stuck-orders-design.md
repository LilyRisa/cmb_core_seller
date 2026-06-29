# Refresh đơn treo (RefreshStuckOrders) — Design Spec

**Ngày:** 2026-06-29 · **Trạng thái:** đã duyệt các quyết định chính, chờ review spec.

## Mục tiêu
Định kỳ (mỗi vài tiếng) **làm mới trạng thái đơn của sàn** cho các đơn đang "treo" để chúng không bị **kẹt không thao tác được vĩnh viễn**. Áp dụng **cả 3 sàn** (TikTok/Shopee/Lazada). Thiết kế **tách biệt, idempotent, không đụng luồng khác**.

## Bối cảnh (đã khảo sát)
- `SyncOrdersForShop` chế độ `unprocessed` (30') chỉ kéo đơn ĐANG ở các raw status đang xử lý; đơn mà sàn đã chuyển status khác, hoặc bị **stale-guard `source_updated_at`** chặn (vd Lazada timestamp lệch +7h trước fix), thì **không** được làm mới → treo.
- `BackfillChannelLabels`/`BackfillChannelTracking`/`FetchChannelLabel` chỉ kéo **tem/tracking**, KHÔNG refresh status.
- Đường làm mới 1 đơn đã được kiểm chứng: `ProcessWebhookEvent` gọi `connector->fetchOrderDetail()` → `mapStatus()` → `OrderUpsertService::upsertWithStatus()` (idempotent).
- Guard sẵn có cần tôn trọng: `meta.tracking_stopped` (user dừng theo dõi), sticky-forward (processing/ready_to_ship không bị tụt về unpaid/pending), abnormal-backward-jump.

## Phạm vi đơn "treo" (đã chốt: chỉ đơn treo)
Query (đơn sàn, chưa giao, đang kẹt):
- `channel_account_id IS NOT NULL` (manual bỏ qua — `fetchOrderDetail` ném `UnsupportedOperation`)
- `status IN ('pending','processing','ready_to_ship')` (chưa terminal, chưa shipped)
- **treo** = `has_issue = true` **OR** tồn tại shipment OPEN có `label_path IS NULL` AND (`label_fetch_next_retry_at IS NULL` OR `<= now()`) (hết lượt lấy tem)
- `last_synced_at < now() - {stuck_hours}` (mặc định 2h) — tránh làm mới dồn dập
- `placed_at >= now() - {max_age_days}` (mặc định 30 ngày) — chặn đơn quá cũ
- KHÔNG `meta.tracking_stopped`
- Bỏ shop đã revoke/soft-deleted (account active)

## Hành vi mỗi đơn
1. `runAs($tenant)` (tránh bug thiếu tenant context như các job label).
2. `dto = connector->fetchOrderDetail($account->authContext(), $order->external_order_id)` (nguồn chính xác, hiện tại của sàn).
3. `upsertWithStatus($dto, force: true)` — **đường force** (xem dưới). Cập nhật status/raw_status nếu sàn đã đổi.
4. **Clear has_issue lỗi thời (đã chốt)**: sau upsert, nếu đơn đã **tiến lên** — status ∈ `shipped/delivered/completed/returning/returned_refunded/cancelled` **HOẶC** đã có shipment `label_path` — VÀ `issue_reason` thuộc nhóm **tem/tracking/fulfillment** (chứa 1 trong: `phiếu giao hàng`, `mã vận đơn`, `sắp xếp vận chuyển`, `in đơn`, `Advance Fulfilment`, `COD đang chờ`, `chờ Shopee`) → set `has_issue=false`, `issue_reason=null`. **KHÔNG** động issue `SKU chưa ghép` (UNMAPPED_REASON) hay âm tồn.

## Đường "force" (quyết định an toàn #1 — đã chốt)
Thêm tham số `bool $force = false` vào `OrderUpsertService::upsertWithStatus()` (và `doUpsert`). Khi `force=true`:
- **BỎ QUA chỉ stale-guard `source_updated_at`** (dòng skip `sourceUpdatedAt <= order.source_updated_at`). Áp dụng dto (nguồn chính xác từ detail) kể cả khi timestamp không mới hơn → xử lý được cả đơn Lazada `source_updated_at` lệch tương lai.
- **VẪN GIỮ**: `meta.tracking_stopped` (vẫn skip), sticky-forward, abnormal-backward-jump, status-history.
- `force` mặc định `false` ⇒ **mọi caller hiện tại (poll/unprocessed/webhook) KHÔNG đổi hành vi**. Chỉ `RefreshStuckOrders` truyền `force=true`.

## Tần suất & chống quá tải
- Scheduler `routes/console.php`: **mỗi 2 giờ**, `->onOneServer()`. Dispatch `RefreshStuckOrders` per active channel account (hoặc 1 job quét tất cả — chọn per-account để cô lập lỗi & dùng `ShouldBeUnique` key theo account).
- `ShouldBeUnique` (window ~3600s) tránh chồng.
- Mỗi mẻ: **cap số đơn/lần** (mặc định 200/account) + **sleep nhỏ giữa các `fetchOrderDetail`** (mặc định 300ms) — tránh burst/timeout (rút kinh nghiệm sự cố FB-curl). Nếu vượt cap, mẻ sau xử lý tiếp (vì `last_synced_at` đã được bump cho đơn đã refresh).
- Per-order try/catch: 1 đơn lỗi (API timeout, NotFound...) **không** abort mẻ; lỗi tạm thời log `info/warning`, **KHÔNG** gắn has_issue.

## Cô lập (không đụng luồng khác)
- **Mới:** `app/app/Modules/Channels/Jobs/RefreshStuckOrders.php` + 1 dòng schedule + (tùy) 1 command `orders:refresh-stuck` để chạy tay/test.
- **Sửa tối thiểu:** thêm param `force` (default false) vào `OrderUpsertService::upsertWithStatus`/`doUpsert`; thêm helper clear-stale-issue (có thể đặt trong job, không sửa core upsert).
- **KHÔNG** đụng `SyncOrdersForShop`, connectors, fulfillment, FE.
- Config thresholds qua `config()` (vd `config/integrations.php` block `order_refresh`: `stuck_hours=2`, `max_age_days=30`, `batch=200`, `sleep_ms=300`). Không dùng `env()` ngoài config.

## Kiểm thử
- Feature: query chọn ĐÚNG đơn treo (channel + pre-shipment + has_issue/no-label-retry-exhausted + last_synced cũ + trong hạn tuổi), loại manual/tracking_stopped/terminal/đơn mới-sync.
- `upsertWithStatus(force:true)`: áp status kể cả khi stale-guard thường sẽ skip (đơn có `source_updated_at` ≥ dto) — dùng connector fake `fetchOrderDetail`. Đồng thời khẳng định `force=false` (mặc định) GIỮ NGUYÊN skip cũ (không regress).
- Vẫn tôn trọng `tracking_stopped` (force vẫn skip) + sticky-forward (không tụt processing→unpaid).
- Clear has_issue: clear khi đã tiến lên + issue loại tem/tracking; KHÔNG clear `SKU chưa ghép`.
- Idempotent: chạy 2 lần = cùng kết quả; không gọi `fetchOrderDetail` cho đơn đã ngoài phạm vi.
- Manual: job bỏ qua (không gọi fetchOrderDetail).

## YAGNI / KHÔNG làm
- Không quét mọi đơn chưa giao (chỉ đơn treo).
- Không re-trigger lấy tem (BackfillChannelLabels 15' đã lo); refresh chỉ sửa STATUS + clear issue lỗi thời.
- Không thêm UI/nút (chạy nền tự động). Không migration (chỉ đọc + upsert cột sẵn có).
- Không đổi stale-guard của sync thường.

## Tệp dự kiến
| Loại | Tệp |
|---|---|
| Mới | `app/app/Modules/Channels/Jobs/RefreshStuckOrders.php` |
| Mới (tùy) | command `orders:refresh-stuck` (Console) để chạy tay/test |
| Sửa | `app/routes/console.php` (1 entry mỗi 2h) |
| Sửa | `app/app/Modules/Orders/Services/OrderUpsertService.php` (param `force` default false) |
| Sửa (tùy) | `app/config/integrations.php` (block `order_refresh` thresholds) |
| Test | `tests/Feature/Channels/RefreshStuckOrdersTest.php` + `tests/.../OrderUpsertForceTest.php` |
