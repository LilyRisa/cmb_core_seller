# Customer Registry Fixes + Phone Unmask — Implementation Plan

**Goal:** Tên mặc định cho khách sàn thiếu tên; sửa tên + avatar khách hàng; bỏ hẳn cơ chế che SĐT nội bộ (Customers/Orders/Accounting). Không backfill dữ liệu cũ, không đổi `CustomerPhoneNormalizer`, không đụng public tracking.

**Spec:** `2026-07-15-customer-registry-fixes-and-phone-unmask-design.md`

## Global Constraints

- Lệnh chạy từ `app/`. Namespace `CMBcoreSeller\` → `app/app/`.
- Module giao tiếp qua Contracts/ hoặc domain events — không `use` Services nội bộ module khác.
- UI: `@ant-design/icons` (không emoji), hạn chế `<Select>` (Radio/Segmented khi tập nhỏ).
- Test baseline: BE chưa green toàn cục — chỉ chạy test liên quan Customers/Orders/Accounting.

---

## Task 1: Tên mặc định khi đơn sàn thiếu tên người mua

**Files:** Modify `app/app/Modules/Customers/Services/CustomerLinkingService.php`; Test `app/tests/Feature/Customers/CustomerLinkingServiceTest.php` (tạo mới nếu chưa có, hoặc thêm case vào test Customers hiện có).

- Thêm private helper `defaultNameFor(Order $order): string` → `"Khách hàng {Nhãn sàn} {ddmmyyyy}"` dùng `$order->placed_at ?: $order->created_at`, nhãn sàn qua `match($order->source) { 'lazada'=>'Lazada', 'tiktok'=>'TikTok Shop', 'shopee'=>'Shopee', default=>ucfirst($order->source) }`.
- Trong nhánh tạo mới (khi `! $customer`) của `linkOrder()`: `'name' => $order->buyer_name ?: ($order->source === 'manual' ? null : $this->defaultNameFor($order))`. (Nhánh `manual` không bao giờ tới đây vì đã bị chặn ở đầu method khi thiếu `buyer_name` — giữ điều kiện tường minh để không đổi hành vi manual nếu code refactor sau này.)
- Nhánh update (khách đã tồn tại) **giữ nguyên** `'name' => $customer->name ?: ($order->buyer_name ?: null)` — không áp tên mặc định ở update (chỉ áp lúc tạo mới, theo spec §4).
- Test: đơn lazada không có buyer_name/ship name → customer mới có `name` bắt đầu bằng `"Khách hàng Lazada "`; đơn thứ 2 cùng khách (đã có tên thật do user sửa sau) không bị ghi đè.

## Task 2: Bỏ che SĐT nội bộ — Backend

**Files:**
- Modify: `app/app/Modules/Customers/Models/Customer.php` (xoá `maskedPhone()`)
- Modify: `app/app/Modules/Customers/Http/Resources/CustomerResource.php` (`phone_masked`→bỏ; `phone` luôn trả khi `!anonymized`)
- Modify: `app/app/Modules/Customers/Http/Controllers/CustomerController.php` (`lookup()` — bỏ closure `maskPhone`, trả `addresses` nguyên `addresses_meta`)
- Modify: `app/app/Modules/Customers/DTO/CustomerProfileDTO.php` (gộp `phoneMasked`+`phoneFull` → `phone`; `fromModel()` bỏ tham số `$withFullPhone`; `toOrderCard()` trả `phone`)
- Modify: `app/app/Modules/Customers/Contracts/CustomerProfileContract.php` (`findById`/`findByPhone` bỏ tham số `bool $withFullPhone = false`)
- Modify: `app/app/Modules/Customers/Services/CustomerProfileResolver.php` (khớp signature mới)
- Modify: `app/app/Modules/Orders/Models/Order.php` (xoá `maskedBuyerPhone()`)
- Modify: `app/app/Modules/Orders/Http/Resources/OrderResource.php` (`buyer_phone_masked`→`buyer_phone`; `customerCard()` bỏ biến `$withPhone`/gate quyền, gọi `findById($tenantId, $customerId)` không tham số thừa)
- Modify: `app/app/Modules/Accounting/Http/Controllers/PartyController.php` (xoá `maskedPhone()`, `customers()` trả `'secondary' => $c->phone`)
- Modify: `app/app/Modules/Tenancy/Support/PermissionCatalog.php` (xoá dòng `customers.view_phone`)
- Modify: `app/app/Modules/Tenancy/Enums/Role.php` (xoá `'customers.view_phone'` khỏi 2 mảng quyền)
- Test: `app/tests/Feature/Customers/CustomerApiTest.php` — sửa/xoá assertion liên quan `phone_masked`/`view_phone`, thêm assertion `phone` luôn đầy đủ không cần quyền.
- Test: kiểm `app/tests/Feature/Orders/*` có assertion `buyer_phone_masked` không — sửa theo `buyer_phone`.

**Lưu ý khi sửa:** `Customer::$hidden = ['phone','email']` vẫn giữ nguyên (chỉ chặn serialize mặc định của model, không ảnh hưởng Resource truy cập tường minh `$this->phone`). `Order::$hidden = ['buyer_phone','raw_payload']` tương tự — giữ nguyên, `OrderResource` truy cập tường minh vẫn lấy được giá trị đã giải mã.

## Task 3: Sửa tên + avatar khách hàng

**Files:**
- Modify: `app/app/Modules/Customers/Services/CustomerService.php` — thêm `updateProfile(Customer $customer, string $name): Customer` (forceFill `name`, save) và `updateAvatar(Customer $customer, string $avatarUrl): Customer`.
- Modify: `app/app/Modules/Customers/Http/Controllers/CustomerController.php` — thêm `update(Request $request, int $id, CustomerService $service)` (`PATCH`, validate `name` required|string|max:120, quyền `customers.note`) và `avatar(Request $request, int $id, CustomerService $service, MediaUploader $uploader)` (multipart `file`, validate ảnh giống `AdminDesktopBackgroundController::media`, quyền `customers.note`).
- Modify: `app/routes/api.php` — thêm 2 route cạnh nhóm `customers/{id}/...` hiện có:
  ```php
  Route::patch('customers/{id}', [CustomerController::class, 'update'])->whereNumber('id')->name('customers.update');
  Route::post('customers/{id}/avatar', [CustomerController::class, 'avatar'])->whereNumber('id')->name('customers.avatar');
  ```
- Test: `app/tests/Feature/Customers/CustomerApiTest.php` — thêm case PATCH đổi tên (200 + resource cập nhật), POST avatar (200 + `avatar_url` đổi), 403 khi thiếu quyền `customers.note`.

## Task 4: FE — bỏ hiển thị SĐT che

**Files:**
- Modify: `app/resources/js/lib/customers.tsx` — type `CustomerCard`/`Customer`: xoá `phone_masked`, `phone` không còn comment "only when caller has customers.view_phone" (luôn có).
- Modify: `app/resources/js/pages/CustomersPage.tsx` — cột "Khách hàng": `c.phone_masked` → `c.phone`.
- Modify: `app/resources/js/pages/CustomerDetailPage.tsx` — `customer.phone ?? customer.phone_masked` → `customer.phone`.
- Modify: `app/resources/js/pages/CreateOrderPage.tsx` — dòng ~519 (`blockedCustomer.phone_masked`) và ~587 (`c.phone_masked` trong `nameOptions`) → `.phone`.
- Modify: `app/resources/js/components/OrderDetailBody.tsx` — dòng ~205 `order.buyer_phone_masked` → `order.buyer_phone`.
- Modify: `app/resources/js/lib/orders.ts` (hoặc `.tsx` — xác định path đúng bằng `grep -rn "buyer_phone_masked" app/resources/js`) — type `Order`: `buyer_phone_masked` → `buyer_phone`.
- Kiểm tra thêm bất kỳ chỗ nào khác còn dùng `phone_masked`/`buyer_phone_masked` bằng `grep -rn "phone_masked" app/resources/js` sau khi sửa — phải về 0 kết quả.

## Task 5: FE — avatar + sửa tên khách hàng

**Files:**
- Modify: `app/resources/js/lib/customers.tsx` — thêm `useUpdateCustomerProfile(id)` (PATCH name) và `useUploadCustomerAvatar(id)` (POST multipart FormData), invalidate cache khách hàng.
- Modify: `app/resources/js/pages/CustomersPage.tsx` — cột "Khách hàng": thêm `<Avatar src={c.avatar_url} icon={<UserOutlined/>}>` trước tên (giống pattern đã dùng ở `CreateOrderPage.tsx` gợi ý tên).
- Modify: `app/resources/js/pages/CustomerDetailPage.tsx` — thêm khối avatar lớn (Avatar size 64-80 + `Upload` ẩn input, gọi `useUploadCustomerAvatar`) và tên cho sửa inline (click để hiện `Input` + nút lưu, gọi `useUpdateCustomerProfile`) trong `PageHeader`/card "Thông tin". Gate nút sửa bằng `useCan('customers.note')`.

## Task 6: Docs + quality gate

**Files:** Modify `docs/05-api/endpoints.md` (thêm PATCH/avatar endpoint, xoá nhắc tới `customers.view_phone`/`phone_masked` nếu có, cập nhật mô tả `phone` luôn đầy đủ).

- Run: `cd app && vendor/bin/pint --test app/Modules/Customers app/Modules/Orders app/Modules/Accounting app/Modules/Tenancy`
- Run: `cd app && vendor/bin/phpstan analyse` (không phát sinh lỗi mới so baseline)
- Run: `cd app && php artisan test --filter=Customer` và `--filter=Order` (theo `test-verify-baseline`: 7 fail GHN/fulfillment sẵn có trên main không liên quan — bỏ qua)
- Run: `cd app && npm run lint && npm run typecheck && npm run build`
- Commit theo từng task hoặc gộp cuối (tuỳ khối lượng diff thực tế).
