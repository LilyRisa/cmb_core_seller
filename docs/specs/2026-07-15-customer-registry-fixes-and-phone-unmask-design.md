# SPEC: Sửa hồ sơ khách hàng (tên mặc định đơn sàn, sửa tên/avatar) + bỏ che SĐT nội bộ

- **Trạng thái:** Draft
- **Phase:** Customers + Orders + Accounting (giao diện)
- **Module backend liên quan:** Customers (sở hữu), Orders (OrderResource/Order model), Accounting (PartyController), Tenancy (permission catalog)
- **Tác giả / Ngày:** 2026-07-15
- **Liên quan:** `0002-customer-registry-and-buyer-reputation.md`, `08-security-and-privacy.md`

## 1. Vấn đề & mục tiêu

Chủ shop kiểm tra đơn Lazada thấy nhiều khách hàng thiếu tên (Lazada không luôn trả `customer_first_name`/`last_name`) và một số khách bị tạo trùng vì SĐT dạng `840xxxxxxxxx` (mã nước 84 + số 0 giữ nguyên) chưa được chuẩn hoá **ở dữ liệu cũ**. Đồng thời, SĐT khách/người mua đang bị che (mask) ở nhiều màn nội bộ (Khách hàng, Đơn thủ công, Báo cáo kế toán) — chủ shop cần thấy đầy đủ vì đây là dữ liệu của họ. Cuối cùng, hồ sơ khách chưa cho sửa tên hay thêm avatar dù cột `avatar_url` đã tồn tại.

**Xác nhận qua code:** lỗi chuẩn hoá SĐT `84+0xxxxxxxxx` đã được vá ở commit `e221e4c0` (06/06/2026, `CustomerPhoneNormalizer::normalize()`) — khách trùng hiện có là dữ liệu **trước** thời điểm đó. Theo quyết định của chủ shop, **không backfill/gộp dữ liệu cũ** — chỉ áp dụng logic mới cho đơn/khách phát sinh từ nay.

## 2. Trong / ngoài phạm vi của spec này

- **Trong:**
  - Tên mặc định `"Khách hàng {Sàn} {ddmmyyyy}"` khi tạo customer mới từ đơn sàn thiếu tên người mua.
  - Sửa tên khách hàng + thêm/đổi avatar (API + UI), avatar hiển thị ở cả list và chi tiết.
  - Bỏ toàn bộ cơ chế che SĐT nội bộ (Customers, Orders — cả trong OrderResource lẫn UI đơn thủ công/chi tiết đơn, Accounting PartyController) + xoá quyền `customers.view_phone`.
- **Ngoài (không làm — theo xác nhận của chủ shop):**
  - Backfill/gộp khách hàng trùng đã tồn tại trước fix chuẩn hoá SĐT (06/06/2026).
  - Sửa `CustomerPhoneNormalizer` (đã đúng, không cần đổi).
  - Trang tra cứu vận đơn công khai (`PublicTrackingService`) — vẫn che SĐT/tên vì đó là bảo vệ người mua trước bên thứ 3, không phải dữ liệu nội bộ của chủ shop.

## 3. Câu chuyện người dùng / luồng chính

1. Đơn Lazada mới đồng bộ về, buyer không có `customer_first_name`/`last_name` lẫn `address_shipping.first_name`/`last_name` (hiếm nhưng xảy ra) → `CustomerLinkingService::linkOrder()` tạo customer mới với `name = "Khách hàng Lazada 15072026"` (ngày đặt đơn) thay vì `null`. Khách hàng vẫn được theo dõi lịch sử mua/uy tín theo SĐT như bình thường.
2. Chủ shop vào **Khách hàng** → thấy avatar (hoặc icon mặc định) + SĐT đầy đủ ở cột danh sách (không còn `090*****23`).
3. Bấm vào 1 khách → màn chi tiết hiện avatar lớn, nút đổi ảnh, tên cho sửa trực tiếp (inline), SĐT đầy đủ.
4. Ở **Đơn thủ công** (tạo/sửa) và **chi tiết đơn**: gợi ý khách theo tên, hộp thoại "khách bị chặn", card khách trong đơn — đều hiện SĐT đầy đủ.
5. Ở **Kế toán** (chọn khách cho phiếu thu 131): ô gợi ý hiện SĐT đầy đủ thay vì `****1234`.

