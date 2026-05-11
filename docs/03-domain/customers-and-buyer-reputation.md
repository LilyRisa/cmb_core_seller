# Sổ khách hàng & cờ rủi ro

**Status:** Draft (sẽ Stable khi spec [0002](../specs/0002-customer-registry-and-buyer-reputation.md) Implemented ở Phase 2) · **Cập nhật:** 2026-05-12

> Tài liệu này định nghĩa **luật nghiệp vụ** của module Customers (cross-order matching theo SĐT, reputation, ẩn danh hoá). Hành vi chi tiết (API/UI/data) ở SPEC-0002. Mọi mâu thuẫn giữa code và file này ⇒ sửa code hoặc cập nhật file này (qua spec mới); **không** để code "âm thầm trôi" khác tài liệu.

## 1. Mục đích & vị trí

`Customers` là module nội bộ trong modular monolith, **đứng giữa `Tenancy` (nền) và `Orders` (data feed chính)**. Vai trò:

- Định danh "người mua" trong phạm vi 1 tenant — khoá duy nhất là **SĐT chuẩn hoá** (`phone_hash`).
- Tổng hợp lifetime stats từ `orders` (chiều đọc — phát sinh khi `OrderUpserted` fire).
- Cho phép seller ghi note/tag/block cho từng khách.
- Tính **reputation score** (heuristic minh bạch) — gợi ý cho NV; không tự động hành động.
- Khi sàn yêu cầu xoá dữ liệu / shop disconnect → **ẩn danh hoá** hồ sơ (giữ phone_hash + stats; xoá phone/tên/email/địa chỉ).

**Cấm:** chia sẻ blacklist chéo tenant; gọi API marketing tới khách; chấm điểm ML; lưu phone plaintext bên ngoài cột `customers.phone` (encrypted).

## 2. Phone normalization (canonical phone)

Chuẩn hoá SĐT là **nguồn sự thật duy nhất** để khớp khách. Một lỗi normalize = 2 khách trùng (gây spam merge). Thuật toán phải deterministic và có test bảng vector.

### 2.1 Thuật toán

```
input: $raw (string|null)

1. if $raw === null || trim($raw) === '' → return null
2. $hasMask = preg_match('/[*xX]/', $raw)          // mask từ sàn ("****21")
3. if $hasMask → return null                       // không khớp được
4. $sign = str_starts_with(trim($raw), '+') ? '+' : ''
5. $digits = preg_replace('/\D/', '', $raw)
6. if strlen($digits) < 8 || strlen($digits) > 15 → return null  // ngoài khoảng E.164
7. // Chuẩn hoá VN sang dạng 0xxx:
   if strlen($digits) === 11 && str_starts_with($digits, '84')
       → $digits = '0' . substr($digits, 2)         // "84987654321" → "0987654321"
   if strlen($digits) === 10 && $digits[0] === '0'
       → keep $digits                                // "0987654321" — canonical VN
   else if $sign === '+'
       → $digits = '+' . $digits                    // canonical quốc tế khác VN
   else
       → return null                                // không xác định được zone
8. // Validate final:
   if !preg_match('/^0\d{9}$|^\+\d{8,15}$/', $digits) → return null
9. return $digits
```

### 2.2 Bảng vector test (cố định — đừng đổi nếu không thêm version normalizer)

| Input | Output | Ghi chú |
|---|---|---|
| `"0987654321"` | `"0987654321"` | canonical VN |
| `"0987 654 321"` | `"0987654321"` | strip space |
| `"(0987) 654-321"` | `"0987654321"` | strip ngoặc/dash |
| `"+84 987 654 321"` | `"0987654321"` | VN quốc tế → nội địa |
| `"+84987654321"` | `"0987654321"` | |
| `"84987654321"` | `"0987654321"` | thiếu `+` nhưng prefix 84 + 11 chữ số |
| `"098-7654-321"` | `"0987654321"` | dash format |
| `"(+84) ****21"` | `null` | mask |
| `"09xx xxx 321"` | `null` | mask `x` |
| `""`, `null`, `"   "` | `null` | rỗng |
| `"abc"` | `null` | không có chữ số |
| `"123"` | `null` | quá ngắn |
| `"+1 415 555 0123"` | `"+14155550123"` | quốc tế khác VN — giữ canonical E.164 |
| `"4155550123"` | `null` | không có `+`, không phải 0xxx VN, không phải 84xxx |

> **Lý do quy ước `0xxx` thay vì `+84xxx`** cho VN: 99% nhập liệu / payload sàn VN dùng `0xxx`, hash sẽ có cùng giá trị bất kể seller gõ dạng nào, và `0xxx` dễ đọc khi debug.

