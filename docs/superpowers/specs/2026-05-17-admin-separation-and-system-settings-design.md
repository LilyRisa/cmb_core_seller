# Admin tách lập + Quản lý cấu hình hệ thống (Settings)

- **Trạng thái:** Design draft (2026-05-17)
- **Phase:** 6.5 — mở rộng SPEC 0020 / 0023
- **Module backend liên quan:** Admin (mở rộng), Settings (lấp module rỗng), Tenancy (User, audit_logs)
- **Tác giả / Ngày:** Team · 2026-05-17
- **Liên quan:** SPEC 0007 §3 (settings shell), SPEC 0020 (admin user management), SPEC 0023 (admin vouchers/plans/broadcast), `docs/01-architecture/multi-tenancy-and-rbac.md`, `docs/05-api/conventions.md`.

## 1. Vấn đề & mục tiêu

Sau khi triển khai SPEC 0020/0023, super-admin vẫn dùng chung bảng `users` + cùng SPA + cùng login form với tenant user (chỉ phân biệt bằng cờ `users.is_super_admin`). Hai vấn đề thực tế:

1. **Không có UI để bảo trì user và admin**: muốn đổi tên / suspend / reset password người dùng đều phải vào DB hoặc Artisan. Hỗ trợ vận hành (support) khó.
2. **Mọi cấu hình hệ thống ở `.env`**: để rebrand, thay khoá TikTok/Lazada, đổi GHN base URL, đổi grace period billing, mỗi lần đều phải SSH sửa file + restart container. Không có audit, không cho phép đổi nóng, không có khung biên (whitelist).

**Mục tiêu:**
1. **Tách auth admin ra khỏi user**: bảng `admin_users` riêng, guard `admin` riêng, login form `/admin/login` riêng. Drop cột `users.is_super_admin`.
2. **Tách SPA admin** thành bundle Vite thứ 2 (`admin.tsx`) — sidebar/theme độc lập, không lẫn code với user app.
3. **Module Settings** quản lý whitelist cấu hình động: 36 key trong 4 nhóm (Branding, Marketplace, Fulfillment, Sync). Secret encrypt bằng `APP_KEY`. Đọc qua helper `system_setting()` với cache `rememberForever` + clear-on-save.
4. **Admin CRUD tenant user**: name/email/reset-password/suspend/reactivate; **Admin CRUD admin_users**: tạo mới/suspend/reset-password chính các super-admin khác.
5. **Audit log mọi action admin** — `audit_logs.admin_user_id` (cột mới, nullable, song song `user_id`).
6. **Không yêu cầu lý do** khi edit settings (chỉ cần audit log) — đã chốt.

## 2. Trong / ngoài phạm vi

**Trong:**
- Bảng `admin_users` + migration backfill từ `users.is_super_admin=true`.
- Bảng `system_settings` + `SystemSettingService` + helper `system_setting()`.
- Cột `users.suspended_at` (cho suspend tenant user).
- Cột `audit_logs.admin_user_id` (nullable).
- Catalog whitelist 36 key (`SystemSettingsCatalog`).
- Guard `admin` + `admin_web` + middleware `auth:admin`. Drop middleware `super_admin` cũ + `Gate::before` cũ.
- Endpoints `/api/v1/admin/auth/*`, `/api/v1/admin/admin-users/*`, `/api/v1/admin/users/*` (CRUD đầy đủ), `/api/v1/admin/system-settings/*`.
- Vite multi-entry `admin.tsx`; route `/admin/{any?}` Blade riêng; AdminApp + AdminLayout + 5 page mới.
- Artisan: `admin:create`, `admin:reset-password`, refactor `admin:promote/demote`.
- Migration call-site nghiệp vụ (TikTok auth, Lazada auth, Gotenberg client, MediaUploader, branding template, GHN base URL, OverQuotaCheckService…) đổi `config()`/`env()` của các key trong catalog sang `system_setting(..., fallback)`.

**Ngoài (làm sau):**
- 2FA TOTP cho admin login (backlog).
- Subdomain `admin.cmbcore.com` tách hạ tầng (v1 dùng path `/admin`).
- Edit `plans.limits` từ UI super-admin (đã có ở SPEC 0023, không động).
- Self-register admin / SSO.
- Audit search/filter UI mới (đã có `AdminAuditLogsPage`).

