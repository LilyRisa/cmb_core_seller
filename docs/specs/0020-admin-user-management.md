# SPEC 0020: Admin hệ thống — Quản lý tenant & can thiệp khi vượt hạn mức

- **Trạng thái:** Draft (2026-05-15)
- **Phase:** 6.4 (mở rộng) — vận hành Billing SaaS
- **Module backend liên quan:** Tenancy (super-admin flag + audit), Billing (over-quota grace + lock middleware), Channels (force-delete reuse có sẵn)
- **Tác giả / Ngày:** Team · 2026-05-15
- **Liên quan:** SPEC 0018 §3.6 + §7 (cập nhật xử lý "vượt hạn mức"), `01-architecture/multi-tenancy-and-rbac.md` §3, §5, `05-api/conventions.md`, `06-frontend/overview.md`, `07-infra/queues-and-scheduler.md`, `08-security-and-privacy.md`.

## 1. Vấn đề & mục tiêu

Sau khi Billing SaaS (SPEC 0018) chạy thật, có 2 tình huống bộ phận hỗ trợ phải xử lý tay nhưng hiện chưa có công cụ:

1. **Khách hết hạn / hạ gói khi đang vượt hạn mức** (vd: shop kết nối 10 sàn ở gói Business, gói rớt về Starter chỉ cho 2). SPEC 0018 §7 hiện chỉ "chặn connect mới + cảnh báo", không khoá — khách vẫn dùng được 8 sàn vượt mức bình thường. Khách yêu cầu hỗ trợ gỡ kênh hộ ⇒ hiện không có endpoint admin.
2. **Khách muốn hạ gói (downgrade) khi đang dùng nhiều kênh**: BillingService chặn cứng `DOWNGRADE_NOT_ALLOWED` (§7); khách phải đợi hết kỳ. Trong thực tế nhân viên hỗ trợ cần thao tác hộ.

**Mục tiêu:**
1. Trang **/admin** cho super-admin (nhân viên vận hành SaaS) — xuyên suốt mọi tenant, KHÔNG cần `X-Tenant-Id`.
2. Super-admin xem được mọi tenant + user + subscription + channel accounts + usage; xoá liên kết kênh / đổi gói / khoá/mở tenant theo yêu cầu khách (có audit).
3. **Thắt chặt logic "vượt hạn mức"**: phát hiện over-quota ⇒ ghi `over_quota_warned_at` + banner cảnh báo 2 ngày; sau 48h vẫn vượt ⇒ middleware `plan.over_quota_lock` **chặn mọi thao tác ghi** (POST/PATCH/DELETE) trừ whitelist (billing, gỡ kênh, admin, auth) — đảm bảo khách không "ăn không" tính năng cao cấp với gói thấp.
4. Thiết kế **mở rộng được**: middleware nhận list resource (`channel_accounts` v1; sau thêm `users`, `skus`, `orders_per_month`…).

## 2. Trong / ngoài phạm vi

**Trong (SPEC này):**
- Cột `users.is_super_admin` (boolean default false) + Artisan command `admin:promote {email}` / `admin:demote {email}`.
- Middleware `super_admin` (chặn nếu user không phải super admin).
- Module `Admin` mới (`app/Modules/Admin`) — chỉ phụ thuộc `Tenancy` (đọc) + `Billing` (sửa subscription) + `Channels` (gỡ liên kết). Không thêm bảng nghiệp vụ riêng.
- Endpoints `/api/v1/admin/*`: list tenants, detail tenant, list users (cross-tenant), force-delete channel, đổi plan (force, có lý do), khoá/mở tenant.
- Cột `subscriptions.over_quota_warned_at` (timestamp nullable) — đánh dấu lần đầu phát hiện vượt mức.
- Middleware `plan.over_quota_lock` (nhận csv resource, vd `plan.over_quota_lock:channel_accounts`) — chặn write methods khi `now() - over_quota_warned_at >= 48h` & vẫn vượt.
- Scheduler `subscriptions:check-over-quota` (mỗi giờ) — set/clear `over_quota_warned_at`.
- FE trang `/admin/tenants` (list + drawer chi tiết + actions); banner "vượt hạn mức / sắp khoá" trong `AppLayout` cho user thường.
- Audit log mọi action super-admin (action prefix `admin.*`).
- Tests: feature (super-admin auth, force-delete channel, over-quota grace timer, post-grace lock).

