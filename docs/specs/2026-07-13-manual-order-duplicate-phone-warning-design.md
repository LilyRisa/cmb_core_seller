# SPEC: Cảnh báo SĐT trùng đơn hàng cũ ở form tạo đơn thủ công + gỡ "Cho khách xem/thử hàng"

- **Trạng thái:** Design
- **Module backend liên quan:** Orders (tái dùng `OrderLookupService`/`OrderLookupContract` có sẵn — không đụng Customers module)
- **Tác giả / Ngày:** lilyrisa · 2026-07-13
- **Liên quan:** SPEC 0021 (`CustomerAddress`/warning ở tạo đơn thủ công), SPEC 0038 (`CustomerWarningService`/bad-report), `docs/03-domain/order-status-state-machine.md`, memory `carrier-default-shipping-settings-per-account` (default "cho xem/thử hàng" nay ở Cài đặt ĐVVC).

## 1. Vấn đề & mục tiêu

Form tạo đơn thủ công (`CreateOrderPage.tsx`) hiện có widget cảnh báo khách (thanh tỷ lệ thành công/thất bại + popover blocked/bad-report), nhưng **không cho biết SĐT đang nhập đã từng tạo đơn hàng nào** — người bán không thấy được mã đơn cụ thể để đối chiếu, kể cả khi khách có đơn đã hoàn trước đó.

Đồng thời, control "Cho khách xem / thử hàng" (`meta.required_note`) trong card Thanh toán đã **hết tác dụng ở backend** (bị `validate()` lọc bỏ, không bao giờ tới `ManualOrderService`) — giá trị hiệu lực thực tế luôn lấy theo cấu hình mặc định của tài khoản ĐVVC ở Cài đặt (`carrier_account.meta.defaults.required_note`). Giữ control này trên form chỉ gây hiểu nhầm là nó có tác dụng.

Mục tiêu:
1. Thêm cảnh báo hiển thị **mã đơn cụ thể** (đơn gần nhất bất kỳ trạng thái nào của SĐT này, và riêng đơn hoàn gần nhất nếu có), bấm mã đơn mở modal xem nhanh.
2. Gỡ control "Cho khách xem / thử hàng" khỏi form tạo đơn thủ công.

## 2. Trong / ngoài phạm vi

- **Trong:**
  - Endpoint mới `GET /api/v1/orders/lookup-by-customer` (module Orders) trả về đơn gần nhất + đơn hoàn gần nhất của một `customer_id`, loại trừ được 1 đơn (dùng khi sửa đơn).
  - FE: hook mới gọi nối tiếp sau `useCustomerLookup`; 1 `Alert` cảnh báo mới đặt ngay dưới card Khách hàng (và card gộp "Khách & nhận hàng" ở compact mode); mã đơn bấm mở `OrderDetailModal` (component tái dùng, đã có sẵn ở `OrdersPage.tsx`).
  - Gỡ hoàn toàn `Form.Item name="required_note"` + plumbing liên quan (const/type, localStorage sticky-prefs, prefill, payload, reset) khỏi `CreateOrderPage.tsx`.
- **Ngoài (không đổi):**
  - Widget cảnh báo hiện có (thanh tỷ lệ + popover blocked/bad-report ở tiêu đề card) — giữ nguyên, không gộp/không sửa.
  - Backend validation `meta.allow_inspection` + fallback legacy trong `ManualOrderService::normalizeMeta()` / `ShipmentService::resolveRequiredNote()` — giữ nguyên (vẫn là đường đọc cho đơn cũ đã tạo trước đây).
  - Cấu hình mặc định "cho xem/thử hàng" per tài khoản ĐVVC ở Cài đặt (`CarrierAccountsPage.tsx`) — không đổi, đây chính là nguồn hiệu lực duy nhất còn lại.

## 3. Luồng chính

### 3.1 Backend — endpoint `orders/lookup-by-customer`

- Route (`app/routes/api.php`, cạnh `orders/stats`): `GET orders/lookup-by-customer` → `OrderController::lookupByCustomer`, middleware `abilities:orders:read` (giống `orders.index`).
- Query params: `customer_id` (int, required), `exclude_order_id` (int, optional — id đơn đang sửa).
- Xử lý: gọi `OrderLookupService::recentByCustomer($tenant->id, $customerId, limit: 20)` (contract có sẵn, `Orders\Contracts\OrderLookupContract`), lọc bỏ phần tử có `id === exclude_order_id`, rồi:
  - `latest_order` = phần tử đầu tiên còn lại sau lọc (bất kỳ status), hoặc `null`.
  - `latest_returned_order` = phần tử đầu tiên còn lại có `statusCode === StandardOrderStatus::ReturnedRefunded->value`, hoặc `null`.
