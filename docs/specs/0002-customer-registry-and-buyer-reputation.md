# SPEC 0002: Sổ khách hàng & cờ rủi ro (cross-order matching theo SĐT)

- **Trạng thái:** Implemented (2026-05-13 — backend + API + FE; còn lại: FE Vitest, "Gán vào khách hàng" thủ công, export CSV — xem §10/§11)
- **Phase:** 2 *(đi cùng module Inventory/SKU — cùng pha "đơn thủ công + sổ khách hàng + tồn lõi")*
- **Module backend liên quan:** **Customers** (mới), Orders, Channels, Tenancy (RBAC), Settings (rules engine — Phase 6)
- **Tác giả / Ngày:** Team · 2026-05-12
- **Liên quan:** SPEC-0001 (TikTok order sync), ADR-0003 (modular monolith), ADR-0010 (RBAC), docs `03-domain/customers-and-buyer-reputation.md`, `03-domain/order-sync-pipeline.md`, `08-security-and-privacy.md` §6.

## 1. Vấn đề & mục tiêu

Sau khi Phase 1 đi vào vận hành, các đơn TikTok về có **SĐT người mua đầy đủ** (TikTok cung cấp `recipient_address.phone_number` cho đơn chưa giao — phục vụ COD); SĐT này hiện đã được hiển thị ở `OrderDetailPage` (xem `Order.shipping_address.phone`). Tuy nhiên hệ thống đang nhìn **mỗi đơn như một dữ kiện rời** — không biết người mua hôm nay đã từng đặt 7 đơn, bom 3 đơn, hoàn 1 đơn ở cả TikTok lẫn (sau này) Shopee/Lazada.

Hậu quả thực tế của VN e-commerce: tỉ lệ huỷ COD cao, người mua "boom hàng" lặp lại bằng nhiều đơn nhỏ, tạo chi phí ship/đóng gói cho seller. Một số khách "VIP" cần lưu ý vận chuyển riêng. Khi NV cấp `staff_order` ngồi xác nhận đơn — họ **cần thấy lịch sử người mua TRƯỚC khi bấm "Xác nhận"**.

**Mục tiêu Phase 2:**
- Nhận diện cùng một người mua trên nhiều đơn (cùng tenant, mọi sàn + đơn manual) bằng **SĐT chuẩn hoá** → lưu hồ sơ khách (sổ khách hàng).
- Tự tổng hợp **lifetime stats** (đã đặt / hoàn thành / huỷ / hoàn hàng / giao thất bại / doanh thu) khi `OrderUpserted`.
- Cho NV **ghi note tay** ("đã bom 2 đơn — gọi xác nhận kỹ", "khách VIP — ship riêng") gắn vào khách; note hiển thị ở mọi đơn của khách đó.
- Tính **reputation badge** (OK / Cần kiểm tra / Rủi ro cao) hiển thị ở list đơn + detail đơn.
- Cho phép **block** một khách (rules engine Phase 6 sẽ dùng để chặn auto-confirm).
- Khi sàn yêu cầu xoá dữ liệu (`data_deletion` webhook) hoặc shop bị disconnect ⇒ **ẩn danh hoá** hồ sơ khách (giữ phone_hash để vẫn join thống kê được, xoá phone/tên/email gốc).

**Không phải mục tiêu (xem §2):** marketing/campaign, chấm điểm AI/ML, kết nối CRM ngoài, gửi tin nhắn cho khách. Đây là **CRM nội bộ cho vận hành đơn**, không phải CRM marketing đầy đủ.

## 2. Trong / ngoài phạm vi của spec này

**Trong:**
- Module `Customers` mới (`app/Modules/Customers/`) sở hữu bảng `customers` + `customer_notes`.
- Phone normalization (chuẩn hoá VN: `+84xxx` → `0xxx`, strip non-digit) + `phone_hash = SHA-256(normalized)` làm khoá khớp.
- Listener `LinkOrderToCustomer` (listen `OrderUpserted`, `afterCommit`) tạo/khớp `customers`, set `orders.customer_id`, recompute stats.
- Reputation heuristic v1 (rule-based, không ML): trừ điểm theo cancel/return/delivery_failed, cộng theo completed, label thành 3 mức.
- API `GET /customers`, `GET /customers/{id}`, `GET /customers/{id}/orders`, `POST /customers/{id}/notes`, `POST /customers/{id}/block`, `POST /customers/{id}/unblock`, `POST /customers/merge` (gộp khách trùng — manual).
- FE: card "Khách hàng" ở `OrderDetailPage`, badge ở `OrdersPage`, trang `/customers` (list + filter + detail).
- Permissions mới: `customers.view`, `customers.note`, `customers.block`, `customers.merge`.
- Ẩn danh hoá hồ sơ khách khi `data_deletion` / disconnect shop.

**Ngoài (Phase sau / spec khác):**
- Tự động chặn đơn dựa vào reputation (rules engine — Phase 6, spec riêng).
- Gửi tin nhắn / email cho khách (CRM marketing — Phase 7+, backlog).
- Tích hợp Zalo OA / SMS gateway cho gọi xác nhận đơn (Phase 7+).
- Khớp khách qua email, địa chỉ, tên (Phase này **chỉ khớp theo SĐT** — nguồn ổn định và unique nhất; email/địa chỉ chỉ lưu kèm hiển thị).
- Tính reputation bằng ML / điểm tín dụng người mua chéo tenant (cấm — privacy + multi-tenant isolation).
- Chia sẻ blacklist giữa các tenant (cấm — privacy, mỗi tenant tự nhìn lịch sử của mình).
- Khôi phục đơn cũ trước khi spec này deploy: job backfill `BackfillCustomersFromOrders` (chạy một lần, đặt trong §10 "Việc cụ thể").

