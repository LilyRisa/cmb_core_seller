# SPEC 0007: Trung tâm Cài đặt (Settings) — tài khoản/gói, trung tâm kết nối, nhân viên & vai trò chi tiết, cài đặt đơn hàng, cài đặt in

- **Trạng thái:** Draft (planning — chia thành các spec con khi triển khai từng phần; xem §10)
- **Phase:** chủ yếu Phase 3–6 (mỗi phần gắn phase khác nhau — xem từng mục)
- **Module backend liên quan:** Tenancy (chính — RBAC/nhân viên), Settings (mới — cấu hình theo tenant), Channels, Fulfillment, Billing, Orders
- **Tác giả / Ngày:** Team · 2026-05-17
- **Liên quan:** `docs/01-architecture/multi-tenancy-and-rbac.md`, `docs/02-data-model/overview.md`, `docs/03-domain/fulfillment-and-printing.md` (§4.4 template in), SPEC-0006 (ĐVVC/in), `docs/05-api/endpoints.md`. Hiện đã có: trang **Cài đặt → Thành viên** (`SettingsMembersPage`, role enum cố định) và **Cài đặt → ĐVVC** (`CarrierAccountsPage`, SPEC-0006). Spec này định hình toàn bộ khu **Cài đặt** và 5 nhóm tính năng người dùng yêu cầu.

## 1. Vấn đề & mục tiêu
Khu "Cài đặt" hiện chỉ có Thành viên + ĐVVC; sidebar có mục "Cài đặt" trỏ `ComingSoon`. Cần một **trung tâm cài đặt** đầy đủ, gồm 5 nhóm:
1. **Tài khoản & gói** — sửa hồ sơ cá nhân, thông tin gian hàng, xem/nâng cấp gói thuê bao.
2. **Trung tâm kết nối** — một nơi quản lý mọi kết nối ngoài: gian hàng (sàn TMĐT), đơn vị vận chuyển, và các "module phụ trợ" (Zalo OA, SMS, phần mềm kế toán, công cụ marketing… — làm sau).
3. **Nhân viên & vai trò chi tiết** — thêm nhân viên chỉ bằng **username + password** (không cần email); khi nhân viên đăng nhập dùng `username@<định-danh-shop>` để hệ thống biết thuộc gian hàng nào; **tạo vai trò tự đặt tên** và **chọn quyền** cho từng vai trò.
4. **Cài đặt đơn hàng** — danh mục **ký hiệu (tag) & cảnh báo** để gắn vào "đơn cần theo dõi"; cấu hình **thời gian lấy hàng** và các tham số theo yêu cầu của sàn; tham số xử lý đơn (tự xác nhận, kho mặc định, thời điểm trừ tồn…).
5. **Cài đặt in** — tạo & quản lý **mẫu in** (picking/packing/hoá đơn) — đáp ứng phần "template in tuỳ biến" còn nợ ở SPEC-0006.

Nguyên tắc: mỗi nhóm là một spec con triển khai riêng (§10), nhưng dùng chung **IA + RBAC + cơ chế lưu cấu hình** mô tả ở đây.

## 2. Kiến trúc thông tin (IA) — cây `/settings/*`
Trang Cài đặt là một layout có **menu trái** (nhóm) + nội dung phải. Route SPA:
```
/settings                       → redirect /settings/profile
/settings/profile               Hồ sơ cá nhân (đổi tên/email/mật khẩu/ảnh đại diện)   — mọi user
/settings/workspace             Thông tin gian hàng (tên, slug, logo, múi giờ, địa chỉ kho mặc định) — owner/admin
/settings/plan                  Gói & nâng cấp (xem gói hiện tại, hạn mức, hoá đơn, nâng cấp/đổi gói) — owner (admin xem)
/settings/connections           Trung tâm kết nối (tabs: Gian hàng | ĐVVC | Khác)     — `channels.view`/`fulfillment.view`
/settings/connections/channels  ↳ = trang Gian hàng hiện có (kết nối/ngắt/resync)
/settings/connections/carriers  ↳ = trang ĐVVC hiện có (SPEC-0006)
/settings/connections/services  ↳ Module phụ trợ (Zalo OA / SMS / kế toán …) — Coming soon
/settings/staff                 Nhân viên (danh sách + thêm bằng username/password)  — `members.manage`
/settings/roles                 Vai trò & quyền (vai trò built-in + vai trò tự tạo)  — `members.manage`
/settings/orders                Cài đặt đơn hàng (tags/cờ cảnh báo, pickup time, xử lý đơn) — `orders.settings`
/settings/print                 Cài đặt in (mẫu in)                                   — `fulfillment.print`
/settings/audit                 Nhật ký thao tác (audit log) — (sẵn có `audit_logs`)  — owner/admin   [tuỳ chọn, làm sau]
```
Sidebar chính: mục "Cài đặt" mở `/settings/profile`; bên trong có submenu các nhóm trên (ẩn nhóm theo quyền).

