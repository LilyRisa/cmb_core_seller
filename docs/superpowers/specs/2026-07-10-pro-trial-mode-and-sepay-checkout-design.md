# Thiết kế: Gỡ `test_unlimited`, Chế độ trải nghiệm Pro, Điều khoản không hoàn & Hoàn thiện SePay

- **Ngày:** 2026-07-10
- **Module:** `Billing` (chính), `Admin`, `Integrations/Payments/SePay`, FE `features/billing` + admin
- **SPEC liên quan:** 0018 (billing), 0020 (over-quota lock), 0023 (admin can thiệp), 0032 (catalog 3 gói + beta mode — **thay thế beta mode ở spec này**)

## 1. Mục tiêu

1. **Gỡ gói `test_unlimited`** khỏi hệ thống; dời mọi tenant đang dùng nó về gói cơ bản (`starter`).
2. Thay cơ chế "beta mode" (vốn = `test_unlimited.is_active`) bằng **Chế độ trải nghiệm Pro**: admin bật/tắt + cấu hình thời lượng trải nghiệm và cửa sổ mở chiến dịch. Tenant (cũ & mới) tự đăng ký Pro để trải nghiệm, **mỗi tenant chỉ 1 lần vĩnh viễn** — kể cả khi admin bật lại về sau.
3. **Modal điều khoản không hoàn lại** dùng chung cho cả (a) đăng ký trải nghiệm và (b) thanh toán gói thật; bắt tick đồng ý mới tiếp tục; lưu mốc đồng ý. Trải nghiệm: đồng ý → kích hoạt ngay. Thanh toán: đồng ý → sang bước trả tiền, đã trả là không hoàn.
4. **Hoàn thiện luồng thanh toán SePay end-to-end** + gắn UI (QR + poll trạng thái + kích hoạt qua webhook).

## 2. Quyết định đã chốt (từ brainstorming)

- "Set thời gian" = **cả hai**: (a) thời lượng mỗi tenant được trải nghiệm (mặc định 30 ngày, admin chỉnh được) và (b) cửa sổ mở đăng ký (ngày bắt đầu/kết thúc chiến dịch, nullable).
- Hết hạn trải nghiệm → **về gói trước đó** của tenant (khôi phục plan + chu kỳ + period_end trước khi bấm trải nghiệm; nếu đó là trial đã hết thì lưới `ensureTrialFallback` xử lý).
- SePay = **hoàn thiện end-to-end + gắn UI** (connector đã có, thiếu UI mua gói thật + verify chạy thật).
- Eligibility trải nghiệm = **chế độ đang bật & trong cửa sổ & tenant chưa từng trải nghiệm & gói hiện tại thấp hơn Pro**.
- Modal điều khoản = áp **cả trải nghiệm & thanh toán**, một component chung.
- `test_unlimited`: **giữ row deactivated** (an toàn FK cho subscription lịch sử đã ended), ẩn khắp UI; chỉ repoint các subscription **đang alive** về `starter`.
- Cấu hình chế độ trải nghiệm đặt ở **màn admin Plans**.

## 3. Phần A — Gỡ `test_unlimited`, dời về `starter`

### 3.1 Thay đổi code
- Xoá `app/app/Modules/Billing/Database/Seeders/TestUnlimitedPlanSeeder.php` và mọi tham chiếu tới nó (đặc biệt trong migration `2026_06_06_120001_upsert_spec_0032_plans.php` — bỏ lời gọi seeder này).
- `SubscriptionService`: xoá `betaModeOn()`, `createBetaUnlimited()`; nhánh `startTrial()` cấp `test_unlimited` khi beta ON → **luôn cấp `trial` 14 ngày** cho tenant mới.
- Xoá `app/app/Modules/Billing/Console/BetaModeCommand.php` (`billing:beta on|off`).
- `AdminTenantController::changePlan()` / `AdminTenantService::changePlan()`: chặn gán `test_unlimited` (thêm vào danh sách mã cấm) và bỏ nhánh period +50 năm cho nó.
- FE admin `AdminTenantDrawer.tsx`: bỏ `test_unlimited` khỏi dropdown gói.
- `PublicPlanController::index`: chỉ trả gói có `code ∈ Plan::CODES` **và** `is_active` (không lộ mã nội bộ dù active).