## 4. Hành vi & quy tắc nghiệp vụ

- **Tên mặc định:** chỉ áp dụng lúc **tạo mới** customer (không ghi đè tên đã có ở lần cập nhật sau — hành vi hiện tại `name ?: fallback` được giữ nguyên, chỉ đổi fallback từ `null` sang chuỗi mặc định). Nhãn sàn: `lazada`→"Lazada", `tiktok`→"TikTok Shop", `shopee`→"Shopee", khác→tên viết hoa chữ đầu của `source`. Không áp dụng cho đơn `manual` (đơn manual đã bắt buộc có `buyer_name` mới tạo customer — quy tắc hiện có, không đổi).
- **Sửa tên/avatar:** quyền `customers.note` (tái dùng quyền "ghi chú khách" hiện có — cùng nhóm "chỉnh sửa hồ sơ phụ", không tạo quyền mới). Tên: bắt buộc, tối đa 120 ký tự. Avatar: ảnh (jpeg/png/webp), tối đa dung lượng theo `config('media.images.max_kb')` (giống ảnh nền desktop admin), lưu tenant-scoped qua `MediaUploader::storeImage()`.
- **Bỏ che SĐT:** không còn khái niệm "ai xem được SĐT" ở phạm vi nội bộ — mọi user vào được các trang Customers/Orders/Accounting (đã qua quyền view tương ứng của trang đó) đều thấy SĐT đầy đủ. Quyền `customers.view_phone` bị xoá khỏi catalog + khỏi danh sách quyền mặc định của role (`Role.php`).
- **Không đổi:** `Customer::phone`/`Order::buyer_phone` vẫn mã hoá at-rest (`encrypted` cast) — chỉ bỏ che lúc **hiển thị** ra API, không đổi cách lưu trữ.

## 5. Dữ liệu

- Không có migration mới (avatar_url đã tồn tại từ trước — SPEC 0038 v2).
- Không đổi domain event nào (CustomerLinked vẫn phát như cũ).

## 6. API & UI

- **`PATCH /api/v1/customers/{id}`** (mới): `{ name: string }` → cập nhật tên, trả `CustomerResource`. Quyền `customers.note`.
- **`POST /api/v1/customers/{id}/avatar`** (mới, multipart `file`): upload + set `avatar_url`, trả `CustomerResource`. Quyền `customers.note`.
- **`CustomerResource`**: `phone_masked` (xoá) → `phone` luôn trả đầy đủ (bỏ điều kiện `$canViewPhone`).
- **`CustomerController::lookup()`**: bỏ mask `addresses_meta[].phone`.
- **`OrderResource`**: `buyer_phone_masked` (xoá) → `buyer_phone` đầy đủ; `customerCard()` bỏ gate `customers.view_phone` (luôn `$withPhone = true` khi gọi `CustomerProfileContract::findById`).
- **`CustomerProfileDTO`**: gộp `phoneMasked`+`phoneFull` thành 1 field `phone` (đầy đủ); `toOrderCard()` trả `phone` thay vì `phone_masked`.
- **`CustomerProfileContract`**: bỏ tham số `$withFullPhone` khỏi `findById`/`findByPhone` (đơn giản hoá — 2 nơi gọi ở Messaging không truyền tham số này nên không vỡ).
- **`Accounting\PartyController::maskedPhone()`**: xoá, trả `$c->phone` đầy đủ trong `secondary`.
- **Permission catalog** (`PermissionCatalog.php`): xoá entry `customers.view_phone`; **`Role.php`**: xoá khỏi mảng quyền `StaffOrders`/`StaffCs`.
- **FE:** đổi `phone_masked` → `phone` ở `lib/customers.tsx` (type `Customer`/`CustomerCard`), `CustomersPage.tsx` (+ cột Avatar), `CustomerDetailPage.tsx` (+ avatar lớn, nút đổi ảnh, tên inline-edit), `CreateOrderPage.tsx` (gợi ý tên, modal khách bị chặn), `OrderDetailBody.tsx` (`buyer_phone_masked`→`buyer_phone`), `lib/orders.ts` (type `Order`).
- Cập nhật `docs/05-api/endpoints.md`.