## 3. Câu chuyện người dùng / luồng chính

### 3.1 NV xác nhận đơn TikTok mới (luồng chính)

1. NV mở `/orders?status=awaiting_confirmation` → click 1 đơn → mở `OrderDetailPage`.
2. Bên cạnh box "Người nhận" (đã có), hiển thị box **"Khách hàng này"**:
   ```
   ┌─ Khách hàng ──────────────────────────────────┐
   │ Nguyễn Văn A  ·  09xx xxx 123                 │
   │ 🟡 Cần kiểm tra (reputation 58)               │
   │                                                │
   │ ⚠️  Đã có 2 lần huỷ COD (xem chi tiết)        │
   │                                                │
   │ Tổng: 7 đơn · 4 hoàn thành · 2 huỷ · 1 hoàn   │
   │ Lần đầu: 12/02/2026  ·  Gần nhất: 8/05/2026   │
   │                                                │
   │ 📝 "Khách bom đơn 2 lần — gọi xác nhận"       │
   │    — Nam · 3 ngày trước                       │
   │                                                │
   │ [Xem hồ sơ]  [+ Thêm ghi chú]  [Chặn khách]   │
   └────────────────────────────────────────────────┘
   ```
3. NV thấy cảnh báo → bấm "Xác nhận đơn" cẩn thận hơn (gọi điện trước), hoặc bấm "Chặn khách" + "Huỷ đơn".
4. Sau xử lý → có thể "Thêm ghi chú" mới gắn với đơn này: `"Đã gọi — khách xác nhận lại. OK."` — note xuất hiện ở mọi đơn sau của khách.

### 3.2 Tổng quan khách hàng

`/customers` → list có filter:
- Search: tên / SĐT (sẽ hash → lookup phone_hash).
- Filter: reputation (OK / Cần kiểm tra / Rủi ro cao / Đã chặn), số đơn (>=N), có note, tag.
- Sort: `last_seen_at desc` (mặc định), `lifetime_revenue desc`, `cancellation_rate desc`.

Click 1 dòng → `/customers/{id}` — profile + tab "Lịch sử đơn" + tab "Ghi chú" (kèm note tự động hệ thống thêm).

### 3.3 Đơn không khớp được khách (SĐT bị mask)

- Khi TikTok mask SĐT (kịch bản `(+84) ****21` cho đơn đã giao xong / data_deletion), `phone_normalized` không chuẩn hoá được → `orders.customer_id = NULL` + `orders.has_issue=false` (không phải lỗi, chỉ là missing).
- UI: card "Khách hàng" hiển thị `"Không xác định được — SĐT đã ẩn"`.
- Khi sau này SĐT đầy đủ về (vd đơn mới của cùng khách, hoặc seller cập nhật tay) → có thể gắn thủ công qua nút "Gán vào khách hàng" (Phase 2 cuối, optional — backlog spec này).

### 3.4 Gộp khách trùng (manual merge)

- NV phát hiện 2 hồ sơ thực ra cùng người (khách đổi SĐT giữa chừng) → `/customers/{id}` → "Gộp với khách khác" → chọn → confirm.
- Backend: chuyển toàn bộ `orders.customer_id` từ B sang A, gộp `customer_notes`, recompute stats của A, **soft-delete** B (`deleted_at`, `merged_into_customer_id=A.id`).
- Permission: `customers.merge` (chỉ `owner`/`admin`).

## 4. Hành vi & quy tắc nghiệp vụ

### 4.1 Phone normalization

Hàm `CustomerPhoneNormalizer::normalize(?string $raw): ?string` — chi tiết thuật toán + bảng case ở `03-domain/customers-and-buyer-reputation.md` §2. Tóm tắt:

1. Trả `null` nếu `$raw` rỗng / chỉ chứa ký tự mask (`*`).
2. Strip mọi ký tự không phải chữ số, giữ lại dấu `+` đầu nếu có.
3. Nếu có chứa chuỗi `*`/`x`/`X` (mask) ⇒ trả `null` (không khớp được).
4. Nếu `0` đầu (`0xxxxxxxxx`, 10 chữ số) ⇒ giữ nguyên (dạng canonical VN).
5. Nếu `+84xxx` hoặc `84xxx` (11 chữ số bắt đầu `84`) ⇒ thay `84` thành `0`.
6. Số khác (vd quốc tế ngoài VN) ⇒ giữ nguyên `+` + chữ số (canonical quốc tế).
7. Validate cuối: phải là (`0`+9 chữ số = 10 ký tự) HOẶC (`+`+8…15 chữ số). Không khớp ⇒ trả `null`.

`phone_hash = bin2hex(hash('sha256', normalized))` (hex 64 ký tự). Hash deterministic, không reverse → index được an toàn. Bản gốc lưu cột `phone` (`encrypted` cast).

### 4.2 Khớp & cập nhật khách (listener `LinkOrderToCustomer`)

