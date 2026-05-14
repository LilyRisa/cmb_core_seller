# SPEC 0018: Billing SaaS — Gói thuê bao + Hạn mức + Cổng thanh toán VN (SePay/VNPay/MoMo)

- **Trạng thái:** Implemented (2026-05-14 — PR1 nền + PR2 SePay + PR3 VNPay/MoMo skeleton; 46/46 test xanh; bật prod khi có merchant credentials)
- **Phase:** 6.4
- **Module backend liên quan:** Billing (mới — đã code đầy đủ), Tenancy (RBAC + audit + event `TenantCreated`), Channels (gating hạn mức gian hàng), Finance/Reports/Procurement/Inventory/Products/Settings (gating tính năng nâng cao)
- **Tác giả / Ngày:** Team · 2026-05-14
- **Liên quan:** `01-architecture/extensibility-rules.md` §1 (trục mở rộng "Cổng thanh toán"), `01-architecture/multi-tenancy-and-rbac.md` §5, `02-data-model/overview.md` §Billing, `05-api/conventions.md`, `06-frontend/overview.md`, `07-infra/queues-and-scheduler.md`, `08-security-and-privacy.md` §3, SPEC-0007 §3.3 (Gói & nâng cấp), SPEC-0011 (Settings shell).

## 1. Vấn đề & mục tiêu

CMBcoreSeller là sản phẩm SaaS — cần **bán được gói thuê bao**. `roadmap.md` Phase 6.4 ghi: `Subscription plan + hạn mức + dùng thử 14 ngày + thanh toán VNPay/MoMo + gating tính năng theo plan`. SPEC này biến nó thành đặc tả triển khai.

**Mục tiêu chốt với chủ dự án 2026-05-14:**
1. Bán **3 gói trả phí** đơn giản (Starter / Pro / Business) + **trial 14 ngày** miễn phí.
2. Chỉ phân biệt gói bằng **số gian hàng kết nối** + **tính năng nâng cao mở khoá** — **không giới hạn số đơn**.
3. Thanh toán bằng **SePay** (chuyển khoản tự động qua webhook sao kê) + **VNPay** (redirect + IPN); **MoMo** chừa khung làm sau.
4. Hết hạn ⇒ **grace period 7 ngày** rồi rớt về `trial` — **không khoá data** (đảm bảo cam kết "Dữ liệu của bạn là của bạn" — `GIOI-THIEU-SAN-PHAM.md` §6).
5. Module `Billing` tuân thủ luật phụ thuộc (`01-architecture/modules.md` §3): chỉ phụ thuộc `Tenancy`; module khác **không** import `Billing` internals — gating qua middleware + permission gate.

## 2. Trong / ngoài phạm vi

**Trong (SPEC này):**
- Bảng `plans · subscriptions · invoices(+invoice_lines) · payments · usage_counters · billing_profiles` (khớp `02-data-model/overview.md` §Billing).
- Seeder 4 gói: `trial · starter · pro · business`.
- `PaymentGatewayConnector` interface + `PaymentRegistry` (mirror `ChannelRegistry`/`CarrierRegistry`).
- `SePayConnector` (luồng chuyển khoản — webhook khớp memo).
- `VnPayConnector` (luồng redirect + IPN, ký HMAC-SHA512).
- `MomoConnector` skeleton — capability=false, mọi method `UnsupportedOperation`.
- Trial auto-start khi tenant tạo (`StartTrialSubscription` listener).
- Middleware `EnforcePlanLimit` (gating số gian hàng) + `EnforcePlanFeature` (gating module nâng cao).
- API `/api/v1/billing/*` + webhook `/webhook/payments/{gateway}`.
- Scheduler: `subscriptions:check-expiring`, `subscriptions:create-renewal-invoices`, `billing:recompute-usage`.
- FE: `/settings/plan`, `/settings/invoices`, `/billing/checkout/:invoice_id`, banner trial.
- RBAC: `billing.view` (owner/admin/accountant), `billing.manage` (owner).
- Tests: feature (trial flow, gating, API, webhook idempotency, tenant isolation) + unit (signer/verifier/prorate).