## 3. Kiến trúc tổng thể

```
app/
├── Models/AdminUser.php                          (mới)
├── Modules/
│   ├── Admin/
│   │   ├── Http/
│   │   │   ├── routes.php                        (đổi auth:sanctum+super_admin → auth:admin)
│   │   │   ├── Controllers/
│   │   │   │   ├── AdminAuthController.php       (mới)
│   │   │   │   └── AdminUserController.php       (mở rộng: show/update/resetPassword/suspend + admin-users CRUD)
│   │   ├── Console/Commands/
│   │   │   ├── AdminCreateCommand.php            (mới)
│   │   │   ├── AdminResetPasswordCommand.php     (mới)
│   │   │   ├── AdminPromoteCommand.php           (refactor)
│   │   │   └── AdminDemoteCommand.php            (refactor)
│   │   └── Database/Migrations/
│   │       ├── 2026_05_18_100000_create_admin_users_table.php
│   │       ├── 2026_05_18_100001_add_admin_user_id_to_audit_logs.php
│   │       ├── 2026_05_18_100002_add_suspended_at_to_users.php
│   │       └── 2026_05_18_100003_backfill_admin_users_and_drop_is_super_admin.php
│   └── Settings/
│       ├── SettingsServiceProvider.php  (đã có — boot helpers + event listener)
│       ├── Database/Migrations/2026_05_18_100004_create_system_settings_table.php
│       ├── Models/SystemSetting.php
│       ├── Services/SystemSettingService.php
│       ├── Support/SystemSettingsCatalog.php
│       ├── Events/SystemSettingChanged.php
│       ├── Listeners/LogSystemSettingChanged.php
│       ├── Database/Seeders/SystemSettingsCatalogSeeder.php
│       ├── Http/
│       │   ├── routes.php
│       │   ├── Controllers/AdminSystemSettingController.php
│       │   └── Requests/UpdateSystemSettingRequest.php
│       └── helpers.php                            (autoload qua composer.json files)

config/
├── auth.php                                       (thêm guard admin_web + admin, provider admin_users)
└── sanctum.php                                    (mở rộng key 'guard' → ['web','admin_web'])

resources/
├── js/
│   ├── app.tsx                                    (không đổi)
│   ├── admin.tsx                                  (mới — entry)
│   └── admin/
│       ├── AdminApp.tsx
│       ├── AdminProtected.tsx
│       ├── AdminLayout.tsx
│       ├── lib/{adminClient,adminAuth,adminUsers,tenantUsers,systemSettings}.tsx
│       ├── pages/AdminLoginPage.tsx, AdminDashboardPage.tsx
│       ├── pages/tenants/...  (move từ resources/js/pages/admin/)
│       ├── pages/users/{AdminUsersPage,AdminUserFormDrawer,TenantUserDrawer}.tsx
│       └── pages/settings/{SystemSettingsPage,SettingsTabBranding,SettingsTabMarketplace,SettingsTabFulfillment,SettingsTabSync}.tsx
├── views/
│   ├── app.blade.php                              (đã có)
│   └── admin.blade.php                            (mới)

routes/web.php — thêm:
  Route::get('/admin/{any?}', fn() => view('admin'))->where('any','.*');
  // user catch-all cập nhật: ->where('any','^(?!api|sanctum|admin).*');

vite.config.ts — input: thêm 'resources/js/admin.tsx'.
```

### 3.1 `config/auth.php` (cập nhật)

```php
'guards' => [
    'web'       => ['driver' => 'session', 'provider' => 'users'],          // hiện có
    'admin_web' => ['driver' => 'session', 'provider' => 'admin_users'],    // mới — login admin SPA
    'sanctum'   => ['driver' => 'sanctum', 'provider' => 'users'],          // hiện có
    'admin'     => ['driver' => 'sanctum', 'provider' => 'admin_users'],    // mới — bảo vệ /api/v1/admin/*
],
'providers' => [
    'users'       => ['driver' => 'eloquent', 'model' => User::class],
    'admin_users' => ['driver' => 'eloquent', 'model' => AdminUser::class],
],
'passwords' => [
    'users'       => [...],
    'admin_users' => ['provider' => 'admin_users', 'table' => 'admin_password_reset_tokens', 'expire' => 60, 'throttle' => 60],
],
```

