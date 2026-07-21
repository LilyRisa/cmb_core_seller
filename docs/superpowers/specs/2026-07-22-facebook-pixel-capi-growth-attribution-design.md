# Facebook Pixel + Conversions API cho trang public & Growth Attribution trong Admin

Ngày: 2026-07-22

## Bối cảnh & mục tiêu

Cần đo lường hiệu quả quảng cáo Facebook dẫn khách tới đăng ký CMBcoreSeller:

1. Nhúng Facebook Pixel vào mọi trang **chưa cần đăng nhập** (trang chủ, đăng nhập, đăng ký, bảng giá...).
2. Kết hợp Conversions API (CAPI) để báo cáo sự kiện đăng ký (`CompleteRegistration`) từ server, dedup với Pixel qua `event_id`.
3. Bắt `utm_source` (và các utm khác) lúc đăng ký, lưu lại theo tenant.
4. Admin xem được tenant nào đăng ký từ nguồn UTM nào, và có báo cáo tổng hợp theo nguồn → số đăng ký → số lên gói trả phí.

Pixel ID + CAPI access token được **super-admin cấu hình qua màn hình `/admin/settings`** (không dùng `.env`) — tái dùng cơ chế `system_setting()` (DB + cache `rememberForever`, đã dùng cho Mail/AI/Marketplace...).

## Xác minh kỹ thuật (tài liệu chính chủ Meta, truy cập bằng trình duyệt 2026-07-22)

Đã mở trực tiếp bằng Playwright (trang docs của Meta render bằng JS, không dùng fetch tĩnh):

- `developers.facebook.com/docs/marketing-api/conversions-api/using-the-api` — endpoint `POST https://graph.facebook.com/v25.0/{PIXEL_ID}/events?access_token={TOKEN}`, body `data: [{event_name, event_time (unix giây), event_id, event_source_url, action_source, user_data, custom_data}]`. `event_time` không được quá 7 ngày trước thời điểm gửi.
- `.../parameters/customer-information-parameters` — `em` (email) bắt buộc: trim + lowercase + SHA-256, kiểu `string or list<string>` (dùng dạng mảng 1 phần tử theo đúng ví dụ chính thức). `client_ip_address`, `client_user_agent`, `fbp`, `fbc` gửi **thô, không hash**.
- `.../deduplicate-pixel-and-server-events` — dedup theo cặp **(event_id, event_name)** phải khớp giữa Pixel (`fbq('track', name, data, {eventID})` — tham số thứ 4) và CAPI (`event_id` + `event_name`). Đây là phương án khuyến nghị (so với dedup theo `fbp`/`external_id`).
- `developers.facebook.com/docs/meta-pixel/reference` — `CompleteRegistration` là **standard event** đúng ngữ nghĩa: "When a registration form is completed. A person submits a completed subscription or signup form."

→ Thiết kế dưới đây khớp 100% với các quy tắc trên.

## 1. Cấu hình Pixel/CAPI trong Admin (DB + cache, không `.env`)

Thêm group mới `growth` vào `SystemSettingsCatalog::all()`:

| Key | Type | Secret | Ghi chú |
|---|---|---|---|
| `growth.facebook.enabled` | bool | no | Bật/tắt nhúng Pixel + gửi CAPI |
| `growth.facebook.pixel_id` | string | no | Pixel ID |
| `growth.facebook.capi_access_token` | string | **yes** | Access token CAPI (mã hoá bằng `Crypt` khi lưu, đúng cơ chế `is_secret` sẵn có) |
| `growth.facebook.test_event_code` | string | no | Điền tạm khi soi ở tab "Test Events" Meta Events Manager, xoá khi chạy thật |

`env` field của catalog entry vẫn cần khai (ví dụ `FACEBOOK_PIXEL_ID`) để tương thích cấu trúc catalog + nút "sync from env" — nhưng luồng vận hành chính là nhập tay qua UI admin, không cần set `.env` trong prod.

FE: thêm `{ value: 'growth', label: 'Tăng trưởng' }` vào mảng `GROUPS` trong `SystemSettingsPage.tsx` — dùng lại UI generic sẵn có, không cần màn hình riêng.

Đọc giá trị qua `system_setting('growth.facebook.pixel_id')` — dùng được cả ở Blade (SSR) và trong job gửi CAPI.

## 2. Nhúng Pixel vào mọi trang chưa đăng nhập

Toàn bộ route (`/`, `/pricing`, `/tools`, `/api-docs`, `/download`, `/login`, `/register`, `/forgot-password`, `/password-reset`, `/email-verified`, kể cả các trang trong app đã đăng nhập) đều render qua **một Blade shell duy nhất** `resources/views/app.blade.php` (`SpaController`). Nhúng base code Pixel ở đây, bọc điều kiện:

```blade
@if(system_setting('growth.facebook.enabled', false) && system_setting('growth.facebook.pixel_id'))
  {{-- fbevents.js base code, fbq('init', PIXEL_ID), fbq('track', 'PageView') --}}
@endif
```

Vì SPA dùng React Router (client-side, không reload), base pixel chỉ bắn 1 `PageView` lúc load cứng đầu. Thêm hook `usePixelPageview()` gắn trong `Root()` (`app.tsx`), lắng nghe đổi `location.pathname` và tự bắn thêm `fbq('track', 'PageView')` — **chỉ khi path thuộc nhóm public/pre-auth** (`/`, `/pricing`, `/tools`, `/api-docs`, `/download`, `/login`, `/register`). Không bắn cho các route trong `AppLayout`/`DesktopShell` đã đăng nhập — tránh lẫn hành vi nội bộ khách hàng vào tài khoản quảng cáo Meta.

## 3. Bắt UTM/fbclid — first-touch, gắn vào lúc đăng ký

Thêm `resources/js/lib/acquisition.ts` (cùng pattern với `lib/extRedirect.ts` đã có):

- Lúc `Root()` mount, đọc `location.search` hiện tại cho `utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term`, `fbclid`. **Chỉ ghi vào `localStorage` nếu key chưa tồn tại** (first-touch — giữ nguồn quảng cáo đầu tiên, các lượt ghé thăm sau không ghi đè).
- Cookie `_fbp`/`_fbc` do chính script Pixel tự set — đọc thẳng 2 cookie này lúc submit đăng ký, không tự dựng giá trị.

`RegisterPage.tsx` (`onFinish`): sinh `event_id` bằng `crypto.randomUUID()`, gắn `acquisition: {...đọc từ localStorage, fbp, fbc}` + `event_id` vào payload gửi `useRegister().mutate()`. `onSuccess`: gọi `fbq('track', 'CompleteRegistration', {}, {eventID: event_id})` nếu `window.fbq` tồn tại (Pixel có thể chưa load nếu admin tắt tính năng). Xoá key acquisition khỏi `localStorage` sau khi đăng ký thành công.

## 4. Lưu & báo cáo phía server

- Migration: thêm cột `tenants.acquisition` (`json`, nullable, sau `settings`) — tách khỏi `settings` (settings = hành vi tenant tự chỉnh; acquisition = dữ liệu tăng trưởng ghi một lần lúc tạo, bất biến). Cast `'acquisition' => 'array'` trong `Tenant` model.
- `AuthController::register`: validate thêm (tất cả `nullable|string|max:255`) `acquisition.utm_source`, `.utm_medium`, `.utm_campaign`, `.utm_content`, `.utm_term`, `.fbclid`, `.fbp`, `.fbc`, `.landing_page`, `.referrer`, và `event_id` (`nullable|string|max:64`). Lúc tạo tenant, ghi vào `tenant->acquisition`:
  - toàn bộ field FE gửi,
  - `ip` = `$request->ip()`, `user_agent` = `$request->userAgent()` — **quan sát phía server**, không tin giá trị client gửi lên cho 2 field này,
  - `event_id`, `captured_at` (`now()->toIso8601String()`).
- Listener mới `ReportSignupToMetaCapi` (`app/app/Modules/Tenancy/Listeners/`), `implements ShouldQueue`, đăng ký trong `TenancyServiceProvider`: `Event::listen(TenantCreated::class, ReportSignupToMetaCapi::class)` — cùng vị trí kiến trúc với `Billing\Listeners\StartTrialSubscription` đang nghe event này (Tenancy là module nền, tự xử lý báo cáo tăng trưởng của chính mình, không phạm quy tắc phụ thuộc module).
  - No-op nếu `growth.facebook.enabled` tắt hoặc thiếu `pixel_id`/`capi_access_token`.
  - POST `https://graph.facebook.com/v25.0/{pixel_id}/events?access_token={token}` với:
    ```json
    {
      "data": [{
        "event_name": "CompleteRegistration",
        "event_time": <unix giây tenant.created_at>,
        "event_id": "<event_id đã lưu>",
        "event_source_url": "<landing_page đầy đủ>",
        "action_source": "website",
        "user_data": {
          "em": ["<sha256(lowercase(trim(owner_email)))>"],
          "client_ip_address": "<acquisition.ip>",
          "client_user_agent": "<acquisition.user_agent>",
          "fbp": "<acquisition.fbp>",
          "fbc": "<acquisition.fbc>"
        }
      }],
      "test_event_code": "<growth.facebook.test_event_code nếu có>"
    }
    ```
  - Idempotent qua cờ `tenant->acquisition['capi_reported_at']` (set sau khi gửi thành công) — tránh gửi trùng nếu event dispatch lại.
  - Best-effort: lỗi HTTP/log warning, KHÔNG làm hỏng luồng đăng ký (cùng triết lý với `ReportOrderConversionToMeta` đã có trong Messaging).

