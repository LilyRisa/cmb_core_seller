# Thiết kế: Popup + email tự động mời trải nghiệm Pro cho tenant mới đăng ký

- **Ngày:** 2026-07-22
- **Module:** `Billing` (chính), `Notifications`, `Tenancy` (chỉ đọc `TenantCreated` + quan hệ owner có sẵn), FE `pages/SettingsPlanPage` + app shell
- **SPEC liên quan:** kế thừa cơ chế "Chế độ trải nghiệm Pro" đã ship — xem `docs/superpowers/specs/2026-07-10-pro-trial-mode-and-sepay-checkout-design.md`

## 1. Bối cảnh & vấn đề

Cơ chế "Chế độ trải nghiệm Pro" (2026-07-10) là **tự phục vụ**: tenant phải tự vào Cài đặt > Gói và bấm nút "Đăng ký trải nghiệm Pro" mới được cấp. Không có thông báo chủ động nào khi admin bật chế độ này — nên gần như không ai biết để bấm. Chủ hệ thống muốn: khi bật chế độ trải nghiệm, **tenant mới đăng ký tài khoản** phải được chủ động mời qua **popup** (kèm ngày bắt đầu/kết thúc) và **email**, thay vì phải tự tìm ra nút.

## 2. Quyết định đã chốt (từ brainstorming)

- **Phạm vi:** chỉ tenant **mới đăng ký tài khoản** (sign-up mới) mới nằm trong diện được mời popup — tenant cũ đang ở gói trial hiện có không được tự động mời lại (họ vẫn tự đăng ký được qua nút cũ ở Cài đặt > Gói nếu muốn).
- **Điều khoản:** đây **không phải** cấp tự động ngầm — popup vẫn phải hiện điều khoản không hoàn (dùng lại `RefundPolicyModal`) và bắt người dùng bấm đồng ý mới thực sự kích hoạt. Không có bước xác nhận nào bị bỏ qua so với luồng tự phục vụ hiện có.
- **Hành vi lặp lại:** nếu người dùng đóng popup (nút X) mà chưa bấm đồng ý/từ chối, popup **hiện lại mỗi lần đăng nhập/tải trang** cho tới khi họ bấm một trong hai nút hành động. Bấm "Không, cảm ơn" thì **tắt vĩnh viễn** (không hiện lại), nhưng nút "Đăng ký trải nghiệm Pro" ở Cài đặt > Gói vẫn còn nếu họ đổi ý sau.
- **Email:** gửi khi trải nghiệm **thực sự được kích hoạt** (sau khi bấm đồng ý trong popup, hoặc nếu họ tự đăng ký qua Cài đặt > Gói như cũ — cùng một điểm kích hoạt nên cùng một email).

## 3. Cơ chế phạm vi (chỉ tenant mới)

Bảng mới `pro_trial_offers` (Billing module) đánh dấu tenant nào **thuộc diện được mời** — tách biệt với `pro_trial_grants` (chỉ có row sau khi *đã kích hoạt*):

- `id`, `tenant_id` (**UNIQUE**), `offered_at`, `declined_at` (nullable), timestamps.

Listener mới `OfferProTrialPopup` gắn vào event `TenantCreated` có sẵn (cùng event mà `StartTrialSubscription` và `ReportSignupToMetaCapi` đang nghe — queue `billing`, cùng pattern). Listener này **luôn** tạo row `offered_at = now()` cho mọi tenant mới, **bất kể** chế độ trải nghiệm đang bật hay tắt tại thời điểm đăng ký. Việc popup có thực sự hiện hay không được quyết định **live** ở bước sau (mục 4) — nhờ vậy tenant vẫn nằm trong diện được mời kể cả khi admin bật chế độ trải nghiệm SAU khi họ đã đăng ký.

Tenant tạo **trước** khi tính năng này lên prod sẽ không có row `pro_trial_offers` → không bao giờ thấy popup, đúng yêu cầu "chỉ tenant mới".

## 4. API

- **Sửa** `GET /api/v1/billing/pro-trial/eligibility` — thêm field `show_popup: bool`, tính:
  `show_popup = eligible && offered && !declined`
  (`eligible` = kết quả `ProTrialService::eligibility()` hiện có; `offered`/`declined` đọc từ `pro_trial_offers` của tenant). FE đã poll endpoint này (`useProTrialEligibility`) nên không cần thêm query mới.
- **Mới** `POST /api/v1/billing/pro-trial/decline` (quyền `billing.manage`) — set `pro_trial_offers.declined_at = now()`. Idempotent (gọi lại không lỗi nếu đã declined).
- `POST /api/v1/billing/pro-trial/register` **giữ nguyên** (không đổi contract) — popup gọi lại đúng endpoint/hook `useRegisterProTrial` mà Cài đặt > Gói đang dùng.

Không cần cờ "đã kích hoạt" riêng trong `pro_trial_offers`: một khi `register()` thành công, `pro_trial_grants` có row → `eligibility.eligible = false` (`already_used`) → `show_popup` tự động false, không cần đồng bộ 2 nơi.

## 5. Email khi kích hoạt