**Ngoài (làm sau / spec khác):**
- **Tokenization auto-renew** (Phase sau): v1 reminder + user tự chuyển khoản/checkout lại; v2 sẽ làm khi VN gateway có API tokenization ổn.
- **Hoá đơn điện tử (HĐĐT) tuân thủ TT78** — Phase 7+ (`vision-and-scope.md` §4); SPEC này chỉ in PDF "phiếu thu" qua Gotenberg.
- **MoMo production** — chỉ làm skeleton; bật thật khi shop có nhu cầu (spec con sau).
- **Gói Enterprise** (> 10 gian hàng) — bán riêng, không qua self-serve checkout; admin tạo `subscription` thủ công.
- **Add-on pack** (mua thêm gian hàng / tính năng đơn lẻ) — backlog, không làm v1.
- **Tích hợp kế toán** (MISA / KiotViet) — Phase 7+.

## 3. Câu chuyện người dùng / luồng chính

### 3.1 Đăng ký mới — trial 14 ngày
1. User `POST /auth/register` tạo user + tenant.
2. Event `TenantCreated` (mới — phát trong `AuthController::register` sau khi tạo tenant) ⇒ listener `StartTrialSubscription` (queue `default`) tạo `subscriptions` row: `plan_code='trial'`, `status='trialing'`, `trial_ends_at = now()->addDays(14)`, `current_period_start = now()`, `current_period_end = now()->addDays(14)`.
3. SPA hiện banner "Bạn đang dùng thử — còn 14 ngày" ở header `AppLayout`.

### 3.2 Nâng cấp gói
1. User vào `/settings/plan` → thấy 4 gói + nút "Chọn gói này".
2. Bấm "Chọn Pro" → modal `<UpgradeModal>`: `Radio.Group` chọn `cycle` (Tháng / Năm) + `Segmented` chọn `gateway` (SePay / VNPay).
3. SPA `POST /api/v1/billing/checkout {plan_code, cycle, gateway}` → BE:
   a. Validate plan/cycle/gateway + check `billing.manage` permission.
   b. Tính `total` theo `cycle`: monthly = `price_monthly`, yearly = `price_yearly` (đã giảm = 10 tháng).
   c. Nếu shop **đang ở trial / đang ở gói rẻ hơn** — prorate KHÔNG áp dụng v1 (đơn giản hoá): hoá đơn mới = full price, subscription cũ giữ tới khi paid rồi mới swap. **Nếu đang ở gói cao hơn cùng cycle** ⇒ `422 ALREADY_ON_PLAN`.
   d. Tạo `invoices` (`status='pending'`, `code=INV-YYYYMM-NNNN`, `due_at=now()+7d`) + 1 `invoice_lines` (`kind=plan`).
   e. Gọi `PaymentRegistry::for($gateway)->checkout(CheckoutRequest)` → trả `CheckoutSession`:
      - **SePay:** `{ method:'bank_transfer', qr_url, account_no, account_name, bank_code, memo, amount, reference: invoice.code }`.
      - **VNPay:** `{ method:'redirect', redirect_url, reference }`.
      - **MoMo:** ném `UnsupportedOperation`.
   f. Trả `{ data: { invoice_id, checkout: CheckoutSession } }` cho SPA.
4. SPA điều hướng:
   - SePay ⇒ trang `/billing/checkout/:invoice_id` hiển thị QR + thông tin chuyển khoản + nút "Tôi đã chuyển" (chỉ UI, không tin client) + polling `GET /billing/invoices/{id}/payment-status` mỗi 5s.
   - VNPay ⇒ `window.location.href = redirect_url`.

### 3.3 Webhook thanh toán về
1. Gateway gửi `POST /webhook/payments/{gateway}` (signed).
2. Controller `PaymentWebhookController@handle` (giống `Channels\WebhookController`):
   a. Verify chữ ký (sai ⇒ 401, không lưu).
   b. Ghi `webhook_events` (provider=`payments.{gateway}`, status=`pending`).
   c. Trả `200` ngay.
   d. Dispatch `ProcessPaymentWebhook` job (queue `webhooks`).
