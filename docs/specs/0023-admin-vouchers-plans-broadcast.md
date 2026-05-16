# SPEC 0023: Admin SaaS — Voucher, Custom trial, Plan editor, Feature override, Manual invoice/Refund, Audit log search, Broadcast

- **Trạng thái:** Draft (2026-05-16)
- **Phase:** 6.4 (mở rộng) — vận hành Billing SaaS bằng công cụ admin
- **Module backend liên quan:** Admin (mở rộng), Billing (vouchers + manual invoice + refund + feature overrides), Notifications (broadcast — kênh `mail` từ SPEC 0022), Tenancy (audit log search)
- **Tác giả / Ngày:** Team · 2026-05-16
- **Liên quan:** SPEC 0018 (Billing SaaS), SPEC 0020 (Admin nền tảng), SPEC 0022 (Notifications module), `05-api/conventions.md`, `06-frontend/overview.md`, `07-infra/queues-and-scheduler.md`, `08-security-and-privacy.md`.

## 1. Vấn đề & mục tiêu

SPEC 0020 đã xây nền tảng admin (super-admin flag, audit, force-delete channel, force-changeplan, suspend) nhưng còn 6 nhóm thao tác bộ phận vận hành SaaS phải vào DB tay:

1. **Tặng quà / giảm giá**: không có cơ chế voucher → mỗi lần khuyến mãi phải đổi thủ công `subscription.current_period_end` + báo kế toán riêng.
2. **Trial linh hoạt**: khách doanh nghiệp xin "test 30/60 ngày" nhưng `AdminTenantService::changePlan` cố định ≥14 ngày.
3. **Đổi cấu hình gói**: muốn nâng `Starter` từ 2 → 3 gian hàng phải sửa seeder + re-deploy.
4. **Mở khoá tính năng cho VIP**: shop ở `Starter` xin dùng tính năng `mass_listing` → hiện phải nâng gói cả set; cần override theo từng tenant.
5. **Thanh toán offline**: khách chuyển khoản ngân hàng / chuyển nhầm gateway → không có công cụ "tạo invoice tay + đánh dấu đã thanh toán".
6. **Tra cứu / broadcast**: không có UI search audit-log xuyên tenant; gửi email thông báo bảo trì phải dùng SMTP CLI.

**Mục tiêu:**
- Bổ sung 8 tính năng admin tạo "operator toolbox" hoàn chỉnh, KHÔNG cần SSH/DB tay.
- Tất cả operation ghi audit (`action` prefix `admin.*`) với `reason` ≥10 ký tự.
- Không phá schema hiện có; chỉ thêm bảng & cột mới.

## 2. Trong / ngoài phạm vi

**Trong (SPEC này):**

| # | Tính năng | Backend | Frontend |
|---|---|---|---|
| A | **Voucher codes** | bảng `vouchers` + `voucher_redemptions`, `VoucherService`, 4 kind (`percent`/`fixed`/`free_days`/`plan_upgrade`), CRUD admin + integration `POST /billing/checkout` (user nhập code) + `POST /admin/vouchers/{id}/grant` (admin tặng thẳng) | `/admin/vouchers` CRUD, tab "Voucher đã dùng" trong tenant drawer, ô "Mã ưu đãi" ở `/settings/plan` checkout |
| B | **Custom trial extension** | `POST /admin/tenants/{tid}/extend-trial {days, plan_code?, reason}` | nút "Gia hạn trial" trong drawer tab "Gói thuê bao" |
| C | **Plan editor** | `GET/PATCH /admin/plans` (sửa limits/features/price), bảo vệ field `code` immutable | `/admin/plans` page (table + drawer chỉnh) |
| D | **Feature override per-tenant** | lưu `subscriptions.meta.feature_overrides: {key: bool}`; cập nhật `EnforcePlanFeature` check override trước plan | tab mới "Tính năng" trong tenant drawer |
| E | **Manual invoice + mark paid** | `POST /admin/tenants/{tid}/invoices` (tạo invoice draft tay) + `POST /admin/invoices/{id}/mark-paid` (fire `InvoicePaid` → `ActivateSubscription`) | drawer tab "Hoá đơn" + nút "Tạo hoá đơn tay" + "Đánh dấu đã thanh toán" |
| F | **Refund payment** | `POST /admin/payments/{id}/refund {reason, rollback_subscription:bool}` | drawer (hoá đơn) — nút "Hoàn tiền" |
| G | **Audit log search** | `GET /admin/audit-logs` cross-tenant với filter `action,user_id,tenant_id,from,to,q` | `/admin/audit-logs` page |
| H | **Broadcast email** | `POST /admin/broadcasts {audience, subject, body_markdown}` + bảng `broadcasts` (log đã gửi); dispatch `BroadcastNotification` qua queue `notifications` | `/admin/broadcasts` page (form + lịch sử) |

