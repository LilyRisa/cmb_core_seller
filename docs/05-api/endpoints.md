# API endpoints (`/api/v1`)

**Status:** Living document · **Cập nhật:** 2026-05-23

> Nguồn người-đọc-được của API. Mọi endpoint mới ⇒ thêm vào đây (đường dẫn, method, quyền cần, request, response, lỗi đặc thù). Quy ước chung: [`conventions.md`](conventions.md). Auth: Sanctum SPA cookie (gọi `GET /sanctum/csrf-cookie` trước khi gửi request thay đổi dữ liệu). Tenant: header `X-Tenant-Id`.

## Hệ thống

| Method | Path | Auth | Mô tả |
|---|---|---|---|
| GET | `/api/v1/health` | — | Probe DB / cache / Redis / queue worker. `200` nếu mọi check *critical* (DB) "ok", `503` nếu không. Trả `data.status` (`ok`/`degraded`), `data.checks.{database,cache,redis,queue}.status`, `app`, `env`, `time`. Mọi response có header `X-Request-Id`. |

## Auth & phiên (public + authenticated)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| POST | `/api/v1/auth/register` | — | `name`, `email`, `password`, `password_confirmation`, `tenant_name?` | `201` `{ data: { id, name, email, email_verified_at:null, tenants:[{id,name,slug,role}] } }` — tạo user + tenant mới (caller = `owner`) + đăng nhập phiên + dispatch `VerifyEmailNotification` qua queue `notifications` (SPEC 0022). Lỗi: `422 VALIDATION_FAILED` (email trùng, mật khẩu < 8…). |
| POST | `/api/v1/auth/login` | — | `login` (email **hoặc** username tài khoản phụ) hoặc `email`, `password`, `remember?` | `200` `{ data: {…user…} }`. Sai thông tin: `422 INVALID_CREDENTIALS`. (SPEC 0031 — chấp nhận username `{tên}@{mã shop}`) |
| POST | `/api/v1/auth/token` | — | `login` (email **hoặc** username) hoặc `email`, `password`, `device_name` | `201` `{ data: { token, user:{…AuthUser…} } }` — **đăng nhập mobile/3rd-party**: cấp bearer token (Sanctum personal access token gắn tên thiết bị, hết hạn `config('sanctum.mobile_token_days')` = 60 ngày). `user.tenants[]` mỗi phần tử gồm `{id,name,slug,code,role,role_id,role_name,permissions[]}` — `permissions` = ability strings (owner ⇒ `["*"]`). Sai thông tin: `422 INVALID_CREDENTIALS`. Throttle `15/1`. (SPEC 2026-06-01, 0031) |
| DELETE | `/api/v1/auth/token` | sanctum | — | `204` — **đăng xuất mobile**: thu hồi token bearer đang dùng. Khi xác thực bằng session SPA (không có token) ⇒ no-op, vẫn `204`. (SPEC 2026-06-01) |
| GET | `/api/v1/auth/devices` | sanctum | — | `200` `{ data: [{ id, device_name, last_used_at, created_at, current }] }` — liệt kê phiên đăng nhập (token) theo thiết bị; `current=true` cho token đang dùng. (SPEC 2026-06-01) |
| DELETE | `/api/v1/auth/devices/{id}` | sanctum | — | `204` — thu hồi 1 token theo id (chỉ token của chính user). Không thuộc user ⇒ `404 TOKEN_NOT_FOUND`. (SPEC 2026-06-01) |
| DELETE | `/api/v1/auth/devices` | sanctum | — | `204` — thu hồi **mọi** token trừ token đang dùng ("đăng xuất các thiết bị khác"). (SPEC 2026-06-01) |
| POST | `/api/v1/auth/logout` | sanctum | — | `204`. |
| GET | `/api/v1/auth/me` | sanctum | — | `200` `{ data: {id,name,email\|null,username\|null,email_verified_at,tenants[]} }`. Mỗi tenant: `{id,name,slug,code,role,role_id,role_name,permissions[]}` — `permissions` = ability strings trong tenant đó (owner ⇒ `["*"]`; SPEC 0029, 0031). Chưa đăng nhập: `401`. |
| PATCH | `/api/v1/auth/profile` | sanctum | `name?`, `email?`, `current_password?`, `password?`, `password_confirmation?` | `200` `{ data: AuthUser }` — sửa hồ sơ. Đổi email/password cần `current_password` đúng (sai ⇒ `422 INVALID_PASSWORD`); email trùng / mật khẩu <8 ⇒ `422`. (SPEC 0011) |
| GET | `/api/v1/me/preferences` | sanctum | — | `200` `{ data: { ui_shell, ui_open_tabs, ui_active_tab } }` — đọc preference giao diện của user hiện tại (`ui_shell ∈ {v1,v2}`, `ui_open_tabs:[{appKey,path}]`, `ui_active_tab:string\|null`). SPEC-0037. |
| PUT | `/api/v1/me/preferences` | sanctum | `ui_shell?` (`∈ {v1,v2}`), `ui_open_tabs?` (`[{appKey,path}]`), `ui_active_tab?` (`string\|null`) | `200` `{ data: { ui_shell, ui_open_tabs, ui_active_tab } }` — ghi (merge) preference giao diện; chỉ các key được gửi mới được cập nhật. `ui_shell` ngoài `{v1,v2}` ⇒ `422`. SPEC-0037. |
| GET | `/api/v1/auth/email/verify/{id}/{hash}` | signed URL | query `expires`, `signature` | `302` redirect `${FRONTEND_URL}/email-verified?status=success\|already\|invalid`. Hash sai / signature sai / hết hạn ⇒ `status=invalid`. Click 2 lần ⇒ `status=already`. (SPEC 0022) Throttle `6/1`. |
| POST | `/api/v1/auth/email/verify/resend` | sanctum | — | `200 { data: { sent: true } }` — dispatch lại `VerifyEmailNotification`. Đã verified ⇒ `200 { data: { sent: false, reason: 'already_verified' } }`. Throttle `6/60`. (SPEC 0022) |
| POST | `/api/v1/auth/password/forgot` | — | `{ email }` | `200 { data: { sent: true } }` (response generic — không xác nhận email có tồn tại không, chống enumerate). Dispatch `ResetPasswordNotification` qua queue `notifications` nếu email khớp. Throttle `5/15`. (SPEC 0022) |
| POST | `/api/v1/auth/password/reset` | — | `{ email, token, password, password_confirmation }` | `200 { data: { reset: true } }`. Token sai/hết hạn ⇒ `422 INVALID_RESET_TOKEN`; password không khớp / không đạt policy ⇒ `422 VALIDATION_FAILED`. **Password policy:** tối thiểu 8 ký tự, ít nhất 1 chữ hoa + 1 chữ thường + 1 ký tự đặc biệt (`min(8)->mixedCase()->symbols()`). Throttle `30/60`. (SPEC 0022) |

## Tra cứu đơn công khai (Public — SPEC 0030)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/public/track` | — | query `code` = `order_number` của **đơn tự tạo** (`source='manual'`) | `200 { data: { order_number, status, status_label, placed_at, delivered_at, carrier_name\|null, cod:{amount,is_cod}, recipient:{name,phone,area}, items:[{name,variation,qty,image}], steps:[{key,label,state}], timeline:[{at,label,source}] } }`. **PII đã mask** server-side: tên ẩn phần cuối, SĐT che giữa, địa chỉ chỉ còn xã/huyện/tỉnh; **không** trả giá/phí/grand_total/mã vận đơn. `timeline` ưu tiên scan ĐVVC (`ShipmentEvent`), fallback `OrderStatusHistory`. Code rỗng/không tồn tại/không phải manual/trùng nhiều tenant ⇒ `404 NOT_FOUND` (generic). Throttle `30/1`. Không tenant header. (SPEC 0030) |

## Thiết bị mobile — Expo Push (SPEC 0029)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| POST | `/api/v1/me/devices` | sanctum + tenant | `{ expo_push_token, platform }` (`platform ∈ {ios,android}`) | `201` `{ data: { id, expo_push_token, platform } }` — **upsert** theo `expo_push_token` (gọi nhiều lần an toàn; lần đầu set baseline `last_notified_at=now` để không push dồn lịch sử). Riêng với Web Push VAPID (`/messaging/push/*`). |
| DELETE | `/api/v1/me/devices/{id}` | sanctum + tenant | — | `204`. Không thuộc user + tenant hiện tại ⇒ `404 DEVICE_NOT_FOUND` (ownership-guarded). |