3. `ProcessPaymentWebhook`:
   a. `PaymentRegistry::for($gateway)->parseWebhook` → `PaymentNotification { external_ref, amount, status, reference, occurred_at, raw }`.
   b. Resolve `invoice` theo `reference` (= invoice.code). Không thấy ⇒ `webhook_events.status='ignored'` + ghi audit "orphan payment".
   c. Dedupe: nếu `payments(gateway, external_ref)` đã tồn tại ⇒ no-op (idempotent).
   d. Tạo `payments` row, set `succeeded`.
   e. Nếu `payment.amount >= invoice.total` ⇒ phát event `InvoicePaid`.
4. Listener `ActivateSubscription` (queue `billing`):
   - Mark `invoice.status='paid'`, `paid_at=now()`.
   - Tìm subscription hiện tại của tenant. Nếu cùng plan + chưa hết hạn ⇒ extend `current_period_end += cycle`. Nếu khác plan ⇒ subscription cũ `cancel_at = period_end` (hoặc `now()` nếu trial), tạo subscription mới `active`, `current_period_start=now()`, `current_period_end=now()+cycle`.
   - Gửi mail "Cảm ơn đã thanh toán" qua queue `notifications`.
   - Ghi `audit_logs`.

### 3.4 Gia hạn / hết hạn
- Scheduler hằng ngày `subscriptions:check-expiring`:
  - `current_period_end ∈ [now, now+7d]` ⇒ tạo invoice gia hạn (`pending`) + gửi mail reminder lần 1.
  - `current_period_end ∈ [now, now+3d]` ⇒ gửi mail reminder lần 2.
  - `current_period_end < now` & chưa thanh toán ⇒ `status='past_due'`. Banner đỏ "Quá hạn, còn N ngày trước khi rớt về gói thử".
  - `past_due` quá 7 ngày ⇒ `status='expired'` + auto-tạo subscription mới `plan=trial` `status='active'` `current_period_end=now+9999d` (vĩnh viễn) — đảm bảo user **không mất data**, chỉ mất truy cập tính năng nâng cao + thừa gian hàng.

### 3.5 Huỷ gói
- `POST /billing/subscription/cancel` ⇒ set `cancel_at = current_period_end` (chạy đến hết kỳ rồi rớt về trial). Không hoàn tiền.

### 3.6 Gating
- **Hạn mức gian hàng:** middleware `EnforcePlanLimit:channel_accounts` áp lên route `POST /channel-accounts/{provider}/connect`. Đếm `channel_accounts` `status='active'` của tenant; ≥ `plan.limits.max_channel_accounts` ⇒ `402 PLAN_LIMIT_REACHED` `{ resource:'channel_accounts', current:N, limit:M, upgrade_to:'pro' }`.
- **Tính năng nâng cao:** middleware `EnforcePlanFeature:<feature>` áp lên các route module nâng cao (`/finance/*`, `/procurement/*` non-view, `/reports/profit`, `/reports/top-products`, `/procurement/demand-planning`, …). Plan không có feature ⇒ `402 PLAN_FEATURE_LOCKED` `{ feature:'finance_settlements', upgrade_to:'pro' }`.
- **FE:** hook `usePlan()` đọc `GET /billing/subscription` (cache 5 phút) → ẩn/disable menu + tooltip "Nâng cấp Pro để mở khoá".

## 4. Hành vi & quy tắc nghiệp vụ

1. **Đơn giá đề xuất (lưu DB, admin sửa được):**
   | Plan | `price_monthly` | `price_yearly` | `max_channel_accounts` | Features |
   |---|---|---|---|---|
   | `trial` | 0 | 0 | 2 | (giống starter) |
   | `starter` | 99.000 | 990.000 | 2 | basic only |
   | `pro` | 199.000 | 1.990.000 | 5 | + procurement, fifo_cogs, profit_reports, finance_settlements, demand_planning |
   | `business` | 399.000 | 3.990.000 | 10 | + mass_listing, automation_rules, priority_support |

2. **Tiền tệ:** `bigint` VND đồng, không float (`02-data-model/overview.md` §1.4).