**Ngoài (làm sau / spec khác):**
- Voucher cho buyer của tenant (mã giảm cho khách mua hàng) — chỉ làm voucher SaaS-billing, KHÔNG làm voucher TMĐT.
- Auto-grant voucher theo điều kiện (lifecycle marketing) — v1 admin grant thủ công.
- Refund qua API gateway thật (SePay/VNPay refund API) — v1 chỉ đánh dấu trong DB; tiền hoàn ngoài app.
- Broadcast SMS/Zalo OA — Phase 6.5 tiếp theo (chỉ `mail` v1).
- Dashboard SaaS metrics (MRR/churn/...) — Tier 3, spec sau.
- Impersonation (admin login as user) — Tier 3, backlog.
- Plan editor: tạo plan mới / xoá plan — v1 chỉ sửa plan có sẵn (tránh huỷ plan đang có subscription).

## 3. Câu chuyện người dùng / luồng chính

### 3.1 Voucher — admin tạo + user redeem ở checkout

```
Admin           Voucher Service       Billing Checkout       Tenant user
  │                  │                       │                    │
  ├──POST /admin/    │                       │                    │
  │  vouchers ──────▶│ INSERT vouchers       │                    │
  │  (kind=percent,  │  code=SUMMER20,       │                    │
  │   value=20,      │  max_redemptions=100  │                    │
  │   expires=...) ◀─│                       │                    │
  │                  │                       │                    │
  │                  │                       ├◀──── POST /billing/checkout
  │                  │                       │      {plan_code:pro, cycle:monthly,
  │                  │                       │       gateway:sepay, voucher_code:SUMMER20}
  │                  │   redeemAtCheckout()  │
  │                  │◀──────────────────────┤
  │                  │ - validate (active/   │
  │                  │   not expired / left) │
  │                  │ - check valid_for_plan │
  │                  │ - return discount     │
  │                  │   {amount:-200000,    │
  │                  │    redemption_id}     │
  │                  │──────────────────────▶│
  │                  │                       │ create invoice +
  │                  │                       │ line(kind=plan, +1000000) +
  │                  │                       │ line(kind=discount, -200000) +
  │                  │                       │ INSERT voucher_redemptions
  │                  │                       │ checkout via gateway w/ total=800000
  │                  │                       │────────────────────▶ user pays
  │                  │                       │                       │
  │                  │                       │                    invoice paid
  │                  │                       │                    → ActivateSubscription
  │                  │                       │                    (existing flow)
```

### 3.2 Voucher — admin grant thẳng (free_days/plan_upgrade)

```
Admin                 VoucherService       Subscription
 │                         │                    │
 ├─POST /admin/vouchers/   │                    │
 │  {id}/grant ───────────▶│ grant(voucher,     │
 │  {tenant_id, reason}    │       tenantId)    │
 │                         │                    │
 │                  voucher.kind=free_days?     │
 │                  ┌──────▼──── yes ───────────▶ extend current_period_end += N days
 │                  │                              audit admin.voucher.grant
 │                  └─ plan_upgrade?            │
 │                     └──────────────────────▶ swap plan (như changePlan)
 │◀──────────────────────────────────────────── INSERT voucher_redemptions + audit
```

### 3.3 Custom trial — admin set N ngày

1. Admin mở drawer tenant → tab "Gói" → "Gia hạn trial".
2. Modal: nhập số ngày (1–365) + plan_code (mặc định `trial`, có thể chọn plan trả phí để "tặng trial trên gói cao") + lý do.
3. BE `POST /admin/tenants/{tid}/extend-trial` → đóng subscription cũ, tạo mới `status=trialing`, `current_period_end = now + days`, audit `admin.trial.extend`.
4. Khác với `changePlan`: status luôn `trialing` (cảnh báo trial banner FE), KHÔNG bị scheduler auto-expire trừ khi quá date — phân biệt rõ trial-tặng vs paid.

### 3.4 Plan editor