**Ngoài (làm sau / spec khác):**
- Auto-disconnect khi vượt mức (KHÔNG làm — vẫn để user/admin chủ động chọn).
- Add-on pack mua thêm slot lẻ.
- Quota nhiều cấp bậc (`users`, `skus`, …) — chỉ chuẩn bị structure, dữ liệu thực sẽ thêm theo nhu cầu.
- Self-serve downgrade (vẫn giữ chặn ở `BillingService`; downgrade qua super-admin).
- Auto-restore tenant đã suspended (admin reactivate tay).
- Hệ thống thông báo email/Zalo "sắp khoá" — sau khi có module Notifications (Phase 6.5).

## 3. Câu chuyện người dùng / luồng chính

### 3.1 Khởi tạo super-admin
```
$ php artisan admin:promote support@cmbcore.vn
✔ User support@cmbcore.vn đã được nâng quyền super-admin.
```
- Lần đầu: chạy command với email đã đăng ký. Idempotent (gọi lại = no-op).
- Demote: `php artisan admin:demote support@cmbcore.vn`.

### 3.2 Super-admin đăng nhập + vào trang admin
1. Đăng nhập bằng tài khoản thường (`POST /api/v1/auth/login`).
2. Sidebar SPA hiện mục **"Admin hệ thống"** (chỉ user có `is_super_admin=true` thấy — kiểm `useAuth().data.is_super_admin`).
3. Click ⇒ `/admin/tenants` (KHÔNG cần chọn tenant).

### 3.3 Hỗ trợ khách gỡ kênh khi vượt hạn mức
1. Khách báo: "shop em hết hạn Pro, rớt trial 2 sàn nhưng còn 5 liên kết — gỡ giúp em 3 sàn này".
2. Admin mở `/admin/tenants` ⇒ tìm theo email khách ⇒ click row ⇒ Drawer chi tiết.
3. Tab "Gian hàng" liệt kê 5 channel accounts (kèm trạng thái + `last_synced_at`); admin tick 3 cái cần xoá ⇒ "Xoá kết nối" (popup xác nhận + nhập lý do).
4. BE: `DELETE /api/v1/admin/tenants/{id}/channel-accounts/{caid}` — dùng lại `ChannelConnectionService::deleteWithOrders` (đã có sẵn, xoá kết nối + đơn + sku_mappings + nhả tồn — y hệt user tự xoá ở `/channels`).
5. Audit log ghi `admin.channel_account.delete` với `{tenant_id, channel_account_id, reason}`.
6. Usage tự recompute (đã có listener `BumpChannelAccountCounter` listen `ChannelAccountRevoked` — không cần code mới).
7. `subscriptions:check-over-quota` chạy lần tới sẽ clear `over_quota_warned_at` (vì hết vượt).

### 3.4 Đổi gói khách theo yêu cầu
1. Drawer ⇒ tab "Gói thuê bao" ⇒ hiển thị plan hiện tại + nút "Đổi gói".
2. Modal: chọn plan + cycle + lý do (bắt buộc, ≥10 ký tự).
3. BE: `POST /api/v1/admin/tenants/{id}/subscription` `{plan_code, cycle, reason}` — admin **bỏ qua** check `DOWNGRADE_NOT_ALLOWED` của BillingService (gọi `ActivateSubscriptionService::swapPlan` trực tiếp, không qua `BillingService::createUpgradeInvoice`).
4. Subscription cũ `cancel_at = now`, subscription mới `active` `current_period_end = now + cycle days`. KHÔNG tạo invoice (admin tự xử lý thanh toán bên ngoài; nếu cần invoice ⇒ tạo tay sau).
5. Audit log `admin.subscription.change` `{from_plan, to_plan, reason}`.
6. Nếu plan mới có `max_channel_accounts < current_channels` ⇒ middleware `over_quota` set timer ở scheduler kế tiếp ⇒ user thấy banner.