3. **`plans.features` (jsonb bool flags):**
   - `procurement` — bật `/procurement/*` non-view (PO/NCC).
   - `fifo_cogs` — bật FIFO chuẩn kế toán (nếu off ⇒ fallback `average`).
   - `profit_reports` — bật `/reports/profit` + `/reports/top-products` + cột "Lợi nhuận ước tính" trên `OrderResource.profit`.
   - `finance_settlements` — bật `/finance/*` + `FetchSettlementsForShop` job.
   - `demand_planning` — bật `/procurement/demand-planning*`.
   - `mass_listing` — Phase 5 follow-up.
   - `automation_rules` — Phase 6.5.
   - `priority_support` — cờ hiển thị badge.

4. **Idempotency:**
   - `payments` unique `(gateway, external_ref)` — webhook chạy 2 lần = 1 row.
   - `invoices` unique `(tenant_id, code)` (`INV-YYYYMM-NNNN`).
   - `usage_counters` unique `(tenant_id, metric, period)` — `UPSERT` atomic.
   - `subscriptions` unique partial index `(tenant_id) WHERE status IN ('trialing','active','past_due')` ⇒ 1 subscription "active" mỗi tenant.

5. **State machine `subscriptions.status`:**
   ```
   trialing → active (sau khi paid) | expired (hết trial chưa paid)
   active   → past_due (hết kỳ chưa paid) | cancelled (user huỷ) | active (gia hạn)
   past_due → active (paid) | expired (quá 7 ngày grace)
   cancelled → expired (sau khi cancel_at)
   expired  → terminal — auto-tạo subscription mới plan=trial active vĩnh viễn
   ```

6. **Phân quyền:**
   - `billing.view` (owner/admin/accountant): GET endpoints, xem `/settings/plan` + `/settings/invoices`.
   - `billing.manage` (owner only): checkout, cancel, update billing_profile.
   - `Role` enum cập nhật `permissions()`.

7. **Audit log** mọi thao tác nhạy cảm: checkout, payment received (cả orphan), subscription activate/cancel/expire, manual mark-paid (admin support).

8. **PII:** không lưu PAN/CVV (VNPay là hosted). `payments.raw_payload` chỉ giữ `transaction_id`, `bank_code`, `amount`, `status`, `time` — KHÔNG lưu trường chứa thông tin thẻ (tuân thủ PCI scope minimization).

## 5. Dữ liệu

### 5.1 Bảng mới (`app/Modules/Billing/Database/Migrations/`)

```
plans                                       -- không tenant-scoped (chia sẻ toàn hệ thống)
  id, code(uniq) varchar(32), name varchar(120), description text?,
  is_active bool default true, sort_order smallint default 0,
  price_monthly bigint default 0, price_yearly bigint default 0, currency char(3)='VND',
  trial_days smallint default 0,
  limits jsonb {max_channel_accounts:int},     -- -1 = không giới hạn
  features jsonb {...bool flags},
  timestamps

subscriptions                               -- BelongsToTenant
  id, tenant_id index, plan_id FK plans.id,
  status varchar(16) [trialing|active|past_due|cancelled|expired] index,
  billing_cycle varchar(8) [monthly|yearly|trial],
  trial_ends_at timestamptz null,
  current_period_start timestamptz, current_period_end timestamptz index,
  cancel_at timestamptz null, cancelled_at timestamptz null, ended_at timestamptz null,
  meta jsonb,
  timestamps
  -- Postgres partial unique: WHERE status IN ('trialing','active','past_due')
  -- SQLite test: dùng index thường + check trong service.

invoices                                    -- BelongsToTenant
  id, tenant_id index, subscription_id FK,
  code varchar(32) [INV-YYYYMM-NNNN] uniq per tenant,
  status varchar(16) [draft|pending|paid|void|refunded] index,
  period_start date, period_end date,
  subtotal bigint, tax bigint default 0, total bigint, currency char(3)='VND',
  due_at timestamptz, paid_at timestamptz null, voided_at timestamptz null,
  customer_snapshot jsonb,   -- snapshot billing_profile lúc tạo (immutable)
  meta jsonb,
  timestamps

invoice_lines                               -- không tenant_id (tra qua invoice)
  id, invoice_id FK cascade,
  kind varchar(16) [plan|addon|discount],
  description varchar(255),
  quantity int default 1, unit_price bigint, amount bigint,
  timestamps

payments                                    -- BelongsToTenant
  id, tenant_id index, invoice_id FK,
  gateway varchar(16) [sepay|vnpay|momo|manual] index,
  external_ref varchar(128),                -- mã giao dịch của cổng
  amount bigint, status varchar(16) [pending|succeeded|failed|refunded] index,
  raw_payload jsonb,
  occurred_at timestamptz, created_at timestamptz
  unique (gateway, external_ref)            -- idempotency dedupe

usage_counters                              -- BelongsToTenant
  id, tenant_id index, metric varchar(32) [channel_accounts],
  period char(7),                            -- 'current' (denormalized count) — v1 chỉ 1 metric
  value bigint, last_updated_at timestamptz
  unique (tenant_id, metric, period)

billing_profiles                            -- BelongsToTenant, 1-1 với tenant
  id, tenant_id uniq index,
  company_name varchar(255)?, tax_code varchar(32)?,
  billing_address varchar(500)?, contact_email varchar(191)?, contact_phone varchar(32)?,
  timestamps
```