`config/sanctum.php` — mở rộng `'guard' => ['web', 'admin_web']` để Sanctum thử cả 2 khi resolve stateful request.

### 3.2 Đường đi auth runtime

```
[Browser /admin/login]
   → GET  /sanctum/csrf-cookie                 (Sanctum set XSRF-TOKEN)
   → POST /api/v1/admin/auth/login {username,password}
       backend: middleware 'web' (start session), Auth::guard('admin_web')->login($admin)
       → session ghi key  login_admin_web_<hash>=<admin_id>
   → GET  /api/v1/admin/auth/me                (middleware 'auth:admin' → Sanctum resolve guard admin → admin_web session)
[Browser /admin/*]
   → axios withCredentials → cookie session + XSRF-TOKEN → backend phân biệt admin/user
```

### 3.3 Cách helper `system_setting()` hoạt động

```
[Caller: any module code]
   $appKey = system_setting('marketplace.tiktok.app_key', config('integrations.tiktok.app_key'));

[SystemSettingService]
   all() — memo + Cache::rememberForever('system_settings:all', fn() => SystemSetting::all()...)
   get($key, $default):
       row = all()[$key] ?? null
       row==null → return $default
       cast(row.value, row.type)        (bool/int/float/json/string)
   set($key, $value, $adminId):
       meta = catalog::require($key)    (throw nếu key không trong catalog)
       encode value theo type → if is_secret then Crypt::encryptString
       SystemSetting::updateOrCreate({key}, {...})
       Cache::forget('system_settings:all')
       event(new SystemSettingChanged($key))
```

Files `config/*.php` **giữ nguyên `env()`** để `php artisan config:cache` vẫn an toàn — chỉ call-site nghiệp vụ chuyển sang `system_setting('...', config('...'))`.

## 4. Hành vi & quy tắc nghiệp vụ

1. **Admin = global**: không scope tenant. Không bao giờ truy cập `/api/v1/*` của user (vì guard khác). Muốn xuyên tenant phải viết endpoint `/api/v1/admin/*` riêng. Drop `Gate::before` cho super-admin.
2. **Admin audit**: mọi action mutating đều ghi `audit_logs {admin_user_id, action, auditable_type, auditable_id, changes, ip}`. Action prefix `admin.*`.
3. **Self-protection**:
   - Admin không thể suspend / reset password chính mình ⇒ 409.
   - Không thể suspend admin active cuối cùng ⇒ 409.
4. **Tenant user suspend**: cột `users.suspended_at` nullable. Middleware `EnsureTenant` kiểm: `suspended_at != null` ⇒ 403 `USER_SUSPENDED`.
5. **Secrets**:
   - Cột `system_settings.is_secret=true` ⇒ `value` encrypt bằng `Crypt::encryptString` (AES-256-CBC theo APP_KEY).
   - GET list trả masked (`value="****"` khi đã set, `null` khi chưa).
   - GET `/system-settings/{key}/reveal` trả plain + ghi audit `admin.setting.reveal`.
6. **Settings cache**:
   - `Cache::rememberForever('system_settings:all', ...)`.
   - `set()` → `Cache::forget()` + dispatch event.
   - Production cache driver = redis/database (KHÔNG array) để cluster đồng bộ.
   - `php artisan config:cache` không ảnh hưởng vì `system_setting()` không đi qua `config()`.
7. **Catalog là source-of-truth**: key không trong catalog ⇒ `set()` throw `SETTING_KEY_NOT_ALLOWED`. UI không hiển thị key ngoài catalog. Catalog map sang env-key tương ứng để seed lần đầu.
8. **Validate value theo type** ở `SystemSettingsCatalog::validate($key, $value)` — type-strict (bool chấp nhận `true/false/0/1/"true"/"false"`; int chấp nhận numeric; json chấp nhận chuỗi JSON valid).
9. **Audit không log value secret**: log chỉ ghi `{key}` và `updated_by_admin_id`, KHÔNG ghi value (đơn giản, tránh leak qua DB backup/dev seed).
10. **Reveal logged**: GET reveal endpoint ghi audit `admin.setting.reveal` để có dấu vết "ai đã đọc plain secret".
11. **Migration backfill**: với mỗi `users.is_super_admin=true`, tạo `admin_users` với:
    - `username` = sanitize(`Str::before(email, '@')`) — nếu trùng → suffix `_<id>`.
    - `email` = users.email (giữ cho reset).
    - `password` = users.password (copy hash bcrypt nguyên — admin reset sau).
    - `name` = users.name.
    - `is_active` = true.
    Sau backfill → `Schema::dropColumn('is_super_admin')`.
