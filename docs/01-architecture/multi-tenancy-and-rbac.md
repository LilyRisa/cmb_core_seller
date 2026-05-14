# Multi-tenancy & Phân quyền (RBAC)

**Status:** Stable · **Cập nhật:** 2026-05-11

## 1. Mô hình

- **Tenant** = một nhà bán (workspace). Sở hữu mọi dữ liệu nghiệp vụ.
- **User** = một tài khoản người (email + mật khẩu). Một user có thể thuộc **nhiều** tenant.
- **TenantUser** (pivot) = (user, tenant, role[, scope]) — vai trò của user trong tenant đó; tuỳ chọn `channel_account_scope` để giới hạn xem được những gian hàng nào.
- Người dùng đăng nhập → chọn tenant đang làm việc → mọi request gắn `tenant_id` đó (header `X-Tenant-Id` hoặc lưu trong session) → middleware `tenant` set "current tenant".

## 2. Cách ly dữ liệu (RULES)

1. **Mọi bảng nghiệp vụ có cột `tenant_id`** (NOT NULL, có index, thường nằm trong khoá phức hợp/partition key phụ).
2. Mọi Eloquent model nghiệp vụ dùng trait `BelongsToTenant` → **Global Scope** tự thêm `where tenant_id = current_tenant` vào mọi query, và tự set `tenant_id` khi `creating`.
3. Truy vấn cần bỏ scope (job hệ thống, admin) phải gọi tường minh `->withoutTenantScope()` — review kỹ mỗi chỗ.
4. Policy luôn kiểm `model.tenant_id === current_tenant` (phòng khi ai đó bỏ scope nhầm).
5. File trong MinIO/S3 đặt theo prefix `tenants/{tenant_id}/...`.
6. Cache key, lock key, queue payload luôn kèm `tenant_id`.
7. Webhook đến: không có "current tenant" → resolve tenant qua `channel_account` (từ shop id trong payload) trước khi xử lý.

## 3. Vai trò mặc định (RBAC)

| Role | Mô tả | Quyền tiêu biểu |
|---|---|---|
| `owner` | Chủ sở hữu tenant (1 người) | Toàn quyền + quản lý billing + xoá tenant + chuyển quyền sở hữu |
| `admin` | Quản trị | Toàn quyền nghiệp vụ; không xoá tenant; quản lý thành viên |
| `staff_order` | NV xử lý đơn | Xem/sửa đơn, đổi trạng thái, tạo đơn tay, in vận đơn; **không** sửa giá vốn, không xem báo cáo lợi nhuận, không billing |
| `staff_warehouse` | NV kho | Tồn kho, nhập/xuất/điều chuyển/kiểm kê, quét đóng gói; xem đơn ở mức cần soạn hàng |
| `accountant` | Kế toán | Đối soát, lợi nhuận, báo cáo tài chính, export; chỉ đọc đơn/tồn |
| `viewer` | Chỉ xem | Đọc dashboard, đơn, tồn; không thao tác |

- Phân quyền chi tiết bằng **permission string** (vd `orders.update`, `inventory.adjust`, `fulfillment.print`, `finance.view`, `billing.manage`...) gán cho role; `owner`/`admin` cấu hình lại role hoặc tạo role tuỳ biến (Phase sau).
- Triển khai: dùng `spatie/laravel-permission` với "team/tenant" mode, hoặc bảng tự viết — quyết định ở Phase 0 (ghi ADR nếu chọn lib).
- Mọi action nhạy cảm ghi `audit_logs` (ai, tenant, action, đối tượng, before/after, ip, time).

## 4. Đăng nhập & phiên
- Sanctum SPA cookie (cùng domain). Đăng ký → tạo user; tạo tenant mới hoặc nhận lời mời vào tenant có sẵn.
- Mời thành viên: owner/admin gửi email mời → người được mời tạo/đăng nhập user → gắn vào `tenant_user` với role chỉ định.
- Đổi mật khẩu, quên mật khẩu, (sau) 2FA cho owner/admin.

## 5. Billing & hạn mức (liên quan — Phase 6.4, SPEC-0018 đã triển khai PR1)
- Mỗi tenant gắn một `Subscription` (gói). 4 gói chuẩn: `trial · starter · pro · business`. Gói quy định: số `channel_account` tối đa (`plan.limits.max_channel_accounts`) + bật/tắt tính năng (`plan.features` jsonb: `procurement`, `fifo_cogs`, `profit_reports`, `finance_settlements`, `demand_planning`, `mass_listing`, `automation_rules`, `priority_support`). **Không** giới hạn số đơn — kiến trúc đủ chịu (đã chốt với chủ dự án 2026-05-14).
- Middleware `plan.limit:channel_accounts` áp lên `POST /channel-accounts/{provider}/connect` ⇒ vượt hạn mức trả `402 PLAN_LIMIT_REACHED`.
- Middleware `plan.feature:<feature>` áp lên route module nâng cao (`/finance/*`, `/suppliers*`, `/purchase-orders*`, `/procurement/demand-planning*`, `/reports/profit`, `/reports/top-products`) ⇒ gói không có feature trả `402 PLAN_FEATURE_LOCKED`.
- Permission strings:
  | Permission | Vai trò built-in được cấp |
  |---|---|
  | `billing.view` | owner, admin, accountant |
  | `billing.manage` | owner only (admin có `*` nhưng phủ định `!billing.manage`) |
- Trial 14 ngày: tự khởi động qua listener `StartTrialSubscription` (listen event `TenantCreated`) khi register. Plan chưa seed ⇒ listener no-op gracefully + middleware "open" (không chặn) để dev/test cũ không vỡ. Production luôn seed `BillingPlanSeeder`.
- Hết hạn ⇒ grace 7 ngày → rớt về subscription `trial` vĩnh viễn (không khoá data). Logic ở `SubscriptionExpiryService` chạy command `subscriptions:check-expiring` hằng ngày.