## 3. Nhóm 1 — Tài khoản, thông tin, gói, nâng cấp

### 3.1 Hồ sơ cá nhân (`/settings/profile`) — Phase 3 (nhỏ)
- Sửa `users.name`, `users.email` (nếu user có email — nhân viên username-only thì ẩn email, xem §5), đổi mật khẩu (yêu cầu mật khẩu hiện tại), ảnh đại diện (`users.avatar_url` — upload qua `MediaUploader`, disk `media.disk`/R2 như SKU image).
- Migration: `users` thêm `avatar_url` (string, null). API: `PATCH /api/v1/auth/profile` `{name?, email?, current_password?, password?, avatar?(multipart)}` hoặc tách `POST /auth/profile/avatar`. Đổi email phải unique; đổi mật khẩu invalidate các session khác (tuỳ chọn).
- RBAC: chính chủ — không cần permission đặc biệt.

### 3.2 Thông tin gian hàng (`/settings/workspace`) — Phase 3 (nhỏ)
- Sửa `tenants.name`, `tenants.slug` (chú ý: slug là **định danh shop** dùng cho login nhân viên — đổi slug ảnh hưởng username nhân viên, xem §5; cân nhắc khoá hoặc cảnh báo mạnh khi đổi), `tenants.settings.logo_url`, `tenants.settings.timezone` (mặc định `Asia/Ho_Chi_Minh`), địa chỉ kho mặc định (gửi/nhận hàng — dùng làm `from_address` cho ĐVVC, ghi vào `tenants.settings.from_address` hoặc `warehouses` mặc định).
- API: `PATCH /api/v1/tenant` `{name?, slug?, settings?}` (chỉ owner/admin). Slug đổi phải unique + chỉ `[a-z0-9-]`.

### 3.3 Gói & nâng cấp (`/settings/plan`) — **Phase 6** (Billing) — UI shell trước, logic sau
- Hiển thị: gói hiện tại (`subscriptions`), hạn mức & mức dùng (`usage_counters`: số gian hàng, số đơn/tháng, số nhân viên…), ngày hết hạn / gia hạn, lịch sử hoá đơn.
- Hành động: **nâng cấp / đổi gói** → trang chọn gói (`plans`) → thanh toán (VNPay/MoMo) → webhook xác nhận → cập nhật `subscriptions`.
- Phase này (3): chỉ làm **UI shell** đọc dữ liệu rỗng / "Mọi gian hàng đang ở gói dùng thử" + nút "Liên hệ nâng cấp". Bảng `plans/subscriptions/usage_counters/invoices` + cổng thanh toán = **SPEC riêng ở Phase 6** (Billing). Không hardcode hạn mức ở Phase 3.
- RBAC: `billing.view` (owner/admin/accountant), `billing.manage` (owner).

## 4. Nhóm 2 — Trung tâm kết nối (`/settings/connections`)