1. Admin vào `/admin/plans` → bảng 4 gói.
2. Click edit → form: name, description, price_monthly, price_yearly, trial_days, limits.max_channel_accounts, features.* (toggle per feature).
3. `PATCH /admin/plans/{id}` → cập nhật + audit `admin.plan.update` với diff.
4. KHÔNG đổi `code` (immutable — code dùng làm khoá ở subscription, integration test, … đổi sẽ vỡ).
5. Cảnh báo FE: "Đổi limit có thể khiến tenant đang dùng bị over-quota — scheduler kế sẽ tự cảnh báo họ."

### 3.5 Feature override per-tenant

1. Drawer tenant → tab "Tính năng" → hiện 7 feature flags + radio per flag: "Theo gói" / "Bật cho tenant" / "Tắt cho tenant".
2. `POST /admin/tenants/{tid}/feature-overrides {features: {procurement: true, mass_listing: false}, reason}`.
3. Service merge vào `subscriptions.meta.feature_overrides`.
4. `EnforcePlanFeature` đọc override trước:
   ```php
   $override = $sub->meta['feature_overrides'][$feature] ?? null;
   if ($override === true)  return next; // bật cho tenant này dù plan không có
   if ($override === false) return locked; // tắt cho tenant này dù plan có
   // override null/missing ⇒ rơi xuống check plan như cũ
   ```
5. Audit `admin.feature_override.set`.

### 3.6 Manual invoice + mark paid

1. Drawer tenant → tab "Hoá đơn" → "Tạo hoá đơn tay" → form: plan_code, cycle, amount (override), due_at, note.
2. `POST /admin/tenants/{tid}/invoices` → tạo `invoice` `status=pending` + `invoice_lines` (1 dòng plan, optional dòng discount manual). `meta.created_by_admin = adminId`, `meta.plan_code`/`cycle` để `ActivateSubscription` activate đúng.
3. Khi khách chuyển khoản, admin verify offline → click "Đánh dấu đã thanh toán" → `POST /admin/invoices/{id}/mark-paid {payment_method, reference, paid_at?}` → tạo `payments` row `status=succeeded`, `gateway=manual`, fire `InvoicePaid` event → existing `ActivateSubscription` listener tự swap plan + audit `admin.invoice.mark_paid`.
4. Subscription mới có cycle/period đúng — KHÔNG cần code mới.

### 3.7 Refund

1. Drawer → hoá đơn paid → "Hoàn tiền" → reason ≥10 ký tự + checkbox "Hạ xuống trial sau khi hoàn".
2. `POST /admin/payments/{id}/refund` → `payment.status=refunded`, `payment.refunded_at=now`, `invoice.status=refunded`. Nếu `rollback_subscription=true`: subscription hiện tại đóng + tạo trial fallback (`SubscriptionService::ensureTrialFallback`).
3. Audit `admin.payment.refund`.
4. KHÔNG gọi gateway API hoàn tiền — đây là **đánh dấu nội bộ**; tiền thực hoàn ngoài app (qua bank/portal merchant).

### 3.8 Audit log search

1. Admin `/admin/audit-logs` → bảng + filter: `action` (LIKE pattern), `user_id`, `tenant_id`, `from`/`to` date, `q` (LIKE trên `changes` text), pagination.
2. `GET /admin/audit-logs?action=admin.*&tenant_id=12&from=2026-05-01&to=2026-05-16` → trả `[AuditLogResource]` (action, user, tenant, ip, changes JSON pretty, created_at).
3. Xuyên tenant (admin); bypass TenantScope.

### 3.9 Broadcast

1. Admin `/admin/broadcasts` → form: audience (`all_owners` / `all_admins_and_owners` / `tenant_ids[]`), subject, body_markdown (parsed bằng `league/commonmark` đã có trong vendor).
2. `POST /admin/broadcasts` → INSERT `broadcasts` row (log) + dispatch `BroadcastNotification` cho từng recipient qua queue `notifications`. Mỗi notification = 1 job (không bulk gửi sync — chống timeout request).
3. FE hiện lịch sử broadcast (id, subject, audience, sent_count, sent_at).
4. Template Blade `broadcast.blade.php` reuse layout `notifications::layout` (SPEC 0022) — render markdown → HTML inline.

## 4. Hành vi & quy tắc nghiệp vụ