### 5.2 Cột thêm bảng có sẵn
- Không thêm cột vào bảng module khác. Module Billing tự đếm qua query `channel_accounts where status='active'` — không cần denormalize.

### 5.3 Domain event mới
- `TenantCreated` (`app/Modules/Tenancy/Events/`) — phát sau `Tenant::create()` trong `AuthController::register` + bất cứ nơi nào tạo tenant.
- `InvoicePaid` (`app/Modules/Billing/Events/`) — phát khi payment thành công + invoice.total đã đủ.
- `SubscriptionActivated` / `SubscriptionExpired` (`app/Modules/Billing/Events/`).

### 5.4 Seeder
- `BillingPlanSeeder` (`app/Modules/Billing/Database/Seeders/`) — upsert 4 gói theo `code`. Đảm bảo idempotent (chạy lại không tạo trùng). Chạy ở `DatabaseSeeder` + ở migration via `php artisan db:seed --class=BillingPlanSeeder` trong CI.

## 6. API & UI

### 6.1 Endpoints mới (cập nhật `docs/05-api/endpoints.md`)

| Method | Path | Auth | Mô tả |
|---|---|---|---|
| GET | `/api/v1/billing/plans` | `billing.view` (không cần tenant — public catalogue) | Trả `[PlanResource]` mọi gói active. |
| GET | `/api/v1/billing/subscription` | `billing.view` + tenant | Trả subscription hiện tại + plan + usage. |
| GET | `/api/v1/billing/usage` | `billing.view` + tenant | `{ channel_accounts: {used, limit} }`. |
| POST | `/api/v1/billing/checkout` | `billing.manage` + tenant | `{plan_code, cycle, gateway}` → `{invoice_id, checkout: CheckoutSession}`. |
| GET | `/api/v1/billing/invoices` | `billing.view` + tenant | List invoices. |
| GET | `/api/v1/billing/invoices/{id}` | `billing.view` + tenant | Invoice + lines. |
| GET | `/api/v1/billing/invoices/{id}/payment-status` | `billing.view` + tenant | `{status, paid_at?}` — SePay UX polling. |
| POST | `/api/v1/billing/subscription/cancel` | `billing.manage` + tenant | Set `cancel_at = period_end`. |
| GET/PATCH | `/api/v1/billing/billing-profile` | view/`billing.manage` | Update info xuất hoá đơn. |

Webhook (ngoài `/api`):
| Method | Path | Mô tả |
|---|---|---|
| POST | `/webhook/payments/sepay` | SePay đẩy về (verify chữ ký HMAC). |
| POST | `/webhook/payments/vnpay` | VNPay IPN (verify HMAC-SHA512). |
| GET | `/payments/vnpay/return` | User redirect — không tin, chỉ UX. |

### 6.2 Response envelope (theo `05-api/conventions.md`)
- Thành công: `{ data: ... }`.
- Lỗi: `{ error: { code, message, details?, trace_id } }`.
- Codes mới: `PLAN_LIMIT_REACHED`, `PLAN_FEATURE_LOCKED`, `ALREADY_ON_PLAN`, `PLAN_NOT_FOUND`, `INVOICE_ALREADY_PAID`, `GATEWAY_UNAVAILABLE`, `WEBHOOK_SIGNATURE_INVALID`.