12. **Rollback migration**: phục hồi cột + set `is_super_admin=true` cho mọi user email match một `admin_users.email`.

## 5. Dữ liệu

### 5.1 Bảng `admin_users` (mới)

```
id              bigint PK
username        string(32)   UNIQUE  not null    -- [a-z0-9._-]{3,32}
email           string       UNIQUE  nullable
name            string               not null
password        string               not null    -- bcrypt 12
is_active       boolean      default true
last_login_at   timestamptz          nullable
last_login_ip   string(45)           nullable
created_at, updated_at
```

Index: `(username)`, `(email)`, `(is_active)`.

### 5.2 Bảng `system_settings` (mới)

```
id                       bigint PK
key                      string(120) UNIQUE  not null
value                    text                 nullable   -- encrypted nếu is_secret
type                     string(16)           not null   -- string|int|bool|float|json
group                    string(32)           not null   -- branding|marketplace|fulfillment|sync
is_secret                boolean    default false
description              text                 nullable
updated_by_admin_id      bigint               nullable   -- không FK cứng (admin xoá ko cascade)
updated_at, created_at
```

Index: `(group)`, `(key)`.

### 5.3 Sửa bảng `users`

- Thêm `suspended_at timestamptz nullable`, index.
- DROP cột `is_super_admin` (sau backfill xong).

### 5.4 Sửa bảng `audit_logs`

- Thêm `admin_user_id bigint nullable` (không FK cứng), index.
- `user_id` đã nullable từ migration gốc — không cần đổi.

### 5.5 Catalog whitelist (36 key)

`app/Modules/Settings/Support/SystemSettingsCatalog.php` — single source of truth. Mỗi entry: `{key, group, type, is_secret, env, label, description?}`.

**Nhóm `branding` (7 key):**
- `notifications.brand_name` ← `NOTIFICATIONS_BRAND_NAME` (string)
- `notifications.brand_tagline` ← `NOTIFICATIONS_BRAND_TAGLINE` (string)
- `notifications.support_email` ← `NOTIFICATIONS_SUPPORT_EMAIL` (string)
- `notifications.primary_color` ← `NOTIFICATIONS_PRIMARY_COLOR` (string)
- `notifications.accent_color` ← `NOTIFICATIONS_ACCENT_COLOR` (string)
- `mail.from_address` ← `MAIL_FROM_ADDRESS` (string)
- `mail.from_name` ← `MAIL_FROM_NAME` (string)

**Nhóm `marketplace` (9 key, 6 secret):**
- `marketplace.tiktok.app_key` ← `TIKTOK_APP_KEY` (string, **secret**)
- `marketplace.tiktok.app_secret` ← `TIKTOK_APP_SECRET` (string, **secret**)
- `marketplace.tiktok.service_id` ← `TIKTOK_SERVICE_ID` (string)
- `marketplace.tiktok.sandbox` ← `TIKTOK_SANDBOX` (bool)
- `marketplace.lazada.app_key` ← `LAZADA_APP_KEY` (string, **secret**)
- `marketplace.lazada.app_secret` ← `LAZADA_APP_SECRET` (string, **secret**)
- `marketplace.lazada.sandbox` ← `LAZADA_SANDBOX` (bool)
- `marketplace.shopee.partner_id` ← `SHOPEE_PARTNER_ID` (string, **secret**)
- `marketplace.shopee.partner_key` ← `SHOPEE_PARTNER_KEY` (string, **secret**)