1. **Audit**: mọi endpoint ghi (`POST/PATCH/DELETE`) `/admin/*` ghi audit_logs với `action=admin.<resource>.<verb>`, `reason` nếu có, `changes` chứa diff.
2. **Idempotency**:
   - Voucher redeem: unique `(voucher_id, tenant_id, invoice_id)` ở `voucher_redemptions` — redeem lại cùng invoice → no-op trả redemption cũ.
   - `mark-paid` idempotent: invoice đã paid → trả 200 + audit `admin.invoice.mark_paid.noop`.
   - `refund` idempotent: payment đã refunded → 422 `ALREADY_REFUNDED`.
   - Broadcast: KHÔNG retry — admin chịu trách nhiệm; nếu cần gửi lại tạo broadcast mới (audit rõ ràng).
3. **Reason ≥10 ký tự** (mọi action ghi).
4. **Voucher max_redemptions = -1 ⇒ không giới hạn**. Count check trong service trước khi insert redemption.
5. **Voucher `valid_plans`** (json array of plan_code): rỗng = mọi plan; có giá trị = chỉ áp dụng với plan trong list.
6. **`feature_overrides` precedence**: override > plan > default. Override `null` = không override (rớt xuống plan). FE radio cho 3 trạng thái.
7. **Plan editor immutable fields**: `code`, `currency` (luôn VND). Đổi `is_active=false` ⇒ plan không hiện ở `/settings/plan` của user nhưng tenant đang ở plan đó vẫn dùng được tới hết kỳ.
8. **Manual invoice**: bỏ qua check `DOWNGRADE_NOT_ALLOWED` của BillingService (admin có thẩm quyền).
9. **Broadcast audience size limit**: 5000 recipients per broadcast (chống misuse). Vượt → 422 + gợi ý chia nhỏ theo tenant_ids.
10. **Phân quyền**: tất cả endpoints `/admin/*` chỉ super-admin (đã có middleware `super_admin`).

## 5. Dữ liệu

### 5.1 Migration mới (3 file)

```
2026_05_16_120001_create_vouchers_table.php
   id, code (unique), name, description,
   kind ENUM('percent','fixed','free_days','plan_upgrade'),
   value INTEGER (% cho percent, VND cho fixed, days cho free_days, plan_id cho plan_upgrade),
   valid_plans JSON nullable,       -- list plan_code áp dụng (null = mọi plan)
   max_redemptions INT default -1,  -- -1 = không giới hạn
   redemption_count INT default 0,  -- cache để hiển thị nhanh
   starts_at TIMESTAMPTZ nullable,
   expires_at TIMESTAMPTZ nullable,
   is_active BOOLEAN default true,
   created_by_user_id BIGINT,       -- admin tạo
   meta JSON nullable,
   timestamps

2026_05_16_120002_create_voucher_redemptions_table.php
   id, voucher_id INDEX, tenant_id INDEX, user_id BIGINT nullable (user redeem ở checkout),
   invoice_id BIGINT nullable (NULL nếu admin grant ngoài checkout),
   subscription_id BIGINT nullable (mark khi grant áp dụng tới subscription cụ thể),
   discount_amount BIGINT default 0,   -- VND, dương cho user redeem ở checkout
   granted_days INT default 0,         -- N ngày cho free_days
   meta JSON nullable,
   created_at, updated_at
   UNIQUE (voucher_id, tenant_id, invoice_id) WHERE invoice_id IS NOT NULL  -- chống double-redeem checkout
   INDEX (voucher_id, tenant_id)

2026_05_16_120003_create_broadcasts_table.php
   id, subject VARCHAR(255), body_markdown TEXT, audience JSON,
   sent_count INT default 0,
   sent_at TIMESTAMPTZ nullable,
   created_by_user_id BIGINT,
   meta JSON nullable,
   timestamps
```

Tất cả reversible (drop_table).

### 5.2 Bảng KHÔNG đổi schema, chỉ dùng `meta`

- `subscriptions.meta.feature_overrides: {feature_key: bool}` — không cần migration vì `meta` đã JSON.
- `invoices.meta.created_by_admin: user_id` — đánh dấu invoice manual.
- `payments.meta.refunded_by_admin: user_id`, `payments.meta.refund_reason`.

### 5.3 Domain events mới

- `VoucherGranted($voucher, $tenantId, $adminUserId, $redemption)` — broadcast/log.
- `VoucherRedeemedAtCheckout($voucher, $invoice, $redemption)`.
- `BroadcastSent($broadcast, $recipientCount)`.