Lắng `OrderUpserted` event, dispatch queue `customers` (queue mới — xem `07-infra/queues-and-scheduler.md`), chạy `afterCommit`:

```
1. $phone = normalize($order->shipping_address['phone'] ?? null)
2. nếu $phone === null:
     - không tạo customer; nếu $order->customer_id != null (vd lần trước có phone nhưng giờ event lại đến với phone mask) ⇒ giữ nguyên.
     - return.
3. $hash = sha256($phone)
4. trong transaction:
   a. SELECT customer WHERE tenant_id=? AND phone_hash=? FOR UPDATE (theo (tenant_id, phone_hash) unique).
   b. nếu null ⇒ INSERT customer { tenant_id, phone_hash, phone (encrypted), name=$order->buyer_name, first_seen_at=$order->placed_at, last_seen_at=$order->placed_at, lifetime_stats=zero_stats(), reputation_score=100, addresses_meta=[$order->shipping_address] }
   c. ngược lại:
      - cập nhật: last_seen_at = max(last_seen_at, $order->placed_at), name = COALESCE(name, $order->buyer_name), addresses_meta = merge_distinct(addresses_meta, $order->shipping_address).
   d. UPDATE orders SET customer_id=$customer->id WHERE id=$order->id AND customer_id IS NULL.
   e. recompute_lifetime_stats($customer)  // xem §4.3
   f. recompute_reputation_score($customer)  // xem §4.4
   g. auto-add note nếu trigger (xem §4.5)
```

**Idempotency:** chạy lại event 2 lần = kết quả như 1 lần. Recompute đọc thẳng từ `orders` (single source of truth), không cộng dồn delta nên không sai khi event lặp.

**Multi-tenant:** unique constraint `customers(tenant_id, phone_hash)` ⇒ tenant khác cùng SĐT = 2 customer khác nhau, không bao giờ leak giữa các tenant.

### 4.3 Lifetime stats (`customers.lifetime_stats` jsonb)

Recompute từ `orders WHERE customer_id=? AND deleted_at IS NULL`:

```json
{
  "orders_total": 7,
  "orders_completed": 4,
  "orders_cancelled": 2,
  "orders_returned": 1,
  "orders_delivery_failed": 0,
  "orders_in_progress": 0,
  "revenue_completed": 8950000,
  "last_order_id": 18234,
  "last_order_status": "delivered",
  "computed_at": "2026-05-12T08:32:11Z"
}
```

Map từ standard order status:
- `orders_completed` = count(`status IN (completed, delivered)`)
- `orders_cancelled` = count(`status = cancelled`)
- `orders_returned` = count(`status IN (returned, refunded)` *(các trạng thái này chính thức xuất hiện ở Phase 7)*)
- `orders_delivery_failed` = count(`status = delivery_failed` *(xuất hiện khi nối ĐVVC ở Phase 3)*)
- `orders_in_progress` = phần còn lại
- `revenue_completed` = SUM(`grand_total` của các đơn `completed/delivered`)

Phase 2 (lúc spec này lên đầu tiên): chỉ có `cancelled`/`delivered`/`in_progress` (trạng thái return/delivery_failed = 0 cho tới Phase 3/7). Cấu trúc jsonb bảo đảm forward-compatible.

### 4.4 Reputation score & label (heuristic v1)

Công thức rule-based đơn giản, cấu hình được trong `config/customers.php`:

```
base = 100
- 15 cho mỗi cancellation (vai trò "bom đơn")
- 10 cho mỗi delivery_failed (không nhận được hàng)
- 8 cho mỗi return/refund (hoàn hàng sau giao)
+ 2 cho mỗi completed (thưởng khách quen, max +30)
clamp [0, 100]
```

Label:
- `>= 80` ⇒ `ok` (xanh, không hiển thị badge cho đỡ rối UI).
- `[40, 79]` ⇒ `watch` ("Cần kiểm tra", vàng).
- `< 40` ⇒ `risk` ("Rủi ro cao", đỏ).
- `is_blocked = true` ⇒ override hiển thị `blocked` (đen).

Khi `customers_completed >= 10` AND `cancellation_rate <= 5%` ⇒ thêm tag tự động `vip` (badge tím), không phải label thay thế.

**Quan trọng:** đây là **gợi ý cho NV**, không phải auto-action. Tự động chặn đơn dựa vào reputation là việc của rules engine ở Phase 6 (sẽ đọc `customers.reputation_score` qua `CustomerProfileContract`).

### 4.5 Auto-note khi trigger

Khi recompute, nếu vượt ngưỡng → tự thêm 1 dòng `customer_notes` với `kind` tương ứng (dedupe theo `(customer_id, kind, current_threshold_bucket)` để không thêm trùng):

| Trigger | `kind` | `severity` | Note text (template) |
|---|---|---|---|
| `orders_cancelled` vừa đạt 2 | `auto.cancel_streak` | `warning` | "Đã có 2 đơn huỷ — kiểm tra kỹ đơn mới" |
| `orders_cancelled` vừa đạt 5 | `auto.cancel_streak` | `danger` | "Đã có 5 đơn huỷ — cân nhắc chặn" |
| `orders_delivery_failed` vừa đạt 2 | `auto.delivery_failed` | `warning` | "2 lần giao thất bại — gọi xác nhận trước khi ship" |
| `orders_returned` vừa đạt 3 | `auto.return_streak` | `warning` | "3 lần trả hàng — sản phẩm có vấn đề?" |
| `orders_completed` đạt 10 | `auto.vip` | `info` | "Khách VIP — đã đặt 10 đơn thành công" |