### 4.1 Mục tiêu
Gom mọi "kết nối ra ngoài" vào một nơi với 3 nhóm thẻ/tab:
- **Gian hàng (sàn TMĐT)** — `channel_accounts` (đã có: kết nối TikTok qua OAuth, resync, ngắt, alias tên; Shopee/Lazada Phase 4). Tái dùng nguyên trang Gian hàng hiện có, nhúng vào tab này.
- **Đơn vị vận chuyển** — `carrier_accounts` (đã có: SPEC-0006, Manual + GHN; GHTK/J&T sau). Tái dùng trang ĐVVC hiện có.
- **Khác / module phụ trợ** — *làm sau*: Zalo OA (gửi tin xác nhận đơn), SMS gateway, phần mềm kế toán (MISA/…), công cụ marketing, kho 3PL/WMS bên thứ ba… Phase 3 chỉ render danh sách thẻ "Sắp có" + mô tả; mỗi cái khi làm = một connector + spec riêng.

### 4.2 Dữ liệu & pattern
- Không gộp `channel_accounts` và `carrier_accounts` thành một bảng — chúng khác hành vi nhiều. Trung tâm kết nối chỉ là **một trang hợp nhất UI** đọc từ 2 nguồn + (tương lai) bảng `external_services` cho nhóm "Khác".
- Khi thêm module phụ trợ: theo đúng nếp connector/registry như Channels/Carriers — bảng `external_services` `(tenant_id, kind, name, credentials🔒, config, status)` + `ExternalServiceRegistry` + interface; **không** sửa core. (Bảng + registry tạo khi làm module đầu tiên của nhóm này.)
- API: `GET /api/v1/connections/summary` (đếm: gian hàng kết nối/cần reconnect, ĐVVC, dịch vụ khác) cho dashboard của trang; còn lại dùng các endpoint sẵn có (`/channel-accounts*`, `/carrier-accounts*`, `/carriers`).
- RBAC: tab Gian hàng cần `channels.view`/`channels.manage`; tab ĐVVC `fulfillment.view`/`fulfillment.carriers`; tab Khác `*` (owner/admin) cho tới khi có permission riêng.

## 5. Nhóm 3 — Nhân viên & vai trò chi tiết — **Phase 3** (quan trọng, làm sớm)

### 5.1 Nhân viên username-only
Yêu cầu: chủ shop **thêm nhân viên chỉ cần username + password**, không cần email; khi nhân viên đăng nhập dùng `username@<định-danh-shop>` để biết thuộc gian hàng nào.
- **`users` thêm cột:** `username` (string, null — chỉ dùng cho nhân viên), `is_staff_account` (bool, default false — phân biệt tài khoản nhân viên username-only với tài khoản chủ/email), `created_by_tenant_id` (int, null — nhân viên thuộc một gian hàng tạo ra). Index unique `(username)` **toàn cục** hoặc unique `(created_by_tenant_id, username)` — chọn **unique theo tenant** + login bằng `username@slug` (xem dưới) ⇒ tránh tranh username giữa các shop. Đặt unique `(created_by_tenant_id, username)`. `email` cho phép null khi `is_staff_account`.
- **Login nhân viên:** form đăng nhập chấp nhận:
  - email (như hiện tại) — cho tài khoản chủ;
  - **`username@<slug>`** — tách `@`, tìm tenant theo `slug` (hoặc theo `id` nếu nhập `username@<id>`), rồi tìm `users` `where created_by_tenant_id = tenant.id and username = ...`. Khớp + đúng password ⇒ đăng nhập, **set tenant hiện tại = tenant đó luôn** (nhân viên thường chỉ thuộc 1 tenant). Hiển thị gợi ý trên form: "Nhân viên đăng nhập bằng: `tên-đăng-nhập@<mã-gian-hàng>`".
  - (Tuỳ chọn UX) nếu nhập `username` trần (không `@`) và username đó chỉ tồn tại ở đúng 1 tenant ⇒ vẫn cho login; nếu trùng nhiều tenant ⇒ yêu cầu thêm `@slug`.