(Tất cả chỉ log audit ở phase này; không có listener nghiệp vụ.)

## 6. API & UI

### 6.1 Endpoints mới — bảng tóm tắt

| Method | Path | Mô tả |
|---|---|---|
| GET | `/api/v1/admin/vouchers` | list + filter (q, kind, active, expired) |
| POST | `/api/v1/admin/vouchers` | tạo voucher |
| GET | `/api/v1/admin/vouchers/{id}` | detail + redemptions count + recent redemptions |
| PATCH | `/api/v1/admin/vouchers/{id}` | sửa (đổi limits/expires/active) — KHÔNG đổi code/kind |
| DELETE | `/api/v1/admin/vouchers/{id}` | soft delete (set is_active=false, không xoá DB) |
| POST | `/api/v1/admin/vouchers/{id}/grant` | grant trực tiếp tới tenant |
| POST | `/api/v1/billing/vouchers/validate` | (user) validate code trước checkout (trả discount preview) |
| POST | `/api/v1/billing/checkout` | (sửa hiện có) thêm field `voucher_code?` |
| POST | `/api/v1/admin/tenants/{tid}/extend-trial` | `{days, plan_code?, reason}` — custom trial |
| GET | `/api/v1/admin/plans` | list plans |
| GET | `/api/v1/admin/plans/{id}` | detail |
| PATCH | `/api/v1/admin/plans/{id}` | sửa name/price/trial_days/limits/features/is_active |
| POST | `/api/v1/admin/tenants/{tid}/feature-overrides` | `{features: {key:bool\|null}, reason}` |
| POST | `/api/v1/admin/tenants/{tid}/invoices` | manual invoice |
| POST | `/api/v1/admin/invoices/{id}/mark-paid` | mark paid manual |
| POST | `/api/v1/admin/payments/{id}/refund` | refund |
| GET | `/api/v1/admin/audit-logs` | search cross-tenant |
| GET | `/api/v1/admin/broadcasts` | list lịch sử broadcast |
| POST | `/api/v1/admin/broadcasts` | tạo + dispatch |
| GET | `/api/v1/admin/broadcasts/{id}` | detail (recipient count, audit) |

### 6.2 Sửa endpoint hiện có

- `POST /api/v1/billing/checkout` thêm validate `voucher_code?: string|null`. Nếu có → `VoucherService::redeemAtCheckout`. Lỗi `INVALID_VOUCHER` / `VOUCHER_EXHAUSTED` / `VOUCHER_EXPIRED` / `VOUCHER_NOT_FOR_PLAN`.
- `GET /api/v1/billing/subscription` (user) — `data` thêm `feature_overrides: {key:bool}` (FE hiển thị badge cho tenant).
- `GET /api/v1/admin/tenants/{id}` (đã có ở SPEC 0020) — `data` thêm:
  - `invoices: [{id, code, status, total, due_at, paid_at}]` (10 gần nhất)
  - `vouchers_redeemed: [{id, voucher_code, granted_days, discount_amount, created_at}]` (10 gần nhất)
  - `feature_overrides: {key:bool}`

### 6.3 Codes lỗi mới

- `INVALID_VOUCHER` (`422`) — voucher_code không tồn tại / inactive.
- `VOUCHER_EXPIRED` (`422`) — hết hạn / chưa tới starts_at.
- `VOUCHER_EXHAUSTED` (`422`) — vượt max_redemptions.
- `VOUCHER_NOT_FOR_PLAN` (`422`) — plan checkout không trong valid_plans.
- `VOUCHER_ALREADY_REDEEMED` (`422`) — tenant đã dùng voucher này cho invoice này.
- `PLAN_IMMUTABLE_FIELD` (`422`) — cố đổi code/currency.
- `INVOICE_ALREADY_PAID` (`409`) — mark-paid nhưng đã paid (no-op trả 200 thay vì 409).
- `ALREADY_REFUNDED` (`422`) — refund nhưng đã refunded.
- `BROADCAST_AUDIENCE_TOO_LARGE` (`422`) — vượt 5000 recipients.

### 6.4 Frontend (`resources/js/`)