> **Expo Push digest:** `messaging:push-digest` (cron 30') gửi Expo Push "Bạn có tin nhắn mới" cho thiết bị KHÔNG hoạt động có inbound mới — song song Web Push VAPID, gate độc lập bằng `services.expo.enabled`.

## Tenant (workspace)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/tenants` | sanctum | — | `200` `{ data:[{id,name,slug,status,role}] }` — các tenant user thuộc về. |
| POST | `/api/v1/tenants` | sanctum | `name` | `201` `{ data:{id,name,slug,role:'owner'} }` — tạo tenant mới, caller = owner. |
| GET | `/api/v1/tenant` | sanctum + tenant | — | `200` `{ data:{id,name,slug,code,status,settings,current_role,current_role_id,can_manage_team} }`. Thiếu header tenant: `400 TENANT_REQUIRED`. Không thuộc tenant: `403 TENANT_FORBIDDEN`. |
| PATCH | `/api/v1/tenant` | sanctum + tenant (`tenant.settings` ⇒ owner/admin) | `name?`, `slug?` (`[a-z0-9-]`, ≤60, unique), `settings?` (merge) | `200` `{ data:{…tenant…} }` — sửa thông tin gian hàng; ghi `audit_logs`. Vai trò khác ⇒ `403`; slug sai/trùng ⇒ `422`. (SPEC 0011) |
| GET | `/api/v1/tenant/permissions` | sanctum + tenant | — | `200` `{ data:[{key,label,permissions:[{key,label,type:'view'\|'action'}]}] }` — catalog quyền gom theo tính năng. (SPEC 0031) |
| GET | `/api/v1/tenant/roles` | sanctum + tenant (`team.manage`) | — | `200` `{ data:[{id,name,permissions,is_owner,is_system,members_count}] }`. (SPEC 0031) |
| POST | `/api/v1/tenant/roles` | sanctum + tenant (`team.manage`) | `name`, `permissions[]` (∈ catalog, ≠ owner-only) | `201` `{ data:{…role…} }`. Quyền ngoài catalog/owner-only ⇒ `422`. |
| PUT | `/api/v1/tenant/roles/{role}` | sanctum + tenant (`team.manage`) | `name`, `permissions[]` | `200`. Vai trò owner ⇒ `403`. |
| DELETE | `/api/v1/tenant/roles/{role}` | sanctum + tenant (`team.manage`) | — | `204`. Owner ⇒ `403`; còn thành viên dùng ⇒ `409 ROLE_IN_USE`. |
| GET | `/api/v1/tenant/members` | sanctum + tenant (`team.manage`) | — | `200` `{ data:[{id,name,email,username,is_sub_account,role_id,role_name}] }`. (SPEC 0031) |
| POST | `/api/v1/tenant/members` | sanctum + tenant (`team.manage`) | `mode:'email'` → `email`,`role_id`; `mode:'sub'` → `name`,`password`,`role_id` | `201` `{ data:{…member…} }`. Sub-account sinh `username={tên}@{mã shop}`. `role_id` owner/không hợp lệ ⇒ `422`; user email chưa có ⇒ `422 USER_NOT_FOUND`; đã là thành viên ⇒ `409 ALREADY_MEMBER`. |
| PUT | `/api/v1/tenant/members/{user}` | sanctum + tenant (`team.manage`) | `role_id` | `200`. Đổi vai trò owner ⇒ `403`; role_id owner/không hợp lệ ⇒ `422`. |
| DELETE | `/api/v1/tenant/members/{user}` | sanctum + tenant (`team.manage`) | — | `204`. Gỡ owner ⇒ `403`. |
| POST | `/api/v1/tenant/members/{user}/reset-password` | sanctum + tenant (`team.manage`) | `password` (≥6) | `204`. Chỉ tài khoản phụ; user thường ⇒ `422 NOT_SUB_ACCOUNT`. |

## Gian hàng (Channels — Phase 1)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/channel-accounts` | sanctum + tenant (`channels.view`) | — | `{ data:[{id,provider,external_shop_id,shop_name,display_name,name,shop_region,seller_type,status,token_expires_at,last_synced_at,last_webhook_at,has_shop_cipher,...}], meta:{ connectable_providers:[{code,name}] } }`. `name` = `display_name ?? shop_name ?? external_shop_id`. Tokens **không** lộ ra. |
| POST | `/api/v1/channel-accounts/{provider}/connect` | sanctum + tenant (`channels.manage`) | `redirect_after?` | `{ data:{ auth_url, provider } }` — SPA redirect tới `auth_url`. `provider ∈ {tiktok}` (Phase 1). Provider không kết nối được ⇒ `422 PROVIDER_NOT_CONNECTABLE`. |
| PATCH | `/api/v1/channel-accounts/{id}` | sanctum + tenant (`channels.manage`) | `{ display_name: string\|null }` | `{ data: ChannelAccountResource }` — đặt alias hiển thị (hai shop có thể trùng `shop_name`). `null`/rỗng = bỏ alias. |
| DELETE | `/api/v1/channel-accounts/{id}` | sanctum + tenant (`channels.manage`) | `{ confirm: "<tên gian hàng>" }` (bắt buộc — phải khớp `effectiveName()` của gian hàng, không phân biệt hoa/thường; chống xóa nhầm) | `{ data:{ deleted_orders:N, unlinked_skus:M } }` — **xóa kết nối + TẤT CẢ đơn hàng của gian hàng + HỦY mọi liên kết SKU** (`sku_mappings`/`channel_listings`) của nó; nhả tồn đã giữ chỗ cho các đơn đó; `connector.revoke()` best-effort; đơn & gian hàng được soft-delete (có thể khôi phục ở DB nếu cần). `confirm` không khớp ⇒ `422` (key `confirm`). `404` nếu không thuộc tenant. *(Trước đây chỉ "tạm ngắt" giữ lịch sử đơn — nay là "xóa hẳn".)* |
| POST | `/api/v1/channel-accounts/{id}/resync` | sanctum + tenant (`channels.manage`) | — | `{ data:{ queued:true, channel_account_id } }` — dispatch `SyncOrdersForShop`. Account không `active` ⇒ `409`. |
| POST | `/api/v1/channel-accounts/{id}/resync-listings` | sanctum + tenant (`channels.manage`) | — | `{ data:{ queued:true, channel_account_id } }` — dispatch `FetchChannelListings` (kéo listing của shop về `channel_listings` + auto-match SKU). Account không `active` ⇒ `409`; connector không hỗ trợ `listings.fetch` ⇒ `422`. |
| PATCH | `/api/v1/channel-accounts/{id}/auto-rts` | sanctum + tenant (`channels.manage`) | `{ auto_rts_after_print: boolean }` | `{ data: ChannelAccountResource }` — bật/tắt tự động đẩy RTS Lazada sau khi in tem. **Chỉ cho gian hàng Lazada** — provider khác ⇒ `422`. Khi ON: sau khi `mark-printed` trên print job của đơn Lazada, hệ thống **chỉ đẩy `/order/rts` lên sàn** (cập nhật phía Lazada) — đơn app **giữ `processing`**, vận đơn giữ `created` để kho vẫn quét đóng hàng; chỉ khi quét/đóng hàng (`markPacked`) app mới sang "Chờ bàn giao". Idempotent qua cờ `raw.rts_pushed_at`. Ghi `AuditLog`. |
| GET | `/api/v1/channel-shop-report` | sanctum + tenant + `plan.feature:shop_health_reports` | — | **Báo cáo sàn (read-only)** — gom sức khỏe/hiệu suất/điểm phạt của các gian hàng Lazada/Shopee/TikTok đã kết nối (SPEC 2026-06-06). `{ data:[{ channel_account_id, provider, shop_name, available, kind:'health'\|'performance'\|null, overall_rating, overall_label, metrics:[{key,name,group,value,unit,target,comparator,passed}], supports_penalty, penalties:[{points,violation_label,issued_at,...}], punishments:[{type_label,tier,ongoing,...}], passed_count, failed_count, total_metrics, note, error }] }`. Mỗi sàn lộ dữ liệu khác nhau (capability `report.health`/`report.penalty`): Lazada/Shopee = sức khỏe, chỉ Shopee có điểm phạt qua API, TikTok = hiệu suất 7 ngày. Lỗi/thiếu-quyền xử lý **per-shop** (`available=false` + `error`), không làm hỏng cả báo cáo. Gói thiếu feature ⇒ `402 PLAN_FEATURE_LOCKED`. |

## Nhật ký đồng bộ (Sync log — Phase 1)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/sync-runs` | sanctum + tenant (`channels.view`) | query: `channel_account_id`, `type` (csv `poll\|backfill\|webhook`), `status` (csv `running\|done\|failed`), `page`, `per_page` (≤100) | `{ data:[{id,channel_account_id,shop_name,provider,type,status,started_at,finished_at,duration_seconds,cursor,stats:{fetched,created,updated,skipped,errors},error}], meta:{ pagination } }`. Scoped theo tenant (`BelongsToTenant`). |
| POST | `/api/v1/sync-runs/{id}/redrive` | sanctum + tenant (`channels.manage`) | — | `{ data:{ queued:true, channel_account_id, type } }` — dispatch lại `SyncOrdersForShop` (kiểu `backfill` nếu run gốc là backfill, ngược lại `poll`). Account không `active` ⇒ `409`. `404` nếu không thuộc tenant. |
| GET | `/api/v1/webhook-events` | sanctum + tenant (`channels.view`) | query: `channel_account_id`, `provider`, `event_type` (csv), `status` (csv `pending\|processed\|ignored\|failed`), `signature_ok` (bool), `page`, `per_page` (≤100) | `{ data:[{id,provider,event_type,raw_type,external_id,external_shop_id,order_raw_status,channel_account_id,shop_name,signature_ok,status,attempts,error,received_at,processed_at}], meta:{ pagination } }`. Lọc theo cột `tenant_id` (bảng log — không có global scope); **payload thô không lộ ra** (có thể chứa PII buyer — SPEC 0001 §8). `order_raw_status` = trạng thái đơn do push mang theo (nếu có) — dùng để cập nhật đơn ngay cả khi re-fetch detail thất bại. |
| POST | `/api/v1/webhook-events/{id}/redrive` | sanctum + tenant (`channels.manage`) | — | `{ data:{ queued:true, webhook_event_id } }` — reset `status=pending`, xoá `error`, dispatch lại `ProcessWebhookEvent`. `404` nếu không thuộc tenant. |

## Đơn hàng (Orders — Phase 1)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/orders` | sanctum + tenant (`orders.view`) | query: `status` (csv mã chuẩn), `source` (csv), `channel_account_id`, `carrier` (csv ĐVVC), `sku` (LIKE `order_items.seller_sku`), `product` (LIKE `order_items.name`), `q` (mã đơn / tên người mua), `placed_from` / `placed_to` (YYYY-MM-DD), `has_issue` (1), `out_of_stock` (1 — đơn có ≥1 SKU âm tồn, SPEC 0013), `stage` (`prepare`\|`pack`\|`handover` — lọc theo bước xử lý dựa trên **vận đơn** không phải trạng thái đơn: `prepare`=đơn trước-giao chưa có vận đơn ("chưa chuẩn bị hàng", mọi nguồn) · `pack`=có vận đơn `pending`/`created` (đã chuẩn bị, chờ đóng gói) · `handover`=có vận đơn `packed` (chờ bàn giao); SPEC 0013), `slip` (`printable`\|`loading`\|`failed` — "tình trạng phiếu giao hàng" của đơn đã "Chuẩn bị hàng": `printable`=vận đơn open đã có tem/phiếu (`label_path`≠null) · `loading`=có vận đơn open, chưa có tem, đơn không `has_issue` (đang chờ queue lấy phiếu) · `failed`=có vận đơn open & đơn `has_issue` ("Chuẩn bị hàng" lỗi — cần "Nhận phiếu giao hàng lại"); SPEC 0013), `tag`, `sort` (`-placed_at`\|`placed_at`\|`-grand_total`\|`grand_total`), `page`, `per_page` (≤100), `include=items` | `{ data:[OrderResource], meta:{ pagination } }`. `OrderResource`: `status`+`status_label`+`raw_status`, `channel_account:{id,name,provider}` (gian hàng), `carrier`, tiền là số nguyên VND đồng + `currency`, `items_count`, `has_issue`/`issue_reason`, `out_of_stock` (bool — SPEC 0013), `tags`, `note`, `packages`, `customer` (SPEC 0002), `profit` (SPEC 0012 — xem dưới), các mốc ISO-8601. |
| GET | `/api/v1/orders/{id}` | sanctum + tenant (`orders.view`) | — | `{ data: OrderResource kèm `items[]` & `status_history[]` }`. `404` nếu không thuộc tenant. |
| GET | `/api/v1/orders/stats` | sanctum + tenant (`orders.view`) | cùng filter như `/orders` (`page` bỏ qua) | `{ data:{ total, has_issue, unmapped, out_of_stock, by_status:{ <mã>: N }, by_stage:{ prepare, pack, handover }, by_slip:{ printable, loading, failed }, by_source:[…], by_shop:[…], by_carrier:[…] } }` — đếm faceted cho status tabs / stage tabs (Chờ xử lý / Đang xử lý / Chờ bàn giao — SPEC 0013) + sub-tab "tình trạng phiếu giao hàng" (`by_slip` — hiện dưới tab "Đang xử lý" khi `failed>0`) + panel "Lọc" + banner "đơn chưa liên kết SKU" (`unmapped`) + tab "Hết hàng" (`out_of_stock`). `by_status`/`by_stage`/`by_slip`/`has_issue`/`unmapped`/`out_of_stock` áp mọi filter trừ `status`/`stage`/`slip`/`has_issue`/`out_of_stock`; `by_source`/`by_shop`/`by_carrier` áp mọi filter trừ `source`/`channel_account_id`/`carrier`. Chi tiết UI: `docs/06-frontend/orders-filter-panel.md`. |
| POST | `/api/v1/orders/sync` | sanctum + tenant (`orders.view`) | — | `{ data:{ queued: N } }` — dispatch `SyncOrdersForShop` cho mọi gian hàng `active` của tenant (nút "Đồng bộ đơn" ở trang Đơn hàng). |
| POST | `/api/v1/orders` | sanctum + tenant (`orders.create`) | `{ sub_source?, status?: pending\|processing, buyer:{name?,phone?,address?,ward?,district?,province?}, items:[{sku_id?, name?, image?, variation?, quantity?, unit_price?, discount?}], shipping_fee?, tax?, is_cod?, cod_amount?, note?, tags? }` | `201 { data: OrderResource }` — tạo đơn `source=manual`, `order_number` tự sinh, reserve tồn ngay (qua `OrderUpserted`), khớp sổ khách hàng nếu có SĐT. Mỗi dòng hàng là **một SKU hệ thống** (`sku_id` — `name`/`seller_sku`/`image` tự lấy từ SKU nếu bỏ trống) **hoặc một "sản phẩm nhanh" ad-hoc** (không `sku_id`; `name` bắt buộc, `image` là URL ảnh đã upload — không theo dõi tồn kho, không bị gắn cờ "SKU chưa ghép"). Xem `docs/03-domain/manual-orders-and-finance.md` §1. (Phase 2 / SPEC 0003.) |
| PATCH | `/api/v1/orders/{id}` | sanctum + tenant (`orders.update`) | `{ buyer?, shipping_fee?, tax?, is_cod?, cod_amount?, note?, tags? }` | `{ data: OrderResource }` — chỉ đơn `manual` chưa `shipped` (sửa line-item: backlog). `422` nếu đã bàn giao. |
| POST | `/api/v1/orders/{id}/cancel` | sanctum + tenant (`orders.update`) | `{ reason? }` | `{ data: OrderResource (cancelled) }` — chỉ đơn `manual` chưa `shipped`; release tồn. `422` nếu đã bàn giao. |
| POST | `/api/v1/orders/bulk-cancel` | sanctum + tenant (`orders.update`) | `{ ids:int[] (≤500), reason? }` | Huỷ HÀNG LOẠT **local "ngừng theo dõi"**: đẩy trạng thái về `cancelled` cho MỌI nguồn (sàn / thủ công có-mã-vận-đơn), **KHÔNG** đẩy thao tác huỷ lên sàn/ĐVVC; đánh `meta.tracking_stopped` ⇒ sync không hồi sinh; release tồn. `{ data:{ cancelled, skipped } }` (đơn đã huỷ bỏ qua). |
| POST | `/api/v1/orders/bulk-delete` | sanctum + tenant (`orders.delete`) | `{ ids:int[] (≤500) }` | Xoá mềm HÀNG LOẠT — **chỉ đơn đã huỷ** (đơn chưa huỷ bỏ qua). `{ data:{ deleted, skipped } }`. |
| POST | `/api/v1/orders/{id}/tags` | sanctum + tenant (`orders.update`) | `{ add?:string[], remove?:string[] }` | `{ data: OrderResource }`. |
| PATCH | `/api/v1/orders/{id}/note` | sanctum + tenant (`orders.update`) | `{ note: string\|null }` | `{ data: OrderResource }`. *(Đổi trạng thái "lõi" của đơn sàn: chặn — chỉ tag/note; đơn manual đi state machine qua các action riêng — Phase 3.)* |

## Sản phẩm / SKU / Tồn kho / Ghép SKU (Phase 2 — SPEC 0003)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET/POST | `/api/v1/products` | `products.view` / `products.manage` | `q?` / `{name, image?, brand?, category?, meta?}` | `ProductResource[]` (+`skus_count`) / `201`. |
| GET/PATCH/DELETE | `/api/v1/products/{id}` | `products.view` / `products.manage` | — / partial | `ProductResource` / soft delete. |
| GET | `/api/v1/skus` | `inventory.view` | `q?` (code/name/barcode), `product_id?`, `is_active?`, `low_stock?` (≤N), `page`, `per_page≤100` | `{ data:[SkuResource{...,on_hand_total,reserved_total,available_total}], meta:{pagination} }`. `SkuResource` gồm cả PIM: `spu_code, category, gtins[], base_unit, cost_price, cost_method (average\|latest), last_receipt_cost, effective_cost, ref_sale_price, ref_profit_per_unit, ref_margin_percent, sale_start_date, note, weight_grams, length_cm, width_cm, height_cm, image_url`. (`cost_price` = giá vốn bình quân gia quyền hiện tại; `effective_cost` = giá vốn hiệu lực theo `cost_method` — SPEC 0012.) |
| POST | `/api/v1/skus` | `products.manage` | `{ sku_code, name, product_id?, spu_code?, category?, barcode?, gtins?:[≤10], base_unit?, cost_price?, cost_method?:average\|latest, ref_sale_price?, sale_start_date?, note?, weight_grams?, length_cm?, width_cm?, height_cm?, attributes?, mappings?:[{channel_account_id, external_sku_id, seller_sku?, quantity?}], levels?:[{warehouse_id, on_hand?, cost_price?}] }` | `201 { data: SkuResource }` — tạo SKU + (tuỳ chọn) ghép listing sàn + tồn đầu kỳ/giá vốn theo kho trong 1 transaction. Mã trùng ⇒ `422 SKU_CODE_TAKEN`; `channel_account_id`/`warehouse_id` lạ ⇒ `422`. `on_hand>0` ⇒ movement `goods_receipt` (`ref_type='sku_create'`, ghi chú "Tồn đầu kỳ"). `cost_method` mặc định `average` (SPEC 0005 / 0012). |
| GET | `/api/v1/skus/{id}` | `inventory.view` | — | `SkuResource` + `levels[]` (theo kho, có `cost_price`) + `mappings[]` (có `channel_listing{id,channel_account_id,external_sku_id,seller_sku,title}`) + `movements[]` (50 gần nhất). |
| PATCH/DELETE | `/api/v1/skus/{id}` | `products.manage` | partial (mọi trường catalogue/PIM kể cả `is_active`, `cost_method`; FE khoá `sku_code`); thêm tuỳ chọn `mappings:[{channel_account_id, external_sku_id, seller_sku?, quantity?}]` — khi có ⇒ **thay thế toàn bộ** liên kết SKU sàn của SKU này (firstOrCreate listing + setMapping single×qty; gỡ link tới listing không còn trong danh sách; `[]` = gỡ hết). **Không** sửa `levels` (tồn) qua đây. / — | `SkuResource` (kèm `mappings`) / soft delete (`409` nếu còn `on_hand`/`reserved`). |
| POST | `/api/v1/skus/{id}/image` | `products.manage` | multipart `image` (PNG/JPG/WEBP, ≤`MEDIA_IMAGE_MAX_KB`≈5MB) | `{ data: SkuResource }` — tải/đè ảnh lên kho media (Cloudflare R2 ở prod); ghi `image_url`+`image_path`, xoá ảnh cũ. Sai định dạng/quá lớn ⇒ `422`. Cấu hình: `docs/07-infra/cloudflare-r2-uploads.md`. (SPEC 0005 §7) |
| DELETE | `/api/v1/skus/{id}/image` | `products.manage` | — | `{ data:{ deleted:true } }` — xoá object ảnh + clear `image_url`/`image_path`. |
| POST | `/api/v1/media/image` | sanctum + tenant (`orders.create` hoặc `products.manage`) | multipart `image` (PNG/JPG/WEBP, ≤`MEDIA_IMAGE_MAX_KB`≈5MB), `folder?` (a-z0-9_-, ≤40, mặc định `misc`) | `{ data:{ path, url } }` — upload ảnh chung lên kho media (Cloudflare R2 ở prod); trả object key + URL công khai để bên gọi tự lưu. Dùng cho ảnh dòng "sản phẩm nhanh" của đơn thủ công (order_item không gắn SKU). Sai định dạng/quá lớn ⇒ `422`. |
| GET/POST | `/api/v1/warehouses` | `inventory.view` / `inventory.adjust` | — / `{name, code?, address?, is_default?}` | `WarehouseResource[]` (tự đảm bảo có 1 kho mặc định) / `201`. |
| PATCH | `/api/v1/warehouses/{id}` | `inventory.adjust` | partial | `WarehouseResource`. |
| GET | `/api/v1/inventory/levels` | `inventory.view` | `sku_id?`, `warehouse_id?`, `negative?` (1), `low_stock?` (≤N), `page` | `{ data:[InventoryLevelResource{on_hand,reserved,safety_stock,available,is_negative,sku,warehouse}], meta }`. |
| POST | `/api/v1/inventory/adjust` | `inventory.adjust` | `{ sku_id, warehouse_id?, qty_change (≠0), note? }` | `201 { data: InventoryMovementResource{qty_change,type,balance_after,...} }` — `on_hand += qty_change`, ghi sổ cái, phát `InventoryChanged` ⇒ đẩy tồn. |
| POST | `/api/v1/inventory/bulk-adjust` | `inventory.adjust` | `{ kind: goods_receipt\|manual_adjust, warehouse_id?, note?, lines:[{ sku_id, qty_change }] }` (≤500 dòng) | `201 { data:{ applied:N, movements:[InventoryMovementResource] } }` — "phiếu" nhập/xuất hàng loạt: mỗi dòng → 1 movement (`ref_type='manual_bulk'`, cùng `note`). `goods_receipt` ⇒ mọi qty > 0; SKU trùng trong phiếu ⇒ `422 DUPLICATE_SKU`; SKU không thuộc tenant ⇒ `422`. (SPEC 0004) |
| POST | `/api/v1/inventory/push-stock` | `inventory.map` | `{ sku_ids:[int] }` (≤500) | `{ data:{ queued:N } }` — đẩy tồn hàng loạt theo bộ chọn: dispatch `PushStockForSku` ngay cho mỗi SKU (không delay). SKU tenant khác bị bỏ. (SPEC 0004) |
| GET | `/api/v1/inventory/movements` | `inventory.view` | `sku_id?`, `warehouse_id?`, `type?` (csv), `ref_type?`+`ref_id?`, `page` | `{ data:[InventoryMovementResource], meta }`. |
| GET/POST | `/api/v1/warehouse-docs/{type}` (`{type}` ∈ `goods-receipts`\|`stock-transfers`\|`stocktakes`) | view: `inventory.view` · ghi: `inventory.adjust`/`transfer`/`stocktake` theo type | list: `status?,warehouse_id?,q?(code),page` · create: `{ note?, warehouse_id \| from_warehouse_id+to_warehouse_id, supplier?(goods-receipts), items:[{sku_id, qty[, unit_cost] \| counted_qty}] (≤500) }` | list `{ data:[{id,code,status,type,note,item_count,warehouse_id\|from/to,supplier?,total_cost?,confirmed_at,created_at}], meta }` / `201` phiếu `draft` (+items; kiểm kê snapshot `system_qty`). Kho/SKU lạ, `from==to` ⇒ `422`. (SPEC 0010 — WMS phiếu kho) |
| GET | `/api/v1/warehouse-docs/{type}/{id}` | `inventory.view` | — | phiếu + `items[]` (`{id,sku_id,sku{id,sku_code,name}, qty\|unit_cost \| system_qty\|counted_qty\|diff}`). |
| POST | `/api/v1/warehouse-docs/{type}/{id}/confirm` | `inventory.adjust`/`transfer`/`stocktake` | — | áp vào sổ cái: nhập kho ⇒ `+on_hand`+giá vốn bình quân, chuyển kho ⇒ `transfer_out`/`transfer_in`, kiểm kê ⇒ re-snapshot+`stocktake_adjust(diff)`; phát `InventoryChanged`. Phiếu đã `confirmed`/`cancelled` ⇒ `422`. |
| POST | `/api/v1/warehouse-docs/{type}/{id}/cancel` | `inventory.adjust`/`transfer`/`stocktake` | — | huỷ phiếu `draft`; đã `confirmed` ⇒ `422`. |
| GET | `/api/v1/channel-listings` | `products.view` | `channel_account_id?`, `channel_account_ids?` (CSV — lọc nhiều shop), `sync_status?`, `mapped?` (0\|1), `q?`, `page` | `{ data:[ChannelListingResource{...,channel_stock,sync_status,is_stock_locked,is_mapped,mappings[]}], meta }`. |
| POST | `/api/v1/channel-listings/sync` | `inventory.map` | — | `{ data:{ queued: N } }` — dispatch `FetchChannelListings` cho mọi shop `active` hỗ trợ `listings.fetch` (nút "Đồng bộ listing từ sàn" ở tab Liên kết SKU). |
| PATCH | `/api/v1/channel-listings/{id}` | `inventory.map` | `{ is_stock_locked? }` | `ChannelListingResource` — ghim/bỏ ghim tự-đẩy tồn. |
| GET | `/api/v1/channel-listings/{id}/marketplace-detail` | `products.manage` | — | `{ data:{ external_product_id, title, description, images[], skus:[{external_sku_id, seller_sku, price}] } }` — đọc TRỰC TIẾP từ sàn (Lazada `/product/item/get`, Shopee `get_item_base_info`+`get_model_list`, TikTok `GET products/{id}`) để mở form sửa. `external_product_id` rỗng / sàn chưa hỗ trợ ⇒ `422`. |
| POST | `/api/v1/channel-listings/{id}/clone-to-shops` | `products.manage` | `{ channel_account_ids:int[] }` | `201 { data:[{id, provider, status}] }` — sao chép sản phẩm đã có trên sàn sang nhiều shop (đọc `getListingDetail` nguồn → master product → tạo nháp mỗi shop). **Cùng nền tảng** copy cả ngành hàng/thương hiệu/thuộc tính ⇒ revalidate (READY, đẩy được luôn); **khác nền tảng** chỉ giữ mô tả/ảnh/SKU ⇒ DRAFT (cần sửa). Bỏ qua shop nguồn. Vào "Chờ đẩy lên sàn". |
| PUT | `/api/v1/channel-listings/{id}/marketplace` | `products.manage` | `{ title?, description?, images?:[url], prices?:[{external_sku_id, price}] }` | `{ data: <như detail> }` — đẩy tiêu đề/mô tả/ảnh/giá lên sàn (Lazada `/product/update`+`/product/price_quantity/update`, Shopee `update_item`+`update_price`, TikTok `partial_edit`+`prices/update`) rồi mirror local. **Tồn KHÔNG sửa ở đây** (đẩy theo master SKU). Chỉ gửi field đã đổi. |
| POST | `/api/v1/sku-mappings` | `inventory.map` | `{ channel_listing_id, sku_id: int\|null }` | `201 { data: SkuMappingResource[] }` (1 phần tử) — ghép/đổi liên kết SKU sàn ↔ 1 SKU hàng hoá (**quan hệ 1-1**: mỗi SKU sàn chỉ thuộc 1 SKU hàng hoá, gọi lại = thay; 1 SKU hàng hoá nhận được nhiều SKU sàn). `sku_id = null` ⇒ bỏ liên kết (`200 { data: [] }`). SKU không thuộc tenant ⇒ `422`. Phát `InventoryChanged` ⇒ tính lại & đẩy tồn. *(Đã bỏ "số lượng / combo bundle".)* |
| POST | `/api/v1/sku-mappings/auto-match` | `inventory.map` | — | `{ data:{ matched: N } }` — tạo `single×1` cho mọi listing chưa ghép có `seller_sku` (chuẩn hoá) trùng `sku_code`. |
| DELETE | `/api/v1/sku-mappings/{id}` | `inventory.map` | — | `{ data:{ deleted:true } }` + đẩy tồn lại. |
| GET | `/api/v1/orders/unmapped-skus` | `orders.view` + `inventory.map` | `order_ids?` (csv — bỏ trống = mọi đơn còn dòng chưa ghép) | `{ data:[{ channel_account_id, channel_account_name, external_sku_id, seller_sku, sample_name, order_count, item_count, existing_listing_id, suggested_sku_id }] }` — các SKU sàn distinct **đã gộp** (cùng `(channel_account_id, external_sku_id\|seller_sku)`); `suggested_sku_id` = master SKU có `sku_code` chuẩn-hoá trùng `seller_sku`. (SPEC 0004) |
| POST | `/api/v1/orders/link-skus` | `inventory.map` | `{ links:[{ channel_account_id, external_sku_id?, seller_sku?, sku_id }] }` (≤200) | `{ data:{ linked:N, listings_created:N, orders_resolved:N } }` — mỗi link: `channel_listing` firstOrCreate `(channel_account_id, external_sku_id)` (tạo từ dữ liệu đơn nếu chưa có) → `sku_mappings` single×1 → sau cùng **re-resolve đồng bộ** mọi đơn `source!=manual` còn dòng chưa ghép (`OrderInventoryService::apply` — re-resolve `order_items.sku_id`, reserve tồn, clear `has_issue`, phát `InventoryChanged`) để phản hồi nhất quán ngay. Idempotent. SKU/shop khác tenant ⇒ `422`. (SPEC 0004 / 0012 sửa lỗi đơn cùng SKU không tự ghép) |

## Giao hàng & in ấn (Fulfillment — Phase 3, SPEC-0006)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/carriers` | `fulfillment.view` | — | `{ data:[{ code, name, capabilities:[], needs_credentials }] }` — các ĐVVC khả dụng (`manual` luôn có; thêm theo `INTEGRATIONS_CARRIERS`). |
| GET/POST | `/api/v1/carrier-accounts` | `fulfillment.view` / `fulfillment.carriers` | — / `{ carrier, name, credentials?, default_service?, is_default?, meta? }` | `CarrierAccountResource[]` / `201`. `credentials` được mã hoá, response chỉ trả `credential_keys`. Carrier không hỗ trợ ⇒ `422`. |
| PATCH/DELETE | `/api/v1/carrier-accounts/{id}` | `fulfillment.carriers` | partial / — | `CarrierAccountResource` / `{ deleted:true }`. |
| POST | `/api/v1/carrier-accounts/{id}/verify` | `fulfillment.carriers` | — | `{ data:{ ok, message, error_code?, expires_at?, verified_at, account } }` — gọi `connector.verifyCredentials` (vd VTP đăng nhập lấy token). |
| POST | `/api/v1/carrier-accounts/ghn/master-data` · `/ghn/shops` | `fulfillment.carriers` | `{ token, level, province_id?, district_id? }` · `{ token }` | proxy GHN (cascading mã quận/phường, danh sách shop) cho form thêm tài khoản. Cache theo hash token. |
| POST | `/api/v1/carrier-accounts/viettelpost/master-data` | `fulfillment.carriers` | `{ level: 'provinces'\|'wards', province_id? }` | proxy danh mục Viettel Post (Tỉnh/Phường đơn vị HC mới v3) cho cascading chọn địa chỉ kho. Danh mục công khai (không cần token); cache 1h. (SPEC 0034) |
| GET | `/api/v1/fulfillment/processing` | `fulfillment.view` | `stage` (`prepare`\|`pack`\|`handover`), `source` (csv nền tảng), `carrier` (csv ĐVVC), `customer` (LIKE tên/mã đơn), `product` (LIKE tên SP/`seller_sku`), `channel_account_id?`, `page`, `per_page≤200` | `{ data:[OrderResource (đã nạp `shipment`)], meta:{pagination, stage} }` — `prepare`≈cần xử lý, `pack`≈đã in chờ đóng gói, `handover`≈đã đóng gói chờ bàn giao. *(SPEC 0013: web UI nay xử lý đơn ngay trên 3 tab "công việc" của `/orders` — lọc bằng `?stage=prepare|pack|handover` — qua cột "Thao tác" + nút "Chuẩn bị hàng" hàng loạt; endpoint này giữ cho app quét đơn / API.)* |
| GET | `/api/v1/fulfillment/processing/counts` | `fulfillment.view` | cùng filter (trừ `page`) | `{ data:{ prepare:N, pack:N, handover:N } }` — badge số lượng mỗi bước. |
| GET | `/api/v1/fulfillment/ready` | `fulfillment.view` | (= `processing?stage=prepare`) | như trên — alias tương thích SPEC-0006. |
| POST | `/api/v1/fulfillment/quote` | `orders.create` / `fulfillment.ship` | `{ carrier_account_id?, weight_grams (≥1), value?, recipient:{ province, district?, ward?, address? } }` (cần `province` + **district HOẶC ward** — hỗ trợ địa chỉ 2 cấp Tỉnh+Phường sau cải cách 2025) | `{ data:{ carrier, carrier_name, fee, insurance_fee } }` **hoặc** `{ data:null }` nếu ĐVVC không hỗ trợ tính phí (vd GHN), lỗi, hoặc chưa cấu hình tài khoản. Carrier-agnostic (`connector.quote`); hiện chỉ **GHTK** trả phí. Dùng cho gợi ý phí ở màn tạo đơn — không chặn tạo đơn. |
| POST | `/api/v1/orders/{id}/ship` | `fulfillment.ship` | `{ carrier_account_id?, service?, tracking_no?, cod_amount?, weight_grams?, note? }` | `201 { data: ShipmentResource }` — **"Chuẩn bị hàng"**: tạo vận đơn, đơn `pending → processing` (đã có shipment chưa huỷ ⇒ trả lại nó). **Đơn sàn**: tự **đẩy trạng thái "sắp xếp vận chuyển / đã in đơn" lên sàn** (`arrangeShipment`) + lấy **AWB & tem PDF thật của sàn** (`getShippingDocument`) lưu vào `shipments.label_url/label_path`; **không** tự sinh mã/tem giả. TikTok dùng SDK fulfillment 202309 (`POST /fulfillment/202309/packages/{package_id}/ship` → `GET …/{package_id}` → `GET …/{package_id}/shipping_documents`); cờ `INTEGRATIONS_TIKTOK_FULFILLMENT` (mặc định true). Connector chưa hỗ trợ "luồng A" (Shopee/Lazada) hoặc gọi sàn lỗi / đơn chưa có `package_id` ⇒ **vẫn tạo vận đơn cục bộ** (mã có thể trống) + gắn cờ `has_issue` nhắc nhở (`issue_reason` là cột `text`, cắt ≤240 ký tự) — không chặn. **Đơn manual**: `carrier_account_id` để trống = mặc định / `manual` (mã `MAN-…`). Sau khi tạo vận đơn, nếu vận đơn **chưa có tem thật** của sàn/ĐVVC ⇒ tự **dispatch print job `delivery`** (queue `labels`) render phiếu giao hàng tự tạo → lưu R2 & gắn vào `shipments.label_url/label_path` ⇒ lúc in chỉ ghép PDF, không render lại (SPEC 0013). **Đơn có SKU âm tồn (`∑on_hand−∑reserved<0`) ⇒ `422` (key `order`)** — không in phiếu khi không có hàng. Trạng thái đơn không hợp lệ (terminal/returning) ⇒ `422`. (FE: đơn lợi nhuận ước tính âm ⇒ popup xác nhận trước khi gọi.) (SPEC 0013/0014) |
| POST | `/api/v1/shipments/bulk-create` | `fulfillment.ship` | `{ order_ids:[≤200], carrier_account_id?, service? }` | `{ data:{ created:[ShipmentResource], errors:[{order_id, message}] } }` — lỗi từng đơn không chặn batch. |
| POST | `/api/v1/shipments/bulk-refetch-slip` | `fulfillment.ship` | `{ order_ids:[≤200] }` | `{ data:{ ok:N, errors:[{order_id, message}] } }` — **"Nhận phiếu giao hàng lại"**: với mỗi đơn đã "Chuẩn bị hàng" nhưng chưa lấy được phiếu (lỗi gọi sàn / Gotenberg) ⇒ thử kéo tem thật của sàn / `arrangeShipment` lại (clear `has_issue` khi thành công) / queue render phiếu tự tạo. Idempotent. (SPEC 0013) |
| GET | `/api/v1/shipments` | `fulfillment.view` | `status?, carrier?, order_id?, q?(tracking/mã đơn), page, per_page` | `{ data:[ShipmentResource], meta:{pagination} }`. |
| GET | `/api/v1/shipments/{id}` | `fulfillment.view` | — | `ShipmentResource` + `events[]`. |
| POST | `/api/v1/shipments/{id}/track` | `fulfillment.ship` | — | `ShipmentResource` (+events) — gọi `connector.getTracking`, ghi `shipment_events` mới, đồng bộ trạng thái shipment & đơn. |
| POST | `/api/v1/shipments/{id}/cancel` | `fulfillment.ship` | — | `ShipmentResource` — `connector.cancel`, shipment `cancelled`, đơn về `processing` nếu chưa shipped. |
| GET | `/api/v1/shipments/{id}/label` | `fulfillment.print` | — | `302` redirect tới `label_url` (`404` nếu chưa có tem; ĐVVC `manual` không có tem). |
| POST | `/api/v1/shipments/pack` | `fulfillment.scan`\|`fulfillment.ship` | `{ shipment_ids:[≤500] }` | `{ data:{ packed:N, results:[{ id, status:'ok'\|'skipped'\|'error', reason?, technical? }] } }` — "đã gói & quét đơn" hàng loạt (vận đơn `created → packed`; **đơn `processing → ready_to_ship`** — SPEC 0013; **chưa** trừ tồn). Validate per-đơn; đơn không hợp lệ (đã huỷ / đã đóng gói / đã bàn giao) bị **skip** (`status=skipped`) thay vì dừng batch. `technical` chỉ xuất hiện khi admin bật `fulfillment.expose_technical_errors`. |
| POST | `/api/v1/shipments/handover` | `fulfillment.ship` | `{ shipment_ids:[≤500] }` | `{ data:{ handed_over:N, results:[{ id, status:'ok'\|'skipped'\|'error', reason?, technical? }] } }` — bàn giao ĐVVC hàng loạt (`created/packed → picked_up`, đơn `→shipped`, trừ tồn). Validate per-đơn; đơn không hợp lệ (đã bàn giao / đã huỷ) bị **skip** thay vì dừng batch. `technical` chỉ xuất hiện khi admin bật `fulfillment.expose_technical_errors`. |
| POST | `/api/v1/scan-pack` | `fulfillment.scan` | `{ code }` | `{ data:{ action:'pack', message, shipment, order:{id,order_number,status} } }` — quét mã vận đơn/mã đơn → "đã gói & quét đơn" (vận đơn `created → packed`; đơn `processing → ready_to_ship` — SPEC 0013). Đã đóng gói/bàn giao ⇒ `409`; không thấy ⇒ `404`. |
| POST | `/api/v1/scan-handover` | `fulfillment.ship`\|`fulfillment.scan` | `{ code }` | `{ data:{ action:'handover', … } }` — (app quét đơn gọi cái này) quét → **bàn giao ĐVVC** (`created/packed → picked_up`, đơn `shipped`, trừ tồn). Đã bàn giao ⇒ `409`. (SPEC 0009) |
| GET/POST | `/api/v1/print-jobs` | `fulfillment.print` | `type?` / `{ type:'label'\|'picking'\|'packing'\|'invoice'\|'delivery', order_ids?:[≤500], shipment_ids?:[≤500] }` | `{ data:[PrintJobResource], meta:{pagination} }` / `201 { data: PrintJobResource }` (`status=pending` → job ở queue `labels` render PDF qua Gotenberg; FE poll). `invoice` = hoá đơn bán hàng (tiền + COD); `delivery` = **phiếu giao hàng tự tạo** (cửa hàng + mã đơn + người nhận + địa chỉ + mã vận đơn/ĐVVC + bảng hàng + COD + ghi chú) — dùng cho đơn manual / khi chưa có tem thật của sàn; sau khi render được gắn vào `shipments.label_url/label_path` của đơn (lưu trữ); `label` = **tem/AWB thật của sàn-ĐVVC** (PDF từ `getShippingDocument`/`getLabel`, đã lưu trên `shipments.label_path`) — ghép 1 file. Phiếu tự render (`delivery`/`packing`/`invoice`) **mỗi đơn 1 trang**, đúng khổ `tenant.settings.print.label_size` (← `config('fulfillment.print.label_paper_size')`, mặc định `A6`). Thiếu cả order_ids & shipment_ids ⇒ `422`. `type=label` mà bộ chọn **lẫn nhiều nền tảng / nhiều ĐVVC** ⇒ `422` (CÙNG nền tảng + CÙNG ĐVVC nhưng khác gian hàng vẫn in chung được). Render xong **chưa** tính là "đã in" — `meta` mang `order_ids`/`shipment_ids` để FE mở file PDF (tab mới) rồi gọi `mark-printed` (popup). (SPEC 0009 / 0012 / 0013) |
| GET | `/api/v1/print-jobs/{id}` | `fulfillment.print` | — | `PrintJobResource` (`status`, `file_url` khi `done`, `meta.skipped[]`, `meta.order_ids[]`/`meta.shipment_ids[]`). |
| POST | `/api/v1/print-jobs/{id}/mark-printed` | `fulfillment.print` | `{ copies?:int 1..50 = 1 }` | `{ data:{ shipment_ids:[…], copies } }` — **"Đánh dấu các đơn đã in"** (popup sau khi mở file PDF): cộng `print_count += copies` + ghi `last_printed_at` cho các vận đơn trong phạm vi của print job. Danh sách đơn hiện icon phiếu in + số lần in. (SPEC 0013) |
| GET | `/api/v1/print-jobs/{id}/download` | `fulfillment.print` | — | `302` redirect tới `file_url` (`409` nếu chưa `done`). |

`OrderResource.shipment` = vận đơn mới nhất chưa huỷ `{ id, carrier, tracking_no, status, label_url, print_count, packed_at }` hoặc null. `ShipmentResource` thêm `print_count`, `last_printed_at`, `packed_at` (trạng thái vận đơn: `pending\|created\|packed\|picked_up\|in_transit\|delivered\|failed\|returned\|cancelled`).

## Dashboard

| Method | Path | Auth | Response |
|---|---|---|---|
| GET | `/api/v1/dashboard/summary` | sanctum + tenant (`dashboard.view`) | `{ data:{ channel_accounts:{total,active,needs_reconnect}, orders:{today,to_process,ready_to_ship,shipped,has_issue,total}, revenue_today } }`. |

## Thông báo in-app (Notifications — SPEC 0036)

`/api/v1/notifications/*` — `sanctum + verified + tenant`, KHÔNG gate gói. Chuông thông báo của user trong tenant hiện tại (1 dòng / 1 user; trạng thái đọc per-user). Realtime qua private channel RIÊNG user `tenant.{tenantId}.notifications.{userId}` (event `.notification.created`); Reverb tắt ⇒ FE poll fallback.

| Method | Path | Auth | Response |
|---|---|---|---|
| GET | `/api/v1/notifications?status=unread&limit=30` | sanctum + tenant | `{ data:[{ id, type, level, title, body, action_url, data, is_read, read_at, created_at }], meta:{ unread_count } }`. `status=unread` lọc chưa đọc. |
| POST | `/api/v1/notifications/{id}/read` | sanctum + tenant | `{ data:{ unread_count } }`. Đánh dấu đã đọc 1 (chỉ notif của chính mình; của user khác ⇒ 404). |
| POST | `/api/v1/notifications/read-all` | sanctum + tenant | `{ data:{ unread_count: 0 } }`. |

Loại (`type`) v1: `channel.reconnect_needed`, `order.negative_total`, `order.cancelled`, `order.return_new`, `ads.monitor_approaching`, `ads.monitor_action` — sinh tự động từ domain event (xem SPEC 0036 §4). `level ∈ {info,warning,critical}`.

## Quảng cáo (Marketing/Ads — SPEC 2026-06-04 FB, SPEC 2026-06-09 TikTok)

`/api/v1/marketing/*` — `sanctum + verified + tenant`, gate gói. Trục `Ads` provider-agnostic (FB + TikTok). OAuth callback nằm ngoài `/api` (xem dưới). Chi tiết: [ADR-0024](../01-architecture/adr/0024-ads-connector-registry.md), [ADR-0025](../01-architecture/adr/0025-ads-tiktok-integration.md).

| Method | Path | Auth | Response |
|---|---|---|---|
| POST | `/api/v1/marketing/ads/connect` | sanctum + tenant + `plan.feature:marketing_facebook` (`marketing.connect`) | `{ data:{ authorize_url } }` — FB Ads OAuth, FE redirect/popup. Chưa bật/cấu hình ⇒ `422`. |
| POST | `/api/v1/marketing/ads/connect-tiktok` | sanctum + tenant + `plan.feature:marketing_tiktok` (`marketing.connect`) | `{ data:{ authorize_url } }` — TikTok Marketing OAuth (advertiser authorization URL). Chưa bật `INTEGRATIONS_ADS=tiktok` / chưa cấu hình `TIKTOK_ADS_*` ⇒ `422`. |

Các route đọc dùng chung (`/marketing/ad-accounts*`, `/insights`, `/report`, `/reconciliation`…) gate any-of `marketing_facebook|marketing_tiktok`; resolve provider động theo `ad_accounts.provider`. Account TikTok là **read-only** (FE ẩn thao tác sửa/tạm dừng inline).

## Webhook & OAuth (ngoài `/api`)

Xem [`webhooks-and-oauth.md`](webhooks-and-oauth.md). `POST /webhook/tiktok` → `WebhookController@handle` (verify chữ ký → ghi `webhook_events` → 200 → `ProcessWebhookEvent`); sai chữ ký ⇒ `401`. `GET /oauth/tiktok/callback?app_key=&code=&state=` → `OAuthCallbackController` (đổi token, tạo `channel_account`, redirect `/channels?connected=tiktok` hoặc `?error=…`). `shopee`/`lazada`: route + handler tồn tại nhưng connector chưa có ⇒ `404 UNKNOWN_PROVIDER` (Phase 4). `GET /oauth/facebook_ads/callback` → `AdsOAuthController@callback` (FB Ads). `GET /oauth/tiktok_marketing/redirect?auth_code=&state=` → `TikTokAdsOAuthController@callback` (TikTok Marketing: đổi `auth_code` → token vô hạn, tạo `ad_accounts` provider=tiktok, dispatch sync; redirect `/marketing?connected=tiktok_marketing` hoặc `?error=…`).

**Carrier webhooks (SPEC 0021):** `POST /webhook/carriers/{carrier}` (`ghn|ghtk|jt|viettelpost`) → `CarrierWebhookController@handle`. Core không hard-code tên carrier — connector khai báo `webhookAuthMode()`: `token_header` (**GHN** — verify header `Token` ↔ `credentials.token`, sai ⇒ `401`) hoặc `tracking_lookup` (**GHTK** — webhook không có Token; resolve tenant theo `label_id` đã lưu, verify nhẹ header `X-Client-Source` nếu có, thiếu ⇒ chấp nhận + log). Idempotent qua `(shipment_id, code, occurred_at)`; trả `200` kể cả khi không khớp vận đơn (tránh ĐVVC retry storm); cập nhật trạng thái qua `*StatusMap` + đồng bộ đơn.

## Sổ khách hàng (Customers — Phase 2, SPEC-0002 · Implemented)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/customers` | sanctum + tenant (`customers.view`) | query: `q` (chuẩn hoá được thành SĐT ⇒ hash + lookup `phone_hash`; ngược lại LIKE `name`), `reputation` csv (`ok\|watch\|risk\|blocked`), `tag`, `min_orders`, `has_note` (1), `blocked` (1), `sort` (`-last_seen_at`\|`-lifetime_revenue`\|`-orders_total`\|`-cancellation_rate`\|`±reputation_score`), `page`, `per_page≤100` | `{ data: CustomerResource[], meta:{ pagination } }`. `CustomerResource`: `name`, `phone_masked` (luôn) + `phone` (chỉ khi role có `customers.view_phone`), `reputation:{score,label}`, `is_blocked`/`block_reason`, `tags`, `lifetime_stats`, `addresses_meta`, `manual_note`, `is_anonymized`, `first_seen_at`/`last_seen_at`, `latest_warning_note?`. Hồ sơ đã ẩn danh ⇒ name/phone/addresses = null/[]. |
| GET | `/api/v1/customers/lookup` | sanctum + tenant (`customers.view`) | query: `phone` | Tra cứu nhanh khi tạo đơn thủ công. `{ data: { customer: CustomerResource\|null, addresses[], open_orders[], returning_orders[], bad_report } }`. `customer=null` nếu chưa có hồ sơ (không 404). `bad_report` (SPEC 0038 v2): tỷ lệ đơn + cảnh báo, **cộng dồn** baseline Pancake (nạp 1 lần, `customer_bad_reports`) + nội bộ (`lifetime_stats` + `customer_reports`). `null` khi không có tín hiệu; hoặc `{ success_count, fail_count, warnings:[{reason, reported_at, source: internal\|pancake\|blocked}], has_warning }`. Pancake chỉ gọi API khi số chưa có khách & chưa có baseline. |
| POST | `/api/v1/customers/reports` | sanctum + tenant (`customers.note`) | `{ order_id, reason }` | SPEC 0038 v2 — báo cáo "bom hàng" cho 1 đơn **thủ công** đã hoàn/thất bại (`delivery_failed\|returning\|returned_refunded`). Idempotent: mỗi đơn 1 lần (đã báo ⇒ `422`). `201 { data:{ id, order_id, reason, reported_at } }`. `OrderResource` phơi `can_bad_report`/`bad_reported`. |
| GET | `/api/v1/customers/{id}` | sanctum + tenant (`customers.view`) | — | `{ data: CustomerResource kèm `notes[]` (id-desc, ≤50) }`. `404` nếu không thuộc tenant / đã merge. |
| GET | `/api/v1/customers/{id}/orders` | sanctum + tenant (`customers.view` + `orders.view`) | query: `source` (csv), `status` (csv), `page`, `per_page≤100` | `{ data: OrderResource[], meta:{ pagination } }` — scoped theo `customer_id`, sort `-placed_at`. |
| POST | `/api/v1/customers/{id}/notes` | sanctum + tenant (`customers.note`) | `{ note: string, severity?: info\|warning\|danger, order_id?: int }` | `201 { data: CustomerNoteResource }` (`kind=manual`). |
| DELETE | `/api/v1/customers/{id}/notes/{noteId}` | sanctum + tenant (`customers.note` + là `author_user_id` hoặc owner/admin) | — | `{ data:{ deleted:true } }`. Auto-note ⇒ `422`. |
| POST | `/api/v1/customers/{id}/block` | sanctum + tenant (`customers.block`) | `{ reason?: string }` | `{ data: CustomerResource }` — `is_blocked=true`, `reputation.label='blocked'`, event `CustomerBlocked`. |
| POST | `/api/v1/customers/{id}/unblock` | sanctum + tenant (`customers.block`) | — | `{ data: CustomerResource }` — label tính lại từ stats, event `CustomerUnblocked`. |
| POST | `/api/v1/customers/{id}/tags` | sanctum + tenant (`customers.note`) | `{ add?:string[], remove?:string[] }` | `{ data: CustomerResource }`. |
| POST | `/api/v1/customers/merge` | sanctum + tenant (`customers.merge`) | `{ keep_id, remove_id }` (`different`) | `{ data: CustomerResource (kept) }` — chuyển `orders.customer_id` + `customer_notes` từ `remove` sang `keep`, recompute stats, soft-delete `remove` (`merged_into_customer_id=keep`), event `CustomersMerged`. Reject khác tenant. |

**Sửa endpoint hiện có:** `GET /api/v1/orders/{id}` (và `GET /orders`) trả thêm field `customer` (object con hoặc `null`) — `null` nếu `customer_id IS NULL` hoặc role không có `customers.view`: `{ id, name, phone_masked, reputation:{score,label}, is_blocked, tags, is_anonymized, lifetime_stats:{orders_total,orders_completed,orders_cancelled,orders_returned,orders_delivery_failed}, manual_note, latest_warning_note? }`. Đọc qua `CustomerProfileContract` (Orders không phụ thuộc model `Customer`).

`OrderResource.profit` (SPEC 0012): `{ cogs, platform_fee, shipping_fee, estimated_profit, platform_fee_pct, cost_complete }` hoặc `null` khi chưa cấu hình phí sàn. `platform_fee = round(grand_total × platform_fee_pct/100)` với `platform_fee_pct = tenant.settings.platform_fee_pct[order.source] ?? 0`; `cogs = Σ effective_cost(sku) × max(1, quantity)` trên các dòng đã ghép SKU; `estimated_profit = grand_total − platform_fee − shipping_fee − cogs`; `cost_complete=false` ⇒ còn dòng chưa có giá vốn SKU ⇒ lợi nhuận chỉ là ước tính dưới (UI cảnh báo ⚠). Cấu hình `platform_fee_pct` qua `PATCH /api/v1/tenant` (`settings` merge — SPEC 0011).

## Mua hàng (Procurement — Phase 6.1, SPEC-0014)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/procurement/suppliers` | `procurement.view` | `q?` (code/name/phone), `is_active?` (1), `page`, `per_page≤100` | `{ data:[SupplierResource{id,code,name,phone,email,address,tax_code,payment_terms_days,is_active,note,skus_count}], meta:{pagination} }`. |
| POST | `/api/v1/procurement/suppliers` | `procurement.manage` | `{ code, name, phone?, email?, address?, tax_code?, payment_terms_days?, note?, is_active? }` | `201 { data: SupplierResource }`. Code trùng/tenant ⇒ `422 SUPPLIER_CODE_TAKEN`. |
| GET/PATCH/DELETE | `/api/v1/procurement/suppliers/{id}` | view/`procurement.manage` | — / partial / — | `SupplierResource` + `prices[]` / `SupplierResource` / soft delete (`409` nếu còn PO `confirmed|partially_received`). |
| GET | `/api/v1/procurement/suppliers/{id}/prices` | `procurement.view` | — | `{ data:[SupplierPriceResource{id,sku_id,sku:{sku_code,name},unit_cost,moq,is_default,valid_from?,valid_to?,note}] }`. |
| POST/PATCH/DELETE | `/api/v1/procurement/suppliers/{id}/prices[/{priceId}]` | `procurement.manage` | `{ sku_id, unit_cost, moq?, is_default?, valid_from?, valid_to?, note? }` | `201`/`200`/`{deleted:true}` — đặt `is_default=true` tự gỡ default cũ của cùng SKU. |
| GET | `/api/v1/purchase-orders` | `procurement.view` | `q?` (code/supplier name), `status?` (csv `draft|confirmed|partially_received|received|cancelled`), `supplier_id?`, `warehouse_id?`, `placed_from?`/`placed_to?`, `sort` (`-created_at`\|`-expected_at`), `page` | `{ data:[PurchaseOrderResource{id,code,supplier:{id,name},warehouse:{id,name},status,total_qty,total_cost,progress_percent,expected_at,confirmed_at,created_at}], meta:{pagination} }`. |
| POST | `/api/v1/purchase-orders` | `procurement.manage` | `{ supplier_id, warehouse_id, expected_at?, note?, items:[{sku_id, qty_ordered, unit_cost?}] (≤500) }` | `201 { data: PurchaseOrderResource + items[] }` — PO `draft`, `code` tự sinh `PO-YYYYMM-NNNN`. `unit_cost` mặc định lấy từ `supplier_prices.is_default` cho SKU; nếu trống ⇒ 0 (chốt lại khi `confirm`). Trùng `sku_id` trong dòng ⇒ `422 DUPLICATE_SKU`. |
| GET | `/api/v1/purchase-orders/{id}` | `procurement.view` | — | `PurchaseOrderResource` + `items[]` (qty_ordered, qty_received, unit_cost, qty_remaining) + `receipts[]` (GoodsReceipts đã link `po_id`). |
| PATCH | `/api/v1/purchase-orders/{id}` | `procurement.manage` | `{ supplier_id?, warehouse_id?, expected_at?, note?, items? }` — chỉ ở `draft` | `PurchaseOrderResource`. PO không phải `draft` ⇒ `422`. |
| POST | `/api/v1/purchase-orders/{id}/confirm` | `procurement.manage` | — | `{ data: PurchaseOrderResource (status=confirmed) }` — chốt `unit_cost` per item (lấy từ `supplier_prices.is_default` nếu trống), recalc `total_qty/total_cost`, `confirmed_at=now`. PO không phải `draft` ⇒ `422`. Idempotent (gọi lại không đổi). |
| POST | `/api/v1/purchase-orders/{id}/cancel` | `procurement.manage` | — | `PurchaseOrderResource (cancelled)` — **chỉ khi `draft`**. Đã confirmed ⇒ `422` (huỷ bằng cách trả hàng + tạo PO mới). |
| POST | `/api/v1/purchase-orders/{id}/receive` | `procurement.receive` | `{ warehouse_id?, lines:[{sku_id, qty}] }` (≤500; `qty ≤ qty_remaining`) | `201 { data:{ purchase_order: PurchaseOrderResource, goods_receipt:{id, code, status:'draft', items_count} } }` — tạo `GoodsReceipt` mới `status=draft`, link `po_id`+`supplier_id`, items copy `unit_cost` từ PO line. FE redirect `/inventory?tab=docs&doc=GR-…` để confirm phiếu. Khi GoodsReceipt được confirm (qua `WarehouseDocumentService::confirmGoodsReceipt`) ⇒ listener `LinkGoodsReceiptToPO` cộng `qty_received` per item; đủ ⇒ PO `received`, chưa đủ ⇒ `partially_received`. Vượt số còn lại ⇒ `422`. |
| GET | `/api/v1/procurement/demand-planning` | `procurement.view` | `window_days?=30`, `lead_time?=7`, `cover_days?=14`, `urgency?` (csv `urgent|soon`), `supplier_id?`, `q?` (sku code/name), `page` | `{ data:[DemandRow{sku_id, sku:{sku_code,name}, avg_daily_sold, on_hand, reserved, available, on_order, days_left, suggested_qty, urgency, default_supplier:{id,name,unit_cost,moq}?, total_cost}], meta:{ pagination, params:{window_days,lead_time,cover_days} } }` — sort `urgent` trước, rồi `soon`, rồi `suggested_qty desc`. (SPEC 0017) |
| POST | `/api/v1/procurement/demand-planning/create-po` | `procurement.manage` | `{ warehouse_id, rows:[{sku_id, qty, supplier_id, unit_cost?}] }` (≤500) | `201 { data:{ purchase_order_ids:[int], count } }` — chia theo `supplier_id` ⇒ **1 PO `draft` per NCC**. SKU không có `supplier_prices.is_default` ⇒ bỏ qua / `422` nếu không cung cấp `supplier_id`. |

## Báo cáo (Reports — Phase 6.1, SPEC-0015)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/reports/revenue` | `reports.view` | `from`, `to` (YYYY-MM-DD; mặc định 30 ngày), `granularity?` (`day|week|month`, mặc định `day`), `source?` (csv), `channel_account_id?`, `warehouse_id?` | `{ data:{ series:[{date, revenue, orders}], totals:{revenue, orders, avg_order_value}, breakdown:{by_source:[{key,name,revenue,share}], by_shop:[…]} } }` — chỉ tính `orders.placed_at ∈ [from,to]`, loại huỷ. SQL portable SQLite↔Postgres qua `truncDateSql` (`DATE_TRUNC` PG / `strftime` SQLite). |
| GET | `/api/v1/reports/profit` | `reports.view` | cùng filter | `{ data:{ series:[{date, revenue, cogs, fees, profit}], totals:{revenue, cogs, fees, profit, margin_percent}, breakdown:{by_source:[…], by_shop:[…]} } }` — chỉ tính đơn `shipped|completed` (có `order_costs` ⇒ COGS thực FIFO). Phí thực từ `settlement_lines` nếu có, ngược lại ước tính theo `platform_fee_pct`. |
| GET | `/api/v1/reports/top-products` | `reports.view` | cùng filter + `limit?=20`, `sort?` (`-revenue|-profit|-quantity`) | `{ data:[{sku_id, sku:{sku_code,name}, quantity, revenue, cogs, profit, margin_percent, orders_count}], meta:{limit, sort} }`. |
| GET | `/api/v1/reports/export` | `reports.export` | `type=revenue|profit|top-products`, `format=csv` (xlsx = follow-up), cùng filter | `200 text/csv; charset=utf-8` — stream CSV **UTF-8 BOM** (`\xEF\xBB\xBF`) để Excel mở tiếng Việt đúng. Filename `Content-Disposition: attachment; filename="<type>-<from>-<to>.csv"`. |

## Tài chính / Đối soát (Finance — Phase 6.2, SPEC-0016)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/finance/settlements` | `finance.view` | `channel_account_id?`, `status?` (csv `pending|reconciled|partial|error`), `period_from?`/`period_to?`, `q?` (external_id), `page` | `{ data:[SettlementResource{id,channel_account:{id,name,provider},external_id,period_start,period_end,settled_at,total_revenue,total_fees,total_payout,status,lines_count,orphan_lines_count}], meta:{pagination} }`. |
| GET | `/api/v1/settlements/summary` | `finance.view` + `plan.feature:finance_settlements` | `from?`, `to?` (mặc định 30 ngày), `channel_account_id?` | `{ data:{ from, to, totals:{settlements, reconciled, pending, error, payout, revenue, fee, shipping}, by_channel:[{channel_account_id, settlements, payout, revenue, fee, shipping}] } }` — **tổng hợp đối soát thực** trong kỳ (tiền sàn đã trả + phí + trạng thái đối chiếu) cho "Báo cáo tổng thể". `fee`/`shipping` thường âm. SPEC 2026-06-06. |
| GET | `/api/v1/finance/settlements/{id}` | `finance.view` | — | `SettlementResource` + `lines[]` (`{id, fee_type, amount, external_order_id, order_id?, order_item_id?, occurred_at}`) — line `order_id=null` = "orphan" (đơn không match local) ⇒ cảnh báo UI. |
| POST | `/api/v1/finance/settlements/sync` | `finance.reconcile` | `{ channel_account_id?, period_start?, period_end? }` | `{ data:{ queued: N } }` — dispatch `FetchSettlementsForShop` (1 job per shop). Capability `finance.fetch=false` (chưa bật `INTEGRATIONS_TIKTOK_FINANCE` / `INTEGRATIONS_LAZADA_FINANCE`) ⇒ `422 FINANCE_NOT_ENABLED`. |
| POST | `/api/v1/finance/settlements/{id}/reconcile` | `finance.reconcile` | — | `{ data:{ matched: N, orphan: M } }` — chạy `SettlementService::reconcile` (match `external_order_id` → `order_id`). Idempotent. |

> Khi có settlement: `OrderResource.profit.fee_source` = `settlement` (dùng phí thực từ `settlement_lines`); chưa có ⇒ `estimate` (dùng `platform_fee_pct`). `OrderResource.profit.cost_source` = `fifo` (đơn đã ship + có `order_costs`) hoặc `estimate` (chưa ship). SPEC 0014/0016.

## Gói thuê bao (Billing — Phase 6.4, SPEC 0018)

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/billing/plans` | sanctum + tenant (`billing.view`) | — | `{ data:[PlanResource{id,code,name,description,price_monthly,price_yearly,currency,trial_days,limits,features}] }` — catalogue 4 gói: `trial · starter · pro · business`. Lưu DB (admin sửa được). |
| GET | `/api/v1/billing/subscription` | sanctum + tenant (`billing.view`) | — | `{ data: SubscriptionResource{plan,plan_code,status,billing_cycle,trial_ends_at,current_period_start/end,cancel_at,days_left,is_trialing,is_past_due}, meta:{ usage:{ channel_accounts:{used,limit}, features:{…} } } }` — tự fallback trial vĩnh viễn khi tenant không có subscription. Plans chưa seed ⇒ `data=null`. |
| GET | `/api/v1/billing/usage` | sanctum + tenant (`billing.view`) | — | `{ data:{ channel_accounts:{used,limit}, features:{…bool flags} } }`. |
| POST | `/api/v1/billing/checkout` | sanctum + tenant (`billing.manage` — owner) | `{plan_code:starter\|pro\|business, cycle:monthly\|yearly, gateway:sepay\|vnpay\|momo}` | `201 { data:{ invoice: InvoiceResource, gateway, checkout: CheckoutSession } }`. Throttle 10/phút/user. `momo` ⇒ `422 GATEWAY_UNAVAILABLE` (v1 chưa wire). PR1 trả `checkout.method='pending'`; PR2/PR3 sẽ thay bằng `CheckoutSession` thật (QR cho SePay / `redirect_url` cho VNPay). Chặn: trial plan ⇒ 422; cùng plan đang active ⇒ 422; downgrade khi đang active ⇒ 422. |
| GET | `/api/v1/billing/invoices` | sanctum + tenant (`billing.view`) | `status?` csv, `page`, `per_page≤100` | `{ data:[InvoiceResource{code,status,subscription_id,period_start,period_end,subtotal,tax,total,currency,due_at,paid_at}], meta:{ pagination } }`. |
| GET | `/api/v1/billing/invoices/{id}` | sanctum + tenant (`billing.view`) | — | `InvoiceResource` + `lines[]`. |
| GET | `/api/v1/billing/invoices/{id}/payment-status` | sanctum + tenant (`billing.view`) | — | `{ data:{ id, status, paid_at? } }` — UX polling cho SePay khi user chờ webhook khớp memo. |
| POST | `/api/v1/billing/subscription/cancel` | sanctum + tenant (`billing.manage` — owner) | — | `{ data: SubscriptionResource (cancel_at = period_end, cancelled_at = now) }`. Trial ⇒ `422 CANNOT_CANCEL_TRIAL` (trial tự hết hạn). |
| GET | `/api/v1/billing/billing-profile` | sanctum + tenant (`billing.view`) | — | `{ data:{ id, company_name, tax_code, billing_address, contact_email, contact_phone } }` — auto firstOrCreate. |
| PATCH | `/api/v1/billing/billing-profile` | sanctum + tenant (`billing.manage` — owner) | partial fields | `{ data: profile }`. |

Webhook payments (ngoài `/api`, làm ở PR2/PR3):
| Method | Path | Mô tả |
|---|---|---|
| POST | `/webhook/payments/sepay` | SePay đẩy về (verify HMAC). Dedupe unique `(gateway='sepay', external_ref)`. — PR2 |
| POST | `/webhook/payments/vnpay` | VNPay IPN (verify HMAC-SHA512). — PR3 |
| GET | `/payments/vnpay/return` | User redirect — UX, không tin. — PR3 |

**Codes lỗi đặc thù Billing:** `PLAN_LIMIT_REACHED` (vượt hạn mức gian hàng, `402`, details `{resource,current,limit,plan_code,upgrade_to}`); `PLAN_FEATURE_LOCKED` (gói không có tính năng, `402`, details `{features,plan_code,upgrade_to}`); `GATEWAY_UNAVAILABLE` (`422`); `CANNOT_CANCEL_TRIAL` (`422`); `NO_ACTIVE_SUBSCRIPTION` (`422`).

**Middleware gating đã áp:**
- `plan.limit:channel_accounts` — `POST /channel-accounts/{provider}/connect`. Hết slot ⇒ `402 PLAN_LIMIT_REACHED`.
- `plan.feature:finance_settlements` — `/api/v1/settlements*`, `/api/v1/channel-accounts/{id}/fetch-settlements`.
- `plan.feature:procurement` — `/api/v1/suppliers*`, `/api/v1/purchase-orders*`.
- `plan.feature:demand_planning` — `/api/v1/procurement/demand-planning*`.
- `plan.feature:profit_reports` — `/api/v1/reports/profit`, `/api/v1/reports/top-products`, `/api/v1/reports/export`. (`/reports/revenue` mở cho mọi gói.)

## Admin hệ thống (Admin — SPEC 0020 · Spec 2026-05-17 mở rộng)

Super-admin xuyên tenant. Spec 2026-05-17 đã tách auth: super-admin ở bảng `admin_users` riêng (KHÔNG ở `users`), login qua `POST /api/v1/admin/auth/login` (username + password), session bảo vệ bởi guard `admin_web`. Tất cả routes `/api/v1/admin/*` yêu cầu `web + auth:admin_web` (KHÔNG cần `X-Tenant-Id`). Rate limit 60/phút/user (auth login: 10/phút/IP). Mọi action ghi audit (`action` prefix `admin.*`, `admin_user_id` = actor, `tenant_id` = target tenant nếu có).

### Admin Auth (Spec 2026-05-17)

| Method | Path | Auth | Mô tả |
|---|---|---|---|
| POST | `/api/v1/admin/auth/login` | web + throttle:10,1 | `{ username, password }` — login super-admin. 401 `ADMIN_AUTH_FAILED` nếu sai/disabled. |
| POST | `/api/v1/admin/auth/logout` | web + `auth:admin_web` | Logout, invalidate session. |
| GET | `/api/v1/admin/auth/me` | web + `auth:admin_web` | Thông tin admin hiện tại. |
| POST | `/api/v1/admin/auth/change-password` | web + `auth:admin_web` | `{ current_password, password }` — admin tự đổi mật khẩu. |

### Admin Users management (Spec 2026-05-17)

| Method | Path | Auth | Mô tả |
|---|---|---|---|
| GET | `/api/v1/admin/admin-users` | web + `auth:admin_web` | List super-admin. Query: `q`, `is_active`, `page`, `per_page`. |
| POST | `/api/v1/admin/admin-users` | web + `auth:admin_web` | `{ username:[a-z0-9._-]{3,32}, email?, name, password ≥8, is_active? }`. Audit `admin.admin_user.create`. |
| GET | `/api/v1/admin/admin-users/{id}` | web + `auth:admin_web` | Chi tiết. |
| PATCH | `/api/v1/admin/admin-users/{id}` | web + `auth:admin_web` | Sửa name/email. Audit `admin.admin_user.update`. |
| POST | `/api/v1/admin/admin-users/{id}/reset-password` | web + `auth:admin_web` | `{ password ≥8 }`. 409 `CANNOT_SELF_MUTATE` nếu target = actor. Audit `admin.admin_user.reset_password`. |
| POST | `/api/v1/admin/admin-users/{id}/suspend` | web + `auth:admin_web` | Vô hiệu hoá (is_active=false). 409 `CANNOT_SELF_MUTATE` / `LAST_ACTIVE_ADMIN`. Audit `admin.admin_user.suspend`. |
| POST | `/api/v1/admin/admin-users/{id}/reactivate` | web + `auth:admin_web` | Kích hoạt lại. Audit `admin.admin_user.reactivate`. |

### Tenant Users (admin) — Spec 2026-05-17 mở rộng

| Method | Path | Mô tả |
|---|---|---|
| GET | `/api/v1/admin/users` | (đã có — bỏ filter `is_super_admin`). List user toàn hệ thống. |
| GET | `/api/v1/admin/users/{id}` | Chi tiết user + tenants + email_verified_at + suspended_at. |
| PATCH | `/api/v1/admin/users/{id}` | Sửa name/email. Audit `admin.user.update`. |
| POST | `/api/v1/admin/users/{id}/reset-password` | `{ password ≥8 }`. Audit `admin.user.reset_password`. |
| POST | `/api/v1/admin/users/{id}/suspend` | Set `users.suspended_at`. EnsureTenant middleware chặn 403 `USER_SUSPENDED` ở route nghiệp vụ tenant. Audit `admin.user.suspend`. |
| POST | `/api/v1/admin/users/{id}/reactivate` | Clear `suspended_at`. Audit `admin.user.reactivate`. |

### System Settings (Spec 2026-05-17 — module Settings)

Cấu hình động lưu trong DB (`system_settings`), ghi đè giá trị từ `.env` cho các key thuộc whitelist `SystemSettingsCatalog` (38 key, 8 secret). Helper `system_setting('key', config('xxx'))` đọc qua cache `rememberForever` + clear-on-save; `set()` phát event `SystemSettingChanged` (listener ghi audit `admin.setting.update`).

| Method | Path | Mô tả |
|---|---|---|
| GET | `/api/v1/admin/system-settings?group={branding\|marketplace\|fulfillment\|sync}` | List theo catalog (merge env_fallback). Secret value trả `"****"` khi đã set, `null` khi chưa. |
| GET | `/api/v1/admin/system-settings/{key}/reveal` | Trả plain value (giải mã nếu secret). Audit `admin.setting.reveal {key}`. |
| PATCH | `/api/v1/admin/system-settings/{key}` | `{ value }` — validate theo type catalog. 422 `SETTING_KEY_NOT_ALLOWED` / `SETTING_VALUE_INVALID`. |
| DELETE | `/api/v1/admin/system-settings/{key}` | Xoá row → fallback env. |
| POST | `/api/v1/admin/system-settings/sync-from-env` | Bootstrap: seed các key chưa có row từ env. Idempotent. |

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/api/v1/admin/tenants` | web + `auth:admin_web` | query: `q`, `over_quota` (1), `suspended` (1), `page`, `per_page≤100` | `{ data:[TenantSummary{id,name,slug,status,created_at,owner:{id,name,email},subscription:{plan_code,status,billing_cycle,current_period_start/end,over_quota_warned_at,over_quota_locked},usage:{channel_accounts:{used,limit,over}}}], meta:{ pagination } }`. |
| GET | `/api/v1/admin/tenants/{id}` | web + `auth:admin_web` | — | `{ data: TenantSummary kèm `channel_accounts[]`, `members[]`, `recent_admin_actions[]` (20 audit_logs gần nhất prefix `admin.*`) }`. |
| DELETE | `/api/v1/admin/tenants/{tid}/channel-accounts/{caid}` | web + `auth:admin_web` | `{ reason: string ≥10 }` | `{ data:{ deleted_orders:N, unlinked_skus:M } }` — reuse `ChannelConnectionService::deleteWithOrders` (xoá kết nối + đơn + sku_mappings + nhả tồn). Audit `admin.channel_account.delete`. |
| POST | `/api/v1/admin/tenants/{tid}/subscription` | web + `auth:admin_web` | `{ plan_code: trial\|starter\|pro\|business, cycle: monthly\|yearly\|trial, reason: string ≥10 }` | `{ data: SubscriptionResource }` — **bypass `DOWNGRADE_NOT_ALLOWED`** của `BillingService` (force-set tay cho khách yêu cầu). Subscription cũ ⇒ cancelled, subscription mới ⇒ active từ `now`. KHÔNG tạo invoice. Audit `admin.subscription.change`. |
| POST | `/api/v1/admin/tenants/{tid}/suspend` | web + `auth:admin_web` | `{ reason: string ≥10 }` | `{ data: TenantSummary (status=suspended) }` — `EnsureTenant` middleware sẽ trả `403 TENANT_SUSPENDED` cho mọi member. Audit `admin.tenant.suspend`. |
| POST | `/api/v1/admin/tenants/{tid}/reactivate` | web + `auth:admin_web` | — | `{ data: TenantSummary (status=active) }`. Audit `admin.tenant.reactivate`. |
| GET | `/api/v1/admin/users` | web + `auth:admin_web` | query: `q` (email/name), `is_super_admin` (1), `page`, `per_page≤100` | `{ data:[{id,name,email,is_super_admin,tenants:[{id,name,slug,role}],created_at}], meta:{ pagination } }`. |

### Admin Tier 1+2 (SPEC 0023)

| Method | Path | Auth | Mô tả |
|---|---|---|---|
| POST | `/api/v1/admin/tenants/{tid}/extend-trial` | web + `auth:admin_web` | `{ days:1..365, plan_code?, reason ≥10 }` ⇒ subscription mới `trialing` period N ngày. Audit `admin.trial.extend`. |
| POST | `/api/v1/admin/tenants/{tid}/feature-overrides` | web + `auth:admin_web` | `{ features:{key:bool\|null}, reason ≥10 }` ⇒ lưu `subscriptions.meta.feature_overrides`. `EnforcePlanFeature` đọc override trước plan: `true` bypass, `false` chặn dù plan có, `null` rớt xuống plan. Audit `admin.feature_override.set`. |
| POST | `/api/v1/admin/tenants/{tid}/invoices` | web + `auth:admin_web` | `{ plan_code, cycle, amount?, period_days?, note? }` ⇒ tạo invoice manual `status=pending` (khách chuyển khoản offline). Audit `admin.invoice.create_manual`. |
| POST | `/api/v1/admin/invoices/{id}/mark-paid` | web + `auth:admin_web` | `{ payment_method?, reference?, paid_at? }` ⇒ tạo Payment `gateway=manual`, invoice `paid`, fire `InvoicePaid` → ActivateSubscription tự swap plan. Idempotent (đã paid ⇒ 200 no-op + audit `admin.invoice.mark_paid.noop`). |
| POST | `/api/v1/admin/payments/{id}/refund` | web + `auth:admin_web` | `{ reason ≥10, rollback_subscription?:bool }` ⇒ payment.status=refunded + invoice.status=refunded. Rollback ⇒ đóng sub + trial fallback. `422 ALREADY_REFUNDED` nếu đã refund. Audit `admin.payment.refund`. |
| GET | `/api/v1/admin/vouchers` | web + `auth:admin_web` | query: `q`, `kind`, `active`, `expired`, page. List vouchers. |
| POST | `/api/v1/admin/vouchers` | web + `auth:admin_web` | `{ code:^[A-Z0-9_-]+$, name, kind, value, valid_plans?, max_redemptions?, starts_at?, expires_at? }`. Audit `admin.voucher.create`. |
| GET | `/api/v1/admin/vouchers/{id}` | web + `auth:admin_web` | Detail + 50 redemptions gần nhất. |
| PATCH | `/api/v1/admin/vouchers/{id}` | web + `auth:admin_web` | Sửa name/limits/expires/active (KHÔNG đổi code/kind). |
| DELETE | `/api/v1/admin/vouchers/{id}` | web + `auth:admin_web` | Soft delete = `is_active=false`. History redemption giữ nguyên. |
| POST | `/api/v1/admin/vouchers/{id}/grant` | web + `auth:admin_web` | `{ tenant_id, reason ≥10 }` — chỉ áp với `kind=free_days\|plan_upgrade`. Audit `admin.voucher.grant`. |
| POST | `/api/v1/billing/vouchers/validate` | sanctum + tenant (`billing.manage`) | `{ code, plan_code, cycle }` — user preview discount trước checkout. Throttle `30/1`. Lỗi: `INVALID_VOUCHER`/`VOUCHER_EXPIRED`/`VOUCHER_EXHAUSTED`/`VOUCHER_NOT_FOR_PLAN`. |
| POST | `/api/v1/billing/checkout` | (sửa) | Field mới `voucher_code?` — voucher percent/fixed sẽ áp `discount` line vào invoice trước khi tạo CheckoutSession. |
| GET | `/api/v1/admin/plans` | web + `auth:admin_web` | List 4 gói. |
| GET | `/api/v1/admin/plans/{id}` | web + `auth:admin_web` | Detail. |
| PATCH | `/api/v1/admin/plans/{id}` | web + `auth:admin_web` | Sửa name/price/trial_days/limits/features/is_active. `code` & `currency` immutable ⇒ `422 PLAN_IMMUTABLE_FIELD`. Audit `admin.plan.update`. |
| GET | `/api/v1/admin/audit-logs` | web + `auth:admin_web` | Cross-tenant search: `action` (LIKE, `*` wildcard), `user_id`, `tenant_id`, `from`/`to`, `q` (LIKE trên changes JSON). |
| GET | `/api/v1/admin/broadcasts` | web + `auth:admin_web` | Lịch sử broadcast (sent_count, sent_at). |
| POST | `/api/v1/admin/broadcasts` | web + `auth:admin_web` | `{ subject, body_markdown, audience:{kind:'all_owners'\|'all_admins_and_owners'\|'tenant_ids', tenant_ids?} }` — dispatch `BroadcastNotification` qua queue `notifications`. Limit 5000 recipients/lần ⇒ `BROADCAST_AUDIENCE_TOO_LARGE`. Tenant suspended bị skip. Audit `admin.broadcast.send`. |
| GET | `/api/v1/admin/broadcasts/{id}` | web + `auth:admin_web` | Detail. |

#### Popup thông báo (Announcements — SPEC 0037)

Popup giữa màn hình cho mọi user (fix bug, tạm dừng dịch vụ…). Admin tạo bằng editor TipTap (chèn ảnh/video upload R2). KHÔNG tenant-scoped. `body_html` sanitize allowlist server-side. User nhớ-đã-xem theo **tab** (sessionStorage): 1 tab hiện 1 lần/popup; tab mới hiện lại.

| Method | Path | Auth | Mô tả |
|---|---|---|---|
| GET | `/api/v1/admin/announcements` | web + `auth:admin_web` | Danh sách (phân trang). |
| POST | `/api/v1/admin/announcements` | web + `auth:admin_web` | `{ title, body_html, is_active?, starts_at?, ends_at?, dismiss_label? }` — sanitize `body_html` trước khi lưu. |
| PATCH | `/api/v1/admin/announcements/{id}` | web + `auth:admin_web` | Sửa (kể cả bật/tắt `is_active`). |
| DELETE | `/api/v1/admin/announcements/{id}` | web + `auth:admin_web` | Xoá. |
| POST | `/api/v1/admin/announcements/media` | web + `auth:admin_web` | multipart `file` (ảnh ≤ `media.images.max_kb` hoặc video mp4/webm ≤ `media.video.max_kb`) → R2 `announcements/` → `{ data:{ url } }`. |
| GET | `/api/v1/announcements/active` | `auth:sanctum` + `verified` | `{ data:[{ id, title, body_html, dismiss_label }] }` — chỉ popup đang `is_active` + trong cửa sổ `starts_at`/`ends_at`. |

**Codes lỗi đặc thù Admin/Over-quota:**
- `ADMIN_AUTH_FAILED` (`401`) — login admin sai hoặc tài khoản đã bị vô hiệu hoá (Spec 2026-05-17).
- `CANNOT_SELF_MUTATE` (`409`) — admin thao tác trên chính tài khoản của mình (suspend / reset password) (Spec 2026-05-17).
- `LAST_ACTIVE_ADMIN` (`409`) — vô hiệu hoá admin active cuối cùng (Spec 2026-05-17).
- `USER_SUSPENDED` (`403`) — `EnsureTenant` chặn tenant user có `users.suspended_at != null` (Spec 2026-05-17).
- `SETTING_KEY_NOT_ALLOWED` (`422`) — key cấu hình ngoài whitelist (Spec 2026-05-17).
- `SETTING_VALUE_INVALID` (`422`) — value không hợp lệ theo type của setting (Spec 2026-05-17).
- `TENANT_SUSPENDED` (`403`) — `EnsureTenant` chặn khi `tenant.status='suspended'`.
- `PLAN_QUOTA_EXCEEDED` (`402`) — middleware `plan.over_quota_lock` chặn write sau 48h ân hạn. Details: `{ resources:[{resource,used,limit,over}], plan_code, warned_at, grace_hours }`.
- `INVALID_VOUCHER` / `VOUCHER_EXPIRED` / `VOUCHER_EXHAUSTED` / `VOUCHER_NOT_FOR_PLAN` / `VOUCHER_NOT_REDEEMABLE` / `VOUCHER_KIND_FOR_CHECKOUT` (`422` SPEC 0023) — voucher checkout / grant lỗi.
- `INVALID_VALUE` (`422` SPEC 0023) — voucher kind/value mismatch.
- `PLAN_IMMUTABLE_FIELD` (`422` SPEC 0023) — đổi `code`/`currency` của plan.
- `ALREADY_REFUNDED` (`422` SPEC 0023) — refund payment đã refund trước đó.
- `BROADCAST_AUDIENCE_TOO_LARGE` (`422` SPEC 0023) — vượt 5000 recipients.

**Sửa endpoint hiện có:**
- `GET /api/v1/auth/me` ⇒ **Spec 2026-05-17:** field `is_super_admin` đã bỏ (super-admin nằm bảng riêng `admin_users`, dùng `/api/v1/admin/auth/me`).
- `GET /api/v1/billing/subscription` ⇒ `data` thêm `over_quota_warned_at: string|null`, `over_quota_locked: bool`, `over_quota_grace_hours: int` (banner FE đọc từ đây).

**Middleware gating mới (SPEC 0020):**
- `plan.over_quota_lock` — áp lên route group `tenant` ở `routes/api.php`. Chặn POST/PATCH/PUT/DELETE khi `subscription.over_quota_warned_at + grace_hours <= now()` & vẫn vượt mức. Whitelist nội bộ: `/billing/*`, `/auth/*`, `/tenant`, `/tenants`, `/media/image`, `DELETE /channel-accounts/{id}`. KHÔNG áp lên `/api/v1/admin/*` vì admin routes không qua middleware `tenant`.

## Sắp có (theo roadmap)

`/webhook/payments/{sepay,vnpay,momo}` + CheckoutSession thật (Phase 6.4 — PR2 SePay, PR3 VNPay) · `/api/v1/automation-rules` (Phase 6.5) · `/api/v1/notifications` + channels Zalo/Email (Phase 6.5) … — thêm vào đây khi xây.

## Đăng sản phẩm lên sàn

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| POST | `/api/v1/extension-tokens` | sanctum + tenant | `{ name? }` | Cấp PAT không hết hạn với ability `copy-product:push` cho extension copy sản phẩm. |
| DELETE | `/api/v1/extension-tokens/{id}` | sanctum + tenant | — | Thu hồi token extension của chính user. |
| GET | `/api/v1/channels/{provider}/categories` | sanctum + tenant | query `channel_account_id`, `parent_id?` | Danh mục theo tầng từ connector publish, cache 12h. |
| GET | `/api/v1/channels/{provider}/attributes` | sanctum + tenant | query `channel_account_id`, `category_id` | Thuộc tính theo danh mục lá. |
| GET | `/api/v1/channels/{provider}/brands` | sanctum + tenant | query `channel_account_id`, `category_id` | Danh sách brand theo danh mục. |
| GET | `/api/v1/channels/{provider}/shipping-options` | sanctum + tenant | query `channel_account_id` | Tùy chọn vận chuyển của shop cho trang soạn nháp (cache 30'). `{ mode:'channels'\|'warehouse_delivery'\|'package', channels?[], warehouses?[], delivery_options?[], notes? }`. Shopee=`get_channel_list`; TikTok=warehouses+delivery_options; Lazada=package-based. |
| POST | `/api/v1/products/{productId}/listings` | sanctum + tenant | `{ channel_account_id, provider }` | Tạo hoặc trả về nháp listing cho một sản phẩm gốc trên một gian hàng. |
| GET | `/api/v1/listings/{id}` | sanctum + tenant | — | Chi tiết nháp listing + SKU. |
| PUT | `/api/v1/listings/{id}` | sanctum + tenant | category/brand/attributes/media/logistics/skus | Lưu nháp và validate lại; đủ field thì `ready`, thiếu thì `draft` + `validation_errors`. |
| POST | `/api/v1/listings/{id}/clone` | sanctum + tenant | `{ channel_account_id }` | Copy listing sang shop khác. Cùng nền tảng copy dữ liệu đã validate; khác nền tảng chỉ copy nội dung dùng chung và giữ nháp cần sửa. |
| POST | `/api/v1/listings/{id}/push` | sanctum + tenant | — | Enqueue push 1 listing, trả `batch_id`. |
| POST | `/api/v1/listings/bulk-push` | sanctum + tenant | `{ listing_ids: int[] }` | Enqueue push nhiều listing `ready`, trả `batch_id`. |
| GET | `/api/v1/push-batches/{id}` | sanctum + tenant | — | Progress batch: tổng, thành công/thất bại, từng job. |

### Phase 7.x đề xuất — Messaging (SPEC-0024 Draft, ADR-0017..0021)

> Spec & ADR đã viết, **chưa code**. Endpoints liệt kê dưới đây là kế hoạch — chỉ thêm chính thức vào table trên khi PR foundation merge.

**Webhook (public, verify chữ ký, 1 controller chung `MessagingWebhookController@handle($provider)`):**
- `POST /webhook/messaging/{provider}` với `provider ∈ {shopee,tiktok,lazada,facebook}` — verify chữ ký, ghi `webhook_events` (`provider='messaging.<code>'`), dispatch `ProcessMessagingWebhook` lên queue `messaging-webhooks`, trả `200 {ok:true}`.
- `GET /webhook/messaging/facebook` — Meta hub.verify_token challenge (chỉ Facebook).

**OAuth callback (Facebook Page mới; 3 sàn còn lại reuse `/oauth/{provider}/callback`):**
- `GET /oauth/facebook_page/callback`

**REST `/api/v1/messaging/*`** (Sanctum + tenant + permission gate):
- `GET    /messaging/conversations` (`messaging.view`) — filter `provider`, `status`, `unread`, `assigned`, `customer_id`, `q`. Page-based 20/page.
- `GET    /messaging/conversations/{id}` (`messaging.view`) — detail + last 50 messages (cursor `before_message_id`). Với `thread_type='comment'`, `comment` gồm: `post_message`, `post_permalink`, `post_picture` (ảnh bài viết — CDN Facebook hết hạn, chỉ preview, refresh khi sync), `post_created_time` (ISO-8601), `hidden`, `private_replied`, `participants[]`, `post_is_video` (bài viết là video → FE phủ icon ▶ lên ảnh preview). FE render Post Card bài viết ghim đầu luồng (thay banner cũ).
- `POST   /messaging/conversations/{id}/messages` (`messaging.reply`) — `{kind:'text', body}`.
- `POST   /messaging/conversations/{id}/messages/media` (`messaging.reply`) — multipart `{kind:'image|video|file', file}`.
- `POST   /messaging/conversations/{id}/messages/template` (`messaging.reply`) — `{template_id, vars}`.
- `POST   /messaging/conversations/{id}/read` (`messaging.view`) — reset unread.
- `PATCH  /messaging/conversations/{id}` (`messaging.view`/`messaging.assign`) — `{status?, assigned_user_id?, tags?, snoozed_until?}`.
- `POST   /messaging/conversations/{id}/link-order` (`messaging.view`) — `{order_id, notify_customer?:bool}` gắn đơn (vừa tạo từ khung chat) vào hội thoại. **SPEC 0031** — `notify_customer=true` (FE panel tạo-đơn-trong-chat đặt) ⇒ tự gửi 1 tin xác nhận đơn (kèm link tra cứu `/tracking?code=`, nút "Xem đơn hàng" nếu connector hỗ trợ interactive, tag `POST_PURCHASE_UPDATE`). Best-effort, idempotent per (conversation, order); chỉ DM (`thread_type='message'`) + đơn manual + kênh hỗ trợ `outbound.text`. Đơn tenant khác ⇒ `404`.
- `POST   /messaging/conversations/{id}/ai-suggestion` (`messaging.reply` + `plan.feature:messaging_ai`) — dispatch + sync wait ≤30s → `{draft_id, draft_text, suggested_attachments}`.
- `POST   /messaging/conversations/{id}/ai-suggestion/{draftId}/accept` — gửi draft.
- `DELETE /messaging/conversations/{id}/ai-suggestion/{draftId}` — reject (audit).
- **Kiểm duyệt comment Facebook** (`thread_type='comment'`, quyền `messaging.reply`):
  - `POST   /messaging/conversations/{id}/comment/hide` — `{hidden:bool}` ẩn/hiện cả luồng.
  - `DELETE /messaging/conversations/{id}/comment` — `{comment_id?}` rỗng ⇒ xoá comment gốc (spam hội thoại); có id con ⇒ chỉ xoá comment đó.
  - `POST   /messaging/conversations/{id}/comment/reply` — multipart `{body?, image?}` trả lời CÔNG KHAI (tạo sub-comment).
  - `POST   /messaging/conversations/{id}/comment/private-reply` — `{body?, image?}` nhắn riêng 1 phần (idempotent với lỗi FB 10900 "đã nhắn riêng").
  - `POST   /messaging/conversations/{id}/comment/like` — `{comment_id?, like:bool}` Page thích/bỏ thích 1 comment (chỉ Facebook — `CommentEngagementConnector`).
  - `POST   /messaging/conversations/{id}/comment/private-message` — multipart `{body?, comment_id?, files[]?}` modal nhắn riêng đầy đủ (text + nhiều đính kèm ảnh/video/file). Phần đầu qua `comment_id` (lấy PSID, lưu `meta.fb_private_psid`), phần sau qua PSID; idempotent 10900.
- **Kết nối & quản lý kênh Facebook Page** (UI `/messaging/channels`):
  - `GET    /messaging/channels` (`messaging.view`) — list page đã kết nối + trạng thái sync (không trả token).
  - `POST   /messaging/channels/{id}/sync` (`messaging.connect`) — đồng bộ lại 1 page (`202`).
  - `POST   /messaging/channels/bulk-sync` (`messaging.connect`) — `{ids:int[]}` đồng bộ lại nhiều page đã chọn; chỉ account `facebook_page` của tenant được xử lý, id lạ bị bỏ qua → `202 {ok, processed}`.
  - `GET    /messaging/channels/{id}/posts` (`messaging.view`) — post picker (connector có `post.list`).
  - `DELETE /messaging/channels/{id}` (`messaging.connect`) — ngắt kết nối 1 page (xoá hẳn + cascade hội thoại).
  - `POST   /messaging/channels/bulk-disconnect` (`messaging.connect`) — `{ids:int[]}` ngắt kết nối nhiều page đã chọn → `{ok, processed, conversations_deleted}`.
- `GET/POST/PATCH/DELETE /messaging/templates[/{id}]` (`messaging.template.manage`).
- **Utility templates (SPEC-0032 — Messenger Utility Messages):** đọc `messaging.view`; mutate/submit/sync `messaging.template.manage`.
  - `GET    /messaging/utility-templates` — lọc `?channel_account_id=&status=` (paginated).
  - `POST   /messaging/utility-templates` — tạo `draft` (`channel_account_id`, `code`, `name`, `language`, `body` với `{{1}}…`, `variables[]`, `buttons[]?`).
  - `GET    /messaging/utility-templates/{id}` · `PATCH /messaging/utility-templates/{id}` (chỉ khi `draft`/`rejected`) · `DELETE /messaging/utility-templates/{id}`.
  - `POST   /messaging/utility-templates/{id}/submit` — gửi Meta duyệt (→ `pending`). Lỗi → 422 `UTILITY_TEMPLATE_SUBMIT_FAILED`.
  - `POST   /messaging/utility-templates/{id}/sync` — đồng bộ trạng thái duyệt (`pending`→`approved`/`rejected`). Lỗi → 422 `UTILITY_TEMPLATE_SYNC_FAILED`.
- `GET/POST/PATCH/DELETE /messaging/auto-reply-rules[/{id}]` (`messaging.rule.manage`).
- `GET    /messaging/knowledge-docs` (`messaging.view`).
- `POST   /messaging/knowledge-docs` (`messaging.ai.train`) — multipart/URL/inline, dispatch `IndexKnowledgeDoc`.
- `DELETE /messaging/knowledge-docs/{id}` (`messaging.ai.train`).
- `GET    /messaging/stats` (`messaging.view`) — `{open, unread, snoozed, by_provider, avg_first_response_minutes_7d, meta.realtime_enabled}`.
- `GET    /tenant/settings/messaging` (`messaging.ai.config`) — `{ai_provider_code?, available_providers:[{code,name}], away_hours, fallback_template_id?}`.
- `PATCH  /tenant/settings/messaging` (`messaging.ai.config`).

**Support — trợ lý trợ giúp sản phẩm + CSKH (SPEC-0028)** (`/api/v1/support/*`, auth + tenant, KHÔNG gate gói):
- `POST   /support/assistant/ask` — `{question, history?}` → `{answer, sources[], mode}` (RAG hỏi-đáp cách dùng; suy biến keyword khi chưa cấu hình Qdrant/AI, không bao giờ 500). Throttle 30/phút.
- `GET    /support/conversations` — hội thoại CSKH của tenant (kèm `messages[]` + `attachments[]` download_url ký TTL ngắn).
- `GET    /support/unread` — `{unread}` (tổng tin CSKH chưa đọc) — nguồn nhẹ cho badge widget.
- `POST   /support/messages` — **multipart** `body?` + `files[]` (≤5, ảnh 25MB/video 100MB/file 25MB; sai MIME/size → 422 `ATTACHMENT_INVALID`) → gửi tin; tự mở cuộc mới nếu cuộc gần nhất đã đóng. Throttle 20/phút.
- `POST   /support/conversations/{id}/read` — đánh dấu đã đọc (xoá unread phía user).
- **Admin (guard `admin_web`)** — xem & nhắn nhiều tin + đóng hội thoại CSKH XUYÊN tenant (SPEC-0028):
  - `GET    /admin/support-conversations` — filter `status` (open|closed), `awaiting` (đang chờ CSKH), `tenant_id`, `q` (nội dung tin); phân trang 50, kèm nhãn tenant/người gửi + preview.
  - `GET    /admin/support-conversations/{id}` — thread đầy đủ (messages + attachments).
  - `POST   /admin/support-conversations/{id}/messages` — **multipart** `body?` + `files[]` → CSKH nhắn (nhiều lần); cuộc đã đóng → 422 `CONVERSATION_CLOSED` (audit `support.conversation.message`).
  - `POST   /admin/support-conversations/{id}/close` — đóng + chèn tin hệ thống + báo user (audit `support.conversation.close`).

**Admin SPA `/api/v1/admin/*`** (admin guard, không cần tenant):
- `GET/POST/PATCH/DELETE /admin/ai-providers[/{code}]` — CRUD provider trong `system_settings.ai_providers.<code>`.
- `POST   /admin/ai-providers/{code}/test` — test connection (sinh 1 reply "hello").
- `GET    /admin/messaging/ai-usage` — per-tenant per-month cost (đọc từ `ai_assistant_runs`).

**Mã lỗi mới (đề xuất):**
- `OUTBOUND_WINDOW_CLOSED` (`422`) — vi phạm window rule (Facebook 24h).
- `CONVERSATION_CLOSED` (`409`) — sàn báo conversation đã đóng.
- `CHANNEL_ACCOUNT_INACTIVE` (`409`) — shop bị deauthorize.
- `ATTACHMENT_INVALID` (`422`) — MIME / size sai.
- `AI_PROVIDER_NOT_AVAILABLE` (`422`) — tenant chưa chọn / provider đã `is_active=false`.
- `MESSAGING_RATE_LIMIT` (`429`) — vượt 60 msg/phút/shop hoặc 30/phút/user.
- `PLAN_FEATURE_LOCKED` (`402`) — `messaging_inbox` hoặc `messaging_ai` không có trong gói.
- `PLAN_LIMIT_REACHED` (`402`) — vượt `messaging_ai_replies_monthly` hoặc `messaging_media_mb_daily`.

## Visual search — Sản phẩm AI training (SPEC 2026-06-16)

Prefix `/api/v1/visual-search` · middleware `auth:sanctum + verified + tenant + plan.over_quota_lock + plan.feature:messaging_ai` (là MỘT PHẦN của AI tự động trả lời — KHÔNG feature gói riêng). Quyền: đọc `messaging.view`, mutate `messaging.ai.train`.

- `GET    /visual-search/items` — danh sách item (phân trang `per_page`, kèm `image_count`, `primary_image_id`).
- `POST   /visual-search/items` — tạo item `{ name, description?, attributes?{}, ref_code?, applies_all_pages?, channel_account_ids?[] }`.
- `GET    /visual-search/items/{id}` — chi tiết + danh sách ảnh + `channel_account_ids`.
- `PATCH  /visual-search/items/{id}` — sửa (các field như tạo, đều optional).
- `DELETE /visual-search/items/{id}` — xoá item + ảnh + vector (job `RemoveTrainingImageVector`).
- `POST   /visual-search/items/{itemId}/images` — **multipart** `images[]` (jpeg/png/webp, ≤ `visual_search.image.max_size_kb`); dedupe theo hash; ảnh đầu → ảnh đại diện; dispatch embed.
- `DELETE /visual-search/items/{itemId}/images/{imageId}` — xoá 1 ảnh (+ vector); tự đổi ảnh đại diện.
- `POST   /visual-search/items/{itemId}/images/{imageId}/primary` — đặt ảnh đại diện.
- `POST   /visual-search/lookup` — **multipart** `image` + `rerank?` + `channel_account_id?` → `{ data: { status: matched|ambiguous|not_found, stage, item?{item_id,name,description,attributes,confidence}, candidates[] } }`. Rate-limit 30/phút.

**Mã lỗi:** `PLAN_FEATURE_LOCKED` (`402`) khi gói không có `messaging_ai`.
