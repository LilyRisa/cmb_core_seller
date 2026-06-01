# Mobile token authentication — design (2026-06-01)

Bearer-token authentication for the CMBcoreSeller mobile app, layered onto the
existing Sanctum setup. Seller users only (`users` provider); super-admin app
stays web/session-only.

## 1. Mục tiêu & bối cảnh

- App mobile của **seller** không dùng được luồng SPA cookie + CSRF của web. Cần
  **bearer token** (Sanctum personal access token) gửi qua header
  `Authorization: Bearer <token>`.
- **Tận dụng tối đa API web:** mọi route nghiệp vụ hiện tại đã nằm sau
  `auth:sanctum` (`app/routes/api.php:62`). Guard `auth:sanctum` xác thực **cả
  cookie SPA lẫn bearer token**, nên một token cấp qua `createToken()` dùng được
  **toàn bộ** endpoint hiện có (`/auth/me`, `/tenants`, `/orders`, `/fulfillment/*`…)
  **không cần viết lại**.
- Nền tảng đã sẵn: `User` dùng trait `HasApiTokens` (`app/app/Models/User.php:27`);
  guard token `sanctum` (driver `sanctum`, provider `users`) đã khai báo
  (`app/config/auth.php:41`). Hiện **chưa endpoint nào phát hành token**.

### Không làm (YAGNI, lần này)
- Đăng ký tài khoản / tạo tenant trên mobile (dùng web).
- Quên/đặt lại mật khẩu trên mobile (dùng web; password broker đã có).
- Access+refresh token rotation (đã chọn bearer dài hạn mỗi thiết bị).
- Phát hành token cho super-admin.

## 2. Quyết định đã chốt

| Vấn đề | Quyết định |
|---|---|
| Mô hình token | Bearer dài hạn **mỗi thiết bị** (Sanctum personal access token thuần) |
| Hết hạn | **60 ngày** kể từ lúc cấp (per-token `expires_at`) |
| Abilities | Mặc định `['*']` cho v1 (để ngỏ scope hẹp hơn sau) |
| Phạm vi | Login (cấp token), logout (thu hồi token hiện tại), quản lý thiết bị |
| Cách tiếp cận | **Phương án A** — controller + route group token riêng, không đụng SPA cookie |
| User base | Chỉ seller `users` |

## 3. Bề mặt HTTP mới

Controller mới: `CMBcoreSeller\Modules\Tenancy\Http\Controllers\TokenAuthController`
(đặt cạnh `AuthController` hiện có). Thêm route vào `app/routes/api.php` trong
nhóm `v1`.

### Public (throttle 15/phút — bằng `/auth/login`)
- `POST /api/v1/auth/token`
  - Body: `email` (required, email), `password` (required), `device_name`
    (required, string ≤255).
  - Validate credentials qua `Auth::guard('web')->validate([...])` (dùng lại
    logic của `AuthController::login`, **không** tạo session).
  - Hợp lệ → `$user->createToken($deviceName, ['*'], now()->addDays(config('sanctum.mobile_token_days', 60)))`.
  - Response `201`: `{ "data": { "token": "<plaintext>", "user": <userPayload> } }`
    (`userPayload` dùng lại từ `AuthController` — id/name/email/email_verified_at/tenants).
  - Sai credentials → `422 { "error": { "code": "INVALID_CREDENTIALS", "message": "Email hoặc mật khẩu không đúng." } }` (đúng shape hiện có).

### Authenticated (`auth:sanctum`)
- `DELETE /api/v1/auth/token` — **logout mobile**: thu hồi token đang dùng
  (`$request->user()->currentAccessToken()->delete()`). `204`.
- `GET /api/v1/auth/devices` — liệt kê token của user hiện tại:
  `{ "data": [ { "id", "device_name", "last_used_at", "created_at", "current": bool } ] }`.
  `current` = token id trùng `currentAccessToken()->id`.
- `DELETE /api/v1/auth/devices/{id}` — thu hồi 1 token theo id. **Ownership
  guard**: chỉ xoá token thuộc `$request->user()` (query `tokens()->whereKey($id)`);
  không thấy → `404`. `204`.