| File mới / sửa | Vai trò |
|---|---|
| `pages/admin/AdminVouchersPage.tsx` | bảng vouchers + filter + nút tạo (modal) + click row → drawer detail (redemptions list, nút "Grant cho tenant", nút "Vô hiệu hoá") |
| `pages/admin/AdminPlansPage.tsx` | bảng 4 gói + click → modal edit (form limits/features/price) |
| `pages/admin/AdminAuditLogsPage.tsx` | bảng audit log + filter (action/user/tenant/date) + drawer xem `changes` JSON |
| `pages/admin/AdminBroadcastsPage.tsx` | form gửi broadcast (audience radio + subject + textarea markdown + preview) + bảng lịch sử |
| `pages/admin/AdminTenantDrawer.tsx` (sửa) | thêm 3 tab: "Hoá đơn" (manual invoice + mark paid + refund), "Tính năng" (overrides), "Voucher" (redemptions). Nút "Gia hạn trial" trong tab "Gói". |
| `pages/SettingsPlanPage.tsx` (sửa user-side) | thêm input "Mã ưu đãi" + preview discount khi checkout |
| `lib/admin.tsx` (sửa) | thêm hooks: `useAdminVouchers/Voucher/CreateVoucher/UpdateVoucher/GrantVoucher`, `useAdminPlans/UpdatePlan`, `useAdminExtendTrial`, `useAdminFeatureOverrides`, `useAdminCreateInvoice/MarkInvoicePaid/RefundPayment`, `useAdminAuditLogs`, `useAdminBroadcasts/CreateBroadcast` |
| `components/AppLayout.tsx` (sửa) | thêm 4 mục menu trong group "Quản trị hệ thống": Voucher & quà tặng / Gói thuê bao / Audit log / Broadcast |

Tuân thủ memory: dùng `@ant-design/icons` (`GiftOutlined`, `TagsOutlined`, `AuditOutlined`, `SoundOutlined`...); ít option dùng `Radio.Group`/`Segmented` thay `Select`.

### 6.5 Job & queue (cập nhật `docs/07-infra/queues-and-scheduler.md`)

- Queue `notifications` (đã có): thêm `BroadcastNotification` — gửi tới recipient theo audience. Mỗi recipient = 1 notification job (tries 3, backoff 10/60/300s).

KHÔNG thêm queue / scheduler mới.

## 7. Edge case & lỗi

- **Voucher hết hạn giữa checkout**: validate ngay khi tạo invoice (transaction). Voucher hết hạn 1 giây trước commit ⇒ rollback invoice, trả `VOUCHER_EXPIRED`.
- **Race khi grant**: lock `vouchers` row bằng `lockForUpdate` trước khi check `redemption_count < max_redemptions`.
- **Manual invoice nhưng tenant đã có alive sub cao hơn**: vẫn cho tạo (admin chịu trách nhiệm); khi mark-paid + activate ⇒ sub cũ cancelled như flow tự nhiên.
- **Refund payment đã activate sub mới**: rollback_subscription=true ⇒ đóng sub mới + fallback trial. False ⇒ chỉ đánh dấu, sub vẫn active (admin tự xử lý ngoài).
- **Feature override = false nhưng plan = true**: middleware chặn 402 — đúng intent (admin tạm khoá tính năng cho tenant cá biệt vd vi phạm).
- **Plan editor đổi max_channel_accounts xuống thấp**: tenant có usage > limit ⇒ scheduler kế tiếp set `over_quota_warned_at` (luồng SPEC 0020 đã xử lý).
- **Broadcast khi tenant suspended**: skip recipient ở tenant suspended (không gửi mail rác); ghi `meta.skipped: [{user_id, reason: tenant_suspended}]`.
- **Voucher kind=plan_upgrade tới tenant đã ở plan cao hơn**: trả `VOUCHER_DOWNGRADE_NOT_ALLOWED` (422) — yêu cầu admin chọn voucher khác.
- **Admin xoá voucher đã có redemption**: chỉ soft delete (`is_active=false`); redemption history giữ nguyên cho kế toán.

## 8. Bảo mật & dữ liệu cá nhân

- Voucher code có thể đoán được (vd `SUMMER2026`) — KHÔNG bí mật như password, OK lộ. Tuy nhiên rate limit `POST /billing/vouchers/validate` 30/phút/user (chống brute-force enumerate code).
- Broadcast body KHÔNG được chứa template syntax thực thi (`{{ ... }}`) — markdown rendered an toàn qua `league/commonmark` `safe` mode.
- `payments.meta.refund_reason` — không chứa PAN/CVV (đã loại từ SPEC 0018 §8).
- Audit log chứa `reason` admin gõ — không log token; admin được dặn không paste secret vào reason field.
- Voucher value/limit không liên quan PII; KHÔNG cần mã hoá at-rest.
- `/admin/audit-logs` có thể tiết lộ pattern hoạt động — chỉ super-admin thấy.