**Nhóm `fulfillment` (15 key, 2 secret):**
- `fulfillment.deduct_on` ← `FULFILLMENT_DEDUCT_ON` (string)
- `fulfillment.default_weight_grams` ← `FULFILLMENT_DEFAULT_WEIGHT_GRAMS` (int)
- `fulfillment.tiktok_arrange_shipment` ← `INTEGRATIONS_TIKTOK_FULFILLMENT` (bool)
- `fulfillment.print_label_size` ← `PRINT_LABEL_SIZE` (string)
- `carriers.enabled_csv` ← `INTEGRATIONS_CARRIERS` (string)
- `carriers.default` ← `INTEGRATIONS_DEFAULT_CARRIER` (string)
- `carriers.ghn.base_url` ← `GHN_BASE_URL` (string)
- `storage.media_disk` ← `MEDIA_DISK` (string)
- `storage.media_image_max_kb` ← `MEDIA_IMAGE_MAX_KB` (int)
- `storage.r2.bucket` ← `R2_BUCKET` (string)
- `storage.r2.endpoint` ← `R2_ENDPOINT` (string)
- `storage.r2.public_url` ← `R2_URL` (string)
- `storage.r2.access_key_id` ← `R2_ACCESS_KEY_ID` (string, **secret**)
- `storage.r2.secret_access_key` ← `R2_SECRET_ACCESS_KEY` (string, **secret**)
- `pdf.gotenberg_url` ← `GOTENBERG_URL` (string)

**Nhóm `sync` (7 key):**
- `throttle.tiktok_per_min` ← `THROTTLE_TIKTOK_PER_MIN` (int)
- `throttle.shopee_per_min` ← `THROTTLE_SHOPEE_PER_MIN` (int)
- `throttle.lazada_per_min` ← `THROTTLE_LAZADA_PER_MIN` (int)
- `sync.poll_interval_minutes` ← `SYNC_POLL_INTERVAL_MINUTES` (int)
- `sync.poll_overlap_minutes` ← `SYNC_POLL_OVERLAP_MINUTES` (int)
- `sync.backfill_days` ← `SYNC_BACKFILL_DAYS` (int)
- `billing.over_quota_grace_hours` ← `BILLING_OVER_QUOTA_GRACE_HOURS` (int)

Tổng: **38 key** (7 + 9 + 15 + 7), trong đó **8 secret** (6 marketplace + 2 R2).

**Key core KHÔNG cho vào catalog** (giữ env): `APP_KEY`, `APP_ENV`, `APP_DEBUG`, `APP_URL`, `DB_*`, `REDIS_*`, `SESSION_*`, `SANCTUM_STATEFUL_DOMAINS`, `BCRYPT_ROUNDS`, `MAIL_HOST/USERNAME/PASSWORD/PORT/SCHEME`, `AWS_*` (S3 internal), `SENTRY_LARAVEL_DSN`, `BROADCAST_CONNECTION`, `QUEUE_CONNECTION`, `CACHE_STORE`, `INTEGRATIONS_CHANNELS` (chọn lúc deploy, không nóng được).

### 5.6 Event

- `SystemSettingChanged($key)` — listener `LogSystemSettingChanged` ghi audit `admin.setting.update {key}` + audit `admin.setting.reveal {key}` cho endpoint reveal.

## 6. API & UI

### 6.1 Endpoint mới — Auth admin

```
POST /api/v1/admin/auth/login            web + throttle:10,1    {username,password} → {data:{id,username,name,email}}
POST /api/v1/admin/auth/logout           auth:admin
GET  /api/v1/admin/auth/me               auth:admin             → {data:{...}}
POST /api/v1/admin/auth/change-password  auth:admin             {current_password, password}
```

### 6.2 Endpoint mới — Admin Users

```
GET    /api/v1/admin/admin-users                       ?q=&is_active=&page=&per_page=
GET    /api/v1/admin/admin-users/{id}
POST   /api/v1/admin/admin-users                       {username,email?,name,password,is_active?}
PATCH  /api/v1/admin/admin-users/{id}                  {email?, name?}
POST   /api/v1/admin/admin-users/{id}/reset-password   {password}
POST   /api/v1/admin/admin-users/{id}/suspend
POST   /api/v1/admin/admin-users/{id}/reactivate
```

### 6.3 Endpoint mới — Tenant Users (mở rộng `AdminUserController`)

```
GET    /api/v1/admin/users                              (đã có — bỏ filter is_super_admin)
GET    /api/v1/admin/users/{id}
PATCH  /api/v1/admin/users/{id}                         {name?, email?}
POST   /api/v1/admin/users/{id}/reset-password          {password?}    (rỗng=gửi email link)
POST   /api/v1/admin/users/{id}/suspend
POST   /api/v1/admin/users/{id}/reactivate
```

### 6.4 Endpoint mới — System Settings