### 2.3 Phone hash

```
phone_hash = bin2hex(hash('sha256', $normalized))   // 64 ký tự hex
```

- **Deterministic** ⇒ index được.
- **Không reverse được** ⇒ nếu DB dump leak, attacker phải brute-force 10 chữ số (10^10 = 10 tỉ — vẫn khả thi nhưng đắt; per-tenant salt là cải tiến Phase 7+ nếu cần).
- **Per-tenant unique:** `customers(tenant_id, phone_hash)` ⇒ không cross-tenant match.

## 3. Mô hình hồ sơ khách

Xem `02-data-model/overview.md` → section **Customers** (cấu trúc cột). Tóm tắt logic:

- **`customers`** ≈ "ai" — định danh + lifetime stats + reputation.
- **`customer_notes`** ≈ "đã xảy ra gì với khách này" — append-only audit-trail. Hỗn hợp `manual` (NV gõ) + `auto.*` (hệ thống thêm khi vượt ngưỡng) + `system.*` (vd `system.merge`).
- **`orders.customer_id`** ≈ "đơn này là của khách nào" — nullable (đơn có SĐT bị mask = không khớp).

**Nguyên tắc data flow:** `orders` là source of truth của doanh thu/trạng thái. `customers.lifetime_stats` luôn **recompute từ `orders`**, không cộng dồn delta — tránh sai khi event lặp/trễ.

## 4. Pipeline khớp đơn → khách

```
OrderUpserted(order) ──[afterCommit]──▶ LinkOrderToCustomer job (queue: customers)
                                            │
                                            │ ShouldBeUnique per (tenant_id, customer_id_or_phone_hash)
                                            ▼
   1. phone = normalize(order.shipping_address.phone)
      └ null ⇒ return (orders.customer_id giữ nguyên — null hoặc đã được set ở event trước)
   2. hash = sha256(phone)
   3. transaction:
        SELECT customer WHERE tenant_id=? AND phone_hash=? FOR UPDATE
        nếu null:
          INSERT customer { tenant_id, phone_hash, phone(encrypted), name=order.buyer_name,
                            first_seen_at=order.placed_at, last_seen_at=order.placed_at,
                            lifetime_stats=zero, reputation_score=100, addresses_meta=[order.shipping_address] }
        nếu có:
          UPDATE customer SET
            last_seen_at = greatest(last_seen_at, order.placed_at),
            name = coalesce(name, order.buyer_name),
            addresses_meta = merge_distinct_last_5(addresses_meta, order.shipping_address)
        UPDATE orders SET customer_id = customer.id WHERE id=order.id AND customer_id IS NULL
        recompute_lifetime_stats(customer)            // SELECT từ orders, ghi đè vào customer.lifetime_stats
        recompute_reputation(customer)                // §5
        maybe_add_auto_note(customer)                 // §6
        fire CustomerLinked(customer, order, created)
        nếu reputation_label đổi: fire CustomerReputationChanged(customer, from, to, fromScore, toScore)
   4. commit
```

**Idempotency:** chạy job 2 lần với cùng order = cùng kết quả (recompute đọc thẳng `orders`, auto-note dedupe theo `dedupe_key`).

**Edge — merge_distinct_last_5:** so sánh address dưới dạng tuple `(line1, city, district, ward, post_code)` đã chuẩn hoá lowercase + strip space; chỉ giữ 5 tuple distinct gần nhất theo `last_seen` của tuple đó.

## 5. Reputation score (heuristic v1)

Công thức trong `app/Modules/Customers/Support/ReputationCalculator.php`, hệ số đọc từ `config/customers.php`:

```php
return [
    'reputation' => [
        'base' => 100,
        'penalty_cancelled' => 15,
        'penalty_delivery_failed' => 10,
        'penalty_returned' => 8,
        'bonus_completed' => 2,
        'bonus_completed_cap' => 30,
        'thresholds' => [
            'risk' => 40,   // < 40 ⇒ "Rủi ro cao"
            'watch' => 80,  // < 80 ⇒ "Cần kiểm tra"; >= 80 ⇒ "OK"
        ],
        'vip_tag' => [
            'min_completed' => 10,
            'max_cancellation_rate' => 0.05,
        ],
    ],
];
```