## 9. Kiểm thử

### 9.1 Unit
- `VoucherService::redeemAtCheckout` — vector test percent (20% off 1M = 200k), fixed (200k off 1M = 800k); voucher expired/exhausted/not-for-plan trả exception đúng.
- `VoucherService::grant` — kind free_days extend `current_period_end`; plan_upgrade swap plan.
- `EnforcePlanFeature` — override true bypass plan check; false chặn dù plan true.

### 9.2 Feature
- `AdminVoucherCrudTest` — super-admin tạo/list/edit/grant/soft-delete; user thường 403.
- `VoucherCheckoutTest` — voucher percent áp dụng vào invoice (subtotal=1M, total=800k, line discount=-200k); redemption_count tăng.
- `CustomTrialTest` — extend-trial 30 ngày ⇒ sub mới `trialing` period_end = now+30d, audit ghi đúng.
- `PlanEditorTest` — PATCH plan đổi limits.max_channel_accounts, đổi feature.mass_listing; PATCH code → 422 PLAN_IMMUTABLE_FIELD.
- `FeatureOverrideTest` — override `mass_listing=true` cho Starter tenant ⇒ access endpoint gated mass_listing thành công.
- `ManualInvoiceTest` — tạo invoice manual ⇒ mark-paid ⇒ subscription mới active đúng plan.
- `RefundTest` — refund với rollback ⇒ subscription đóng + trial fallback; không rollback ⇒ subscription vẫn active.
- `AuditLogSearchTest` — filter action/tenant/date trả đúng rows; user thường 403.
- `BroadcastTest` — POST broadcast audience=all_owners ⇒ dispatch 1 notification mỗi owner; recipients limit 5000.

### 9.3 FE smoke
- Render 4 trang mới (vouchers, plans, audit-logs, broadcasts); drawer thêm 3 tab hiển thị OK.

## 10. Tiêu chí hoàn thành

- [ ] 3 migration mới + rollback OK.
- [ ] 17 endpoint mới hoạt động + qua middleware `super_admin`.
- [ ] Voucher checkout integration: user nhập code → discount line trên invoice → ActivateSubscription chạy như cũ.
- [ ] Custom trial set N ngày → sub trialing đúng period.
- [ ] Plan editor sửa giá/limits/features không cần re-deploy.
- [ ] Feature override per-tenant hoạt động (test mass_listing trên Starter).
- [ ] Manual invoice + mark-paid → ActivateSubscription fire.
- [ ] Refund đánh dấu + (optional) rollback subscription.
- [ ] Audit log search cross-tenant.
- [ ] Broadcast gửi email qua queue `notifications` (Mailpit nhận đủ).
- [ ] FE: menu 4 mục mới + 3 tab drawer mới + ô voucher ở /settings/plan.
- [ ] `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test` xanh; ≥20 test mới.
- [ ] `endpoints.md` cập nhật đầy đủ; `queues-and-scheduler.md` ghi BroadcastNotification; `roadmap.md` đánh dấu sub-task của Phase 6.4 (admin toolbox completed).
- [ ] Deploy guide cập nhật `docker-compose.prod.yml` (KHÔNG cần env mới); migration order; horizon restart.

## 11. Câu hỏi mở

- **Q1 Voucher one-time vs reusable per tenant**: v1 cho phép cùng tenant redeem cùng voucher cho **nhiều invoice khác nhau** (vd voucher 20% off mãi mãi). Nếu cần "1 tenant chỉ dùng 1 lần" ⇒ thêm flag `one_per_tenant: bool` — chưa làm v1.
- **Q2 Refund qua gateway thật**: SePay không có API refund (chuyển khoản tay); VNPay có nhưng cần spec con (auth merchant). v1 chỉ đánh dấu nội bộ.
- **Q3 Broadcast attachment**: chưa support file đính kèm — chỉ markdown text. Cần file ⇒ link tới MinIO signed URL trong body.
- **Q4 Plan editor cho phép tạo plan mới**: rủi ro xung đột với BillingPlanSeeder; v1 chỉ sửa 4 gói có sẵn. Tạo gói Enterprise tuỳ chỉnh ⇒ Tier 3.