```
GET    /api/v1/admin/system-settings                    ?group=branding|marketplace|fulfillment|sync
GET    /api/v1/admin/system-settings/{key}/reveal       → plain value (audit)
PATCH  /api/v1/admin/system-settings/{key}              {value}
DELETE /api/v1/admin/system-settings/{key}              (xoá row → fallback env)
POST   /api/v1/admin/system-settings/sync-from-env      (bootstrap seed)
```

### 6.5 Sửa endpoint hiện có

- Bỏ field `is_super_admin` khỏi mọi response (`/api/v1/auth/me`, `/admin/users`).
- Bỏ middleware `super_admin` trên mọi route (route group cũ đã chỉ dùng `auth:sanctum`+`super_admin` → đổi `auth:admin`).
- `EnsureTenant`: thêm check `users.suspended_at` → 403 `USER_SUSPENDED`.

### 6.6 Error codes mới

| Code | HTTP | Khi nào |
|---|---|---|
| `ADMIN_AUTH_FAILED` | 401 | Login sai/disabled. |
| `CANNOT_SELF_MUTATE` | 409 | Admin tự suspend/reset password. |
| `LAST_ACTIVE_ADMIN` | 409 | Suspend admin active cuối cùng. |
| `USER_SUSPENDED` | 403 | Tenant user bị suspend. |
| `SETTING_KEY_NOT_ALLOWED` | 422 | Key không có trong catalog. |
| `SETTING_VALUE_INVALID` | 422 | Value sai type. |

### 6.7 Frontend — Admin SPA tách

`vite.config.ts` thêm input `resources/js/admin.tsx`. `routes/web.php` route `/admin/{any?}` → `admin.blade.php`. Bundle admin **không import** từ `resources/js/app.tsx` hay `resources/js/pages/*`. Theme AntD admin: navy/đỏ (phân biệt với editorial postal user). Icons `@ant-design/icons` (memory `ui-use-font-icons-not-emoji`). Lựa chọn ít option dùng `Radio.Group`/`Segmented` (memory `ui-avoid-select-prefer-radio`).

Pages: `AdminLoginPage`, `AdminDashboardPage`, `AdminTenantsPage` (move), `AdminUsersPage` (Tabs Admin/Tenant), `AdminUserFormDrawer`, `TenantUserDrawer`, `SystemSettingsPage` (Segmented tabs theo group) với 4 sub-tab Branding/Marketplace/Fulfillment/Sync. Component `SecretInput` (mask `****` + nút Reveal + countdown ẩn lại 10s).

### 6.8 Artisan

- `admin:create {username} {--email=} {--name=}` — prompt password hidden.
- `admin:reset-password {username}` — prompt password hidden.
- `admin:promote {email}` (refactor) — tạo admin_users mirror từ user.
- `admin:demote {username}` (refactor) — set admin_users.is_active=false.

## 7. Edge case & lỗi

1. Admin tự suspend / reset password → 409 `CANNOT_SELF_MUTATE`.
2. Suspend admin active cuối → 409 `LAST_ACTIVE_ADMIN`.
3. Key bị xoá khỏi catalog sau deploy → row DB còn nhưng `system_setting()` không đọc (catalog là filter). Cleanup tay nếu cần.
4. Secret value rỗng — bypass decrypt, trả `null`.
5. `Crypt::decryptString` fail (APP_KEY đổi) — log warning, trả fallback env, không crash.
6. Cache driver phải là redis/database trên production để cluster đồng bộ; array driver chỉ dùng test.
7. `php artisan config:cache` không ảnh hưởng `system_setting()` (nó không qua `config()`).
8. Backfill: username trùng → suffix `_<id>`.
9. Tenant user suspend với session active → middleware tenant chặn next request (403). Không invalidate session ngay.
10. CSRF mismatch sau login admin → SPA retry sau `/sanctum/csrf-cookie`.
11. Cùng browser login cả user + admin → Sanctum hỗ trợ multi-guard session keys song song. Logout 1 không kéo cái còn lại.
12. `is_super_admin` cũ ở callers → grep & xoá hết (User model, FE `useAuth().is_super_admin`, Gate::before).
13. `audit_logs.user_id` not-null cũ → migration đảm bảo nullable.

## 8. Bảo mật & dữ liệu cá nhân