- Response: `{ "data": { "latest_order": OrderSummary::toArray()|null, "latest_returned_order": OrderSummary::toArray()|null } }` (tái dùng `toArray()` sẵn có: `id, number, status_code, status, date, ...`).
- `limit: 20` đủ để tìm ra đơn hoàn gần nhất trong lịch sử gần mà không quét toàn bộ; nếu không có đơn hoàn nào trong 20 đơn gần nhất → `latest_returned_order = null` (chấp nhận được, khách có > 20 đơn gần đây mà đơn hoàn cũ hơn nữa là trường hợp hiếm).

### 3.2 Frontend — hiển thị cảnh báo

- Hook mới `useOrderLookupByCustomer(customerId, excludeOrderId)` trong `lib/orders.ts` — React Query, `enabled: !!customerId`, không cần debounce riêng (chained sau `useCustomerLookup` đã debounce).
- Trong `CreateOrderPage.tsx`: sau khi `useCustomerLookup(phone)` trả về `customer.id`, gọi hook trên; khi ở chế độ sửa đơn truyền `excludeOrderId = id đơn hiện tại`.
- UI: `Alert type="warning" showIcon` đặt **ngay dưới** card Khách hàng (full layout) / card gộp "Khách & nhận hàng" (compact layout), tách biệt với popover ⚠ ở tiêu đề card:
  - Nếu có `latest_order`: dòng "SĐT này đã có đơn hàng trước đó: **#{number}**" — `{number}` là link, `onClick` mở `<OrderDetailModal orderId={latest_order.id} .../>` (mount 1 lần trong page, state local `viewDuplicateOrderId`).
  - Nếu có `latest_returned_order`: thêm dòng "Có đơn hoàn: **#{number}**" cũng bấm mở modal (state riêng hoặc dùng chung modal, set lại `orderId` khi bấm dòng nào). Hiển thị **cộng thêm**, không thay thế dòng trên — kể cả khi trùng cùng 1 đơn.
  - Không có `latest_order` (SĐT chưa từng có đơn / chưa match `customer_id`) → không hiển thị Alert.

### 3.3 Gỡ "Cho khách xem / thử hàng"

Xoá trong `CreateOrderPage.tsx`:
- `Form.Item name="required_note"` + `Segmented` (card Thanh toán).
- Const/type: `REQUIRED_NOTE_VALUES`, `RequiredNote`, `DEFAULT_REQUIRED_NOTE`, `toRequiredNote()`.
- Sticky localStorage prefs `OrderTogglePrefs.required_note` + seeding từ `carrierAccounts[].meta.defaults.required_note` lúc mount đơn mới.
- Prefill lúc sửa đơn (đọc `meta.required_note` / `meta.allow_inspection`).
- Field `meta.required_note` trong `buildPayload()` (create + sticky-persist-on-success) và bước reset khi bắt đầu đơn mới (compact tab mode).

Không đổi bất kỳ file backend nào ở mục này (đã xác nhận: `meta.required_note` chưa từng tới được `ManualOrderService`, không có hành vi backend nào phụ thuộc field này).

## 4. Edge case

- SĐT chưa từng có `Customer` khớp (`customer_id` null) → không gọi endpoint mới, không hiển thị Alert (giữ hành vi nhất quán với widget cảnh báo hiện có).
- Sửa đơn thủ công đã tồn tại: `exclude_order_id` loại chính đơn đang sửa ra khỏi kết quả, tránh tự cảnh báo về chính nó.
- `latest_order` và `latest_returned_order` trỏ cùng 1 đơn: vẫn hiển thị đủ 2 dòng (đơn giản, không cần logic dedupe).
- Đổi SĐT nhiều lần liên tiếp trong lúc gõ: hook Alert tự re-fetch theo `customer_id` mới (hoặc `null` nếu SĐT không khớp customer nào → Alert biến mất).

## 5. Testing

- Backend (Feature test, `tests/Feature/Orders/`): tạo 2-3 đơn cùng SĐT/`customer_id` với trạng thái khác nhau (bao gồm 1 đơn `returned_refunded`) → gọi `orders/lookup-by-customer` → đúng `latest_order`/`latest_returned_order`; kiểm `exclude_order_id` loại đúng đơn; kiểm tenant scoping (đơn tenant khác không lọt vào).
- Frontend: không có JS test runner trong repo (theo `test-verify-baseline`) — verify thủ công qua trình duyệt: nhập SĐT đã có đơn cũ → Alert hiện đúng mã đơn + bấm mở đúng modal; nhập SĐT có đơn hoàn → hiện thêm dòng; sửa đơn cũ → không tự cảnh báo về chính nó; xác nhận control "Cho khách xem/thử hàng" không còn trên form và tạo đơn vẫn thành công (required_note lấy theo default ĐVVC như trước).