### 3.5 Vòng đời "vượt hạn mức"
```
T+0  ──── tenant downgrade / kết nối vượt mức được admin cho phép (vd lock cũ bypass)
     │    next run của scheduler check-over-quota:
     │      detect over-quota ⇒ set subscriptions.over_quota_warned_at = now()
     │      banner xuất hiện ở mọi trang user thường: "Còn 2 ngày trước khi khoá"
     │
T+48h ─── scheduler chạy tiếp:
     │      still over ⇒ KHÔNG đổi gì (banner đổi sang đỏ "đã khoá")
     │      mọi request POST/PATCH/DELETE qua middleware ⇒ 402 PLAN_QUOTA_EXCEEDED
     │
khi user / admin gỡ kênh ⇒ scheduler kế tiếp:
            no longer over ⇒ over_quota_warned_at = null, banner biến mất, lock tan
```

## 4. Hành vi & quy tắc nghiệp vụ

1. **Super-admin = global**: KHÔNG scope theo tenant; quyền `is_super_admin=true` đè mọi check (`Gate::before` nếu super-admin ⇒ allow everything). Nhưng: super-admin **vẫn phải ghi audit_logs** cho mọi action ghi (admin hành xử như user, không phải god mode âm thầm).
2. **Audit log**: `action` luôn prefix `admin.*` (vd `admin.channel_account.delete`, `admin.subscription.change`, `admin.tenant.suspend`). `tenant_id` ở audit_log = target tenant (KHÔNG phải tenant của admin — admin không có tenant).
3. **Whitelist khi `over_quota_lock` đang khoá**:
   - GET (mọi route) — user vẫn xem được data.
   - `/api/v1/billing/*` — để user nâng cấp (kể cả POST checkout).
   - `DELETE /api/v1/channel-accounts/{id}` — để user tự gỡ kênh thừa.
   - `/api/v1/auth/*` — login/logout/profile.
   - `/api/v1/admin/*` — super-admin vẫn vào bình thường.
   - `/api/v1/tenant` GET, `/api/v1/tenants` GET — workspace switcher vẫn hoạt động.
4. **Idempotency**:
   - `over_quota_warned_at` set 1 lần khi từ "ok ⇒ vượt"; clear khi từ "vượt ⇒ ok". Không reset timer nếu vẫn đang vượt (tránh user mở-đóng-mở để gia hạn vô hạn).
   - `admin:promote/demote` idempotent.
5. **Trạng thái tenant**: thêm `tenants.status='suspended'` (đã có cột `status` từ SPEC 0011) — super-admin có thể tạm khoá tenant nguy hiểm (vi phạm điều khoản). `EnsureTenant` middleware kiểm: `status='suspended'` ⇒ `403 TENANT_SUSPENDED`.
6. **Tính over-quota**: dùng lại `UsageService::count($tenantId, 'channel_accounts')` (đã có). So với `plan.limits.max_channel_accounts`. `-1` = unlimited (không bao giờ over).
7. **Grace period config**: `config('billing.over_quota_grace_hours', 48)` — đổi qua env `BILLING_OVER_QUOTA_GRACE_HOURS`.
8. **Phân quyền**: chỉ `is_super_admin=true` ⇒ `/admin/*`. Không có "đại lý" / nhiều cấp bậc admin v1.

## 5. Dữ liệu

### 5.1 Migration mới (2 file)

```
users.is_super_admin                    boolean default false, index khi true
subscriptions.over_quota_warned_at      timestamptz nullable, index
```

Cả 2 đều reversible (drop column).