- `ProTrialService::register()` fire event mới `ProTrialActivated(int $tenantId, CarbonImmutable $grantedAt, CarbonImmutable $expiresAt)` **sau khi transaction commit** (không fire bên trong transaction — tránh gửi email nếu rollback).
- Listener mới `SendProTrialActivatedEmail` (module `Notifications`, `ShouldQueue`, `queue = 'notifications'`, cùng pattern `SendWelcomeEmailOnVerified`) nghe event này:
  - Resolve tenant → owner user: `$tenant->users()->wherePivot('role', Role::Owner->value)->first()` (đúng pattern `ReportSignupToMetaCapi` đang dùng — `Tenancy` là module nền nên `Billing`/`Notifications` được phép dùng quan hệ này).
  - Nếu có owner + có email → `$owner->notify(new ProTrialActivatedNotification($grantedAt, $expiresAt))`.
- Notification mới `ProTrialActivatedNotification` (mail-only, theo khuôn `WelcomeNotification`): tiêu đề "Bạn đã được kích hoạt gói Pro trải nghiệm", nội dung nêu rõ ngày bắt đầu/kết thúc (hiển thị theo `app_display_tz()`, format `d/m/Y`), nhắc "gói sẽ tự động về gói trước đó sau khi hết hạn — không mất phí".
- Áp dụng cho **cả hai lối kích hoạt** (popup lẫn nút tự phục vụ ở Cài đặt > Gói) vì cùng đi qua `ProTrialService::register()`.

## 6. Frontend

- Component mới `ProTrialOfferModal`, mount **một lần ở app shell** (nơi các modal toàn cục khác của app user sống — không phải trong `SettingsPlanPage`, để hiện được ở bất kỳ trang nào sau đăng nhập).
- Đọc `useProTrialEligibility()` (hook có sẵn); hiện modal khi `data.show_popup === true`.
- Nội dung:
  - Ngày dự kiến hết hạn nếu kích hoạt ngay: `eligibility.ends_preview` (format hiển thị).
  - Nội dung điều khoản không hoàn — tái dùng `RefundPolicyModal` (`mode='trial'`), không viết lại text.
  - 2 nút hành động: **"Đồng ý kích hoạt"** (gọi `useRegisterProTrial(REFUND_TERMS_VERSION)` có sẵn → thành công thì đổi nội dung modal sang xác nhận ngắn "Đã kích hoạt tới ngày Y" rồi tự đóng) và **"Không, cảm ơn"** (gọi mutation `decline` mới → đóng, không hiện lại).
  - Nút đóng (X): chỉ đóng cục bộ (state React), không gọi API nào — lần tải trang/đăng nhập sau `show_popup` vẫn `true` nên hiện lại.

## 7. Tổng hợp API

| Method | Path | Thay đổi |
|---|---|---|
| GET | `/api/v1/billing/pro-trial/eligibility` | Thêm field `show_popup` |
| POST | `/api/v1/billing/pro-trial/decline` | **Mới** |
| POST | `/api/v1/billing/pro-trial/register` | Không đổi |

## 8. Migration

1. `..._create_pro_trial_offers_table.php` — bảng mới như mục 3.

## 9. Rủi ro & lưu ý

- `TenantCreated` hiện được nghe bởi 3 listener (`StartTrialSubscription`, `ReportSignupToMetaCapi`, và listener mới `OfferProTrialPopup`) — mỗi listener độc lập, lỗi một cái không chặn cái khác (Laravel queue mỗi listener job riêng).
- Listener ghi `pro_trial_offers` chạy **không phụ thuộc** `ProTrialSettings::enabled()` tại thời điểm đăng ký — quyết định có chủ đích: "chỉ tenant mới" nghĩa là *thuộc nhóm tenant mới đăng ký* (so với tenant cũ đã có trước tính năng này), không phải "chỉ tenant đăng ký sau khi admin đã bật công tắc". Nhờ vậy admin có thể bật/tắt chế độ nhiều lần mà không cần quan tâm tenant đăng ký trước hay sau lần bật gần nhất — điều kiện `enabled` chỉ được kiểm tra live qua `eligibility()`.
- `ProTrialActivated` phải fire sau commit để tránh gửi email cho một grant bị rollback (race đăng ký trùng).
- Không đổi hành vi `ProTrialService::eligibility()`/`register()` hiện có — chỉ cộng thêm, không sửa logic 1-lần-vĩnh-viễn đã có.

## 10. Kiểm thử

- Listener: tenant mới → có row `pro_trial_offers` (offered_at set), bất kể mode on/off lúc đăng ký.
- Eligibility: `show_popup` đúng theo tổ hợp offered/declined/eligible.
- Decline: gọi xong → `show_popup` false, gọi lại không lỗi (idempotent).
- Register qua popup: kích hoạt thành công → `show_popup` false (do `already_used`), email được queue.
- Register qua nút cũ ở Cài đặt > Gói: vẫn hoạt động y như trước, cũng nhận email (chung code path).
- Tenant tạo trước tính năng (không có row `pro_trial_offers`): `show_popup` luôn false dù các điều kiện khác đều đạt.
