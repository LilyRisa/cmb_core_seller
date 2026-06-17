# 0038 — Cảnh báo khách "bom hàng" từ Pancake POS cho đơn thủ công

Trạng thái: Implemented · Ngày: 2026-06-17 · Liên quan: [0002 customer-registry-and-buyer-reputation](0002-customer-registry-and-buyer-reputation.md), [0021 manual-orders-ghn-fulfillment](0021-manual-orders-ghn-fulfillment.md), [0007 settings-center](0007-settings-center.md)

## 1. Bối cảnh & mục tiêu

Màn **tạo đơn thủ công** (`CreateOrderPage`) đã có component `CustomerWarning` hiển thị cảnh báo dựa trên **dữ liệu nội bộ**: khách bị chặn (`is_blocked`), đơn chưa hoàn thành (`open_orders`), đơn đang/đã hoàn (`returning_orders`) — lấy từ `GET /api/v1/customers/lookup?phone=...`.

Với **khách mới** (số điện thoại chưa từng phát sinh đơn trong hệ thống) thì nội bộ **không có dữ liệu gì** để cảnh báo. Tính năng này **bù đắp** khoảng trống đó bằng cách tra cứu dữ liệu "bom hàng" cộng đồng từ **Pancake POS** và hiển thị cùng cảnh báo nội bộ.

Mục tiêu: khi lookup một số điện thoại ở màn tạo đơn thủ công, hệ thống gọi Pancake `bad_report_info`, lưu kết quả vào DB (có cache), và hiển thị thêm một khối cảnh báo trong `CustomerWarning`.

## 2. Phạm vi

- **CHỈ áp dụng đơn thủ công.** Endpoint `customers/lookup` hiện chỉ được gọi từ `CreateOrderPage` (màn tạo đơn thủ công). Đơn đồng bộ từ sàn (Channels) **không** đi qua luồng này và **không** gọi Pancake.
- **Cấu hình global (toàn hệ thống).** Một tài khoản Pancake duy nhất dùng chung cho mọi tenant, khai trong **admin platform** (`/admin/*`) qua System Settings. Đây là nguồn blacklist cộng đồng, không phải tài khoản riêng từng seller.
- Ngoài phạm vi (YAGNI): đồng bộ ngược (tự báo cáo khách lên Pancake), nhập/xuất, cấu hình per-tenant, dùng Pancake cho mục đích khác ngoài bad-report.

## 3. Cấu hình (admin / System Settings)

Thêm 3 key vào `SystemSettingsCatalog` (`app/app/Modules/Settings/Support/SystemSettingsCatalog.php`), group `integrations` (hoặc `marketplace`):

| Key | type | is_secret | env (fallback) | Ý nghĩa |
|-----|------|-----------|----------------|---------|
| `integrations.pancake.enabled` | bool | false | `PANCAKE_ENABLED` | Bật/tắt tính năng |
| `integrations.pancake.shop_id` | string | false | `PANCAKE_SHOP_ID` | Shop ID Pancake (vd `1720000852`) |
| `integrations.pancake.access_token` | string | false | `PANCAKE_ACCESS_TOKEN` | Token (hiển thị rõ trong admin cho dễ thao tác) |

Các hằng số kỹ thuật (base URL, timeout, retries, TTL cache) đặt trong `config/integrations.php` block `pancake` (đọc bằng `config()`), không cần admin chỉnh:

```php
'pancake' => [
    'api_base_url' => env('PANCAKE_API_BASE_URL', 'https://pos.pancake.vn/api/v1'),
    'cache_ttl_minutes' => (int) env('PANCAKE_CACHE_TTL_MIN', 1440), // 24h
    'http' => [
        'timeout' => (int) env('PANCAKE_HTTP_TIMEOUT', 15),
        'connect_timeout' => 8,
        'retries' => (int) env('PANCAKE_HTTP_RETRIES', 1),
        'retry_sleep_ms' => (int) env('PANCAKE_HTTP_RETRY_SLEEP_MS', 500),
    ],
],
```

Đọc credential tại runtime qua `system_setting()` (ưu tiên DB, fallback env):
`system_setting('integrations.pancake.access_token', config(...))`.

UI admin: thêm group hiển thị trong `AdminSystemSettingController` index/update (cùng cơ chế các marketplace key sẵn có — không cần code UI mới ngoài việc khai catalog).

## 4. Tầng integration — gọi API Pancake

Tôn trọng luật module: **Customers module không import nội bộ Integrations**. Giao tiếp qua một Contract + DTO chuẩn.