### 5.2 Bảng KHÔNG đổi
- Reuse `audit_logs` có sẵn (đã hỗ trợ `tenant_id` nullable không? — kiểm: hiện migration không cho nullable; thêm migration đổi `tenant_id` nullable để super-admin ghi log không gắn tenant cũng được — nhưng v1 ta luôn ghi `tenant_id` = target tenant, nên KHÔNG cần đổi schema).

### 5.3 Domain event
- `TenantSuspended($tenant, $reason, $adminUserId)` — phát khi admin suspend tenant. Phase này chưa listen ở đâu (chỉ ghi audit).
- `SubscriptionForceChanged($subscription, $fromPlan, $reason)` — khi super-admin đổi plan bỏ qua flow tự nhiên.

## 6. API & UI

### 6.1 Endpoints mới (`/api/v1/admin/*`)

| Method | Path | Auth | Mô tả |
|---|---|---|---|
| GET | `/api/v1/admin/tenants` | sanctum + `super_admin` | List tenants (search q theo name/slug/owner email; filter `over_quota=1`, `suspended=1`; page, per_page≤100). Trả `[TenantAdminResource]` gồm `id, name, slug, status, created_at, owner:{id,name,email}, subscription:{plan_code, status, current_period_end, over_quota_warned_at}, usage:{channel_accounts:{used,limit, over:bool}}`. |
| GET | `/api/v1/admin/tenants/{id}` | sanctum + `super_admin` | Chi tiết: tenant + subscription + channel_accounts list + members list + recent audit_logs (10 dòng admin gần nhất). |
| GET | `/api/v1/admin/users` | sanctum + `super_admin` | List users hệ thống (`q` theo email/name, `is_super_admin?`, page). Trả `[{id,name,email,is_super_admin,tenants:[{id,name,role}]}]`. |
| DELETE | `/api/v1/admin/tenants/{tid}/channel-accounts/{caid}` | sanctum + `super_admin` | Force delete: body `{reason: string ≥10}`. Dùng `ChannelConnectionService::deleteWithOrders`. Audit `admin.channel_account.delete`. |
| POST | `/api/v1/admin/tenants/{tid}/subscription` | sanctum + `super_admin` | Đổi plan: body `{plan_code, cycle: monthly\|yearly, reason: string ≥10}`. Bypass `DOWNGRADE_NOT_ALLOWED`. Tạo subscription mới `active`, cancel sub cũ. Audit `admin.subscription.change`. |
| POST | `/api/v1/admin/tenants/{tid}/suspend` | sanctum + `super_admin` | body `{reason: string ≥10}`. Set `tenants.status='suspended'`. Audit `admin.tenant.suspend`. |
| POST | `/api/v1/admin/tenants/{tid}/reactivate` | sanctum + `super_admin` | Set `tenants.status='active'`. Audit `admin.tenant.reactivate`. |

Tất cả routes `/api/v1/admin/*` qua middleware `auth:sanctum` + alias mới `super_admin` (KHÔNG có `tenant` — vì admin global).

### 6.2 Sửa endpoint hiện có

- `GET /api/v1/auth/me` — thêm field `is_super_admin: bool` vào response.
- `GET /api/v1/billing/subscription` — `data` thêm `over_quota_warned_at: string|null` + `over_quota_locked: bool` (đã quá grace). Banner FE đọc từ đây.

### 6.3 Codes lỗi mới
- `PLAN_QUOTA_EXCEEDED` (`402`) — middleware lock sau grace. Details: `{resource, current, limit, plan_code, warned_at}`.
- `TENANT_SUSPENDED` (`403`) — EnsureTenant chặn tenant suspended.
- `SUPER_ADMIN_REQUIRED` (`403`) — không phải super-admin.