### 6.3 FE (`resources/js/features/billing/`)
- `pages/SettingsPlanPage.tsx` — thay `ComingSoon` của SPEC-0011:
  - Header: gói hiện tại + nút "Huỷ gói" (chỉ owner).
  - Card "Đang dùng" với progress: `usage.channel_accounts / plan.limits.max_channel_accounts`.
  - Bảng so sánh 4 gói (sticky highlight gói hiện tại) — dùng `<Radio.Group buttonStyle="solid">` chọn cycle (Tháng / Năm), không Select.
  - Nút "Chọn gói này" trên mỗi gói khác gói hiện tại ⇒ `<UpgradeModal>` với `<Segmented>` chọn gateway (SePay / VNPay; MoMo disabled "Sắp có").
- `pages/SettingsInvoicesPage.tsx` — list invoices + nút "Tải PDF" + nút "Thanh toán lại" (đối với invoice `pending`).
- `pages/CheckoutPage.tsx` (`/billing/checkout/:invoice_id`) —
  - SePay: hiển thị QR (`qr_url` từ SePay) + thông tin chuyển khoản + polling.
  - VNPay: tự redirect ngay khi mount.
- `components/TrialBanner.tsx` — gắn vào `AppLayout` header. Hiện banner khi `subscription.status='trialing'` (còn N ngày) hoặc `past_due` (còn N ngày trước khi rớt).
- `hooks/useBilling.ts` — `useSubscription()` + `useInvoices()` + `useCheckout()` qua TanStack Query.
- Icon: `<CreditCardOutlined/>`, `<BankOutlined/>`, `<QrcodeOutlined/>`, `<CrownOutlined/>` — không emoji (theo memory `ui-use-font-icons-not-emoji`).

### 6.4 Sidebar
- Đã có "Cài đặt" parent. Thêm sub-item "Gói & thanh toán" → `/settings/plan` (icon `<CreditCardOutlined/>`). Sub-item "Hoá đơn" → `/settings/invoices` (icon `<FileTextOutlined/>`).

### 6.5 Job & queue (cập nhật `docs/07-infra/queues-and-scheduler.md`)
Thêm queue `billing` (priority thấp, supervisor-default):
- `Billing\Jobs\ProcessPaymentWebhook` (`tries=5, backoff exponential`)
- `Billing\Listeners\StartTrialSubscription` (listen `TenantCreated`)
- `Billing\Listeners\ActivateSubscription` (listen `InvoicePaid`)
- `Billing\Listeners\BumpChannelAccountCounter` (listen `ChannelAccountConnected`/`ChannelAccountRevoked`)

Scheduler:
- mỗi giờ — `billing:recompute-usage` (safety-net recount `channel_accounts` cho mọi tenant).
- hằng ngày 04:00 — `subscriptions:check-expiring` (tạo invoice gia hạn -7d, mail reminder -7d/-3d, mark `past_due`, mark `expired` sau 7 ngày past_due, auto-tạo trial vĩnh viễn).

## 7. Edge case & lỗi

- **Webhook đến trước khi invoice tạo xong** (race): `ProcessPaymentWebhook` không thấy invoice ⇒ retry với backoff. Sau quá hạn vẫn không thấy ⇒ ghi `webhook_events.status='ignored'` + audit "orphan", admin có thể manual reconcile.
- **Webhook đến 2 lần** (gateway retry): unique `(gateway, external_ref)` ⇒ insert ignore, no-op.
- **Số tiền chuyển khác total** (SePay): nếu `payment.amount < invoice.total` ⇒ payment `succeeded` nhưng invoice vẫn `pending` — user thấy "Đã nhận N/M, vui lòng chuyển thêm". Nếu `>` ⇒ ghi nhận hết, invoice paid, thừa = "ghi nhớ" trong `payments.meta.overpay` (không tự refund).
- **Đổi gói giữa kỳ:**
  - Trial → Paid: trial subscription `cancel_at=now`, paid subscription `current_period_start=now`.
  - Paid → Paid cao hơn: v1 đơn giản — invoice mới full price, subscription cũ `cancel_at = period_end`, subscription mới `active` `current_period_start=now`. **Không prorate v1** (`open question Q1` ở §11).
  - Paid → Paid thấp hơn: chặn `422 DOWNGRADE_NOT_ALLOWED` v1 — user phải đợi hết kỳ rồi mới downgrade (đơn giản hoá; v2 sẽ làm prorate refund).