## 7. Edge case & lỗi

- Đơn sàn thiếu cả `buyer_name` lẫn tên trong `shipping_address` → tên mặc định theo ngày **đặt đơn** (`order.placed_at`, fallback `created_at`), không phải ngày sync.
- Avatar upload lỗi (sai định dạng/quá dung lượng) → 422, không đổi `avatar_url` cũ.
- Khách đã `pii_anonymized_at` (đã ẩn danh) → API sửa tên/avatar vẫn cho phép gọi nhưng `CustomerResource` vẫn trả `name`/`avatar_url = null` như logic ẩn danh hiện có (không phá quy tắc ẩn danh).

## 8. Bảo mật & dữ liệu cá nhân

Đây là **nới lỏng có chủ đích** theo yêu cầu chủ shop (chủ dữ liệu của chính họ) — không phải lỗ hổng. Phạm vi nới lỏng: chỉ các màn **nội bộ** (yêu cầu đăng nhập + quyền view của module tương ứng). Trang tra cứu vận đơn **công khai** (không đăng nhập, bên thứ 3/khách hàng xem) giữ nguyên che SĐT/tên — không đụng `PublicTrackingService`. `phone`/`buyer_phone` vẫn mã hoá tại rest, chỉ bỏ che lúc trả API.

## 9. Kiểm thử

- **Feature:** `CustomerLinkingServiceTest`/`CustomerApiTest` — đơn sàn thiếu tên → tên mặc định đúng format; đơn đã có tên → không bị ghi đè bởi tên mặc định ở lần update sau.
- **Feature:** `CustomerApiTest` — `CustomerResource.phone` luôn đầy đủ (không còn phụ thuộc quyền); `PATCH /customers/{id}` đổi tên; `POST /customers/{id}/avatar` set avatar_url.
- **Feature:** `OrderResource`/`OrderApiTest` — `buyer_phone` đầy đủ, không còn `buyer_phone_masked`.
- Theo memory `test-verify-baseline`: chỉ chạy test liên quan Customers/Orders/Accounting (BE chưa green toàn cục).

## 10. Tiêu chí hoàn thành

- [ ] `CustomerLinkingService`: tên mặc định `"Khách hàng {Sàn} {ddmmyyyy}"` khi tạo mới thiếu tên (đơn sàn).
- [ ] `PATCH /customers/{id}` + `POST /customers/{id}/avatar` (BE + FE).
- [ ] Xoá masking + quyền `customers.view_phone` ở toàn bộ BE (Customers, Orders, Accounting, Tenancy) + FE (Customers, Orders, Accounting).
- [ ] FE: avatar ở list + chi tiết khách hàng, sửa tên inline.
- [ ] Test liên quan pass; pint/phpstan không phát sinh lỗi mới; `npm run lint && npm run typecheck && npm run build` xanh.
- [ ] `docs/05-api/endpoints.md` cập nhật.

## 11. Câu hỏi mở

- Không còn — các điểm mở đã chốt qua trao đổi với chủ shop (không backfill dữ liệu cũ; bỏ hẳn cơ chế che thay vì đổi quyền mặc định; quyền sửa hồ sơ tái dùng `customers.note`).
