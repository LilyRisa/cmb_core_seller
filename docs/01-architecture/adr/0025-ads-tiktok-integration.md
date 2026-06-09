# ADR-0025: TikTok Marketing API (Ads) — provider thứ 2 của trục `Ads`

- **Trạng thái:** Accepted
- **Ngày:** 2026-06-09
- **Liên quan:** ADR-0017 (connector/registry), ADR-0024 (trục `Ads`), SPEC 2026-06-09 (TikTok Marketing read), tài liệu nguồn: `tai_lieu_tiktok_ads/` (TikTok API for Business v1.3)

## Bối cảnh

Trục `Ads` (ADR-0024) đã có Facebook. Cần thêm **TikTok Marketing API** để seller quản lý quảng cáo TikTok (đọc ad account → campaign → ad group → ad + báo cáo). App TikTok đã được duyệt các quyền: quản lý tài khoản quảng cáo, quản lý quảng cáo, báo cáo, đo lường, quản lý khách hàng tiềm năng, chẩn đoán ad, quản lý sự kiện CRM, danh mục thanh toán, quản lý chuyển đổi tùy chỉnh. Redirect ủy quyền đã cấu hình trên TikTok portal: `https://app.cmbcore.com/oauth/tiktok_marketing/redirect`.

Câu hỏi: thêm như provider mới của trục `Ads` (tái dùng hạ tầng) hay viết module riêng?

## Quyết định

Thêm **TikTok như provider thứ 2 của trục `Ads`** — `app/app/Integrations/Ads/TikTok/TikTokAdsConnector.php` implements `AdsConnector`. Tái dùng nguyên trạng module `Marketing` (Models/Jobs/Services/Report/Reconciliation) vì toàn bộ đã resolve connector động qua `AdsRegistry::for($account->provider)`.

Phạm vi giai đoạn này: **read-only** (kết nối + đồng bộ entity + báo cáo + đối soát). Các thao tác ghi (tạo/sửa campaign/adgroup/ad, pixel, audience, targeting…) ném `UnsupportedOperation` (capability = false), để Phase sau.

Khác biệt TikTok so với Facebook (đối chiếu tài liệu đã tải):

- **Token dài hạn KHÔNG hết hạn.** Đổi `auth_code` (redirect trả về tham số `auth_code`, không phải `code`; hiệu lực 1 giờ, dùng 1 lần) lấy token qua `POST /open_api/v1.3/oauth2/access_token/` (JSON `{app_id, secret, auth_code}`) → `access_token` + `advertiser_ids[]` + `scope[]`. ⇒ `ad_accounts.token_expires_at = null`, không cần refresh.
- **Authorization URL** = "Advertiser authorization URL" cấu hình trên portal; dựng `https://business-api.tiktok.com/portal/auth?app_id&state&redirect_uri`. `state` mang token chống CSRF (bảng `oauth_states`, provider `tiktok_marketing`).
- **Gọi API** bằng header `Access-Token`; base `https://business-api.tiktok.com/open_api/v1.3/`.
- **Danh sách account:** `/oauth2/advertiser/get/` (app_id+secret) → `advertiser_id`+`advertiser_name`; chi tiết (currency, status, timezone, owner_bc_id) qua `/advertiser/info/`.
- **Entity:** `/campaign/get/`, `/adgroup/get/`, `/ad/get/` (advertiser_id + `filtering` JSON + page/page_size, lặp theo `page_info`). **"Ad group" của TikTok map vào level chuẩn `adset`** để tái dùng schema/Jobs/FE; nhãn hiển thị tiếng Việt vẫn là "Nhóm quảng cáo".
- **Insights/báo cáo:** `/report/integrated/get/` (`report_type=BASIC`, `data_level=AUCTION_CAMPAIGN/AUCTION_ADGROUP/AUCTION_AD/AUCTION_ADVERTISER`, dimensions=[<level>_id], metrics, start_date/end_date). Tiền theo currency account, **trả về không số thập phân** (VND khớp với schema integer).
- **Đồng bộ = polling** (báo cáo không có webhook realtime) — tái dùng `SyncAdAccountEntities` + `SyncAdInsights` (idempotent, `ShouldBeUnique`). TikTok không có header throttle kiểu FB ⇒ `AdInsightThrottleDTO` trả mặc định (không "hot").

Plan-gate: feature key riêng **`marketing_tiktok`** (tách quyền FB/TikTok theo gói). Route đọc dùng chung đổi gate sang any-of `marketing_facebook|marketing_tiktok` (tương thích ngược FB); route connect mỗi provider gate bằng key của mình.

## Phương án đã cân nhắc

- **A. Provider mới của trục `Ads` (chọn).** Đúng extensibility-rules; tái dùng toàn bộ Marketing module; thêm = 1 connector + 1 OAuth controller + cấu hình. Không sửa code FB đang chạy.
- B. Tổng quát hóa `AdsOAuthController` thành provider-parametric. ✗ Phải refactor controller FB đang chạy → rủi ro, đụng luồng live.
- C. Module TikTok riêng. ✗ Lặp Models/Jobs/Services, vi phạm DRY/ADR-0024.

## Hệ quả

- **Tích cực:** Marketing module phục vụ đa provider không đổi core; test bằng `Http::fake`; bật theo `INTEGRATIONS_ADS` (off mặc định). Token TikTok không hết hạn ⇒ ít lỗi vận hành hơn FB.
- **Đánh đổi / việc theo sau:**
  - FE báo cáo dùng chung có thao tác inline (sửa tên/ngân sách, tạm dừng) là **write** — với account TikTok (read-only) phải **ẩn/disable** theo `provider` để tránh gọi API không hỗ trợ.
  - `AdAccountController@refreshAccounts` (rediscover) hiện theo luồng FB; với TikTok việc khám phá account lại nằm ở OAuth callback (đổi `auth_code`).
  - Phase sau: thêm `AdsWriteConnector` cho TikTok (tạo/sửa entity) + AI phân tích.
  - Các trường FB-specific trên `ad_accounts` (`fb_account_status`, `disable_reason`, `business_*`) để `null` cho TikTok; dữ liệu riêng TikTok (timezone, owner_bc_id, secondary_status, objective_type, budget_mode) lưu vào `meta`.