## 5. Admin biết tenant đăng ký từ UTM nào + lên gói

- `AdminTenantController::index`/`summary()`: thêm `acquisition` (utm_source/campaign/medium) vào response mỗi tenant; thêm query filter `utm_source` (exact hoặc `like`) trên danh sách.
- Trang chi tiết tenant admin: thêm khối "Nguồn đăng ký" — hiển thị đủ `utm_source/medium/campaign/content/term/fbclid/landing_page/referrer` + trạng thái đã báo CAPI (`capi_reported_at`) để debug khi cần.
- **Trang mới** `AdminGrowthPage.tsx` tại `/admin/growth`:
  - Backend: `AdminGrowthController` + `AdminGrowthService` (tách khỏi `AdminTenantService` — đã 500+ dòng, giữ single-responsibility).
  - Filter: khoảng ngày đăng ký (`from`/`to`), group-by (`utm_source` mặc định | `utm_campaign` | `utm_medium`).
  - Mỗi dòng: nguồn (rỗng → hiển thị "Không xác định"), số đăng ký, số đã lên gói trả phí (có subscription `status=active` với `billing_cycle` khác `trial`, HOẶC ≥1 invoice `status=paid`), tỉ lệ chuyển đổi (%), tổng doanh thu VND (`SUM(invoices.total WHERE status=paid)` của các tenant thuộc nhóm).
  - Click 1 dòng → điều hướng sang `AdminTenantsPage` đã lọc sẵn `utm_source` tương ứng (tái dùng filter mục trên).

## Phạm vi không làm

- Không track `PageView` cho route trong app đã đăng nhập (dashboard, đơn hàng...) — tránh lẫn hành vi nội bộ khách hàng vào tài khoản quảng cáo Meta, không đúng mục tiêu "đo lường đăng ký".
- Không thêm Google Ads/gclid CAPI — ngoài phạm vi yêu cầu (chỉ Facebook Pixel/CAPI). Cột `utm_source` vẫn generic nên vẫn lọc/báo cáo được cho nguồn quảng cáo khác nếu sau này cần.
- Không gửi `ph`/`fn`/`ln`/`external_id` trong `user_data` CAPI — chỉ có email lúc đăng ký, các field khác không bắt buộc theo tài liệu (chỉ `em`/`client_ip_address`/`client_user_agent` trở lên là hợp lệ để gửi).

## File / thay đổi dự kiến

**Backend**
- `app/app/Modules/Settings/Support/SystemSettingsCatalog.php` — thêm 4 key group `growth`.
- `app/app/Modules/Tenancy/Database/Migrations/xxxx_add_acquisition_to_tenants.php` — cột `acquisition` json nullable.
- `app/app/Modules/Tenancy/Models/Tenant.php` — cast `acquisition`.
- `app/app/Modules/Tenancy/Http/Controllers/AuthController.php` — validate + lưu `acquisition` lúc `register()`.
- `app/app/Modules/Tenancy/Listeners/ReportSignupToMetaCapi.php` — mới.
- `app/app/Modules/Tenancy/TenancyServiceProvider.php` — đăng ký listener.
- `app/app/Modules/Admin/Http/Controllers/AdminTenantController.php` — thêm `acquisition` vào `summary()`, filter `utm_source`.
- `app/app/Modules/Admin/Http/Controllers/AdminGrowthController.php`, `app/app/Modules/Admin/Services/AdminGrowthService.php` — mới.
- `app/app/Modules/Admin/Http/routes.php` — route `GET /admin/growth/attribution`.
- `app/resources/views/app.blade.php` — nhúng base Pixel code có điều kiện.

**Frontend**
- `app/resources/js/lib/acquisition.ts` — mới (capture + read first-touch UTM/fbp/fbc).
- `app/resources/js/lib/pixel.ts` — mới (helper `fbq` type-safe, `usePixelPageview()`).
- `app/resources/js/app.tsx` — gắn `usePixelPageview()` trong `Root()`.
- `app/resources/js/pages/RegisterPage.tsx` — gắn `acquisition` + `event_id` vào payload, bắn `CompleteRegistration` `onSuccess`.
- `app/resources/js/admin/pages/settings/SystemSettingsPage.tsx`, `admin/lib/systemSettings.tsx` — thêm tab `growth`.
- `app/resources/js/admin/pages/tenants/AdminTenantsPage.tsx` — cột "Nguồn" + filter.
- `app/resources/js/admin/pages/growth/AdminGrowthPage.tsx` — mới.
- `app/resources/js/admin/lib/admin.tsx` (hoặc file lib tương ứng) — hook `useAdminGrowthAttribution()`.
- Đăng ký route `/admin/growth` trong admin router + menu.

**Migration cần chạy ở prod:** có (`tenants.acquisition`).