- **Tạo nhân viên:** `POST /api/v1/tenant/staff` (quyền `members.manage`) `{username, password, name?, role_id?|role?}` ⇒ tạo `users` (`is_staff_account=true`, `created_by_tenant_id=<tenant hiện tại>`, `email=null`), attach vào `tenant_user` với vai trò. Username: `[a-z0-9._-]{3,32}`. Trả về cả "chuỗi đăng nhập" `username@slug` để chủ shop đưa cho nhân viên. Cho phép **reset mật khẩu** nhân viên (`POST /tenant/staff/{userId}/reset-password`), **vô hiệu hoá** (`PATCH … {is_active}` — thêm cột `tenant_user.is_active` hoặc dùng `users` soft-delete giới hạn theo tenant), **đổi vai trò**, **gỡ khỏi gian hàng**.
- Tài khoản chủ (email) vẫn đăng ký/đăng nhập như cũ; có thể mời người khác bằng email (cơ chế `tenant/members` hiện có) — giữ song song với luồng staff username-only.

### 5.2 Vai trò chi tiết (custom roles)
Hiện RBAC là `Role` enum cứng (owner/admin/staff_order/staff_warehouse/accountant/viewer) + map permission cố định, resolve qua `Gate::before`. Cần cho phép **tạo vai trò tự đặt tên + chọn quyền**.
- **Bảng `roles`** (tenant-scoped): `(tenant_id, name, slug, permissions json (list<string>), is_system bool, description?, created_at)`. `is_system=true` cho 6 vai trò built-in (seed sẵn cho mỗi tenant **hoặc** giữ built-in trong enum và chỉ lưu vào bảng các vai trò custom — chọn **lưu cả built-in vào bảng `roles` khi tạo tenant** để UI thống nhất; built-in `is_system` không cho xoá, có thể clone).
- **`tenant_user.role`** (string) ⇒ đổi sang **`role_id` (FK roles)**. Migration chuyển dữ liệu: với mỗi tenant, tạo các `roles` system tương ứng từ `Role` enum + map `tenant_user.role` cũ → `role_id`. Giữ cột `role` cũ tạm thời (để rollback) hoặc xoá sau khi backfill xong.
- **Catalog quyền:** một danh sách phẳng các permission string gom theo module, dùng cho **trình chỉnh vai trò** (checkbox theo nhóm: Đơn hàng, Khách hàng, Tồn kho/SKU, Sản phẩm, Gian hàng, Giao hàng & in, Tài chính, Báo cáo, Cài đặt/Nhân viên, Dashboard). Định nghĩa ở `app/Modules/Tenancy/Support/PermissionCatalog.php` (mảng `['orders.view' => 'Xem đơn', 'orders.update' => '…', …]` + nhóm). Permission mới khi thêm module phải bổ sung vào catalog.
- **Resolver:** `Gate::before` (hoặc một `PermissionResolver` service) đọc `tenant_user.role_id` → `roles.permissions` (list) → kiểm `in_array($permission, $perms)` hoặc `'*'`. Hỗ trợ phủ định `!permission` như enum hiện tại. `User::can()` / `useCan()` FE không đổi giao diện.
- **API:** `GET /api/v1/tenant/roles` (list — system + custom), `POST /tenant/roles` `{name, permissions:[…], description?}`, `PATCH /tenant/roles/{id}` (đổi tên/quyền — không sửa được `is_system` thì chỉ xem; **không** cho hạ quyền owner để tự khoá mình), `DELETE /tenant/roles/{id}` (chỉ custom, không ai đang dùng ⇒ `409` nếu còn nhân viên). `GET /api/v1/permissions` (catalog cho UI). Quyền: `members.manage` (owner/admin). **Chốt chặn:** không cho phép tạo/sửa vai trò có quyền vượt quá quyền của người tạo (admin không tạo được vai trò có `tenant.delete` v.v.); luôn còn ít nhất 1 owner.
- **UI `/settings/roles`:** bảng vai trò → mở trình chỉnh quyền (checkbox theo nhóm + "chọn tất cả nhóm"); clone vai trò; gán vai trò cho nhân viên ở `/settings/staff`.
- Thêm permission strings cần thiết cho khu Cài đặt: `members.manage` (đã có ngầm qua `*`? — không, chưa có; thêm `members.manage` cho owner/admin), `orders.settings`, `print.templates` (hoặc tái dùng `fulfillment.print`), `billing.view`/`billing.manage`.