- `DELETE /api/v1/auth/devices` — thu hồi **mọi token trừ token hiện tại**
  ("đăng xuất mọi thiết bị khác"). `204`.

> `GET /api/v1/auth/me` và toàn bộ route nghiệp vụ **giữ nguyên** — mobile gọi
> với `Authorization: Bearer <token>` + `X-Tenant-Id`. Không thêm endpoint trùng.

## 4. Cấu hình

- `app/config/sanctum.php`: đổi default `expiration` về **`null`** (env
  `SANCTUM_EXPIRATION` bỏ trống = không override hạn từng token), để per-token
  `expires_at` (60 ngày) được tôn trọng. Lưu ý: `expiration` không ảnh hưởng
  session SPA (theo chú thích config) ⇒ thay đổi an toàn cho web.
- Thêm knob `mobile_token_days` (default `60`) vào `config/sanctum.php` (hoặc
  đọc env `MOBILE_TOKEN_DAYS`) để chỉnh hạn token mobile mà không sửa code.

## 5. Bảo mật

- Throttle `POST /auth/token` = 15/phút (bằng login) chống brute-force.
- `device_name` bắt buộc ⇒ quản lý thiết bị có ý nghĩa.
- Token trả về **một lần duy nhất** (plaintext) lúc cấp; sau đó chỉ còn hash.
- Ownership guard tuyệt đối ở mọi thao tác thu hồi (chỉ thao tác token của chính
  user — tránh IDOR).
- `verified` + `tenant` + `plan.over_quota_lock` vẫn áp dụng cho route nghiệp vụ
  ⇒ mobile chịu cùng ràng buộc như web (user chưa verify email vẫn bị
  `403 EMAIL_NOT_VERIFIED`).
- User bị `suspended_at` vẫn cấp được token nhưng bị `EnsureTenant` chặn ở route
  nghiệp vụ — giữ nhất quán với login web (không chặn ở bước cấp token).

## 6. Rule/convention ghi vào docs (cùng PR)

- `docs/05-api/conventions.md` §2 — thêm mục **"Token auth (mobile / 3rd-party
  client)"**: dùng bearer token cấp tại `POST /api/v1/auth/token`; cùng middleware
  `auth:sanctum` phục vụ cả SPA cookie lẫn token; route nghiệp vụ vẫn cần
  `X-Tenant-Id`. Đây là rule mở rộng cho mọi client không-SPA về sau.
- `docs/05-api/endpoints.md` — thêm 4 endpoint mới (mục Auth).
- `docs/01-architecture/adr/` — ADR ngắn: "Auth đa client — SPA cookie + Sanctum
  bearer token cho mobile/3rd-party" (quyết định, hệ quả, vì sao không
  access/refresh).

## 7. Kiểm thử (Feature test, mirror `tests/Feature/Tenancy/AuthTenantTest.php`)

1. `POST /auth/token` sai mật khẩu → `422 INVALID_CREDENTIALS`, không cấp token.
2. `POST /auth/token` đúng → `201`, trả `token` non-empty + payload user; DB có 1
   row `personal_access_tokens` với `expires_at ≈ now()+60d`.
3. Gọi 1 endpoint nghiệp vụ (vd `GET /auth/me`, `GET /orders`) bằng
   `Authorization: Bearer <token>` + `X-Tenant-Id` → `200`.
4. `DELETE /auth/token` → `204`; gọi lại endpoint cũ bằng token đó → `401`.
5. `GET /auth/devices` → liệt kê đúng số token, cờ `current` đúng.
6. `DELETE /auth/devices/{id}` của **token user khác** → `404`, token kia còn sống
   (ownership guard).
7. `DELETE /auth/devices` → mọi token khác bị thu hồi, token hiện tại còn sống.

## 8. File đụng tới (dự kiến)

- **Mới:** `app/app/Modules/Tenancy/Http/Controllers/TokenAuthController.php`
- **Mới:** `app/tests/Feature/Tenancy/MobileTokenAuthTest.php`
- **Sửa:** `app/routes/api.php` (thêm nhóm route token)
- **Sửa:** `app/config/sanctum.php` (`expiration` default → null; thêm `mobile_token_days`)
- **Docs:** `conventions.md`, `endpoints.md`, ADR mới
