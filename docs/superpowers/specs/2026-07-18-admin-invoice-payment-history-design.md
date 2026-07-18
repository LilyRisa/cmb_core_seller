# Thiết kế: Lịch sử thanh toán hóa đơn (Admin)

- **Ngày:** 2026-07-18 · **Tác giả:** lilyrisa
- **Trạng thái:** Implemented
- **Liên quan:** `docs/specs/0018-billing-saas.md` (pipeline checkout → invoice → webhook → payment), `docs/specs/0023-admin-vouchers-plans-broadcast.md` (nguồn gốc endpoint mark-paid/refund per-tenant hiện có, RBAC `billing.view`/`billing.manage`).

## 1. Vấn đề & mục tiêu

Admin hiện **không có** màn hình nào xem được lịch sử hóa đơn/thanh toán xuyên suốt toàn hệ thống (mọi tenant). Backend đã có sẵn model `Invoice`/`Payment` đầy đủ trạng thái, nhưng chỉ lộ ra qua 10 dòng cuối nhúng trong `GET /admin/tenants/{id}` (không lọc, không phân trang) — không đủ để giám sát hoặc tra cứu khi khách báo "đã chuyển khoản nhưng chưa thấy lên gói".

Mục tiêu: 1 trang admin mới, xem **toàn bộ hóa đơn mọi tenant**, có bộ lọc, bao gồm cả các yêu cầu thanh toán **đã mở nhưng chưa hoàn thành** (`status='pending'`).

## 2. Trong / ngoài phạm vi

- **Trong:** Endpoint `GET /api/v1/admin/invoices` (mới) + trang FE `AdminInvoicesPage.tsx` (mới) — bảng hóa đơn có lọc (trạng thái/tenant/khoảng ngày/mã HD) + Drawer chi tiết lồng bảng các lần thanh toán liên quan. Chỉ xem (view-only).
- **Ngoài (không làm ở bản này):** nút thao tác (mark-paid/refund) từ trang này — 2 endpoint đó đã tồn tại (SPEC 0023) nhưng chưa gắn UI, để dành làm sau nếu cần. Log webhook thất bại/orphan (`webhook_events` provider `payments.*`) — không có Invoice tương ứng nên không tự nhiên khớp vào bảng hóa đơn, phức tạp hơn, để riêng. Khôi phục tab "Hóa đơn" trong `AdminTenantDetailPage` (đã có hook/type sẵn, chưa dùng) — không thuộc yêu cầu lần này. Không có job tự động chuyển `pending` quá hạn (`due_at`) sang trạng thái "hết hạn" — `Invoice` không có status đó; bản này chỉ hiển thị `due_at` để admin tự nhận biết hóa đơn "chờ" đã quá hạn, không tự đổi trạng thái.

## 3. Luồng chính

1. Admin vào menu **Lịch sử thanh toán** → gọi `GET /api/v1/admin/invoices` (mặc định: mọi trạng thái, mới nhất trước, trang 1).
2. Lọc theo **trạng thái** (Segmented: Tất cả/Chờ/Đã thanh toán/Hủy/Hoàn tiền), **tenant** (ô nhập tenant_id, cùng quy ước với `AdminAuditLogsPage`), **khoảng ngày tạo** (`RangePicker` trên `created_at`), **mã hóa đơn** (`Input.Search` trên `code`) — mỗi lần đổi filter gọi lại API với query tương ứng, reset về trang 1.
3. Click 1 dòng → mở Drawer: thông tin đầy đủ hóa đơn (mã, tenant, gói/kỳ, số tiền, trạng thái, các mốc thời gian) + bảng con "Các lần thanh toán" (dữ liệu `payments[]` đã nạp sẵn kèm theo hóa đơn trong response — không gọi thêm API).

## 4. Hành vi & quy tắc

- **"Chờ" = mở yêu cầu chưa hoàn thành**, đúng theo `Invoice::STATUS_PENDING` — không cần khái niệm mới, không cần bảng mới.
- Lọc theo tenant dùng ô nhập `tenant_id` dạng số, **nhất quán với `AdminAuditLogController`** hiện có (không xây thêm component tìm-kiếm-tenant mới).
- `payments[]` nạp qua eager-load (`Invoice::with('payments')`) — số lượng payment mỗi hóa đơn nhỏ (thường 1-3 lần thử), không cần phân trang lồng.
- Sắp xếp mặc định: `created_at desc`.
- RBAC: quyền `billing.view` (đã có sẵn, dùng chung với các trang billing khác).

## 5. Dữ liệu

- Không thêm bảng/migration. Dùng `invoices` + `payments` sẵn có (`withoutGlobalScope(TenantScope::class)` vì đây là view xuyên tenant, giống `AdminTenantController`).

## 6. API

`GET /api/v1/admin/invoices` — quyền `billing.view`.

Query params: `status?` (`pending|paid|void|refunded`), `tenant_id?` (int), `q?` (tìm theo `code`, LIKE), `date_from?`/`date_to?` (lọc `created_at`, ISO date), `page?`, `per_page?` (mặc định 20).

Response: `{ data: InvoiceWithPayments[], meta: { pagination } }` — theo đúng khuôn phân trang hiện có ở mọi admin list endpoint khác. Mỗi `InvoiceWithPayments` = `AdminInvoice` (type đã có sẵn ở `admin.tsx`) + tên/mã tenant + `payments: AdminPayment[]`.

Cập nhật `docs/05-api/endpoints.md` (mục Admin) khi code xong.

## 7. Testing

- Feature test: filter theo từng field (status/tenant_id/q/date range) trả đúng tập con; phân trang đúng; quyền `billing.view` chặn user không có quyền; response chứa `payments[]` đúng của từng invoice (không lẫn giữa các invoice/tenant khác nhau — đây là bug dễ gặp nhất khi eager-load xuyên tenant).
- FE: không cần test tự động (dự án không có JS test runner) — verify bằng `npm run typecheck && npm run lint && npm run build` + smoke check thủ công.

## 8. Tiêu chí hoàn thành

- [ ] `GET /api/v1/admin/invoices` hoạt động đúng mọi filter + phân trang, quyền `billing.view`.
- [ ] `AdminInvoicesPage.tsx` hiển thị bảng + bộ lọc + Drawer chi tiết đúng thiết kế, thêm vào menu admin.
- [ ] pint/phpstan/test xanh; FE lint/typecheck/build xanh.
- [ ] `docs/05-api/endpoints.md` cập nhật endpoint mới.