## 6. Nhóm 4 — Cài đặt đơn hàng (`/settings/orders`) — Phase 3 (vừa)

### 6.1 Danh mục ký hiệu (tag) & cảnh báo cho "đơn cần theo dõi"
- Hiện `orders.tags` (json list string) cho phép gắn tag tự do và `orders.has_issue`/`issue_reason` cho cờ vấn đề (hệ thống tự set "SKU chưa ghép"). Bổ sung **danh mục tag/cờ do người dùng định nghĩa**:
  - **Bảng `order_flags`** (tenant-scoped): `(tenant_id, key, label, color, kind: 'manual'|'auto', severity: 'info'|'warning'|'danger', auto_rule json?, is_active)`. `kind=manual` ⇒ nhân viên gắn tay ở chi tiết đơn (chọn từ danh mục thay vì gõ tự do); `kind=auto` ⇒ có `auto_rule` (điều kiện đơn giản, vd `{ field:'cod_amount', op:'>=', value:5000000 }`, hoặc `{ buyer_reputation:'risky' }`, hoặc `{ status_age_hours:{status:'processing', gt:24} }`) — một job/listener đánh giá khi `OrderUpserted` (hoặc scheduled) và set/clear flag trên đơn.
  - `orders` thêm cột `flags` (json list<key>) bên cạnh `tags` (tags = nhãn tự do; flags = từ danh mục, có màu/độ-nghiêm-trọng/hiển thị nổi bật + lọc được). Hoặc gộp: dùng `tags` + một bảng tra `order_flags` để biết tag nào có ý nghĩa "cảnh báo". Khuyến nghị: **cột `flags` riêng** cho rõ ràng + filter `/orders?flag=…` + đếm trong `/orders/stats`.
  - UI: trang Đơn hàng có bộ lọc "Cờ" (chip) như đã làm cho carrier/source; chi tiết đơn hiển thị flag nổi bật (badge màu) đầu trang; "đơn cần theo dõi" = preset filter `flags ⊇ {…}` hoặc `has_issue=true`.
- API: `GET/POST /api/v1/order-flags`, `PATCH/DELETE /order-flags/{id}` (`orders.settings`); `POST /orders/{id}/flags {add:[],remove:[]}` (`orders.update`). Listener `EvaluateOrderFlags` (queue `default`) cho auto-rule.

### 6.2 Thời gian lấy hàng & tham số theo yêu cầu sàn
- **Pickup time windows:** cấu hình khung giờ kho sẵn sàng cho ĐVVC tới lấy (vd "08:00–11:00" và "14:00–17:00", các ngày trong tuần), dùng khi tạo vận đơn (gửi lên ĐVVC nếu API hỗ trợ — GHN có `pick_shift`/`pickup_time`). Lưu ở `tenants.settings.pickup_windows` hoặc bảng `pickup_windows` `(tenant_id, weekday, from, to, carrier?)`.
- **Tham số xử lý đơn:** `tenants.settings.orders` JSON — `auto_confirm` (tự chuyển đơn sàn về `processing`?), `default_warehouse_id`, `deduct_on` (`shipped`|`created` — đồng bộ với `config/fulfillment.php` nhưng cho tenant override), `auto_create_shipment` (tự tạo vận đơn khi đơn vào `processing`?), `default_carrier_account_id`, `print_packing_on_ship` (tự sinh packing list khi tạo vận đơn?), thời hạn cảnh báo "đơn xử lý chậm" (giờ). Các tham số "theo yêu cầu của sàn" (vd thời gian phải bàn giao trước khi sàn huỷ đơn) hiển thị read-only/cảnh báo dựa trên metadata sàn.
- **Bảng `tenant_settings`** vs `tenants.settings` JSON: với cấu hình nhỏ/ít → `tenants.settings` JSON đủ; nếu nhiều/cần query → tách `tenant_settings` `(tenant_id, key, value json)`. Khuyến nghị bắt đầu bằng `tenants.settings` JSON có schema rõ + một `TenantSettings` value-object/service đọc-ghi (`app/Modules/Settings/TenantSettings.php`) để không rải `->settings['...']` khắp nơi.
- API: `GET/PATCH /api/v1/tenant/settings` (theo nhóm: `?group=orders|pickup|print|…`) — quyền tuỳ nhóm (`orders.settings`, `fulfillment.carriers`, …).