`severity` ảnh hưởng UI: `info` = xám, `warning` = vàng, `danger` = đỏ.

### 4.6 Phân quyền

| Permission | Owner | Admin | staff_order | staff_warehouse | accountant | viewer |
|---|---|---|---|---|---|---|
| `customers.view` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `customers.note` | ✓ | ✓ | ✓ | – | – | – |
| `customers.block` | ✓ | ✓ | – | – | – | – |
| `customers.merge` | ✓ | ✓ | – | – | – | – |
| `customers.view_phone` *(SĐT đầy đủ trong UI)* | ✓ | ✓ | ✓ | – | – | – |

Khác role thấy SĐT dạng mask `09xx xxx 123` (đã có sẵn `Order.maskedBuyerPhone()`). Truy cập "xem đầy đủ SĐT" ghi `audit_logs` (tuỳ chọn, bật ở Phase 7 — xem `08-security-and-privacy.md` §6 rule 4).

## 5. Dữ liệu

### 5.1 Bảng mới (module Customers sở hữu)

**`customers`** (partition: không — số khách << số đơn, một bảng thường đủ):
| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `bigint identity` | PK |
| `tenant_id` | `bigint` NOT NULL | FK `tenants`, index, prefix mọi index phức hợp |
| `phone_hash` | `char(64)` NOT NULL | SHA-256 hex, **unique `(tenant_id, phone_hash)`** |
| `phone` | `text` NOT NULL | `encrypted` cast (Laravel) |
| `name` | `varchar(255)?` | NULL nếu sàn không trả tên |
| `email` | `text?` | `encrypted` cast (hiếm khi có với TikTok; cho tương lai) |
| `email_hash` | `char(64)?` | SHA-256(lowercase email) — optional secondary key, nullable |
| `addresses_meta` | `jsonb` | Mảng địa chỉ distinct đã thấy (giữ tối đa 5 gần nhất; struct giống `orders.shipping_address`) |
| `lifetime_stats` | `jsonb` NOT NULL | §4.3 |
| `reputation_score` | `smallint` NOT NULL DEFAULT 100 | 0..100 |
| `reputation_label` | `varchar(16)` NOT NULL DEFAULT `'ok'` | `ok|watch|risk|blocked` — denormalized cho filter nhanh |
| `tags` | `jsonb` NOT NULL DEFAULT `'[]'` | Mảng string, vd `["vip","bom"]` |
| `is_blocked` | `boolean` NOT NULL DEFAULT false | |
| `blocked_at` | `timestamptz?` | |
| `blocked_by_user_id` | `bigint?` | FK `users` |
| `block_reason` | `varchar(255)?` | |
| `manual_note` | `text?` | Note "đỉnh" — luôn hiển thị nổi bật ở UI; rỗng ⇒ không hiển thị box riêng |
| `first_seen_at` | `timestamptz` NOT NULL | |
| `last_seen_at` | `timestamptz` NOT NULL | index `(tenant_id, last_seen_at DESC)` |
| `merged_into_customer_id` | `bigint?` | FK self — khi merge, B trỏ về A |
| `pii_anonymized_at` | `timestamptz?` | Đánh dấu hồ sơ đã ẩn danh (sau `data_deletion` / disconnect) |
| `created_at`, `updated_at` | `timestamptz` | |
| `deleted_at` | `timestamptz?` | Soft delete (merge B = soft-delete B) |

Index thêm:
- `(tenant_id, reputation_label)` cho filter list.
- `(tenant_id, is_blocked)` cho rules engine.
- GIN `(tenant_id, tags jsonb_path_ops)` cho filter theo tag.

**`customer_notes`** (append-only — không soft delete):
| Cột | Kiểu | Ghi chú |
|---|---|---|
| `id` | `bigint identity` | PK |
| `tenant_id` | `bigint` NOT NULL | |
| `customer_id` | `bigint` NOT NULL | FK `customers`, index `(tenant_id, customer_id, created_at DESC)` |
| `author_user_id` | `bigint?` | NULL nếu auto (hệ thống) |
| `kind` | `varchar(32)` NOT NULL | `manual` \| `auto.cancel_streak` \| `auto.return_streak` \| `auto.delivery_failed` \| `auto.vip` \| `system.merge` |
| `severity` | `varchar(8)` NOT NULL DEFAULT `'info'` | `info` \| `warning` \| `danger` |
| `note` | `text` NOT NULL | |
| `order_id` | `bigint?` | FK `orders` — note có context đơn cụ thể (vd "Đã gọi xác nhận đơn #ORD-123") |
| `dedupe_key` | `varchar(64)?` | unique `(customer_id, dedupe_key)` cho auto-note (chống thêm trùng cùng ngưỡng) |
| `created_at` | `timestamptz` | |

### 5.2 Thay đổi bảng `orders` (module Orders sở hữu — migration ở Orders module)

Thêm cột:
- `customer_id bigint NULL` — FK `customers.id` (ON DELETE SET NULL).
- Index `(tenant_id, customer_id, placed_at DESC)` cho query "đơn của khách X".