- **Cancel rồi đăng ký lại**: subscription cũ `cancelled` → chạy hết kỳ → `expired` → auto-tạo trial. User mua lại = `checkout` bình thường ⇒ subscription mới.
- **Tenant không có subscription** (legacy / migration): middleware tự tạo trial vĩnh viễn (fallback an toàn).
- **Vượt hạn mức tại thời điểm downgrade** (vd shop đang có 5 gian hàng, hết hạn Pro rớt trial 2 gian hàng): không tự disconnect — chỉ chặn connect mới + banner cảnh báo "Bạn đang có 5 gian hàng nhưng gói trial chỉ cho 2 — nâng cấp Pro để tiếp tục đồng bộ tất cả gian hàng". Không mất data.
- **Gateway lỗi trong checkout** (SePay/VNPay API 5xx): `POST /billing/checkout` trả `502 GATEWAY_UNAVAILABLE` + giữ invoice `pending` (user có thể thử lại).
- **SePay merchant chưa cấu hình** (`env` thiếu): `PaymentRegistry::for('sepay')` ném `GatewayNotConfigured` → checkout trả `422`.

## 8. Bảo mật & dữ liệu cá nhân (`08-security-and-privacy.md`)

- **Webhook signature** verify cả SePay + VNPay (sai ⇒ 401, không lưu, không xử lý — §4.1).
- **Secret** (`SEPAY_API_KEY`, `SEPAY_WEBHOOK_SECRET`, `VNPAY_HASH_SECRET`, …): chỉ `env`, KHÔNG trong DB, KHÔNG trong repo (§3).
- **`payments.raw_payload`** chỉ giữ metadata không nhạy cảm (transaction_id, amount, bank_code, status, time). KHÔNG lưu PAN, CVV, OTP, full bank account của buyer (PCI scope minimization).
- **Audit log**: checkout, payment received, subscription activate/cancel/expire — ghi `audit_logs` (ai, tenant, action, before/after, ip, time).
- **Rate limit** `/billing/checkout`: 10/phút/user (chống spam tạo invoice). `/webhook/payments/*`: 600/phút/IP (gateway có thể burst).
- **Idempotency-Key** chấp nhận trên `POST /billing/checkout` (`05-api/conventions.md` §6).
- **Tenant scope**: `subscriptions`, `invoices`, `payments`, `usage_counters`, `billing_profiles` đều dùng `BelongsToTenant`. `plans` shared (read-only cho user). Webhook resolve tenant qua `invoice.tenant_id` (theo memo/reference), không trust gateway truyền.
- **Gateway credentials** trong `config/integrations.php` block `payments` (giống pattern tiktok/lazada) — `.env.example` cập nhật mẫu.

## 9. Kiểm thử (`09-process/testing-strategy.md`)

### 9.1 Unit
- `VnPaySigner` — vector cố định (request known → signature known), deterministic.
- `SePayWebhookVerifier` — HMAC verify đúng/sai/thiếu header.
- `BillingService::computeInvoiceTotal` — monthly vs yearly, trial = 0.
- `Role::can('billing.view'/'billing.manage')` — đúng cho từng role.

### 9.2 Feature
- `BillingTrialTest` — register → tenant tạo → trial subscription auto-start với đúng `trial_ends_at`.
- `BillingGatingTest` — middleware `EnforcePlanLimit`: trial (2 shop) connect thứ 3 ⇒ `402`. `EnforcePlanFeature`: trial gọi `/finance/settlements` ⇒ `402`.
- `BillingApiTest` — list plans, get subscription, checkout SePay returns QR session, checkout VNPay returns redirect_url, RBAC owner/accountant/staff, tenant isolation.
- `BillingWebhookTest` — SePay webhook valid sig → invoice paid + subscription active; sai sig ⇒ 401; trùng webhook ⇒ 1 payment row (idempotent); orphan ref ⇒ `ignored`; underpay ⇒ invoice vẫn pending.
- `SubscriptionLifecycleTest` — scheduler `check-expiring`: tạo invoice gia hạn -7d, set past_due, set expired, fallback trial vĩnh viễn.