- **Contract** (đặt trong Customers module để module sở hữu interface nó cần):
  `app/app/Modules/Customers/Contracts/CustomerBadReportProvider.php`
  ```php
  interface CustomerBadReportProvider {
      /** Trả null nếu tắt/không cấu hình/lỗi/không có dữ liệu. */
      public function lookup(string $phone): ?BadReportData;
  }
  ```
- **DTO chuẩn** `app/app/Modules/Customers/Contracts/BadReportData.php` (readonly):
  ```php
  // orderFail, orderSuccess, warningCount: int
  // warnings: array<{reason: string, reportedAt: ?string}>  // chỉ reason + ngày tạo
  // matchedPhone: string  // số chuẩn hoá Pancake trả về, vd "+84..."
  ```
- **Implementation** `app/app/Integrations/Pancake/PancakeBadReportProvider.php`
  + `PancakeClient` (HTTP) theo pattern TikTok/Lazada connector:
  ```php
  Http::timeout($t)->connectTimeout($ct)->retry($r, $sleep, throw: false)->acceptJson()
      ->get($base.'/shops/'.$shopId.'/orders/bad_report_info', [
          'access_token' => $token,
          'phone_number' => $phone,
      ]);
  ```
  Map response → `BadReportData`:
  - `reports_by_phone[<matched>]` → `orderFail`, `orderSuccess`, `warningCount` (key `warning`).
  - `warning_phone_number[]` → `warnings[]` chỉ lấy **`reason`** + **`inserted_at`** (ngày tạo báo cáo). Bỏ `reported_by`, `page_id`, `id`...
  - Khi nhiều số trong `reports_by_phone`/`available_for_report`: chọn số khớp với input (so khớp đuôi sau khi chuẩn hoá). Nếu không khớp → coi như không có dữ liệu.
- **Đăng ký binding** ở `IntegrationsServiceProvider`: `bind(CustomerBadReportProvider::class, PancakeBadReportProvider::class)`.
- **Xử lý lỗi (quan trọng):** mọi lỗi (tắt config, thiếu token, HTTP fail, timeout, `success=false`, parse lỗi) ⇒ **nuốt lỗi, log warning, trả `null`**. Không bao giờ chặn việc lookup/tạo đơn.

## 5. Lưu trữ + cache

Bảng mới **`customer_bad_reports`** — tách khỏi `customers` để chạy được **cả khi khách chưa tồn tại** (đơn thủ công khách mới), đồng thời làm cache.

Migration `app/app/Modules/Customers/Database/Migrations/..._create_customer_bad_reports_table.php`:

| cột | kiểu | ghi chú |
|-----|------|---------|
| `id` | bigint PK | |
| `tenant_id` | bigint, index | `BelongsToTenant` |
| `phone_hash` | string(64), index | `CustomerPhoneNormalizer::normalizeAndHash()` — KHÔNG lưu số thô |
| `order_fail` | int default 0 | |
| `order_success` | int default 0 | |
| `warning_count` | int default 0 | |
| `warnings` | json nullable | `[{reason, reported_at}]` |
| `has_data` | bool default false | true nếu Pancake trả khớp số (phân biệt "đã tra, sạch" vs chưa tra) |
| `synced_at` | timestamp | mốc cache |
| timestamps | | |

Unique `(tenant_id, phone_hash)`. Model `CustomerBadReport` dùng trait `BelongsToTenant`. **Lưu ý SoftDelete/updateOrCreate**: dùng `updateOrCreate(['tenant_id','phone_hash'], ...)` thuần (bảng này không soft-delete) — không vướng gotcha 23505.

**Quy tắc cache (TTL 24h):** trong lookup, nếu có bản ghi và `synced_at >= now-TTL` ⇒ dùng bản đã lưu; ngược lại gọi provider, `updateOrCreate`, rồi dùng kết quả mới. Provider trả `null` (lỗi tạm) ⇒ giữ bản cũ nếu có, không ghi đè bằng rỗng.

## 6. Tích hợp vào `CustomerController::lookup()`

Endpoint hiện trả envelope `{ customer, addresses, open_orders, returning_orders }`. **Khớp với logic trước đó**: thêm 1 key cùng cấp `bad_report` vào envelope, để `CustomerWarning` tiêu thụ thống nhất với các trường nội bộ.

Luồng trong `lookup()` (sau khi đã có `$hash`):
1. Tính dữ liệu nội bộ như cũ (customer, open/returning orders).
2. Gọi service mới `CustomerBadReportService->fetch($hash, $phone)` (Customers/Services):
   - đọc/ghi cache `customer_bad_reports` theo §5;
   - gọi `CustomerBadReportProvider` khi cache thiếu/cũ;
   - trả về `BadReportData|null`.