Không thay đổi unique constraint của `orders`. Không ràng buộc NOT NULL (đơn có thể không khớp được khách).

### 5.3 Domain event

Module **Customers** phát:
- `CustomerLinked(Customer $customer, Order $order, bool $created)` — sau khi listener khớp/tạo khách. Cho phép module khác (vd Notifications Phase 6, rules engine Phase 6) phản ứng.
- `CustomerReputationChanged(Customer $customer, string $fromLabel, string $toLabel, int $fromScore, int $toScore)` — chỉ phát khi `label` đổi (ko phát mỗi lần score giảm 1 điểm).
- `CustomerBlocked(Customer $customer, ?User $by, ?string $reason)`, `CustomerUnblocked(Customer $customer, ?User $by)`.
- `CustomersMerged(Customer $kept, Customer $removed, User $by)`.

Module **Customers** **lắng nghe**:
- `OrderUpserted` (từ Orders) ⇒ `LinkOrderToCustomer` listener.
- `ShopDeauthorized` / `ChannelAccountRevoked` (từ Channels) ⇒ `AnonymizeCustomersForShop` listener (background — xem §8).
- `WebhookEvent { type: data_deletion }` (từ Channels) ⇒ `AnonymizeCustomersForDataDeletionRequest` listener.

### 5.4 Contract giữa module

`app/Modules/Customers/Contracts/CustomerProfileContract.php`:
```php
interface CustomerProfileContract {
    public function findByPhone(string $tenantId, string $rawPhone): ?CustomerProfileDTO;
    public function findById(int $tenantId, int $customerId): ?CustomerProfileDTO;
    public function isBlocked(int $tenantId, int $customerId): bool;
}
```

`CustomerProfileDTO` (giữ payload mỏng — chỉ cái cần ngoài module Customers):
```
{ id, phone_masked, name, reputation_score, reputation_label, lifetime_stats, is_blocked, tags, latest_warning_note? }
```

Module Orders dùng contract này để **tự render** card "Khách hàng" ở `OrderDetailResource` mà không phụ thuộc model `Customer` cụ thể. Bind interface→implementation trong `CustomersServiceProvider`.

## 6. API & UI

### 6.1 API mới (cập nhật `05-api/endpoints.md`)

| Method | Path | Auth | Mô tả |
|---|---|---|---|
| GET | `/api/v1/customers` | sanctum + tenant (`customers.view`) | Query: `q` (tên hoặc SĐT — nếu chuỗi 10 ký tự số ⇒ hash lookup theo `phone_hash`; ngược lại LIKE `name`), `reputation` (csv: `ok|watch|risk|blocked`), `tag`, `min_orders`, `has_note`, `sort` (`-last_seen_at|-lifetime_revenue|-cancellation_rate`), `page`, `per_page≤100`. Response `{ data: CustomerResource[], meta:{ pagination } }`. |
| GET | `/api/v1/customers/{id}` | sanctum + tenant (`customers.view`) | `{ data: CustomerResource kèm `notes[]` (mới nhất trước, 50 gần nhất) }`. |
| GET | `/api/v1/customers/{id}/orders` | sanctum + tenant (`customers.view`+`orders.view`) | Cùng filter/format như `/orders`, scoped theo `customer_id`. |
| POST | `/api/v1/customers/{id}/notes` | sanctum + tenant (`customers.note`) | `{ note: string, severity?: info\|warning\|danger, order_id?: bigint }` ⇒ `201 { data: CustomerNoteResource }`. |
| DELETE | `/api/v1/customers/{id}/notes/{noteId}` | sanctum + tenant (`customers.note` + chính là `author_user_id` hoặc owner/admin) | Xoá note do mình tạo (đề phòng gõ nhầm). Auto-note không xoá được. |
| POST | `/api/v1/customers/{id}/block` | sanctum + tenant (`customers.block`) | `{ reason?: string }` ⇒ `{ data: CustomerResource }` — set `is_blocked=true`, fire `CustomerBlocked`. |
| POST | `/api/v1/customers/{id}/unblock` | sanctum + tenant (`customers.block`) | — ⇒ `{ data: CustomerResource }`. |
| POST | `/api/v1/customers/merge` | sanctum + tenant (`customers.merge`) | `{ keep_id, remove_id }` ⇒ `{ data: CustomerResource (kept) }`. Reject nếu khác tenant; transaction; recompute stats; fire `CustomersMerged`. |
| POST | `/api/v1/customers/{id}/tags` | sanctum + tenant (`customers.note`) | `{ add?:[], remove?:[] }` ⇒ `{ data }`. |

**`CustomerResource`** (response body):
```json
{
  "id": 18234,
  "phone": "0987654321",            // chỉ trả nếu role có customers.view_phone; ngược lại "phone_masked": "09xx xxx 321"
  "phone_masked": "09xx xxx 321",
  "name": "Nguyễn Văn A",
  "reputation": { "score": 58, "label": "watch" },
  "is_blocked": false,
  "block_reason": null,
  "tags": ["bom"],
  "lifetime_stats": { ... §4.3 ... },
  "addresses_meta": [ ... ],
  "first_seen_at": "2026-02-12T01:23:45Z",
  "last_seen_at": "2026-05-08T11:02:33Z",
  "manual_note": "Khách bom đơn 2 lần — gọi xác nhận",
  "latest_warning_note": { "kind":"auto.cancel_streak", "note":"Đã có 2 đơn huỷ — kiểm tra kỹ đơn mới", "severity":"warning", "created_at":"..." }
}
```