## 7. Nhóm 5 — Cài đặt in (`/settings/print`) — đáp ứng phần còn nợ của SPEC-0006 §2

### 7.1 Mẫu in (`print_templates`)
- Bảng (đã có trong `docs/02-data-model/overview.md`): `print_templates` `(tenant_id, type:'picking'|'packing'|'invoice', name, paper_size:'A4'|'A5'|'A6'|'80mm'|…, layout json, logo_url, is_default, is_active, created_at)`.
- **Lưu ý:** **vận đơn (label) không có mẫu tuỳ biến** — phải dùng đúng file ĐVVC/sàn cấp (domain doc rule 1). "Cài đặt in" chỉ áp cho **picking list / packing list / hoá đơn bán hàng** (phiếu tự render).
- **`layout` JSON** mô tả các block và vị trí: `header` (logo + tên shop + tiêu đề phiếu), `order_info` (mã đơn, ngày, nguồn), `recipient` (tên/SĐT/địa chỉ — có thể ẩn SĐT), `barcode`/`qr` (mã đơn / tracking), `items_table` (cột nào: tên, SKU, biến thể, SL, đơn giá, thành tiền), `totals`, `note`, `footer` (lời cảm ơn / chính sách đổi trả). Mỗi block: `{ enabled, order, options }`. Render: một engine HTML (Blade partial hoặc một renderer thuần) nhận `layout` + dữ liệu đơn → HTML → **Gotenberg** → PDF (tái dùng `GotenbergClient`, thay `PrintTemplates` dựng cứng của SPEC-0006 bằng renderer theo `print_templates`).
- **Mặc định:** seed sẵn 1 template `picking` + 1 `packing` `is_system`/`is_default` cho mỗi tenant (chính là layout của `PrintTemplates` hiện tại). Người dùng **clone & chỉnh**; chọn template nào là mặc định cho mỗi loại; khi tạo `print_jobs` không chỉ định template → dùng template mặc định của loại đó.
- API: `GET/POST /api/v1/print-templates`, `PATCH/DELETE /print-templates/{id}` (`print.templates` hoặc `fulfillment.print`); `POST /print-templates/{id}/preview` `{order_id}` → trả PDF mẫu (hoặc HTML) để xem trước. `POST /print-jobs` (SPEC-0006) thêm tham số `template_id?`.
- **UI `/settings/print`:** danh sách mẫu theo loại → trình chỉnh dạng form (bật/tắt block, sắp xếp, chọn cột bảng item, upload logo, chọn khổ giấy) + khung **xem trước** (gọi `…/preview`). (Không cần WYSIWYG kéo-thả ở v1 — form + preview là đủ.)
- Liên quan "lưu & in lại phiếu 90 ngày" (domain doc §8): vẫn là spec riêng; cài đặt thời hạn giữ phiếu (`print.retention_days`) đặt ở nhóm này.

## 8. Phân quyền (bổ sung vào `Role` enum + catalog §5.2)
| Permission | Ý nghĩa | Vai trò built-in được cấp |
|---|---|---|
| `members.manage` | Thêm/sửa/xoá nhân viên, vai trò | owner, admin |
| `tenant.settings` | Sửa thông tin gian hàng | owner, admin |
| `orders.settings` | Cài đặt đơn hàng (flags/pickup/xử lý) | owner, admin |
| `print.templates` | Tạo/sửa mẫu in | owner, admin, staff_warehouse(?) |
| `billing.view` / `billing.manage` | Xem / quản lý gói & thanh toán | view: owner/admin/accountant · manage: owner |
| `connections.manage` (tuỳ chọn) | Quản lý module phụ trợ nhóm "Khác" | owner, admin |
(Gian hàng đã có `channels.view/manage`; ĐVVC `fulfillment.view/carriers`.) Vai trò custom chọn từ catalog đầy đủ.