Pseudo:
```php
$score = max(0, min(100,
    $cfg['base']
    - $stats['orders_cancelled']        * $cfg['penalty_cancelled']
    - $stats['orders_delivery_failed']  * $cfg['penalty_delivery_failed']
    - $stats['orders_returned']         * $cfg['penalty_returned']
    + min($cfg['bonus_completed_cap'], $stats['orders_completed'] * $cfg['bonus_completed'])
));

$label = $customer->is_blocked    ? 'blocked'
       : ($score < $cfg['thresholds']['risk'])  ? 'risk'
       : ($score < $cfg['thresholds']['watch']) ? 'watch'
                                                : 'ok';
```

**Giải thích được:** UI tooltip của `<ReputationBadge>` hiển thị break-down:
> "58 điểm = 100 − 2 huỷ × 15 + 4 hoàn thành × 2 = 58"

— seller nhìn vào hiểu ngay vì sao khách bị đánh dấu watch, không "đen hộp".

**VIP tag** (auto, không thay label): nếu `orders_completed >= 10` AND `cancellation_rate <= 5%` ⇒ thêm `'vip'` vào `tags`. Mất điều kiện ⇒ gỡ tag.

## 6. Auto-notes (cờ tự động)

Mỗi lần `recompute_reputation` xong, kiểm tra ngưỡng & thêm note nếu bucket vượt ngưỡng và chưa có note cùng `dedupe_key`:

| Bucket | `kind` | `severity` | `dedupe_key` | Note text |
|---|---|---|---|---|
| `orders_cancelled` ∈ [2,4] | `auto.cancel_streak` | `warning` | `"cancel_streak_2"` | "Đã có {n} đơn huỷ — kiểm tra kỹ đơn mới" |
| `orders_cancelled` >= 5 | `auto.cancel_streak` | `danger` | `"cancel_streak_5"` | "Đã có {n} đơn huỷ — cân nhắc chặn khách" |
| `orders_delivery_failed` >= 2 | `auto.delivery_failed` | `warning` | `"delivery_failed_2"` | "{n} lần giao thất bại — gọi xác nhận trước khi ship" |
| `orders_returned` >= 3 | `auto.return_streak` | `warning` | `"return_streak_3"` | "{n} lần trả hàng — kiểm tra sản phẩm/đóng gói" |
| `orders_completed` >= 10 | `auto.vip` | `info` | `"vip_10"` | "Khách VIP — đã đặt {n} đơn thành công" |

`dedupe_key` unique theo `(customer_id, dedupe_key)` ⇒ note chỉ thêm 1 lần khi vừa qua ngưỡng; lần sau không lặp. NV vẫn thấy note vĩnh viễn trong history.

## 7. Ẩn danh hoá (anonymize) — luật chi tiết

Bối cảnh: sàn yêu cầu, hoặc seller disconnect shop. Mục tiêu: xoá định danh, **giữ thống kê tổng** để seller vẫn xem được "tháng N có X đơn, Y doanh thu" mà không thể trace ngược ra người mua.

### 7.1 `data_deletion` từ sàn (cho 1 đơn / 1 shop / 1 buyer cụ thể)

Tham chiếu: SPEC-0001 §8 đã định nghĩa job ẩn danh hoá `orders` (phase 7 implement). Spec 0002 mở rộng:

Khi xử lý `data_deletion` cho `(shop, buyer)`:
1. Tìm tất cả `orders` thuộc shop đó của customer (khớp qua `orders.customer_id` ↔ `customer.phone_hash`).
2. Anonymize các `orders`: `buyer_phone = NULL`, `shipping_address.phone = '[ANONYMIZED]'`, `buyer_name = '[ANONYMIZED]'`, `shipping_address.line1/.../recipient = '[ANONYMIZED]'`. `raw_payload` cũng xoá các trường PII.
3. Với customer đó:
   - Nếu **vẫn còn đơn ở shop khác** trong cùng tenant ⇒ giữ hồ sơ; chỉ xoá `addresses_meta[]` các tuple chỉ xuất hiện ở shop bị data_deletion (so qua `orders.shipping_address` của shop khác); recompute `lifetime_stats` (sẽ giảm vì các đơn bị anonymize cũng giảm `revenue_completed` nếu trạng thái lưu lại — nhưng status thì KHÔNG anonymize; số tiền cũng KHÔNG anonymize; chỉ PII của buyer bị xoá ⇒ stats KHÔNG đổi).
   - Nếu **chỉ có đơn ở shop bị data_deletion** ⇒ `customer.phone = NULL`, `customer.name = NULL`, `customer.email = NULL`, `customer.addresses_meta = '[]'`, `customer.manual_note = NULL` (tuỳ chính sách — note seller gõ có thể chứa PII; an toàn nhất là xoá), `pii_anonymized_at = now()`. Giữ `phone_hash`/`lifetime_stats`/`reputation_score`/`is_blocked` để seller vẫn nhìn được tổng.
