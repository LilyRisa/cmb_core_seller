# SPEC 0022: Xác thực email tài khoản + Email thông báo (notifications nền tảng)

- **Trạng thái:** Draft
- **Phase:** 6.5 — Automation Rules + Notifications (sub-task đầu tiên — nền tảng kênh `mail`)
- **Module backend liên quan:** Tenancy (User/Auth), Notifications (mới)
- **Tác giả / Ngày:** lilyrisa · 2026-05-16
- **Liên quan:** `08-security-and-privacy.md` §1, `07-infra/queues-and-scheduler.md` (queue `notifications` đã có), `05-api/conventions.md`, `05-api/endpoints.md`

## 1. Vấn đề & mục tiêu

Hiện tại đăng ký xong là dùng app được luôn — không có xác thực email, không có quên mật khẩu, không có template email branded. Phase 6.5 trên roadmap quy định Notifications (Email/Zalo/In-app) là việc tiếp theo sau Billing. Spec này **đặt nền tảng kênh `mail`** với 3 email cốt lõi:

1. **Xác thực email tài khoản** (hard gating — chặn API tới khi verified).
2. **Email chào mừng** (sau khi verify xong).
3. **Email quên mật khẩu** (yêu cầu cũ ở `08-security-and-privacy.md` §1).

Khung module `Notifications` tạo trong spec này là chỗ để Phase 6.5 tiếp theo cắm channel Zalo OA / Telegram / In-app, và các event nghiệp vụ (hết tồn / đơn lỗi / settlement bất thường) — không phải gom chung mọi notification vào một spec.

## 2. Trong / ngoài phạm vi

- **Trong:**
  - Module `Notifications` mới (service provider, controllers, notification classes, view layout + 3 view con).
  - `User` implement `MustVerifyEmail` + override `sendEmailVerificationNotification` / `sendPasswordResetNotification` để dùng notification class branded.
  - 4 endpoint: `GET /auth/email/verify/{id}/{hash}`, `POST /auth/email/verify/resend`, `POST /auth/password/forgot`, `POST /auth/password/reset`.
  - Middleware `verified` gắn vào group `tenant` (hard gating).
  - Template email Blade (1 layout + 3 view) — inline CSS email-safe, responsive max-width 600px, brand `CMBcoreSeller`.
  - Cập nhật seeder `owner@demo.local` set `email_verified_at`.
  - Test feature (Pest/PHPUnit) + Mailable render test.
  - Cập nhật `endpoints.md`, `queues-and-scheduler.md`, `roadmap.md` (đánh dấu sub-task của Phase 6.5).
- **Ngoài (làm sau / spec khác):**
  - SPA FE pages `/email-verified`, `/password-reset` (BE redirect tới URL → FE sprint sau).
  - Notification channels khác (Zalo OA, Telegram, In-app, Web push).
  - Notification cho event nghiệp vụ (hết tồn, đơn lỗi, settlement bất thường) — Phase 6.5 sub-task tiếp theo.
  - Mời thành viên qua email (`POST /tenant/members` hiện chỉ add user đã tồn tại).
  - 2FA / login từ thiết bị lạ — Phase sau (đã ghi `08-security-and-privacy.md` §1).
  - Notification preferences UI (tenant chọn channel nào, gửi email nào).

## 3. Luồng chính

### 3.1 Đăng ký + xác thực email

```
SPA                BE                       Queue          SMTP/Mailpit
 │                  │                         │                 │
 │── POST /register ──▶                       │                 │
 │                  │ create User+Tenant      │                 │
 │                  │ fire Registered event   │                 │
 │                  │  → SendEmailVerification│                 │
 │                  │    Notification listener│                 │
 │                  │ (Laravel default)       │                 │
 │                  │    → User.sendEmail     │                 │
 │                  │       VerificationNotif │                 │
 │                  │       (overridden) ────▶│ enqueue (notifications)
 │                  │ Auth.login (session)    │                 │
 │◀─ 201 user JSON ─│                         │                 │
 │                  │                         │── render ──────▶│ send
 │                  │                         │                 │
User mở email, bấm "Xác thực email" → GET /api/v1/auth/email/verify/{id}/{hash}?signature=…
                    │ verify signed URL       │                 │
                    │ markEmailAsVerified()   │                 │
                    │ fire Verified event     │                 │
                    │  → SendWelcomeEmailOn   │                 │
                    │    Verified listener ──▶│ enqueue welcome │
                    │ 302 → ${FRONTEND_URL}/  │                 │
                    │   email-verified?status=│                 │
                    │   success               │                 │
```