## 9. Bảng/cột mới (tổng hợp — chia theo spec con)
- `users`: `+username` (null, unique theo `created_by_tenant_id`), `+is_staff_account` (bool), `+created_by_tenant_id` (null), `+avatar_url` (null); `email` cho phép null khi staff. *(spec §5.1, §3.1)*
- `roles`: `(tenant_id, name, slug, permissions json, is_system, description?, is_active, timestamps)`. `tenant_user`: `role` → `role_id` (FK roles) + `+is_active` (bool). *(spec §5.2)*
- `order_flags`: `(tenant_id, key, label, color, kind, severity, auto_rule json?, is_active, timestamps)`; `orders.+flags` (json list<key>). *(spec §6.1)*
- `pickup_windows` *(tuỳ chọn — hoặc JSON)*: `(tenant_id, weekday, from, to, carrier?)`. *(spec §6.2)*
- `print_templates`: `(tenant_id, type, name, paper_size, layout json, logo_url, is_default, is_active, timestamps)`. *(spec §7 — bảng này đã có trong data-model overview, chỉ chưa migrate)*
- `tenant_settings` *(tuỳ chọn — hoặc dùng `tenants.settings` JSON)*: `(tenant_id, key, value json)`. *(spec §6.2)*
- *(Phase 6 / Billing)* `plans`, `subscriptions`, `usage_counters`, `invoices`, `payment_intents` — spec riêng.
- *(tương lai)* `external_services` cho module phụ trợ — spec riêng khi làm cái đầu tiên.

## 10. Lộ trình triển khai (mỗi mục = 1 spec con khi làm)
| # | Spec con đề xuất | Phase | Phụ thuộc |
|---|---|---|---|
| A | **0008 — Settings shell + Hồ sơ + Thông tin gian hàng** (IA §2, route tree, layout menu trái; §3.1, §3.2) | 3 | — |
| B | **0009 — Nhân viên username-only + Vai trò & quyền chi tiết** (§5) | 3 | A |
| C | **0010 — Trung tâm kết nối** (§4 — gom Gian hàng + ĐVVC + thẻ "Khác" Coming soon; `connections/summary`) | 3 | A |
| D | **0011 — Cài đặt đơn hàng** (§6 — order_flags + auto-rule + pickup windows + tham số xử lý đơn) | 3–4 | A, B |
| E | **0012 — Mẫu in tuỳ biến** (§7 — print_templates + renderer theo layout + preview; thay `PrintTemplates` cứng của SPEC-0006) | 3–4 | A, SPEC-0006 |
| F | **NNNN — Billing (gói/hạn mức/thanh toán)** (§3.3) | 6 | A |
| G | **NNNN — Module phụ trợ** (Zalo OA / SMS / kế toán …) — mỗi cái 1 spec | 6+ | C |
Ưu tiên: **A → B** trước (Settings shell + nhân viên/vai trò là nền cho mọi thứ còn lại), rồi C, D, E. F/G để Phase 6+.

## 11. Lưu ý chéo
- **Đổi `slug` gian hàng** làm đổi chuỗi đăng nhập của nhân viên (`username@slug`) ⇒ cảnh báo mạnh; cân nhắc cho phép login bằng cả `username@<id>` (id không đổi) song song.
- **Audit**: mọi thao tác trong Cài đặt (thêm/xoá nhân viên, đổi vai trò/quyền, đổi cài đặt đơn hàng, sửa mẫu in, đổi gói) ghi `audit_logs`.
- **Tenant scope**: tất cả bảng mới dùng `BelongsToTenant`; `roles`/`order_flags`/`print_templates` không bao giờ chia sẻ chéo tenant.
- **Migration `tenant_user.role` → `role_id`** là thay đổi schema nhạy cảm ⇒ làm cẩn thận, có backfill + giữ cột cũ một đợt; cập nhật `Gate::before`, `Role` enum (giữ làm "định nghĩa seed" cho vai trò system), `useCan()` (không đổi API).
- **Không over-engineer cấu hình**: ưu tiên `tenants.settings` JSON + value-object cho tới khi có nhu cầu query/báo cáo trên cấu hình mới tách bảng riêng.
