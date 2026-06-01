# ADR-0023: Auth đa client — SPA cookie (web) + Sanctum bearer token (mobile / 3rd-party)

- **Trạng thái:** Accepted
- **Ngày:** 2026-06-01
- **Người quyết định:** Team
- **Liên quan:** SPEC `docs/superpowers/specs/2026-06-01-mobile-token-auth-design.md`, ADR-0001 (SPA embedded), `docs/05-api/conventions.md` §2

## Bối cảnh

App mobile của seller cần đăng nhập vào cùng API `/api/v1`. Web SPA hiện dùng
**Sanctum stateful (cookie + CSRF)** — không phù hợp cho mobile (không có cookie
jar/CSRF như trình duyệt). Câu hỏi: cấp token cho mobile theo cơ chế nào, và đặt
ở đâu để không phá luồng SPA hiện có.

**Sự thật nền:**
- Mọi route nghiệp vụ đã nằm sau middleware `auth:sanctum` (`routes/api.php`).
  Guard `auth:sanctum` xác thực **cả** session cookie (stateful) lẫn **bearer
  token** — nên một personal access token dùng được **toàn bộ** endpoint hiện có,
  không cần viết lại API cho mobile.
- `User` đã có trait `HasApiTokens`; guard `sanctum` (provider `users`) đã khai
  báo trong `config/auth.php`. Hạ tầng token sẵn sàng, chỉ thiếu endpoint phát
  hành/thu hồi.

**Phương án đã cân nhắc:**

A. **Bearer dài hạn mỗi thiết bị (Sanctum personal access token thuần)** — login
   cấp 1 token gắn tên thiết bị, hết hạn 60 ngày, thu hồi qua quản lý thiết bị.
   - ✓ Đơn giản, tận dụng hạ tầng có sẵn; không thêm bảng.
   - ✓ Cùng `auth:sanctum` ⇒ token dùng được mọi API web.
   - ✗ Token lộ thì hiệu lực tới khi hết hạn/thu hồi (giảm thiểu bằng hạn 60 ngày
     + quản lý/thu hồi thiết bị).

B. **Access token ngắn + refresh token xoay vòng (OAuth-style).**
   - ✓ An toàn hơn khi token rò rỉ.
   - ✗ Sanctum không hỗ trợ sẵn ⇒ tự dựng bảng refresh, endpoint refresh, rotation,
     thu hồi — phức tạp, thừa so với nhu cầu hiện tại (YAGNI).

C. **Mở rộng `AuthController::login` cấp token khi có `device_name`.**
   - ✗ Trộn 2 chế độ auth trong 1 method; route SPA nằm trong nhóm middleware
     stateful (CSRF) còn bearer cần nhóm `auth:sanctum` khác ⇒ rối, dễ lỗi.

## Quyết định

Chọn **phương án A**, đặt trong **controller + route group token riêng** (tách
hẳn luồng SPA cookie):

- `TokenAuthController` (module Tenancy) cấp/thu hồi token + quản lý thiết bị:
  `POST /auth/token` (public, throttle 15/1), `DELETE /auth/token` (logout),
  `GET /auth/devices`, `DELETE /auth/devices/{id}`, `DELETE /auth/devices`.
- Hạn token: per-token `expires_at = now()+60 ngày` (`config('sanctum.mobile_token_days')`).
  `config/sanctum.php` đặt `expiration = null` để KHÔNG override hạn từng token
  (vẫn không ảnh hưởng session SPA).
- Abilities mặc định `['*']` cho v1 (để ngỏ scope hẹp hơn về sau).
- Chỉ seller `users`; super-admin app vẫn web/session-only.
- Shape `data.user` dùng chung trait `ResolvesAuthUserPayload` giữa `AuthController`
  (SPA) và `TokenAuthController` (mobile) — 1 nguồn duy nhất.

## Hệ quả

**Tích cực:**
- Mobile dùng lại **toàn bộ** API web qua `Authorization: Bearer <token>` +
  `X-Tenant-Id`. Không phân nhánh API theo loại client.
- Đường mở rộng chuẩn cho mọi client không-SPA về sau (3rd-party integration):
  cùng endpoint `POST /auth/token`, chỉ cần cấp abilities/hạn phù hợp.
- Không thêm bảng (dùng `personal_access_tokens` của Sanctum).

**Tiêu cực / đánh đổi:**
- Không có refresh rotation ⇒ token sống 60 ngày; rủi ro rò rỉ giảm thiểu bằng
  thu hồi theo thiết bị. Nếu sau này cần bảo mật cao hơn → xem lại phương án B.
- `expiration` global đổi sang `null`: hiện chưa token nào khác được phát nên an
  toàn; nếu sau này cấp token cho mục đích khác cần hạn riêng thì set per-token.

**Quy ước:**
- Endpoint nghiệp vụ vẫn chịu `verified` + `tenant` + `plan.over_quota_lock` như
  web (mobile không được "lách" ràng buộc).
- Tài liệu: `docs/05-api/conventions.md` §2 + `docs/05-api/endpoints.md` (mục Auth).