### 6.4 FE (`resources/js/`)
- `lib/admin.tsx` — hooks: `useAdminTenants(filters)`, `useAdminTenant(id)`, `useAdminUsers(filters)`, `useAdminDeleteChannel()`, `useAdminChangePlan()`, `useAdminSuspend()`, `useAdminReactivate()`.
- `pages/admin/AdminTenantsPage.tsx` — bảng tenants + filter (search, over_quota, suspended) + click row mở Drawer.
- `pages/admin/AdminTenantDrawer.tsx` — Drawer 3 tab: "Gian hàng" (table channel accounts + nút xoá), "Gói thuê bao" (current sub + nút đổi gói), "Thành viên" (read-only list).
- `components/OverQuotaBanner.tsx` — banner trong `AppLayout` (hiện cho user thường khi `subscription.over_quota_warned_at !== null`): hiển thị "Bạn đang vượt mức kết nối sàn (X/Y). Còn N giờ trước khi tài khoản bị khoá thao tác. → Nâng cấp gói / → Gỡ kênh thừa". Khi quá grace ⇒ `type='error'` + label "Đã khoá".
- Sidebar `AppLayout`: thêm group **"Quản trị hệ thống"** với 2 mục `/admin/tenants` + `/admin/users` — **chỉ render nếu** `useAuth().data.is_super_admin === true`.
- Icon: `<ToolOutlined/>`, `<UserSwitchOutlined/>`, `<SafetyCertificateOutlined/>` (theo memory `ui-use-font-icons-not-emoji`).
- Lựa chọn ít phương án dùng `Radio.Group`/`Segmented` (theo memory `ui-avoid-select-prefer-radio`).

### 6.5 Job & queue (cập nhật `docs/07-infra/queues-and-scheduler.md`)
Scheduler mới:
- mỗi giờ — `subscriptions:check-over-quota`: iterate alive subscriptions; recompute over-quota; set/clear `over_quota_warned_at` atomically. Idempotent.

KHÔNG thêm queue mới (đồng bộ trong scheduler — nhanh, không I/O ngoài).

## 7. Edge case & lỗi

- **Admin tự xoá kênh của tenant đang lock**: vẫn cho phép — đó là cách user lẫn admin "thoát" lock.
- **Plan limits = -1 (unlimited)**: `over_quota` luôn false. Scheduler tự clear timer nếu plan mới unlimited.
- **Subscription = null / plan = null** (chưa seed): coi như không vượt mức — middleware "open" như EnforcePlanLimit hiện tại.
- **Race**: hai request đồng thời gỡ kênh; UsageService đếm theo `channel_accounts` count thực ⇒ tự correct ở scheduler tới.
- **Super-admin login từ máy lạ**: KHÔNG có 2FA v1 — backlog. Đã có rate limit login + audit log.
- **Demote chính mình**: command yêu cầu xác nhận tương tác (`--force` để bỏ qua trong CI).
- **Tenant suspended có audit_log đang chạy**: `audit_logs` không qua `tenant` middleware ⇒ ghi được kể cả khi tenant suspended.
- **Khoá đang lock + user vào `/billing/checkout`**: allowed (whitelist) ⇒ user thanh toán xong ⇒ `ActivateSubscription` swap plan ⇒ scheduler kế clear timer.
- **Banner hiển thị sai sau khi gỡ kênh**: do scheduler chạy mỗi giờ; admin/user có thể trigger `POST /admin/tenants/{id}/recompute-quota` (làm sau — v1 dùng banner stale tối đa 1h, chấp nhận được).
- **`channel_accounts` over_quota = true nhưng plan thật cho phép**: kiểm config plan `max_channel_accounts`; nếu data sai ⇒ admin sửa plan ở DB (admin UI chưa cho sửa plan v1).

## 8. Bảo mật & dữ liệu cá nhân

- Super-admin có thể xem **mọi data của mọi tenant** — bypass TenantScope (chỉ trong `/admin/*` controllers; phần còn lại không đổi). Đây là vai trò "operator", cần ghi audit log mỗi action ghi.
- Token / credentials của channel_account KHÔNG lộ ra `/admin/*` response (giống `/channels` — chỉ trả meta).
- Lý do (`reason`) tối thiểu 10 ký tự — ép admin viết rõ vì sao thao tác (phục vụ tranh chấp sau).
- Audit log lưu `user_id` (admin ID) + `ip` + `action` + `auditable_type/id` + `changes` (gồm `reason`).
- Endpoint `/api/v1/admin/*` rate limit `60/phút/user` (chống misuse).
- KHÔNG log password / token vào audit `changes`.