### 3.2 Migration DB mới `..._repoint_test_unlimited_to_starter.php`
- Tìm `plans.code = 'test_unlimited'` → `$oldId`; `plans.code = 'starter'` → `$starterId`.
- Với **mọi subscription status ∈ {trialing, active, past_due}** có `plan_id = $oldId`:
  - Set `status = 'cancelled'`, `cancelled_at = now`, `ended_at = now` cho row cũ.
  - Tạo subscription mới: `plan_id = $starterId`, `status = 'active'`, `billing_cycle = 'monthly'`, `current_period_start = now`, `current_period_end = now + 1 tháng`, `meta.migrated_from = 'test_unlimited'`.
  - Tôn trọng partial unique `subscriptions_one_alive_per_tenant` (cancel trước, tạo sau, trong transaction/theo tenant).
- Set `plans` row `test_unlimited`: `is_active = false`.
- **Không xoá row** (giữ FK cho subscription ended lịch sử). Idempotent: chạy lại không tạo trùng (kiểm tra đã có sub alive starter chưa).
- Skip khi chạy test (giống các migration seed hiện có).

## 4. Phần B — Chế độ trải nghiệm Pro

### 4.1 Cấu hình toàn hệ thống (qua `system_setting()`)
Khoá (system-scope, không tenant):
- `billing.pro_trial.enabled` — bool, mặc định false.
- `billing.pro_trial.duration_days` — int, mặc định 30.
- `billing.pro_trial.window_start` — date (ISO) nullable.
- `billing.pro_trial.window_end` — date (ISO) nullable.

> Kiểm chứng: `system_setting()` hỗ trợ khoá system-scope (không tenant). Nếu chỉ hỗ trợ tenant-scope, fallback sang một bảng `pro_trial_settings` một-hàng hoặc dùng khoá system-setting toàn cục. Xác minh trong lúc lập kế hoạch.

Admin API:
- `GET /api/v1/admin/pro-trial-settings` → `{ enabled, duration_days, window_start, window_end }`.
- `PUT /api/v1/admin/pro-trial-settings` → validate (`duration_days` 1..365; window_start ≤ window_end nếu cả hai có) → ghi setting + `audit_logs` (`admin.pro_trial.settings`).

FE admin: khối "Chế độ trải nghiệm Pro" trong `AdminPlansPage.tsx` — switch bật/tắt, input số ngày, 2 date picker khoảng mở.

### 4.2 Bảng mới `pro_trial_grants` (Billing module, durable)
Migration `..._create_pro_trial_grants_table.php`:
- `id`, `tenant_id` (**UNIQUE** — đảm bảo 1 lần/tenant vĩnh viễn), `granted_at`, `expires_at`,
- `previous_plan_id` (nullable FK plans), `previous_cycle` (string nullable), `previous_period_end` (timestamp nullable),
- `terms_accepted_at`, `terms_version` (string),
- `reverted_at` (nullable — đánh dấu đã hạ gói xong), timestamps.
- Có `tenant_id` + trait `BelongsToTenant` nhưng UNIQUE toàn cục trên `tenant_id`.

Model `app/app/Modules/Billing/Models/ProTrialGrant.php`.

### 4.3 Eligibility (server-side, nguồn sự thật)
Service `ProTrialService::eligibility($tenantId): array`:
- `enabled == true`;
- `now` trong `[window_start, window_end]` (bỏ qua cạnh null);
- chưa có `pro_trial_grants` cho tenant;
- subscription alive hiện tại có `plan.code ∈ {trial, starter}` (thấp hơn Pro; không cho nếu đang pro/business/…).
- Trả `{ eligible: bool, reason: string|null, duration_days, ends_preview }`.