- Admin bypass TenantScope khi vào `/admin/*` (như SPEC 0020) — guard `admin` xác nhận identity.
- Token/credentials channel_account không lộ ra `/admin/users` hay `/admin/tenants` response.
- Rate limit:
  - `/admin/auth/login` = 10/phút/IP (brute force).
  - Còn lại `/admin/*` = 60/phút/user (giữ SPEC 0020).
- Session admin lifetime = 60 phút (ngắn hơn user 120).
- Audit log:
  - Mọi mutation `/admin/*` ghi `admin.<resource>.<action>` + `admin_user_id` + ip.
  - `admin.setting.reveal {key}` mỗi lần reveal secret.
  - KHÔNG log password / value secret trong `changes`.
- Cookie `Secure` + `HttpOnly` + `SameSite=Lax`.

## 9. Kiểm thử

### 9.1 Unit
- `SystemSettingService::get/set/forget/cast/encode` — 6 case.
- `SystemSettingsCatalog::require/validate` — per-type.
- `AdminUser::isLastActive()` helper.

### 9.2 Feature
- `AdminAuthLoginTest` (login OK / sai pwd / disabled / rate limit).
- `AdminAuthGuardTest` (`/admin/*` từ user session → 401; từ no session → 401).
- `AdminUserCrudTest` (CRUD + self-mutate guard + last-active guard).
- `TenantUserManageTest` (PATCH/reset/suspend → middleware tenant chặn).
- `SystemSettingApiTest` (GET masked; PATCH non-secret/secret; reveal logged; DELETE → fallback; key ngoài catalog → 422; value sai type → 422).
- `SystemSettingCacheTest` (set → cache forget → next get đọc DB; set 2 lần → cache đổi).
- `BackfillSuperAdminTest` (tạo 2 user is_super_admin → migration → 2 admin_users + cột drop).

### 9.3 FE smoke
- `/admin/login` form render + submit OK redirect.
- `/admin` không session → redirect `/admin/login`.
- `/admin/settings` render 4 tabs + sửa 1 bool → toast + persist.
- Reveal secret → modal hiện plain + countdown ẩn.

## 10. Tiêu chí hoàn thành

- [ ] Migration `admin_users` + `system_settings` + `users.suspended_at` + `audit_logs.admin_user_id` apply OK + rollback OK.
- [ ] Backfill: `users.is_super_admin=true` → `admin_users` 1-1; drop column.
- [ ] Artisan `admin:create`, `admin:reset-password`, `admin:promote`, `admin:demote` chạy được.
- [ ] `POST /admin/auth/login` + `auth:admin` middleware bảo vệ đúng `/api/v1/admin/*`. User thường 401.
- [ ] CRUD admin_users + tenant users qua API: full happy + guards (self-mutate, last-active, suspended).
- [ ] System settings CRUD: GET masked, PATCH validate type, reveal audit, DELETE fallback env, catalog enforce.
- [ ] Cache `rememberForever` + clear-on-save verified.
- [ ] FE `/admin/login` + `/admin/{dashboard,users,settings,tenants,plans,vouchers,broadcasts,audit-logs}` render & function.
- [ ] Bundle admin tách (`admin.tsx`) — user bundle không chứa code admin.
- [ ] ≥ 25 test mới, suite xanh.
- [ ] Catalog cover đủ 38 key (Section 5.5). Mọi call-site nghiệp vụ với key thuộc catalog → `system_setting('...', config('...'))`.
- [ ] Docs `endpoints.md` cập nhật mục Admin Auth/Users/Settings; `multi-tenancy-and-rbac.md` thêm khái niệm `admin_users` tách bảng.

## 11. Câu hỏi mở

- **Q1 2FA TOTP cho admin**: backlog. SPEC 0020 cũng để backlog.
- **Q2 Subdomain `admin.cmbcore.com`**: backlog. v1 dùng path `/admin` đơn giản hơn.
- **Q3 Audit retention**: chưa định nghĩa policy xoá log cũ (giữ vĩnh viễn v1).
- **Q4 Edit `plans.limits` từ UI**: đã có `AdminPlanController::update` ở SPEC 0023 — phạm vi khác.
- **Q5 SystemSettingChanged event subscribers**: v1 chỉ ghi audit log. Sau có thể subscribe để invalidate cache của module liên quan (vd OAuth client cache TikTok).