**Mở rộng `OrderResource`** (Phase 2): thêm field `customer` (object con) — `null` nếu `customer_id IS NULL` hoặc role không có `customers.view`:
```json
"customer": {
  "id": 18234,
  "name": "Nguyễn Văn A",
  "phone_masked": "09xx xxx 321",
  "reputation": { "score": 58, "label": "watch" },
  "is_blocked": false,
  "tags": ["bom"],
  "lifetime_stats": { "orders_total": 7, "orders_cancelled": 2, "orders_returned": 1, "orders_completed": 4 },
  "latest_warning_note": { ... }
}
```

Không trả full `notes[]` ở Order detail (để gọn) — UI bấm "Xem hồ sơ" mới fetch `/customers/{id}`.

### 6.2 FE (cập nhật `06-frontend/overview.md`)

**Trang/route mới:**
- `/customers` — list (giống `OrdersPage` về phong cách: filter bar + bảng + status tabs theo reputation_label).
- `/customers/:id` — detail (tabs: Tổng quan / Lịch sử đơn / Ghi chú).

**Component mới:**
- `<CustomerSummaryCard customerId={…} />` — dùng ở `OrderDetailPage` (sidebar) + `OrdersTable` (hover tooltip).
- `<ReputationBadge score label />` — badge xanh/vàng/đỏ + tooltip giải thích.
- `<CustomerNotesList notes={…} canAdd={…} />` — render `customer_notes`, có form thêm note.

**Sửa trang sẵn có:**
- `OrdersPage` — cột buyer kèm `<ReputationBadge>` nhỏ; click → mở popover quick-view.
- `OrderDetailPage` — thêm card "Khách hàng" như §3.1 ở cột phải, trên hoặc dưới card "Người nhận" (đã có).

### 6.3 Job mới (cập nhật `07-infra/queues-and-scheduler.md`)

| Job | Queue | Trigger | Mục đích |
|---|---|---|---|
| `LinkOrderToCustomer` | `customers` | listen `OrderUpserted` (afterCommit) | §4.2 |
| `AnonymizeCustomersForShop` | `customers` | listen `ChannelAccountRevoked` / cron sau N ngày disconnect | Ẩn danh hoá khách thuộc shop bị ngắt |
| `AnonymizeCustomersForDataDeletionRequest` | `customers` | listen `WebhookEvent type=data_deletion` | Ẩn danh hoá theo yêu cầu sàn |
| `BackfillCustomersFromOrders` | `customers` (one-shot) | artisan command `customers:backfill` | Khôi phục `customers` từ `orders` đã có khi deploy spec lần đầu |
| `RecomputeCustomerStats` | `customers` | manual / hourly sweep cho các khách có order trong giờ qua | Phòng trường hợp event listener lỗi — đảm bảo eventually consistent |

Queue `customers` priority thấp hơn `webhooks`/`orders-sync` (không chặn order pipeline). `LinkOrderToCustomer` `ShouldBeUnique` theo `customer_id` để tránh race khi 2 đơn cùng khách upsert song song.

### 6.4 Không thêm logic theo tên sàn