API `GET /api/v1/billing/pro-trial/eligibility` (quyền `billing.manage`) → dùng cho FE ẩn/hiện nút "Đăng ký trải nghiệm Pro".

### 4.4 Đăng ký trải nghiệm
API `POST /api/v1/billing/pro-trial/register` body `{ terms_accepted: true, terms_version: string }` (quyền `billing.manage`):
- FormRequest: `terms_accepted` phải `accepted`; `terms_version` required.
- `ProTrialService::register()` trong transaction + khoá theo tenant (chống đua):
  1. Re-check eligibility; nếu fail → 422 với mã lỗi rõ (`PRO_TRIAL_NOT_ELIGIBLE` / `PRO_TRIAL_ALREADY_USED` / `PRO_TRIAL_WINDOW_CLOSED`).
  2. Đọc subscription alive hiện tại → lưu `previous_plan_id`, `previous_cycle`, `previous_period_end`.
  3. Tạo row `pro_trial_grants` (unique tenant_id — nếu vi phạm race → coi như đã dùng).
  4. Cancel sub cũ; tạo sub **Pro** `active`, `current_period_end = now + duration_days`, `meta = { pro_trial: true, revert_plan_id, revert_cycle, revert_period_end }`.
  5. Không tạo invoice, không thanh toán.
- Trả subscription mới.

### 4.5 Hết hạn → về gói trước đó
- Hook vào cơ chế hết hạn sẵn có (`SubscriptionExpiryService` + scheduled command). Khi gặp sub có `meta.pro_trial == true` và `current_period_end < now`:
  - Cancel sub trải nghiệm; tạo sub mới trên `meta.revert_plan_id` với `billing_cycle = revert_cycle`, `current_period_end = revert_period_end`.
  - Nếu `revert_period_end` đã ở quá khứ (vd trial cũ đã hết) → để `ensureTrialFallback` cấp trial lưới an toàn.
  - Set `pro_trial_grants.reverted_at = now`. **Không xoá** row grant → không đăng ký lại được.
- Idempotent, chạy trong scheduler định kỳ.

## 5. Phần C — Modal điều khoản không hoàn lại (dùng chung)

- Hằng số `TERMS_VERSION` (vd `'refund-v1'`) ở cả FE & BE (config `billing.php` → `config('billing.refund_terms_version')`), để đối chiếu.
- FE component `RefundPolicyModal`:
  - Hiển thị nội dung điều khoản không hoàn (tiếng Việt), checkbox "Tôi đã đọc và đồng ý…", nút "Tiếp tục" disabled tới khi tick.
  - Prop `mode: 'trial' | 'payment'` để đổi tiêu đề/nội dung phụ (trial nhấn mạnh "tự động hạ gói sau khi hết hạn"; payment nhấn "không hoàn tiền").
- Luồng trải nghiệm: nút "Đăng ký trải nghiệm" → `RefundPolicyModal(mode=trial)` → tick → `pro-trial/register({terms_accepted, terms_version})` → kích hoạt ngay → toast + refresh subscription.
- Luồng thanh toán: nút "Đăng ký/Nâng cấp" → `RefundPolicyModal(mode=payment)` → tick → `checkout({..., terms_accepted, terms_version})` → mở `CheckoutModal` (mục D).
- BE lưu mốc đồng ý:
  - Trải nghiệm → `pro_trial_grants.terms_accepted_at / terms_version`.
  - Thanh toán → `checkout` FormRequest yêu cầu `terms_accepted` (accepted) + `terms_version`; ghi vào `invoices.meta.terms_accepted_at / terms_version`. Thiếu → 422.

## 6. Phần D — Hoàn thiện SePay end-to-end + UI

