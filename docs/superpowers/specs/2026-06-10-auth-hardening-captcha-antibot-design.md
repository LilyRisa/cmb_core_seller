# SPEC 2026-06-10 — Auth hardening: Turnstile CAPTCHA + chặn email giả + prune rác

**Mục tiêu:** Chống bot/brute-force và rác DB ở luồng auth. Email verification ĐÃ bắt buộc sẵn (middleware `verified` → `403 EMAIL_NOT_VERIFIED`); throttle ĐÃ có (register/login 15/1, forgot 5/15, reset 30/60). Bổ sung:

1. **CAPTCHA Cloudflare Turnstile** trên register / login / forgot.
2. **Chặn domain email dùng-một-lần** lúc đăng ký.
3. **Prune tài khoản chưa xác minh quá hạn** (mặc định 1 ngày) để dọn rác.

## A. CAPTCHA (Turnstile)
- **`config/captcha.php`** (file RIÊNG, không đụng integrations.php): `enabled` (`CAPTCHA_ENABLED`, mặc định false), `provider='turnstile'`, `site_key` (`TURNSTILE_SITE_KEY`), `secret` (`TURNSTILE_SECRET`), `verify_url`, `disposable_domains` (denylist).
- **`CaptchaVerifier`** (Tenancy/Services): `verify(?string $token, ?string $ip): bool` — POST `challenges.cloudflare.com/turnstile/v0/siteverify`; `enabled=false` ⇒ luôn true (dev/test).
- **Middleware `VerifyCaptcha`** (alias `captcha`): đọc `captcha_token` (body) / header `cf-turnstile-response`; fail ⇒ `422 CAPTCHA_FAILED`. Pass-through khi `enabled=false`. Gắn vào `auth/register`, `auth/login`, `auth/password/forgot` (cùng các route đã có throttle).
- **Public config** `GET /api/v1/auth/captcha-config` → `{enabled, provider, site_key}` (AuthController) để FE render widget.
- **FE** `lib/captcha.tsx`: `useCaptchaConfig()` + `<Captcha onVerify={token=>…} />` (load script Turnstile, render khi enabled). 3 trang Login/Register/ForgotPassword: thêm widget + gửi `captcha_token`; disable submit tới khi có token (khi enabled). Khi disabled: ẩn widget, không gửi.

## B. Chặn email dùng-một-lần (đăng ký)
- Rule `NotDisposableEmail` (Tenancy/Rules) dùng `config('captcha.disposable_domains')` (mailinator, 10minutemail, tempmail, guerrillamail, yopmail, …). `AuthController.register` thêm rule cho `email` → `422` "Email dùng một lần không được chấp nhận." (Không check MX.)

## C. Prune tài khoản chưa xác minh
- Command `users:prune-unverified --days=1 [--dry-run]` (app/Console/Commands): user `email_verified_at IS NULL`, `created_at < now-Nd`, `is_sub_account=false`, mà tenant sở hữu **rỗng hoàn toàn** (không channel_accounts/ad_accounts/orders/products) và user là thành viên duy nhất.
- Xóa **transaction từng account** (tenant + row tự-sinh); lỗi FK ⇒ rollback + bỏ qua + log (an toàn, không trạng thái dở). In/log số xóa.
- Lịch: `Schedule::command('users:prune-unverified')->daily()` (routes/console.php).

## Test
- `CaptchaVerifier` (Http::fake success/fail; disabled→true); `VerifyCaptcha` middleware (enabled cần token, disabled bỏ qua); register chặn disposable domain (422); prune chỉ xóa unverified-rỗng-quá-hạn, GIỮ user verified / có dữ liệu / còn hạn / sub-account.

## Ngoài phạm vi
MX/DNS check; captcha form khác; soft-delete/khôi phục tài khoản đã prune; thay đổi chính sách `verified` (đã bắt buộc sẵn).