## 9. Kiểm thử

### 9.1 Unit
- `User::isSuperAdmin()` — đọc cột.
- `OverQuotaCheckService::detectFor($tenantId)` — vector test: trial 2 shop / có 3 shop ⇒ over; có 1 shop ⇒ ok.

### 9.2 Feature
- `AdminAuthTest` — user thường gọi `/admin/tenants` ⇒ `403 SUPER_ADMIN_REQUIRED`. Super-admin ⇒ `200`.
- `AdminChannelDeleteTest` — super-admin DELETE channel của tenant khác ⇒ delete OK + audit log + usage count giảm.
- `AdminPlanChangeTest` — super-admin force-downgrade ⇒ swap subscription bỏ qua DOWNGRADE_NOT_ALLOWED.
- `OverQuotaGraceTest` — scheduler chạy lần 1 (over) ⇒ set `over_quota_warned_at`; chạy lần 2 trong grace ⇒ giữ nguyên timestamp; tới ngoài grace ⇒ middleware chặn POST `/api/v1/orders` với `402 PLAN_QUOTA_EXCEEDED`. Khi gỡ kênh ⇒ scheduler clear ⇒ middleware mở lại.
- `OverQuotaWhitelistTest` — sau khi bị lock: `GET /api/v1/orders` ⇒ 200; `POST /api/v1/billing/checkout` ⇒ 201; `DELETE /api/v1/channel-accounts/{id}` ⇒ 200; `POST /api/v1/orders` ⇒ 402.
- `TenantSuspendTest` — admin suspend ⇒ user của tenant đó nhận `403 TENANT_SUSPENDED` khi vào tenant.

### 9.3 FE
- Smoke: `/admin/tenants` render bảng; click row mở drawer; user thường không thấy menu admin trong sidebar.

## 10. Tiêu chí hoàn thành

- [ ] Migration `users.is_super_admin` + `subscriptions.over_quota_warned_at` apply OK + rollback OK.
- [ ] `php artisan admin:promote {email}` / `admin:demote {email}` chạy được (idempotent).
- [ ] Super-admin login → sidebar hiện menu Admin; user thường KHÔNG thấy.
- [ ] Force-delete channel của tenant khác qua `/admin/*` chạy được; audit log ghi đúng `tenant_id` của target + `user_id` của admin.
- [ ] Tenant có 5 shop, downgrade về plan 2 shop (qua admin) ⇒ scheduler set `over_quota_warned_at` ⇒ banner FE hiện ⇒ sau 48h (test forward time) ⇒ POST tới `/api/v1/orders` trả 402 `PLAN_QUOTA_EXCEEDED`. DELETE 3 kênh ⇒ scheduler clear ⇒ POST lại OK.
- [ ] Test suite xanh; thêm tối thiểu 10 test mới (Admin + OverQuota).
- [ ] `endpoints.md` cập nhật mục Admin + sửa Billing subscription response; `queues-and-scheduler.md` thêm scheduler over-quota; `multi-tenancy-and-rbac.md` thêm khái niệm super-admin + cập nhật SPEC 0018 §7 (chuyển từ "soft warning" sang "2-day grace then hard lock").

## 11. Câu hỏi mở

- **Q1 Quota nhiều cấp bậc**: Khi ra module trả phí mới (users, automation rules…), thêm resource vào middleware `plan.over_quota_lock:channel_accounts|users|...` — structure đã sẵn.
- **Q2 2FA cho super-admin**: backlog (Phase sau).
- **Q3 Auto-disconnect khi vượt mức**: vẫn không làm (rủi ro mất data). Admin/user chủ động chọn kênh nào gỡ.
- **Q4 Self-serve downgrade**: vẫn chặn (`BillingService::createUpgradeInvoice`); muốn downgrade ⇒ liên hệ hỗ trợ. v2 sẽ nới khi có prorate.