4. `customer_notes` thuộc về customer này: với note `manual` mà `note` chứa SĐT/tên (heuristic regex) ⇒ thay text bằng `"[REDACTED]"`; auto-note giữ nguyên (không chứa PII).

### 7.2 Disconnect shop (chủ động)

Khi seller `DELETE /channel-accounts/{id}` ⇒ `channel_account.status='revoked'` (đã có). Sau **90 ngày** (cấu hình `customers.anonymize_after_days`) ⇒ scheduled job `AnonymizeCustomersForShop(shopId)` chạy:
- Lặp customers có đơn thuộc shop đó.
- Áp luật giống §7.1 cho từng customer.

90 ngày = buffer cho khiếu nại/đối soát. Trước khi tới hạn, seller có thể "Khôi phục kết nối" — nếu shop reconnect cùng `external_shop_id` thì job huỷ.

### 7.3 PII detection trong `manual_note` / `customer_notes.note`

Heuristic regex (dùng cho redact §7.1 bước 4 — không phải để chặn input):
- SĐT VN: `/0\d{9}|(\+|0{0,2})84\d{8,9}/`
- Email: regex chuẩn RFC 5322 đơn giản hoá.
- Tên thì khó detect; chấp nhận leak nhỏ (seller chịu trách nhiệm).

## 8. Tương tác với module khác

| Module | Cách giao tiếp với Customers |
|---|---|
| **Orders** | Phát `OrderUpserted`/`OrderDeleted` → Customers listen. Đọc qua `CustomerProfileContract` để hiển thị card "Khách hàng" trong `OrderDetailResource`. **Không** import model `Customer` trực tiếp. |
| **Channels** | Phát `ChannelAccountRevoked`/`ChannelAccountDataDeletion` → Customers listen (job anonymize). Không import nhau. |
| **Settings (Phase 6 rules engine)** | Đọc `CustomerProfileContract::isBlocked()` + `reputation_score` trong rule evaluator để quyết định auto-confirm/auto-cancel. **Customers không phụ thuộc Settings.** |
| **Notifications (Phase 6)** | Listen `CustomerReputationChanged`/`CustomerBlocked` để gửi thông báo cho seller. |
| **Reports** | Read-only — xem aggregate "khách quay lại %", "top khách theo revenue" — qua interface đọc hoặc view DB. |
| **Inventory / Products / Fulfillment / Procurement / Finance / Billing** | **Không** đụng Customers. |

Sơ đồ phụ thuộc (chính):
```
Orders ──fires──▶ OrderUpserted  ──▶  Customers (LinkOrderToCustomer)
Channels ──fires──▶ ShopDeauthorized / DataDeletionRequest  ──▶  Customers (AnonymizeCustomers*)
Orders ──reads──▶ CustomerProfileContract (impl trong Customers)
Settings (Phase 6) ──reads──▶ CustomerProfileContract
```

## 9. Performance & vận hành

- **Volume estimation:** 1 tenant trung bình ~100 đơn/ngày × 365 ngày ≈ 36k đơn/năm. Khách quay lại ~30% ⇒ ~25k customers/năm. Một bảng thường (không partition) là quá đủ tới ~1M dòng. Khi 1 tenant vượt 1M customers → spec riêng để partition theo `(tenant_id, phone_hash >> N)` hoặc tách tenant.
- **Recompute cost:** mỗi `LinkOrderToCustomer` đọc tất cả đơn của customer đó. Khách có 50 đơn ⇒ 50 row scan với index `(tenant_id, customer_id)` — vẫn ms. Không bottleneck.
- **`customers:recompute-stale` hourly sweep:** đề phòng listener miss event (vd queue chết giữa chừng). Quét `orders` updated trong giờ qua, dispatch `LinkOrderToCustomer` cho mỗi distinct `customer_id`.
- **Backfill khi deploy lần đầu:** `customers:backfill` đi qua `orders` chia batch 1000 đơn, dispatch job; hiển thị progress bar. Khoảng ~10' cho 100k đơn (đủ với hầu hết tenant).
- **Search bằng SĐT (UI search box):** SPA gửi raw → backend normalize + hash + `WHERE phone_hash=?` ⇒ O(log n) qua index. Không LIKE plaintext.

## 10. Câu hỏi mở (mirror SPEC-0002 §11)

Tham chiếu spec để cập nhật quyết định khi reviewed.
