# API endpoints (`/api/v1`)

**Status:** Living document · **Cập nhật:** 2026-05-11

> Nguồn người-đọc-được của API. Mọi endpoint mới ⇒ thêm vào đây (đường dẫn, method, quyền cần, request, response, lỗi đặc thù). Quy ước chung: [`conventions.md`](conventions.md). Auth: Sanctum SPA cookie (gọi `GET /sanctum/csrf-cookie` trước khi gửi request thay đổi dữ liệu). Tenant: header `X-Tenant-Id`.

## Hệ thống

| Method | Path | Auth | Mô tả |
|---|---|---|---|
| GET | `/api/v1/health` | — | Probe DB / cache / Redis / queue worker. `200` nếu mọi check *critical* (DB) "ok", `503` nếu không. Trả `data.status` (`ok`/`degraded`), `data.checks.{database,cache,redis,queue}.status`, `app`, `env`, `time`. Mọi response có header `X-Request-Id`. |

## Auth & phiên (public + authenticated)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| POST | `/api/v1/auth/register` | — | `name`, `email`, `password`, `password_confirmation`, `tenant_name?` | `201` `{ data: { id, name, email, tenants:[{id,name,slug,role}] } }` — tạo user + tenant mới (caller = `owner`) + đăng nhập phiên. Lỗi: `422 VALIDATION_FAILED` (email trùng, mật khẩu < 8…). |
| POST | `/api/v1/auth/login` | — | `email`, `password`, `remember?` | `200` `{ data: {…user…} }`. Sai thông tin: `422 INVALID_CREDENTIALS`. |
| POST | `/api/v1/auth/logout` | sanctum | — | `204`. |
| GET | `/api/v1/auth/me` | sanctum | — | `200` `{ data: {…user… với tenants[]} }`. Chưa đăng nhập: `401`. |

## Tenant (workspace)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/tenants` | sanctum | — | `200` `{ data:[{id,name,slug,status,role}] }` — các tenant user thuộc về. |
| POST | `/api/v1/tenants` | sanctum | `name` | `201` `{ data:{id,name,slug,role:'owner'} }` — tạo tenant mới, caller = owner. |
| GET | `/api/v1/tenant` | sanctum + tenant | — | `200` `{ data:{id,name,slug,status,settings,current_role} }`. Thiếu header tenant: `400 TENANT_REQUIRED`. Không thuộc tenant: `403 TENANT_FORBIDDEN`. |
| GET | `/api/v1/tenant/members` | sanctum + tenant (owner/admin) | — | `200` `{ data:[{id,name,email,role}] }`. Vai trò khác: `403`. |
| POST | `/api/v1/tenant/members` | sanctum + tenant (owner/admin) | `email`, `role` (một trong `owner\|admin\|staff_order\|staff_warehouse\|accountant\|viewer`) | `201` `{ data:{id,name,email,role} }`. User chưa có tài khoản: `422 USER_NOT_FOUND` (luồng mời qua email bổ sung sau). Đã là thành viên: `409 ALREADY_MEMBER`. Ghi `audit_logs`. |

## Webhook & OAuth (ngoài `/api`)

Xem [`webhooks-and-oauth.md`](webhooks-and-oauth.md). Hiện `POST /webhook/{tiktok\|shopee\|lazada}` và `GET /oauth/{tiktok\|shopee\|lazada}/callback` là stub (`501`) — handler thật ở Phase 1 (TikTok trước).

## Sắp có (theo roadmap)

`/api/v1/channel-accounts` (kết nối/ngắt gian hàng) · `/api/v1/orders` (+ `/{id}/status`, `/bulk`) · `/api/v1/sku-mappings` · `/api/v1/print-jobs` · `/api/v1/jobs/{id}` … — thêm vào đây khi xây.
