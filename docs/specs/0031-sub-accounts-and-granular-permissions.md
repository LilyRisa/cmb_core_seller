# SPEC 0031: Tài khoản phụ & phân quyền chi tiết (custom roles)

- **Trạng thái:** Reviewed → Implemented
- **Phase:** (bổ sung)
- **Module backend liên quan:** Tenancy (chính), Models/User; ảnh hưởng gián tiếp mọi module qua `Gate`.
- **Tác giả / Ngày:** Team · 2026-06-05
- **Liên quan:** SPEC 0020 (admin user management), SPEC 0029 (mobile API access), SPEC 0022 (account verification); `01-architecture/modules.md` (Tenancy là base), `05-api/conventions.md`, `05-api/endpoints.md`.

## 1. Vấn đề & mục tiêu

Hiện phân quyền dựa trên **7 role cố định** (`Role` enum: owner/admin/staff_order/staff_warehouse/staff_cs/accountant/viewer). Mỗi role có một tập quyền **hard-code** trong `Role::permissions()`. Nhà bán không thể: (a) tự đặt tên role, (b) chọn quyền chi tiết theo từng tính năng (chỉ xem / thao tác), (c) tạo **tài khoản phụ cho nhân viên không có email** (vd nhân viên kho không dùng email). Đăng nhập hiện chỉ bằng email (unique, bắt buộc verify).

Mục tiêu:
1. **Role tuỳ biến theo tenant**: owner tự tạo/đổi tên role và chọn quyền chi tiết (giữ catalog quyền chi tiết theo hành động hiện có). `owner` là role built-in **toàn quyền**.
2. **Tài khoản phụ không cần email**: tạo theo định danh `username = {tên}@{mã shop 5 ký tự a-z0-9}`, owner đặt mật khẩu; đăng nhập được cả **web** lẫn **mobile** bằng username.
3. **Validate & enforce ở cấp API** để app mobile dựa vào (không phụ thuộc UI web).
4. **Không gãy các phần đã chạy**: toàn bộ controller đang `Gate::authorize('<chuỗi quyền>')` phải tiếp tục hoạt động nguyên trạng; thành viên hiện hữu giữ nguyên quyền sau migrate.

## 2. Trong / ngoài phạm vi

- **Trong:** bảng `roles` theo tenant + CRUD; `tenant_user.role_id`; cột `users.username/is_sub_account/created_by_user_id`; cột `tenants.code` (5 ký tự) + backfill; `PermissionCatalog` (nguồn chân lý chuỗi quyền, gom nhóm tính năng × hành động, nhãn VN, loại view/action); đổi `CurrentTenant::can()` đọc quyền từ `role_id`; seed role owner + preset (từ enum hiện tại) cho mọi tenant; API: catalog quyền, CRUD role, quản lý thành viên (thêm user email / tạo user phụ / đổi role / gỡ / đặt lại mật khẩu user phụ); login chấp nhận username HOẶC email (web + mobile token); `/me` trả `tenant.code` + role (id+tên) + `permissions[]`; UI web "Cài đặt → Thành viên & Phân quyền".
- **Ngoài (Phase sau):** luồng mời thành viên qua email; SSO; phân quyền theo từng gian hàng (đã có `channel_account_scope`, giữ nguyên, không mở rộng đợt này); role dùng chung nhiều tenant; audit UI; user phụ tự đổi mật khẩu / quên mật khẩu (không có email ⇒ owner đặt lại).

## 3. Luồng chính

**Tạo role tuỳ biến:** Owner (hoặc user có `team.manage`) → `GET /api/v1/tenant/permissions` (lấy catalog gom nhóm) → chọn quyền dạng ma trận → `POST /api/v1/tenant/roles {name, permissions:[...]}` → validate: tên unique trong tenant, mọi `permission` ∈ catalog & **không** thuộc nhóm owner-only → tạo `roles` row (`is_owner=false`, `is_system=false`). Sửa: `PUT /api/v1/tenant/roles/{role}` (cấm sửa role `is_owner`). Xoá: `DELETE` (chặn nếu còn thành viên gán role đó ⇒ yêu cầu đổi role trước; cấm xoá owner role).

**Tạo tài khoản phụ:** Owner → `POST /api/v1/tenant/members {mode:"sub", name, password, role_id}` → sinh `username = slug(name) + '@' + tenant.code` (đảm bảo unique trong tenant; trùng tên ⇒ thêm hậu tố số) → tạo `users{name, username, email:NULL, is_sub_account:true, created_by_user_id, email_verified_at:now, password:hashed}` → `tenant_user.attach(role_id)` → `AuditLog::record('tenant.member.created_sub')`. Thêm user email sẵn có: `POST .../members {mode:"email", email, role_id}` (như cũ).

**Đăng nhập (web + mobile):** field định danh đổi tên thành `login` (nhận email hoặc username). `AuthController@login` (web cookie) & `TokenAuthController@token` (mobile Bearer) resolve user: nếu `login` chứa `@<5 ký tự a-z0-9>` khớp 1 tenant.code ⇒ tìm theo `username`; ngược lại tìm theo `email`. Sai ⇒ lỗi 422 chuẩn. User phụ `email=NULL` nên **bỏ qua** chặn `verified`.

