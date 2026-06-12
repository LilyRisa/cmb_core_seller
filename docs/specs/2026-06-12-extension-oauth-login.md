# Đăng nhập Chrome Extension qua redirect OAuth

- Date: 2026-06-12
- Status: Implemented
- Hợp đồng 2 repo: `EXTENSION_OAUTH_LOGIN_CONTRACT.md` (repo `cmb_copy_product`). File này ghi phần BACKEND (`cmb_core_seller`).

## Mục tiêu

Extension đăng nhập **bằng chính web `app.cmbcore.com`** (tận dụng captcha/login sẵn có), nhận token qua redirect — không có form login trong extension, không hardcode extension id.

## Backend (đã làm)

- Web route `GET /extension/connect` (`routes/web.php`, middleware `web` — session/Sanctum stateful; **không** bọc `auth`, tự xử lý). `ExtensionConnectController::connect`.
- Luồng:
  1. Validate `redirect_uri` theo allowlist `^https://[a-p]{32}\.chromiumapp\.org(/.*)?$` (+ `config('integrations.extension.dev_redirect_uris')` cho dev). Sai ⇒ `400`, không redirect.
  2. Guest ⇒ `redirect('/login?redirect=<path /extension/connect tương đối>')`.
  3. **Đã đăng nhập nhưng CHƯA verify email ⇒ `redirect('/?redirect=...')`** — KHÔNG mint token (giữ luật SPEC 0022). SPA hiện `VerifyEmailPage`; verify xong tự quay lại `/extension/connect`.
  4. Đã verify ⇒ tenant = `session('current_tenant_id')` hoặc tenant đầu tiên ⇒ mint token `copy-product:push` (như `ExtensionTokenController::store`) ⇒ `302` về `redirect_uri#token=..&token_id=..&tenant_id=..&state=..`.
- Token ở **fragment** (`#`) → không lọt access log. Ability hẹp `copy-product:push` (không `*`).

## SPA (đã làm)

- `lib/extRedirect.ts`: chỉ chấp nhận path nội bộ `/extension/connect`. `captureExtRedirect` lưu `?redirect=` vào localStorage; `takeExtRedirect` lấy & xoá.
- `LoginPage` / `RegisterPage`: lưu `redirect` khi mở trang.
- `RequireAuth`: bắt `?redirect=` ở root; **chỉ khi user đã verify** mới `window.location = redirect` (tiêu thụ). ⇒ Đăng ký xong **phải verify email rồi mới chuyển hướng** về extension — đúng yêu cầu.

## Nghiệm thu

- `GET /extension/connect?redirect_uri=https://<32 a-p>.chromiumapp.org/&state=x`:
  - Đã login + verified ⇒ `302` `redirect_uri#token=..&token_id=..&tenant_id=..&state=x`.
  - Chưa login ⇒ `/login?redirect=...`; login/đăng ký + verify xong quay lại ⇒ token.
  - Chưa verify ⇒ về SPA verify, **không** token.
  - `redirect_uri` lạ (vd `https://evil.com`) ⇒ `400`.
- Token gọi `POST /api/v1/products` (kèm `X-Tenant-Id`) ⇒ 2xx; `GET /api/v1/orders` ⇒ `403` (ability hẹp).

Tests: `tests/Feature/Auth/ExtensionConnectTest.php`.