Connector đã có: `SePayConnector.checkout()` trả `CheckoutSession{ method: 'bank_transfer', qr_url, account_no, account_name, memo=invoice.code, amount }`; webhook `POST /webhook/payments/sepay` → `PaymentService::applyNotification()` → `invoice=paid` → `InvoicePaid` → `ActivateSubscription`.

Bổ sung:
- **Cổng mặc định:** đảm bảo `config('integrations.payments.enabled')` gồm `sepay` và là default; FE `useCheckout` mặc định `gateway='sepay'`; UI chỉ hiện cổng nằm trong danh sách enabled (ẩn MoMo skeleton).
- **FE `CheckoutModal`:** sau `checkout` trả `CheckoutSession`, hiển thị ảnh `qr_url`, số TK, tên TK, nội dung CK (mã invoice), số tiền (VND). **Poll** `GET /api/v1/billing/invoices/{id}` mỗi ~4s (dừng khi `paid` hoặc timeout/đóng modal). Khi `paid` → màn "Kích hoạt thành công" + refresh `useSubscription`. Nút "Tôi đã chuyển khoản" chỉ trigger poll ngay; kích hoạt vẫn do webhook (idempotent).
- **Endpoint xem invoice** (nếu chưa có GET đơn lẻ): `GET /api/v1/billing/invoices/{id}` trả `{ status, ... }` cho FE poll (quyền `billing.manage`, tenant-scoped).
- **Verify chạy thật:** giả lập webhook SePay (payload mẫu + header `Authorization: Apikey`) tới `/webhook/payments/sepay` để xác nhận invoice → paid → subscription active. Chạy trước khi coi là hoàn tất.

## 7. Tổng hợp API

| Method | Path | Mục đích |
|---|---|---|
| GET | `/api/v1/billing/pro-trial/eligibility` | Kiểm tra đủ điều kiện trải nghiệm |
| POST | `/api/v1/billing/pro-trial/register` | Đăng ký trải nghiệm Pro (kèm terms) |
| GET | `/api/v1/billing/invoices/{id}` | Poll trạng thái thanh toán |
| POST | `/api/v1/billing/checkout` (đổi) | Thêm `terms_accepted`, `terms_version` |
| GET | `/api/v1/admin/pro-trial-settings` | Đọc cấu hình chế độ trải nghiệm |
| PUT | `/api/v1/admin/pro-trial-settings` | Cập nhật cấu hình |
| GET | `/api/v1/public/plans` (đổi) | Lọc chỉ `Plan::CODES` |

## 8. Migrations

1. `..._repoint_test_unlimited_to_starter.php` — repoint sub alive + deactivate plan (skip khi test).
2. `..._create_pro_trial_grants_table.php`.

## 9. Rủi ro & lưu ý

- Tenant riêng của admin nếu đang `test_unlimited` sẽ về `starter`; quyền quản trị không phụ thuộc gói (super-admin bypass). Muốn full: `changePlan → pro` hoặc feature-overrides.
- Đua đăng ký trải nghiệm: chặn bằng UNIQUE(tenant_id) + khoá transaction.
- `system_setting()` scope: xác minh hỗ trợ system-scope trước khi code (mục 4.1).
- Không phá `subscriptions_one_alive_per_tenant`: mọi thao tác đổi gói phải cancel sub cũ trước khi tạo mới.
- Idempotent toàn bộ migration & webhook (bất biến của hệ thống).

## 10. Kiểm thử

- Repoint: seed 1 tenant test_unlimited → migrate → assert về starter, 1 sub alive.
- Eligibility: các tổ hợp enabled/window/đã-dùng/gói-hiện-tại.
- Register: tạo grant + sub Pro; đăng ký lần 2 → 422; admin bật lại sau khi đã dùng → vẫn 422.
- Hết hạn: sub pro_trial quá hạn → về đúng previous plan; grant giữ nguyên.
- Terms: checkout/register thiếu `terms_accepted` → 422.
- SePay: giả lập webhook → invoice paid → sub active; gọi lại webhook (dedupe) → không nhân đôi.