### 3.2 Hard gating
- Group routes `['auth:sanctum', 'tenant', 'plan.over_quota_lock']` thêm middleware `verified`.
- Endpoint nằm ngoài: `/auth/me`, `/auth/logout`, `/auth/email/verify/*`, `/tenants` (index/store) — user chưa verify vẫn login được, vẫn thấy "email chưa verify" trong `/auth/me` response, vẫn resend được.
- Middleware `verified` Laravel mặc định redirect; em override trả JSON envelope:
  ```json
  { "error": { "code": "EMAIL_NOT_VERIFIED", "message": "Vui lòng xác thực email trước khi sử dụng tính năng này." } }
  ```
  HTTP 403.

### 3.3 Quên mật khẩu
```
User                BE                       Queue
 │── POST /auth/password/forgot ─▶            │
 │   { email }     │ Password Broker          │
 │                 │ sendResetLink ───────────▶ enqueue ResetPasswordNotification
 │◀─ 200 (generic)─│                          │
 │                 │
User mở email, bấm "Đặt lại mật khẩu" → ${FRONTEND_URL}/password-reset?token=…&email=…
 │                 │
 │── POST /auth/password/reset ─▶
 │   { email, token, password,│ Password Broker
 │     password_confirmation }│ reset
 │◀─ 200 / 422 ────│
```

## 4. Hành vi & quy tắc nghiệp vụ

- **Verify token:** signed URL Laravel (`URL::temporarySignedRoute`), TTL `config('auth.verification.expire', 60)` phút. Hash = sha1(email) — kiểm tra ở controller chống bypass.
- **Reset token:** Password Broker chuẩn Laravel, hash bcrypt lưu `password_reset_tokens` (Laravel có migration sẵn), TTL `config('auth.passwords.users.expire', 60)` phút. Throttle gửi lại: `config('auth.passwords.users.throttle', 60)` giây giữa 2 lần.
- **Throttle endpoint:**
  - `POST /auth/email/verify/resend`: 6/giờ/user (`throttle:6,60`).
  - `POST /auth/password/forgot`: 5/15p/IP+email (`throttle:5,15`).
  - `POST /auth/password/reset`: 30/giờ/IP (`throttle:30,60`).
  - `GET /auth/email/verify/{id}/{hash}`: 6/phút/IP (chống brute hash).
- **Anti-enumerate:** `POST /auth/password/forgot` luôn trả `200 { data: { sent: true } }` bất kể email tồn tại không (đúng OWASP).
- **Idempotency verify:** click link lần 2 với cùng signed URL → vẫn redirect success (user đã verified).
- **Queue:** mọi notification implement `ShouldQueue` + `public string $queue = 'notifications'`. `tries=3`, backoff [10s, 60s, 300s].
- **Localization:** chuỗi email tiếng Việt (`APP_LOCALE=vi`), title `[CMBcoreSeller] Xác thực email`, `[CMBcoreSeller] Chào mừng`, `[CMBcoreSeller] Đặt lại mật khẩu`.
- **Phân quyền:** mọi user có thể self-service (verify mình, quên mật khẩu).
- **Audit:** không log token/secret; chỉ log event "user X verified email" / "password reset requested" qua channel `auth` (`config/logging.php`).

## 5. Dữ liệu

- `users.email_verified_at` — đã có cột, đã có cast `datetime`. Không cần migration mới.
- `password_reset_tokens` — Laravel default đã tạo (kiểm trong migrations).
- **Migration mới:** không.
- **Domain event phát ra:**
  - `Illuminate\Auth\Events\Registered` (Laravel built-in) — đã fire khi register.
  - `Illuminate\Auth\Events\Verified` — fire khi `markEmailAsVerified()`.