3. Đưa vào response: `'bad_report' => BadReportResource | null`.

Hoạt động **bất kể customer tồn tại hay không** (đã tách bảng). Khi `customer === null` vẫn trả `bad_report`.

`BadReportResource` shape:
```json
{ "order_fail": 4, "order_success": 8, "warning_count": 3,
  "warnings": [ { "reason": "bom hàng ...", "reported_at": "2026-01-18T05:51:04Z" } ],
  "synced_at": "2026-06-17T..Z" }
```
Timestamp ISO-8601 UTC (chuẩn API dự án). Tuân thủ PII: chỉ lưu/đối chiếu qua `phone_hash`, không log số thô; `reported_by` không lưu, không trả.

## 7. Frontend — mở rộng `CustomerWarning`

Trong `app/resources/js/pages/CreateOrderPage.tsx`, type `CustomerLookupResult` + hook `useCustomerLookup` (`features/customers/...`) thêm field `bad_report`.

`CustomerWarning` thêm một khối (sau khối nội bộ) khi `bad_report?.has_data`:
- Dòng số liệu: **"Pancake: X đơn fail · Y thành công · Z lần bị cảnh báo"** (icon `@ant-design/icons`, không emoji — theo guideline UI).
- Danh sách `warnings`: mỗi dòng **lý do** + **ngày tạo báo cáo** (format `app_display_tz()`/UTC+7). Cắt bớt nếu >5–10 mục như cách `open/returning` đang làm.
- Alert chuyển `type="warning"` (đỏ) nếu `order_fail > 0` hoặc `warning_count > 0` (gộp vào biến `danger` sẵn có cùng `is_blocked`/`returning`).
- Điều kiện ẩn toàn bộ Alert cập nhật lại: ẩn khi không có cả dữ liệu nội bộ lẫn `bad_report.has_data`.

## 8. Map trường Pancake → hệ thống (khớp logic trước đó)

| Pancake | Lưu DB | Hiển thị | Tương đương khái niệm nội bộ |
|---------|--------|----------|------------------------------|
| `reports_by_phone[].order_fail` | `order_fail` | "X đơn fail" | gần với `returning_orders` (đơn hỏng/hoàn) → tô đỏ |
| `reports_by_phone[].order_success` | `order_success` | "Y thành công" | tham chiếu tích cực |
| `reports_by_phone[].warning` | `warning_count` | "Z lần bị cảnh báo" | gần với `is_blocked`/risk → tô đỏ |
| `warning_phone_number[].reason` | `warnings[].reason` | lý do bom | — |
| `warning_phone_number[].inserted_at` | `warnings[].reported_at` | ngày tạo báo cáo | — |
| (các field khác) | bỏ | — | — |

## 9. Xử lý lỗi & bảo mật

- Provider luôn fail-soft (trả `null`); lookup không bao giờ vỡ vì Pancake.
- Không log access_token / số điện thoại thô. Đối chiếu qua `phone_hash`.
- Token mã hoá tại DB (is_secret). Tôn trọng PII masking như [memory marketplace-buyer-pii-masking].
- Rate: cache 24h + dedup theo `(tenant_id, phone_hash)` đã giảm tải; không cần throttle riêng giai đoạn đầu.

## 10. Kiểm thử

- **Unit** `PancakeBadReportProvider`: map payload mẫu (đề bài) → DTO đúng; chọn đúng số khi nhiều số; `success=false`/HTTP 4xx/timeout → `null` (dùng `Http::fake`).
- **Feature** `customers/lookup`: (a) cache miss gọi provider + ghi `customer_bad_reports`; (b) cache hit (synced_at mới) KHÔNG gọi provider; (c) provider `null` không ghi đè cache cũ; (d) khách chưa tồn tại vẫn trả `bad_report`; (e) `enabled=false` ⇒ `bad_report=null`, không gọi HTTP. Mock provider/`Http::fake`.
- Tôn trọng baseline: không phá test sẵn có; không yêu cầu toàn xanh ngoài phạm vi (xem memory test-verify-baseline).

## 11. Việc cần làm (tóm tắt)

