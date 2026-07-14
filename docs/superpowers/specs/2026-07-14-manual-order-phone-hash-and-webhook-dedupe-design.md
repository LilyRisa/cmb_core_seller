# SĐT hash tra trùng đơn thủ công + Webhook dedupe atomic — Design

> Bám theo `docs/technical-audit-2026-07-14.md` mục #1 (tra SĐT O(N)) + #2 (webhook dedupe race, thiếu unique
> constraint thật). Cả 2 claim đã verify chéo với code thật (`Order.buyer_phone` cast `encrypted`; migration
> `webhook_events` dedup index hiện chỉ là `index()` thường, không `unique()`) — đúng như audit mô tả.
> Ngày: 2026-07-14 · Trạng thái: approved. Làm trên `main`.

## 1. SĐT hash — tra trùng đơn thủ công nhanh

**Vấn đề:** `OrderLookupService::recentManualByPhone()` load TOÀN BỘ đơn `source=manual` của tenant rồi giải mã +
so khớp SĐT trong PHP (`buyer_phone` cast `encrypted` ⇒ DB không query trực tiếp được).

**Đã có sẵn hạ tầng, không cần thiết kế mới:** `CustomerPhoneNormalizer::hash()`/`normalizeAndHash()`
(`app/app/Modules/Customers/Support/CustomerPhoneNormalizer.php:62-73`) — SHA-256 phẳng của SĐT đã chuẩn hoá,
đúng convention đã dùng cho `customers.phone_hash` (`char(64)`, xem migration
`2026_05_13_100001_create_customers_table.php:19`). Tái dùng y hệt cho `orders`, không phát minh HMAC/salt mới.

**Cột mới trên `orders` (nullable `char(64)`, additive — không đổi cột cũ):**
- `buyer_phone_hash` — hash của `buyer_phone` đã chuẩn hoá.
- `recipient_phone_hash` — hash của `shipping_address['phone']` đã chuẩn hoá (JSON, không index trực tiếp được).

Index: `(tenant_id, source, buyer_phone_hash)` + `(tenant_id, source, recipient_phone_hash)`.

**Ghi hash tại write-time** (`ManualOrderService::create()` dòng ~94-97, `update()` dòng ~231-254) — bất cứ chỗ nào
set `buyer_phone`/`shipping_address` thì set kèm 2 cột hash bằng `CustomerPhoneNormalizer::normalizeAndHash()`.

**Query đổi thành:**
```php
Order::where('tenant_id', $tenantId)->where('source', 'manual')->whereNull('deleted_at')
    ->where(fn ($q) => $q->where('buyer_phone_hash', $hash)->orWhere('recipient_phone_hash', $hash))
    ->orderByDesc('created_at')->orderByDesc('id')->limit($limit)->get();
```
O(log n) qua index thay vì load hết + filter PHP.

**Backfill:** đơn thủ công cũ (tạo trước khi cột hash tồn tại) có hash NULL ⇒ tạm thời KHÔNG match được cho tới
khi chạy backfill. Migration chỉ thêm cột (không backfill trong `up()` — bảng có thể lớn, backfill trong migration
chặn deploy). Thêm artisan command `orders:backfill-phone-hash` chạy tay sau migrate (đúng pattern
`visualsearch:reindex` đã có), idempotent + chunk theo id.

## 2. Webhook dedupe — unique constraint thật + insert atomic

**Vấn đề (nghiêm trọng hơn audit mô tả):** migration `2026_05_23_000001_add_webhook_events_dedup_index.php` chỉ
tạo `index()` — KHÔNG có ràng buộc DB nào. `WebhookIngestService::ingest()` chỉ `exists()` rồi `create()` ở tầng
app ⇒ race window thật giữa 2 webhook trùng đến cùng lúc. Index cũng thiếu `order_raw_status` so với khoá dedupe
thực tế dùng trong query (`WebhookIngestService.php:44-57`).

**Rủi ro production cần xử lý cẩn thận:** bảng `webhook_events` hiện KHÔNG có ràng buộc unique, nên rất có thể đã
tồn tại row trùng thật (do đúng race này, hoặc do retry ngoài ý muốn trước đây). Thêm `unique()` thẳng vào 1
migration sẽ FAIL nếu dữ liệu hiện tại đã có trùng. Vì vậy chia làm 2 giai đoạn migrate (2 lần deploy), không gộp:

