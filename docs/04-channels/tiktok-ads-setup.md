# TikTok Marketing API (Ads) — cấu hình & vận hành

Hướng dẫn bật và vận hành tích hợp **quảng cáo TikTok** (read-only: kết nối + đồng bộ + báo cáo). Kiến trúc: provider thứ 2 của trục `Ads` (xem [ADR-0025](../01-architecture/adr/0025-ads-tiktok-integration.md)). Nguồn API: thư mục `tai_lieu_tiktok_ads/` (TikTok API for Business v1.3).

## 0. Tổng quan luồng

```
Seller bấm "Kết nối TikTok" (FE)
  → POST /api/v1/marketing/ads/connect-tiktok  → trả authorize_url
  → popup TikTok: advertiser duyệt quyền + xác minh email
  → redirect về https://app.cmbcore.com/oauth/tiktok_marketing/redirect?auth_code=...&state=...
  → GET /oauth/tiktok_marketing/redirect (TikTokAdsOAuthController@callback)
       · verify state (oauth_states, provider=tiktok_marketing)
       · POST /oauth2/access_token/ {app_id, secret, auth_code} → access_token (vô hạn) + advertiser_ids[]
       · với mỗi advertiser_id: /advertiser/info/ lấy name/currency/status → upsert ad_accounts (provider=tiktok)
       · dispatch SyncAdAccountEntities (đồng bộ campaign/adgroup/ad)
  → polling SyncAdInsights kéo báo cáo /report/integrated/get/
```

## 1. App TikTok (TikTok API for Business portal)

- App đã được duyệt các nhóm quyền: quản lý tài khoản quảng cáo, quản lý quảng cáo, báo cáo, đo lường, quản lý khách hàng tiềm năng, chẩn đoán ad, quản lý sự kiện CRM, danh mục thanh toán, quản lý chuyển đổi tùy chỉnh.
- **Advertiser redirect URL** (My Apps → App Detail → Basic Information): phải có chính xác
  `https://app.cmbcore.com/oauth/tiktok_marketing/redirect`
  (cấu hình tối đa 10 redirect URL, gồm cả localhost cho dev). Sau khi thêm, portal sinh lại "Advertiser authorization URL".
- Lấy **App ID** và **Secret** ở mục Basic Information.

## 2. Biến môi trường

```dotenv
# Bật provider ads (CSV). 'tiktok' để bật TikTok; thêm 'facebook' nếu cũng dùng FB Ads.
INTEGRATIONS_ADS=tiktok

TIKTOK_ADS_APP_ID=your_app_id
TIKTOK_ADS_APP_SECRET=your_app_secret
# Mặc định suy ra https://<APP_URL>/oauth/tiktok_marketing/redirect — chỉ set nếu khác.
TIKTOK_ADS_REDIRECT_URI=https://app.cmbcore.com/oauth/tiktok_marketing/redirect
# (tùy chọn) base URL, đổi khi test sandbox.
TIKTOK_ADS_BASE_URL=https://business-api.tiktok.com/open_api/v1.3
TIKTOK_ADS_AUTH_URL=https://business-api.tiktok.com/portal/auth
```

> Lưu ý: `TIKTOK_ADS_*` TÁCH BIỆT với `TIKTOK_APP_KEY/SECRET` (đó là TikTok Shop — kênh marketplace, khác hoàn toàn).

## 3. Plan-gate

Tính năng khóa sau feature key `marketing_tiktok` (mặc định: chỉ gói **Pro**). Khai ở 4 nơi đồng bộ:
`BillingPlanSeeder::featureKeys()`, FE `lib/billing.tsx`, `pages/PlansPage.tsx`, `admin/pages/tenants/AdminPlansPage.tsx`. Chạy `php artisan db:seed --class="CMBcoreSeller\\Modules\\Billing\\Database\\Seeders\\BillingPlanSeeder"` để cập nhật catalog.

## 4. Đồng bộ (jobs)

Tái dùng `SyncAdAccountEntities` (campaign→adgroup→ad) và `SyncAdInsights` (báo cáo). Cả hai `ShouldBeUnique` + idempotent. Lên lịch như Facebook trong scheduler (polling ~15'). TikTok token không hết hạn nên không cần job refresh token.

## 5. Endpoint TikTok dùng (v1.3, header `Access-Token`)

| Mục đích | Endpoint |
|---|---|
| Đổi auth_code → token | `POST /oauth2/access_token/` (JSON) |
| Danh sách advertiser | `GET /oauth2/advertiser/get/` (app_id, secret) |
| Chi tiết account | `GET /advertiser/info/` (advertiser_ids, fields) |
| Campaigns | `GET /campaign/get/` |
| Ad groups (→ level `adset`) | `GET /adgroup/get/` |
| Ads | `GET /ad/get/` |
| Báo cáo (insights) | `GET /report/integrated/get/` (report_type=BASIC) |

## 6. Test

`Http::fake` với response mẫu lấy từ `tai_lieu_tiktok_ads/`:
- `tests/Unit/Integrations/Ads/TikTokAdsConnectorTest.php` — exchange token, list advertisers, get campaign/adgroup/ad, report.
- `tests/Feature/Marketing/TikTokAdsOAuthTest.php` — callback `auth_code` → tạo `ad_accounts` + dispatch sync (`Queue::fake`).

Connector bật trong test bằng `config(['integrations.ads' => ['tiktok'], 'integrations.ads_tiktok' => [...]])` rồi register vào `AdsRegistry` (giống mẫu messaging FB trong test env).

## 7. Lộ trình (ngoài phạm vi hiện tại)

Tạo/sửa quảng cáo (write `AdsWriteConnector`), AI phân tích chiến dịch TikTok, pixel/CRM events/audience, BC management, GMV Max / Smart+.