- **Listeners đăng ký mới (`NotificationsServiceProvider`):**
  - `Registered` → `SendEmailVerificationNotification` (Laravel default, đăng ký qua `EventServiceProvider` mặc định — kiểm).
  - `Verified` → `SendWelcomeEmailOnVerified` (em viết).

## 6. API & UI

### Endpoint mới (cập nhật `docs/05-api/endpoints.md` mục Auth)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/auth/email/verify/{id}/{hash}` | signed URL | query `expires`, `signature` | `302` redirect `${FRONTEND_URL}/email-verified?status=success\|already\|invalid`. Hash sai/hết hạn ⇒ redirect `status=invalid`. |
| POST | `/api/v1/auth/email/verify/resend` | sanctum | — | `200 { data: { sent: true } }` (idempotent — đã verified ⇒ `200 { data: { sent: false, reason: 'already_verified' } }`). Throttle `6/60`. |
| POST | `/api/v1/auth/password/forgot` | — | `{ email }` | `200 { data: { sent: true } }` (generic — không xác nhận email tồn tại). Throttle `5/15`. |
| POST | `/api/v1/auth/password/reset` | — | `{ email, token, password, password_confirmation }` | `200 { data: { reset: true } }`. Lỗi: `422 INVALID_RESET_TOKEN` (token sai/hết hạn), `422 VALIDATION_FAILED` (password yếu / không khớp). Throttle `30/60`. |

### Thay đổi response hiện có
- `GET /api/v1/auth/me` `data` thêm trường `email_verified_at: ISO|null` để FE biết hiện banner "chưa verify".
- `POST /api/v1/auth/register` response data cũng có `email_verified_at: null`.

### Middleware mới
- `verified` (Laravel built-in `EnsureEmailIsVerified`) — gắn vào group tenant trong `routes/api.php`. Handler custom trả JSON envelope `EMAIL_NOT_VERIFIED` thay vì redirect.

### Job mới (cập nhật `docs/07-infra/queues-and-scheduler.md` queue `notifications`)
- `VerifyEmailNotification` (queue: `notifications`, tries 3, backoff 10/60/300s).
- `WelcomeNotification` (queue: `notifications`, tries 3).
- `ResetPasswordNotification` (queue: `notifications`, tries 3).
- Listener `SendWelcomeEmailOnVerified` (implements `ShouldQueue`, queue `notifications`).

### Config mới
- `config/notifications.php`:
  ```php
  return [
      'brand' => [
          'name' => env('NOTIFICATIONS_BRAND_NAME', 'CMBcoreSeller'),
          'tagline' => env('NOTIFICATIONS_BRAND_TAGLINE', 'Quản lý bán hàng đa sàn'),
          'support_email' => env('NOTIFICATIONS_SUPPORT_EMAIL', 'support@cmbcore.com'),
          'primary_color' => env('NOTIFICATIONS_PRIMARY_COLOR', '#10B981'),
          'logo_url' => env('NOTIFICATIONS_LOGO_URL'),
      ],
      'frontend_url' => env('FRONTEND_URL', env('APP_URL')),
  ];
  ```

## 7. Edge case & lỗi

- **Email không tới** (SMTP fail): job retry 3 lần với backoff; quá hạn → Horizon failed jobs UI. User vẫn có thể resend.
- **Verify link hết hạn:** redirect `?status=invalid` (FE hiện CTA "gửi lại"). User vẫn login được vào `/auth/me` để bấm resend.
- **Reset token đã dùng:** Password Broker trả `INVALID_TOKEN` → controller chuyển sang code chuẩn `INVALID_RESET_TOKEN`.
- **User đổi email:** sau khi `PATCH /auth/profile` đổi email → `email_verified_at` cũng set lại `null` + tự fire `Registered` (đã có ở controller, hoặc thêm). **Out of scope cho spec này** — note vào backlog.
- **Race condition click 2 lần:** lần 2 vẫn 200, `markEmailAsVerified()` no-op.
- **Tenant header có nhưng user chưa verify:** middleware `verified` chặn 403 trước cả middleware `tenant` (thứ tự middleware quan trọng).