Module Customers **không** import gì từ TikTok/Shopee/Lazada. Dữ liệu vào duy nhất qua `OrderUpserted` event (`OrderDTO`'s `shippingAddress.phone`/`buyerName`). Khác biệt sàn = khác `OrderDTO` đầu vào, không phải khác code Customers. Tuân thủ `extensibility-rules.md`.

## 7. Edge case & lỗi

- **SĐT mask / null:** không tạo customer; `orders.customer_id=null`; UI hiển thị "Không xác định được — SĐT đã ẩn".
- **SĐT đầy đủ ban đầu, sau bị mask** (vd TikTok mask sau khi giao xong): hồ sơ khách đã có; lần upsert sau chỉ update `last_seen_at`/stats; không cần re-match.
- **Cùng SĐT nhiều khách** (vd 2 vợ chồng dùng chung số): hệ thống coi như 1 — chấp nhận giới hạn này; NV có thể "Tách khách" (backlog Phase 2 cuối — không trong spec này).
- **Khách dùng 2 SĐT khác nhau:** tạo 2 hồ sơ → NV merge tay khi phát hiện (`POST /customers/merge`).
- **Race condition** 2 đơn cùng khách upsert song song: `SELECT … FOR UPDATE` trong transaction + unique `(tenant_id, phone_hash)` ⇒ một bên thắng INSERT, bên kia UPDATE; cả hai đều set được `orders.customer_id` chính xác.
- **Tenant isolation:** unique key có `tenant_id` ⇒ tuyệt đối không khớp chéo tenant. Test riêng (xem §9).
- **Cancellation rate fluctuating:** label đổi mỗi lần status đổi → có thể spam event. Mitigation: chỉ fire `CustomerReputationChanged` khi `label` (không phải `score`) thay đổi.
- **Order bị xoá (soft delete đơn manual)** ⇒ stats phải recompute (loại đơn đã xoá ra). Listener `RecomputeCustomerStats` cũng trigger ở `OrderDeleted` event (Phase 2).
- **Merge đơn từ B → A khi A đang được upsert:** transaction giữ row lock `customer_id=A`; B sẽ retry sau commit.
- **Khách bị block xong vẫn có đơn mới về** (vd webhook trễ): vẫn link bình thường (data integrity); UI hiển thị badge "Đã chặn"; rules engine Phase 6 sẽ quyết định có auto-cancel đơn không.
- **Hash collision SHA-256:** thực tế zero — không xử lý.
- **`pii_anonymized_at IS NOT NULL`** ⇒ UI hiển thị "Hồ sơ đã ẩn danh theo yêu cầu xoá dữ liệu — không thể xem chi tiết". `phone`/`name`/`email`/`addresses_meta` đã được clear; `phone_hash`/`lifetime_stats` giữ lại (số tổng không định danh — phục vụ thống kê seller).

## 8. Bảo mật & dữ liệu cá nhân

- **PII mới được lưu:** `customers.phone` (encrypted), `customers.name` (plain — đã có trong `orders.buyer_name`), `customers.email` (encrypted — hiếm có), `customers.addresses_meta` (plain jsonb — đã có trong `orders.shipping_address`). **Tổng cộng không tạo PII mới**, chỉ centralize cái đã có ở `orders` thành một bảng searchable.
- **`phone_hash`:** SHA-256 không reverse được → ok để index. **Không** lưu kèm `phone` plaintext cùng record nào ngoài cột `phone` encrypted.
- **`data_deletion` webhook từ sàn** (xem `08-security-and-privacy.md` §6 rule 3 + spec 0001 §8):
  - Trước spec này: chỉ ẩn danh hoá `orders` của shop đó.
  - Spec này thêm: job `AnonymizeCustomersForDataDeletionRequest` — với mỗi customer có >=1 đơn thuộc shop bị data_deletion:
    1. nếu khách còn đơn ở shop khác *trong cùng tenant* ⇒ giữ hồ sơ (vẫn cần cho seller); chỉ xoá địa chỉ/note có gắn `order_id` thuộc shop đó.
    2. nếu khách **chỉ có đơn ở shop bị data_deletion** ⇒ clear `phone`/`name`/`email`/`addresses_meta` (set NULL hoặc `'[ANONYMIZED]'`); giữ `phone_hash`/`lifetime_stats`/`customer_id` (số tổng không định danh — phục vụ thống kê tenant); set `pii_anonymized_at = now`.
- **Disconnect shop:** job `AnonymizeCustomersForShop` chạy sau N ngày (cấu hình; default 90 ngày để khiếu nại/đối soát kịp) — cùng cơ chế trên.
- **Permission `customers.view_phone`:** mặc định chỉ `owner`/`admin`/`staff_order`. Role khác thấy `phone_masked`. Truy cập "xem đầy đủ SĐT" có thể bật audit (xem `08-security-and-privacy.md` rule 4 — Phase 7).
- **Không chia sẻ PII ngoài tenant:** unique `(tenant_id, phone_hash)` ⇒ không có endpoint nào cross-tenant lookup. Reports/Billing không truy cập `customers.phone`.
- **Search bằng SĐT:** SPA gửi raw phone trong `GET /customers?q=...` → backend normalize + hash → query `phone_hash`. Raw phone không xuất hiện trong URL log (lý do dùng query param, không path — và log filter phải redact `q=` nếu có vẻ là số điện thoại; bổ sung ở `config/logging.php`).

## 9. Kiểm thử

### 9.1 Unit (`tests/Unit/Customers/`)
- `CustomerPhoneNormalizerTest` — bảng vector:
  - `"+84987654321"` → `"0987654321"`.
  - `"(+84) 98-765-4321"` → `"0987654321"`.
  - `"0987 654 321"` → `"0987654321"`.
  - `"84987654321"` → `"0987654321"`.
  - `"(+84) ****21"` → `null`.
  - `""`, `null`, `"abc"` → `null`.
  - `"+1 415 555 0123"` → `"+14155550123"` (không phải VN ⇒ giữ canonical quốc tế).
  - `"123"` → `null` (quá ngắn).
- `ReputationCalculatorTest` — bảng stats → score + label:
  - `{completed:5, cancelled:0}` → score 100, label `ok`.
  - `{completed:4, cancelled:2}` → score `100 + 4*2 - 2*15 = 78`, label `watch`.
  - `{cancelled:5}` → score `100 - 5*15 = 25`, label `risk`.
  - Cap `+30` cho bonus completed: `{completed:20}` → score `min(100, 100+30) = 100`.
- `CustomerProfileDTOTest` — mask theo permission.

### 9.2 Feature (`tests/Feature/Customers/`)
- `LinkOrderToCustomerTest` — fire `OrderUpserted` → listener tạo customer + set `orders.customer_id` + tính stats đúng. Chạy 2 lần = không tạo dòng `customer_notes` trùng (idempotent).
- `CustomerMatchingTest` — 2 đơn cùng SĐT (khác format `+84` vs `0`) → cùng 1 customer.
- `TenantIsolationTest` — cùng SĐT, 2 tenant → 2 customers riêng; tenant A không lookup được customer của B qua API.
- `AnonymizeCustomersForShopTest` — disconnect shop → khách "single-shop" bị clear PII; khách "multi-shop" trong cùng tenant giữ hồ sơ; `phone_hash` giữ lại.
- `CustomerApiTest` — list/filter/sort/pagination, search bằng SĐT raw → hash lookup, search bằng tên → LIKE.
- `CustomerNotesTest` — staff_order thêm note; viewer 403; auto-note dedupe.
- `CustomerBlockTest` — block/unblock + event fire.
- `CustomerMergeTest` — gộp B vào A: `orders.customer_id` chuyển, `customer_notes` chuyển, stats A recompute, B `deleted_at` + `merged_into_customer_id=A.id`; B's API trả 410 Gone.
- `RaceConditionTest` — 2 listener parallel cho cùng khách → unique constraint giữ data integrity (test bằng `withTransactions` + manual race).

### 9.3 FE
- Smoke render `/customers` + `/customers/:id`.
- `<ReputationBadge>` snapshot cho 4 label.
- `<CustomerSummaryCard>` render đúng cho `null` customer (đơn không khớp), `blocked` customer, `vip` customer.

## 10. Tiêu chí hoàn thành (Acceptance criteria)

- [x] Migration `customers` + `customer_notes` + thêm cột `orders.customer_id` (reversible).
- [x] Module `Customers`: `Customer`/`CustomerNote` model + `CustomersServiceProvider` + `Contracts/CustomerProfileContract` + `CustomerProfileDTO` + bind.
- [x] `CustomerPhoneNormalizer` + test bảng vector. (`afterCommit`/`ShouldBeUnique` bỏ trên listener — `OrderUpsertService` đã fire `OrderUpserted` ngoài transaction; race xử lý bằng `lockForUpdate` + unique `(tenant_id, phone_hash)`.)
- [x] `ReputationCalculator` + test (config `config/customers.php`).
- [x] Listener `LinkOrderToCustomer` (queue `customers`) + test idempotent.
- [x] Listener/job anonymize cho `data_deletion` (event `DataDeletionRequested`) + disconnect shop (`ChannelAccountRevoked` → job trễ `anonymize_after_days`).
- [x] Command `customers:backfill` (one-shot, progress bar).
- [x] Command scheduled `customers:recompute-stale` (mỗi giờ).
- [x] API: `GET /customers`, `GET /customers/{id}`, `GET /customers/{id}/orders`, `POST /customers/{id}/notes`, `DELETE /customers/{id}/notes/{noteId}`, `POST /customers/{id}/block`, `POST /customers/{id}/unblock`, `POST /customers/{id}/tags`, `POST /customers/merge` + phân quyền.
- [x] Mở rộng `OrderResource` với field `customer` (qua `CustomerProfileContract`); UI `OrderDetailPage` render `<CustomerSummaryCard>`.
- [x] FE trang `/customers` + `/customers/:id` + `<ReputationBadge>` + mục sidebar. *(Badge ở `OrdersPage` chưa thêm — backlog nhỏ.)*
- [x] Permissions mới (`customers.view`, `customers.note`, `customers.block`, `customers.merge`, `customers.view_phone`) trong `Role` enum.
- [x] Test (unit + feature) — cover §9. *(Vitest FE — sau.)*
- [x] Tài liệu cập nhật: spec này (Implemented), `05-api/endpoints.md`, `07-infra/queues-and-scheduler.md`, `00-overview/roadmap.md`. *(`08-security-and-privacy.md`/`glossary.md`/`customers-and-buyer-reputation.md` — bổ sung khi rảnh; `modules.md`/`02-data-model` đã có sẵn section Customers.)*

## 11. Câu hỏi mở

- **Có nên cho phép khách đặt nhiều SĐT** (1 customer ↔ N phone records)? *Tạm thời không — đơn giản hoá; merge tay khi cần.* Nếu volume thật cao, mở spec riêng "Multi-identifier customer" sau Phase 5.
- **Reputation có nên là ML?** *Phase này không.* Heuristic rule-based đủ minh bạch và "giải thích được" cho seller (quan trọng — seller cần biết tại sao 1 khách bị đánh dấu rủi ro). ML là backlog post-Phase 7.
- **Lưu trữ chính sách:** ẩn danh sau bao nhiêu ngày khi disconnect shop? *Default 90 ngày* (đủ cho khiếu nại/đối soát); cấu hình per-tenant ở `tenant_settings.customers.anonymize_after_days` (Phase 6 — Settings module).
- **Có cần export danh sách khách** (CSV) cho seller? *Có — `GET /customers?export=csv`*, nhưng nội dung CSV không chứa `phone` đầy đủ trừ khi role có `customers.view_phone` + xác nhận lần 2 (audit logged). Backlog Phase 2 cuối.
- **Hiển thị reputation chéo sàn nhưng cùng SĐT:** khách A đặt 5 đơn TikTok + 3 đơn manual (Phase 2) — stats là tổng cộng. Có cần break-down theo source ở UI? *Có — tab "Lịch sử đơn" trong customer detail có filter `source` như `/orders`.*
- **Khi nâng cấp lên Shopee/Lazada (Phase 4):** format SĐT có khác? *Shopee VN thường trả `+84xxxxxxxxx` hoặc `0xxxxxxxxxx`, Lazada tương tự — `CustomerPhoneNormalizer` đã cover; rà soát fixtures khi connector ra.*