**Enforce mỗi request:** `EnsureTenant` middleware nạp `tenant_user` (kèm `role_id`) → `CurrentTenant::set($tenant, $membership)`. `Gate::before` → `CurrentTenant::can($ability)` → phân giải quyền từ **role được gán**: owner role ⇒ `'*'` (toàn quyền); role thường ⇒ kiểm tra `$ability` ∈ `role.permissions`. Mọi `Gate::authorize('...')` cũ chạy nguyên.

## 4. Hành vi & quy tắc nghiệp vụ

- **Owner toàn quyền:** role `is_owner=true` luôn trả `true` cho mọi ability (kể cả quyền owner-only). Không sửa/xoá được; mỗi tenant đúng 1 owner role. Tài khoản tạo tenant tự gán owner role.
- **Quyền owner-only (không gán cho role tuỳ biến):** `tenant.delete`, `tenant.transfer`, `billing.manage`. Validate role CRUD từ chối các quyền này (trừ owner role).
- **Catalog là nguồn chân lý:** mọi chuỗi quyền hợp lệ định nghĩa ở `PermissionCatalog`; **giữ nguyên tên chuỗi cũ** (`orders.view`, `orders.update`, `inventory.adjust`…) ⇒ không sửa controller. Thêm `team.manage` (quản lý thành viên + role). Quyền gom theo **tính năng** (Đơn hàng, Kho, Sản phẩm, Giao vận, Tin nhắn, Khách hàng, Tài chính, Kế toán, Mua hàng, Báo cáo, Kênh, Quảng cáo, Dashboard, Cài đặt, Thành viên), mỗi quyền có nhãn VN + loại `view|action`.
- **Quản lý role/thành viên:** cần `team.manage` (owner luôn có; preset "Quản trị" có sẵn). Không cho hạ/xoá owner cuối cùng của tenant.
- **Mã shop:** `tenants.code` 5 ký tự `[a-z0-9]`, sinh tự động (random, tránh ký tự dễ nhầm tuỳ chọn), **unique toàn hệ thống**, **immutable**. Backfill cho tenant hiện có bằng migration.
- **Username:** `unique` toàn cục (vì có thể trùng tên giữa các shop nhưng `@code` khác ⇒ duy nhất). Định dạng `^[a-z0-9._-]+@[a-z0-9]{5}$`. User phụ không có email ⇒ không nhận email thông báo/verify; owner đặt lại mật khẩu qua `POST /members/{user}/reset-password`.
- **Mobile validate cấp API:** mọi endpoint giữ `Gate::authorize`; lỗi quyền trả envelope `{error:{code:"forbidden", message, trace_id}}` (403). Role CRUD trả `{error:{code:"validation", details}}` (422) khi quyền ngoài catalog/owner-only. Mobile **không** được tin client-side; nguồn chặn là API.
- **Tương thích & migrate:** cột `tenant_user.role` (string enum cũ) **giữ lại** tạm; resolution ưu tiên `role_id`. Migration seed: mỗi tenant → tạo owner role + các preset (Quản trị/NV đơn/NV kho/CSKH/Kế toán/Chỉ xem) với quyền **giống hệt** `Role::permissions()` hiện tại → set `tenant_user.role_id` khớp `role` string đang có. Sau migrate: quyền mọi thành viên y nguyên.

## 5. Dữ liệu

**Module Tenancy** (sở hữu):
- `roles` (id, tenant_id, name, permissions jsonb (list chuỗi; owner role lưu `["*"]`), is_owner bool, is_system bool, timestamps; unique `(tenant_id, name)`; index `(tenant_id)`). `BelongsToTenant`.
- `tenant_user` (thêm: `role_id` nullable FK→roles nullOnDelete; **giữ** `role` string + `channel_account_scope`). Resolution: `role_id` trước, fallback `role` string (giai đoạn chuyển tiếp).
- `users` (thêm: `username` string nullable **unique**; `is_sub_account` bool default false; `created_by_user_id` nullable FK→users). `email` đổi sang **nullable** (user phụ NULL) — vẫn `unique` (Postgres cho phép nhiều NULL); login email chỉ áp dụng khi email != null.
- `tenants` (thêm: `code` char(5) **unique**, not null sau backfill).

**Lớp/đối tượng:**
- `PermissionCatalog` (Tenancy/Support): hằng danh sách `feature => [ ['key'=>'orders.view','label'=>'Xem đơn hàng','type'=>'view'], ... ]`; helper `all(): list<string>`, `assignable(): list<string>` (loại owner-only), `grouped(): array` (cho API), `isValid(string): bool`, `OWNER_ONLY: list<string>`.
- `Role` enum: **giữ lại** làm nguồn seed preset (`presetPermissions(Role): list<string>`); không còn là runtime source.
- `CurrentTenant`: `role()` trả `Role`-or-custom; thêm `roleModel(): ?RoleModel`; `can()` đọc từ `roleModel` (owner ⇒ true). Giữ API public cũ.