1. Migration + Model `CustomerBadReport`.
2. Contract `CustomerBadReportProvider` + DTO `BadReportData` (Customers/Contracts).
3. `PancakeClient` + `PancakeBadReportProvider` (Integrations/Pancake) + binding.
4. `CustomerBadReportService` (cache + gọi provider) trong Customers/Services.
5. Khai 3 key vào `SystemSettingsCatalog` + block `config/integrations.php:pancake` + env mẫu.
6. Sửa `CustomerController::lookup()` thêm `bad_report` + `BadReportResource`.
7. FE: type + `useCustomerLookup` + `CustomerWarning` khối Pancake.
8. Tests (unit + feature).
9. Cập nhật `docs/05-api/endpoints.md` (đổi response `customers/lookup`).

## Revision v2 (2026-06-17) — report nội bộ + UI thanh tỷ lệ

Mở rộng/điều chỉnh theo yêu cầu chủ dự án:

**A. Pancake gọi 1 lần (bỏ TTL).** Pancake chỉ được gọi khi DB **chưa có dữ liệu nào** cho số đó — tức không có report nội bộ **và** chưa có dòng `customer_bad_reports`. Đã có ⇒ không gọi lại (bỏ `cache_ttl_minutes`). `CustomerBadReportService::fetchOnce()` trả DTO từ cache nếu có dòng (kể cả "sạch"); chỉ gọi provider khi chưa có dòng, lỗi ⇒ không ghi (lần sau thử lại).

**B. Report nội bộ (`customer_reports`).** Người bán tự báo "bom hàng" cho **đơn thủ công bị hoàn**:
- Bảng `customer_reports`: `tenant_id, phone_hash, order_id (unique), order_number, reason, reported_by_user_id, reported_at`. **Mỗi đơn 1 report** (unique order_id).
- `POST /api/v1/customers/reports` `{ order_id, reason }` (perm `customers.note`): đơn phải `source=manual` & trạng thái hoàn/thất bại (`delivery_failed|returning|returned_refunded`); idempotent (đã báo ⇒ 422). `phone_hash` lấy từ khách đã liên kết, thiếu thì từ `buyer_phone`.
- `OrderResource` thêm `can_bad_report` (thuần source+status) và `bad_reported` (qua `CustomerReportContract::isOrderReported`, chỉ query khi `can_bad_report`).
- **Nút "Báo cáo bom hàng"** trên màn chi tiết đơn (đơn hoàn manual) → nhập lý do → tạo report; đã báo ⇒ nút khoá.

**C. Ưu tiên nội bộ, thiếu mới Pancake.** `CustomerWarningService::buildSummary($customer,$hash,$phone)`:
- Có dữ liệu nội bộ (`customer != null` hoặc có report) ⇒ `source=internal`: `success_count=lifetime.orders_completed`, `fail_count=orders_returned+orders_delivery_failed`, `warnings` = report nội bộ + (khách bị chặn). KHÔNG gọi Pancake.
- Không có ⇒ `source=pancake`: dùng `fetchOnce()` (gọi 1 lần). `warnings` = cảnh báo Pancake.
- Chỉ trả khi có tín hiệu (`success+fail>0` hoặc có warning), ngược lại `null`.

**D. Đổi payload `bad_report`** (thay shape cũ): `{ source, success_count, fail_count, warnings:[{reason, reported_at, source}], has_warning }`.

**E. UI màn tạo đơn (thay khối Alert cũ).** Phần khách hàng: **thanh progress tỷ lệ** thành công (xanh, trái) / hoàn (đỏ, phải), hover xem chi tiết số đơn (+ đơn đang xử lý). Cạnh đó **nút icon cảnh báo**, **sáng nền** khi `has_warning`; bấm mở popover **danh sách cảnh báo** (read-only: lý do + ngày + nguồn). **Không** có tạo cảnh báo ở màn đang tạo đơn (việc tạo nằm ở nút trên đơn hoàn).

**F. Hồ sơ khách + gợi ý tên (UI tạo đơn).** Customers thêm cột `avatar_url, source, dob, address` (persist từ `order.meta` qua `CustomerLinkingService` khi link đơn — bền vững). Màn tạo đơn:
- Nút **3 chấm** mở modal "Thông tin khách hàng": avatar (upload `customer-avatars`), tên, ngày sinh/tuổi, địa chỉ, nguồn khách hàng. Dùng chung `form` → đẩy vào `order.meta`.
- Ô **Tên khách hàng** = AutoComplete: gõ ≥2 ký tự gợi ý (tên · SĐT · số đơn), chọn → điền tên + SĐT (kích hoạt lookup). Tìm kiếm **scope theo tenant** (đã sẵn, không dùng chung).
- Tên khách → **tự điền Tên người nhận** (cùng cơ chế SĐT). **Bỏ** field "Dự kiến nhận hàng".
