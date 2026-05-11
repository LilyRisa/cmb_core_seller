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

## Gian hàng (Channels — Phase 1)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/channel-accounts` | sanctum + tenant (`channels.view`) | — | `{ data:[{id,provider,external_shop_id,shop_name,shop_region,seller_type,status,token_expires_at,last_synced_at,last_webhook_at,has_shop_cipher,...}], meta:{ connectable_providers:[{code,name}] } }`. Tokens **không** lộ ra. |
| POST | `/api/v1/channel-accounts/{provider}/connect` | sanctum + tenant (`channels.manage`) | `redirect_after?` | `{ data:{ auth_url, provider } }` — SPA redirect tới `auth_url`. `provider ∈ {tiktok}` (Phase 1). Provider không kết nối được ⇒ `422 PROVIDER_NOT_CONNECTABLE`. |
| DELETE | `/api/v1/channel-accounts/{id}` | sanctum + tenant (`channels.manage`) | — | `{ data:{…account, status:'revoked'} }` — `connector.revoke()` best-effort; lịch sử đơn giữ lại; dừng sync. `404` nếu không thuộc tenant. |
| POST | `/api/v1/channel-accounts/{id}/resync` | sanctum + tenant (`channels.manage`) | — | `{ data:{ queued:true, channel_account_id } }`. Account không `active` ⇒ `409`. |

## Đơn hàng (Orders — Phase 1)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/orders` | sanctum + tenant (`orders.view`) | query: `status` (csv mã chuẩn), `source` (csv), `channel_account_id`, `q` (mã đơn / tên người mua), `placed_from` / `placed_to` (YYYY-MM-DD), `has_issue` (1), `tag`, `sort` (`-placed_at`\|`placed_at`\|`-grand_total`\|`grand_total`), `page`, `per_page` (≤100), `include=items` | `{ data:[OrderResource], meta:{ pagination:{page,per_page,total,total_pages} } }`. `OrderResource`: `status` (mã chuẩn) + `status_label` + `raw_status`, tiền là số nguyên VND đồng + `currency`, `items_count`, `has_issue`/`issue_reason`, `tags`, `note`, `packages`, các mốc thời gian ISO-8601. |
| GET | `/api/v1/orders/{id}` | sanctum + tenant (`orders.view`) | — | `{ data: OrderResource kèm `items[]` & `status_history[]` }`. `404` nếu không thuộc tenant. |
| GET | `/api/v1/orders/stats` | sanctum + tenant (`orders.view`) | cùng filter như `/orders` (trừ `status`/`page`) | `{ data:{ total, has_issue, by_status:{ <mã>: số lượng } } }` — dùng cho badge ở status tabs. |
| POST | `/api/v1/orders/{id}/tags` | sanctum + tenant (`orders.update`) | `{ add?:string[], remove?:string[] }` | `{ data: OrderResource }`. |
| PATCH | `/api/v1/orders/{id}/note` | sanctum + tenant (`orders.update`) | `{ note: string\|null }` | `{ data: OrderResource }`. *(Đổi trạng thái "lõi" của đơn sàn: chặn ở Phase 1 — chỉ tag/note.)* |

## Dashboard

| Method | Path | Auth | Response |
|---|---|---|---|
| GET | `/api/v1/dashboard/summary` | sanctum + tenant (`dashboard.view`) | `{ data:{ channel_accounts:{total,active,needs_reconnect}, orders:{today,to_process,ready_to_ship,shipped,has_issue,total}, revenue_today } }`. |

## Webhook & OAuth (ngoài `/api`)

Xem [`webhooks-and-oauth.md`](webhooks-and-oauth.md). `POST /webhook/tiktok` → `WebhookController@handle` (verify chữ ký → ghi `webhook_events` → 200 → `ProcessWebhookEvent`); sai chữ ký ⇒ `401`. `GET /oauth/tiktok/callback?app_key=&code=&state=` → `OAuthCallbackController` (đổi token, tạo `channel_account`, redirect `/channels?connected=tiktok` hoặc `?error=…`). `shopee`/`lazada`: route + handler tồn tại nhưng connector chưa có ⇒ `404 UNKNOWN_PROVIDER` (Phase 4).

## Sổ khách hàng (Customers — Phase 2, SPEC-0002 Draft)

> Spec đang Draft — khi Implemented, dời các dòng dưới đây thành bảng đầy đủ (như bảng Orders / Channels phía trên), kèm exact request/response. Tóm tắt hợp đồng:

| Method | Path | Auth | Mô tả |
|---|---|---|---|
| GET | `/api/v1/customers` | sanctum + tenant (`customers.view`) | Query: `q` (tên hoặc SĐT — nếu match `/^\+?\d{8,15}$/` ⇒ normalize + hash + lookup `phone_hash`; ngược lại LIKE `name`), `reputation` csv (`ok|watch|risk|blocked`), `tag`, `min_orders`, `has_note`, `sort` (`-last_seen_at|-lifetime_revenue|-cancellation_rate`), `page`, `per_page≤100`. Trả `CustomerResource[]`. |
| GET | `/api/v1/customers/{id}` | sanctum + tenant (`customers.view`) | Kèm `notes[]` 50 gần nhất. |
| GET | `/api/v1/customers/{id}/orders` | sanctum + tenant (`customers.view` + `orders.view`) | Cùng filter/format `/orders`, scoped theo customer. |
| POST | `/api/v1/customers/{id}/notes` | sanctum + tenant (`customers.note`) | `{ note, severity?, order_id? }` ⇒ `201 CustomerNoteResource`. |
| DELETE | `/api/v1/customers/{id}/notes/{noteId}` | sanctum + tenant (`customers.note` + là author hoặc owner/admin) | Auto-note không xoá được. |
| POST | `/api/v1/customers/{id}/block` | sanctum + tenant (`customers.block`) | `{ reason? }`. |
| POST | `/api/v1/customers/{id}/unblock` | sanctum + tenant (`customers.block`) | — |
| POST | `/api/v1/customers/{id}/tags` | sanctum + tenant (`customers.note`) | `{ add?:[], remove?:[] }`. |
| POST | `/api/v1/customers/merge` | sanctum + tenant (`customers.merge`) | `{ keep_id, remove_id }` — chuyển `orders.customer_id`, gộp notes, soft-delete `remove`. |

**Sửa endpoint hiện có:** `GET /api/v1/orders/{id}` (và `GET /orders`) trả thêm field `customer` (object con) nếu `customer_id != null` và role có `customers.view`: `{ id, name, phone_masked, reputation:{score,label}, is_blocked, tags, lifetime_stats:{orders_total,orders_completed,orders_cancelled,orders_returned}, latest_warning_note? }`. Phone đầy đủ chỉ trả khi role có `customers.view_phone` (mặc định owner/admin/staff_order).

## Sắp có (theo roadmap)

`/api/v1/products`, `/api/v1/skus`, `/api/v1/sku-mappings` (Phase 2) · `/api/v1/orders` tạo đơn tay + `/{id}/status` + `/bulk` (Phase 2) · `/api/v1/print-jobs`, `/api/v1/shipments` (Phase 3) · `/api/v1/sync-runs`, `/api/v1/webhook-events` (Nhật ký đồng bộ + re-drive) · `/api/v1/jobs/{id}` … — thêm vào đây khi xây.
