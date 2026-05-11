# Quy ước API (`/api/v1`)

**Status:** Stable · **Cập nhật:** 2026-05-11

> SPA React gọi API qua đây. Giữ nhất quán để frontend không phải đoán.

## 1. Đường dẫn & versioning
- Tiền tố: `/api/v1/...`. Breaking change ⇒ `/api/v2`, giữ `v1` một thời gian (deprecation notice).
- Tài nguyên dạng số nhiều, kebab-case: `/api/v1/channel-accounts`, `/api/v1/orders`, `/api/v1/sku-mappings`, `/api/v1/print-jobs`...
- Hành động không phải CRUD ⇒ sub-resource động từ: `POST /api/v1/orders/{id}/confirm`, `POST /api/v1/channel-accounts/{id}/resync`, `POST /api/v1/orders/{id}/status` (đổi trạng thái).

## 2. Xác thực & tenant
- Auth: **Sanctum SPA (cookie)**. Trước khi login, SPA gọi `GET /sanctum/csrf-cookie`. Các call sau gửi cookie + header `X-XSRF-TOKEN`.
- Tenant hiện tại: header `X-Tenant-Id: <id>` (hoặc lưu trong session sau khi chọn). Middleware `tenant` set current tenant + kiểm user thuộc tenant đó. Thiếu/không hợp lệ ⇒ `403`.
- Mọi endpoint nghiệp vụ yêu cầu `auth:sanctum` + `tenant`. Endpoint công khai: `/api/v1/auth/login|register|forgot-password`, `/api/v1/health`.

## 3. Định dạng response (envelope thống nhất)
Thành công:
```json
{ "data": { ... } }                          // 1 đối tượng
{ "data": [ ... ], "meta": { "pagination": { "page":1, "per_page":20, "total":134, "total_pages":7, "next_cursor":"..." } } }
```
Lỗi:
```json
{ "error": { "code": "ORDER_INVALID_TRANSITION", "message": "Không thể chuyển từ completed về processing.", "details": { ... }, "trace_id": "..." } }
```
- `code` = chuỗi hằng (SCREAMING_SNAKE) để frontend xử lý theo loại lỗi; `message` = tiếng Việt cho người dùng; `trace_id` để tra log/Sentry.
- HTTP status: `200/201/204` thành công; `400` (validate), `401` (chưa đăng nhập), `403` (không đủ quyền / sai tenant), `404`, `409` (xung đột trạng thái / idempotency), `422` (validate chi tiết — Laravel mặc định), `429` (rate limit), `5xx` (lỗi server, kèm `trace_id`).
- Validate lỗi (`422`): `{ "error": { "code":"VALIDATION_FAILED", "message":"...", "details": { "field": ["msg"] } } }`.

## 4. Phân trang, lọc, sắp xếp
- Mặc định **page-based**: `?page=1&per_page=20` (per_page tối đa 100). Danh sách lớn / đồng bộ liên tục dùng **cursor**: `?cursor=...&limit=...` (trả `meta.pagination.next_cursor`). Mỗi endpoint chọn một kiểu, ghi rõ ở `endpoints.md`.
- Lọc: query param trực tiếp, vd `/orders?status=processing&source=tiktok&channel_account_id=12&placed_from=2026-05-01&placed_to=2026-05-10&q=0987...&tag=urgent`. Nhiều giá trị: `status=processing,ready_to_ship`.
- Sắp xếp: `?sort=-placed_at` (dấu `-` = giảm dần). Cho phép một tập field whitelisted.

## 5. Resource (serialization)
- Dùng Laravel API Resource. Quy ước field: `snake_case`. ID lộ ra ngoài: dùng dạng không lộ số tự tăng (UUID/hashid) — chốt ở Phase 0.
- Tiền: trả số nguyên đồng (`"grand_total": 199000`) + `"currency": "VND"`. Frontend tự format.
- Thời gian: ISO-8601 UTC (`"placed_at": "2026-05-10T03:21:00Z"`). Frontend hiển thị theo `Asia/Ho_Chi_Minh`.
- Trạng thái: trả **mã chuẩn** (`"status": "processing"`) + `"status_label": "Đang xử lý"` + `"raw_status": "AWAITING_SHIPMENT"`.
- Quan hệ nặng: chỉ include khi `?include=items,shipments` (whitelist); mặc định không kèm.

## 6. Idempotency & hành động hàng loạt
- Hành động tạo có thể trùng (vd tạo print job, tạo vận đơn) ⇒ chấp nhận header `Idempotency-Key`; cùng key trong cửa sổ thời gian ⇒ trả lại kết quả cũ.
- Bulk actions: `POST /api/v1/orders/bulk { "action":"confirm", "order_ids":[...], ... }` → trả `{ "data": { "succeeded":[...], "failed":[{ "id":..., "error":{...} }] } }` (một số fail không làm fail cả lô) — hoặc tạo job + trả `job_id` nếu lâu, SPA poll `/api/v1/jobs/{id}`.

## 7. Rate limiting & lỗi
- Throttle: login (vd 5/phút/IP), API chung (vd 120/phút/user), endpoint nặng (export, bulk) thấp hơn. Trả `429` + `Retry-After`.
- Mọi `5xx` log kèm `trace_id` (gửi Sentry). Không lộ stacktrace ra client ở production.

## 8. Tài liệu API
- Mọi endpoint mới ⇒ thêm vào `docs/05-api/endpoints.md` (đường dẫn, method, quyền cần, request, response, lỗi đặc thù). Cân nhắc generate OpenAPI từ code (Phase sau) — nhưng `endpoints.md` là nguồn người-đọc-được tối thiểu phải có.