### 9.3 Contract
- `SePayConnectorContractTest` — `Http::fake` SePay create-vqr endpoint → `checkout()` trả đúng `qr_url`, `parseWebhook` parse đúng payload fixture.
- `VnPayConnectorContractTest` — build URL signed đúng, parse IPN với fixture.

### 9.4 FE
- Vitest tối thiểu cho `usePlan()` hook (mock API, kiểm cache 5'). FE pages render-only test (smoke).

## 10. Tiêu chí hoàn thành (Acceptance criteria)

- [ ] Migration 7 bảng + seeder 4 gói chạy được; `php artisan migrate:fresh --seed` xanh.
- [ ] Register → tenant trial 14 ngày tự bật.
- [ ] Trial connect 2 shop OK, shop thứ 3 ⇒ `402 PLAN_LIMIT_REACHED`.
- [ ] Trial gọi `/finance/settlements` ⇒ `402 PLAN_FEATURE_LOCKED`. Pro/Business ⇒ 200.
- [ ] Checkout SePay → QR hiển thị; webhook fixture valid sig → invoice `paid` + subscription `active` + tenant đổi plan + audit log + mail dispatched.
- [ ] Checkout VNPay → redirect URL chứa `vnp_*` ký HMAC đúng; IPN fixture valid sig → flow tương tự SePay.
- [ ] Webhook trùng (cùng `external_ref`) ⇒ chỉ 1 `payments` row.
- [ ] Scheduler check-expiring: subscription quá hạn 7 ngày ⇒ `expired` + auto-tạo trial vĩnh viễn; user vẫn login & truy cập đơn cũ được.
- [ ] FE `/settings/plan`: hiện 4 gói + cycle radio + checkout modal + banner trial.
- [ ] RBAC: viewer/staff không thấy `/settings/plan` (`billing.view` cho accountant trở lên); chỉ owner bấm được "Nâng cấp" (`billing.manage`).
- [ ] Tenant isolation: tenant A không thấy subscription/invoice/payment của tenant B.
- [ ] Test suite vẫn xanh (>= test count cũ; thêm tối thiểu 20 test mới cho Billing).
- [ ] `endpoints.md` cập nhật phần `/api/v1/billing/*`; `queues-and-scheduler.md` cập nhật queue `billing` + scheduler; `multi-tenancy-and-rbac.md` §3 permission table thêm `billing.*`; `roadmap.md` Phase 6.4 → "Implemented".

## 11. Câu hỏi mở (giải quyết khi có dữ liệu thực)

- **Q1 Prorate khi upgrade giữa kỳ:** v1 không prorate (đơn giản). v2 sẽ refund tỉ lệ kỳ còn lại — chờ feedback shop.
- **Q2 Yearly → đổi sang Monthly khác plan:** chặn v1 (`422 CYCLE_CHANGE_NOT_ALLOWED`), v2 sẽ làm.
- **Q3 SePay endpoint thật:** tài liệu SePay `https://my.sepay.vn/docs` — cần test với account thật để xác định shape webhook chính xác (chữ ký Authorization header, format `transferType`/`transferAmount`). v1 dùng fixture theo public docs; bật flag production sau khi đối chiếu.
- **Q4 VNPay version:** dùng `2.1.0` (HMAC-SHA512). Verify với `vnp_TmnCode`/`vnp_HashSecret` sandbox khi có.
- **Q5 Hoá đơn VAT (HĐĐT) hợp lệ:** Phase 7+ — tích hợp VNPT/VIETTEL einvoice. SPEC này chỉ in PDF "phiếu thu" qua Gotenberg.
- **Q6 Gói Enterprise (> 10 gian hàng):** admin tạo `subscription` thủ công, không qua self-serve; trang admin chưa làm ở SPEC này.