**Giai đoạn 1 (migration + code, an toàn tuyệt đối — chỉ additive):**
- Thêm cột `dedupe_status_key` (string, nullable) trên `webhook_events`.
- `WebhookIngestService::ingest()` ghi `dedupe_status_key = $event->orderRawStatus ?? ''` mỗi lần tạo row mới
  (không dùng generated column của DB — tránh vênh cú pháp SQLite dev/test vs Postgres prod).
- Giữ nguyên nhánh `exists()` fast-path hiện tại (đỡ tốn exception ở trường hợp thường), nhưng bọc
  `WebhookEvent::create()` trong try/catch cho lỗi vi phạm unique (chưa có constraint ở giai đoạn 1 nên catch này
  chưa kích hoạt — chuẩn bị sẵn cho giai đoạn 2).

**Chạy tay sau giai đoạn 1:** artisan command `webhooks:backfill-dedupe-key` —
1. Backfill `dedupe_status_key = order_raw_status ?? ''` cho row cũ (chunk theo id).
2. Tìm nhóm trùng theo `(provider, event_type, external_id, external_shop_id, dedupe_status_key)`, XOÁ các row
   trùng chỉ giữ lại `id` nhỏ nhất mỗi nhóm (log số lượng đã xoá để audit).
Idempotent — chạy lại không đổi gì nếu đã sạch.

**Giai đoạn 2 (migration riêng, deploy SAU khi đã chạy command backfill ở trên và xác nhận sạch):**
- Migration tự kiểm tra KHÔNG còn row trùng trước khi thêm `unique()` — còn trùng thì ném lỗi rõ ràng, dừng
  migrate (không âm thầm thêm được constraint nửa vời), báo lại chạy `webhooks:backfill-dedupe-key` trước.
- Thêm `unique(['provider','event_type','external_id','external_shop_id','dedupe_status_key'])`.
- `WebhookIngestService`: catch `QueryException` khi `create()` vi phạm unique (race hiếm giữa 2 webhook đến
  cùng lúc) ⇒ coi như duplicate, trả `200 duplicate` giống nhánh `exists()` hiện tại — KHÔNG để lộ 500.

Đây là đánh đổi duy nhất so với thiết kế ban đầu trình bày với user: **2 lần deploy** (giai đoạn 1 → chạy backfill
command xác nhận sạch → giai đoạn 2) thay vì 1 migration duy nhất, để không risk fail migrate trên dữ liệu
production đã có trùng.

## 3. Testing

- Unit: `CustomerPhoneNormalizer::normalizeAndHash` đã có test sẵn — không cần thêm.
- Feature: `ManualOrderService::create/update` set đúng 2 cột hash theo buyer_phone/shipping_address.phone.
- Feature: `OrderLookupService::recentManualByPhone` trả đúng kết quả qua query hash (buyer lẫn recipient khớp),
  không trả đơn nguồn khác `manual`, respect `$limit`.
- Feature: artisan command `orders:backfill-phone-hash` — backfill đúng, idempotent (chạy 2 lần không đổi).
- Feature: `WebhookIngestService` giai đoạn 1 — ghi đúng `dedupe_status_key`; giai đoạn 2 — unique constraint thật
  chặn insert trùng, service bắt được lỗi và trả `200 duplicate` thay vì 500; migration giai đoạn 2 tự chặn nếu
  chạy khi dữ liệu còn trùng (test bằng cách seed trùng thủ công trước rồi assert migration ném exception).
- Feature: artisan command `webhooks:backfill-dedupe-key` — backfill key + xoá đúng row trùng (giữ id nhỏ nhất),
  idempotent.

## 4. Giới hạn / phụ thuộc ngoài code

- Webhook dedupe cần **2 lần deploy tuần tự** (giai đoạn 1 → chạy `webhooks:backfill-dedupe-key` xác nhận log
  "0 duplicate rows removed" hoặc chấp nhận số liệu xoá → giai đoạn 2), không migrate gộp 1 lần trên production.
- SĐT hash cần chạy `orders:backfill-phone-hash` sau giai đoạn migrate đầu (1 lần deploy, không cần 2 giai đoạn
  vì cột hash chỉ ADD, không có unique constraint nào phụ thuộc dữ liệu sạch).
- Đơn thủ công tạo TRƯỚC khi chạy backfill sẽ không được cảnh báo trùng SĐT cho tới khi backfill chạy xong —
  chấp nhận được vì đây là tính năng cảnh báo (warning), không phải chặn cứng.