**Migration:** reversible. Thứ tự: tạo `roles`; thêm cột `tenants.code` (+ backfill sinh mã unique); thêm cột `users.username/is_sub_account/created_by_user_id` + đổi `email` nullable; thêm `tenant_user.role_id`; data-migration seed roles + map role_id. Không cascade xoá user khi xoá tenant (giữ ràng buộc hiện có).

## 6. HTTP API (envelope chuẩn `{data}/{error}`, `auth:sanctum` + tenant)

- `GET /api/v1/tenant/permissions` — catalog gom nhóm (mọi thành viên xem được để render UI ẩn/hiện). `{data:[{feature, label, permissions:[{key,label,type}]}]}`.
- `GET /api/v1/tenant/roles` — list role của tenant (`team.manage`). `POST` tạo; `PUT /{role}` sửa; `DELETE /{role}` xoá. Validate qua FormRequest dựa `PermissionCatalog::assignable()`.
- `GET /api/v1/tenant/members` — list (kèm role id+tên, username, is_sub_account).
- `POST /api/v1/tenant/members` — `mode=email` (thêm user email sẵn có) | `mode=sub` (tạo user phụ: name, password, role_id).
- `PUT /api/v1/tenant/members/{user}` — đổi role_id; `DELETE /{user}` gỡ khỏi tenant (chặn gỡ owner cuối).
- `POST /api/v1/tenant/members/{user}/reset-password` — owner/team.manage đặt lại mật khẩu user phụ.
- **Sửa login:** `POST /api/v1/auth/login` & `POST /api/v1/auth/token` nhận `login` (email|username) thay cho `email` (giữ tương thích: vẫn chấp nhận `email`). Cập nhật `05-api/endpoints.md`.
- `/me` (`GET /api/v1/auth/me`): tenant summary thêm `code`, `role:{id,name}`; giữ `permissions[]` (owner ⇒ `["*"]`).

## 7. Phân quyền (của chính tính năng này)

`team.manage` — xem/quản lý thành viên & role. Owner luôn có. Không có `team.manage` ⇒ 403 ở mọi endpoint mục 6 (trừ `GET /permissions` và `/me`). Chặn thao tác phá vỡ: không xoá/hạ owner cuối; không sửa/xoá owner role; không tự gán role owner cho người khác (chuyển owner là `tenant.transfer`, owner-only, ngoài phạm vi đợt này).

## 8. Frontend (web)

Trang **Cài đặt → Thành viên & Phân quyền** (`features/team` mirror backend): tab Thành viên (list, tạo user phụ — hiện mã shop, gán role, đặt lại mật khẩu, gỡ) + tab Vai trò (list role, tạo/sửa với **ma trận** tính năng × {Chỉ xem, Thao tác} bằng Checkbox/Radio — **không** `<Select>` theo chuẩn UI; icon @ant-design). Hook `usePermissions()` đọc `permissions[]` từ `/me` để ẩn/disable (nhấn mạnh: chặn thật ở API). Logic trong hooks, component "dumb" (chuẩn FE).

## 9. Test

- **Unit:** `PermissionCatalog` hợp lệ (không trùng key, owner-only ⊂ all); `CurrentTenant::can()` theo role DB; owner role bypass mọi ability; seed preset == `Role::permissions()` cũ (bảo chứng migrate không đổi quyền).
- **Feature:** tạo user phụ → login bằng username (web cookie + mobile token) thành công, email-less bỏ qua verify; gán role tuỳ biến → `Gate::authorize` cho phép quyền đã chọn & 403 quyền không chọn; role CRUD từ chối quyền ngoài catalog/owner-only (422); không xoá owner cuối; cô lập tenant (role/user phụ tenant A không thấy ở B); `/me` trả code + role + permissions.
- **Migration:** sau migrate, thành viên cũ giữ nguyên `permissions[]`.
- Lưu ý baseline test (memory): FE/BE không globally green; chạy `--filter` cho phần này.

## 10. Rủi ro & giảm thiểu

- **`email` nullable** có thể vướng chỗ assume email không-null (gửi mail, verify, `firstOrFail` theo email). Giảm thiểu: chỉ resolve theo email khi `login` là email; user phụ không vào luồng mail; rà các `where('email', …)` (đặc biệt reset-password, verify) chỉ áp dụng cho user có email.
- **Đổi field login `email`→`login`** có thể ảnh hưởng client cũ. Giảm thiểu: chấp nhận **cả** `email` và `login` (backward-compatible).
- **Resolution role kép (role_id vs role string):** giữ cột cũ + ưu tiên role_id; xoá cột `role` ở spec dọn dẹp sau khi chắc chắn.
- **Phình `Role` enum hết vai trò runtime:** giữ làm seed-only, đánh dấu rõ trong code để không ai thêm quyền vào enum nữa (thêm vào `PermissionCatalog`).