## 8. Bảo mật & dữ liệu cá nhân

- Token verify/reset chỉ qua link signed URL, không log raw. Email log redact theo `config/logging.php`.
- Chỉ PII của user (chủ shop) — không phải buyer PII (`08-security-and-privacy.md` §6). Vẫn không hiển thị PII của buyer trong template email.
- `support_email` ở config, không hardcode.
- Anti-enumerate: forgot password response generic.
- Rate limit chống brute force theo bảng §4.
- Mail driver prod = SMTP (TLS); dev = `mailpit` (đã có trong override.yml).

## 9. Kiểm thử

**Feature tests** (`tests/Feature/Notifications/`):
- `EmailVerificationTest`:
  - register fires `Registered` & dispatches `VerifyEmailNotification` (`Notification::fake`).
  - signed URL valid → user `email_verified_at` set + `Verified` event fired + redirect success.
  - signed URL với hash sai → redirect `status=invalid`, user vẫn chưa verified.
  - signed URL hết hạn (`temporarySignedRoute(now()->subMinutes(5))`) → redirect `status=invalid`.
  - resend khi đã verified → 200 với `sent: false, reason: already_verified`.
  - resend bình thường → dispatch `VerifyEmailNotification`.
  - hit `/tenant` khi chưa verify → 403 `EMAIL_NOT_VERIFIED`.
  - hit `/tenant` khi đã verify → 200 (luồng cũ).
- `PasswordResetTest`:
  - forgot với email tồn tại → dispatch `ResetPasswordNotification` + 200.
  - forgot với email không tồn tại → 200 (generic) + không dispatch.
  - reset với token + password hợp lệ → 200, mật khẩu mới hash đúng, có thể login.
  - reset với token sai → 422 `INVALID_RESET_TOKEN`.
  - reset với password yếu → 422 `VALIDATION_FAILED`.
- `WelcomeOnVerifiedTest`:
  - fire `Verified` event → listener dispatch `WelcomeNotification`.

**Unit/Mailable render** (`tests/Unit/Notifications/`):
- `VerifyEmailNotificationTest::test_renders_with_brand_and_cta` — assert mail HTML contain "CMBcoreSeller", "Xác thực email" button, URL ký.
- `ResetPasswordNotificationTest::test_renders_reset_url_to_frontend` — assert URL chứa `${FRONTEND_URL}/password-reset?token=…&email=…`.

**Manual visual** (dev):
- `docker compose up -d`, register, mở Mailpit `http://localhost:8025`, kiểm template hiển thị OK cả desktop + mobile width.

## 10. Tiêu chí hoàn thành (Acceptance criteria)

- [ ] Module `Notifications` tạo + đăng ký trong `bootstrap/providers.php`.
- [ ] 4 endpoint + middleware `verified` JSON-envelope hoạt động.
- [ ] `User implements MustVerifyEmail`; 3 notification class qua queue `notifications`.
- [ ] Template Blade `layout + verify + welcome + reset` render tốt ở Mailpit (desktop + mobile).
- [ ] Seeder `DemoOwnerSeeder` (hoặc tương đương) set `email_verified_at` cho owner demo.
- [ ] `endpoints.md` mục Auth bổ sung 4 endpoint mới + sửa response register/me thêm `email_verified_at`.
- [ ] `queues-and-scheduler.md` queue `notifications` ghi rõ 3 job + listener.
- [ ] `roadmap.md` Phase 6.5 đánh dấu sub-task "kênh `mail` + verify + welcome + reset" ✅.
- [ ] `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test` xanh.
- [ ] Commit Conventional Commit `feat(notifications): account verification + branded email templates (SPEC 0022)`.

## 11. Câu hỏi mở

- Logo: hiện chưa có asset chính thức cho `CMBcoreSeller` — tạm dùng wordmark text. Khi có file PNG/SVG thì set `NOTIFICATIONS_LOGO_URL` ở `.env`.
- Đổi email (sau verify) → tự động fire `Registered` lại để gửi verify cho địa chỉ mới? Hiện ngoài scope.
- Notification preferences UI (tắt welcome / chỉ verify) → spec sau khi có thêm channel.
