# Cấu hình Facebook Ads (Marketing API) — SPEC 2026-06-04 / ADR-0024

Bật lấy dữ liệu quảng cáo Facebook **near-real-time** + dashboard trong CMBcoreSeller.
Phase 1 = đọc (insights + dashboard). Phase 2 = AI đánh giá. Phase 3 = 1-click apply.

> **Thực tế API (quan trọng):** Facebook Ads **không có streaming/webhook hiệu suất**.
> Insights refresh **~15 phút**; re-attribution tới **28 ngày** (số gần đây dao động —
> dashboard gắn nhãn "đang hoàn tất"). "Real-time" ở đây = **poll ~15'** + nút Làm mới.
>
> Nguồn: [Insights API](https://developers.facebook.com/docs/marketing-api/insights/) ·
> [Limits/best-practices](https://developers.facebook.com/docs/marketing-api/insights/best-practices/) ·
> [Rate limiting](https://developers.facebook.com/docs/marketing-api/overview/rate-limiting/) ·
> [Campaign structure](https://developers.facebook.com/docs/marketing-api/campaign-structure/).

## 0. Kiến trúc (đã code — chỉ cấu hình + verify)

| Thành phần | Vị trí | Vai trò |
|---|---|---|
| `FacebookAdsConnector` | `app/Integrations/Ads/Facebook/` | OAuth `ads_read`, list ad accounts/entities, `/insights` + parse throttle header |
| `AdsRegistry` | `app/Integrations/Ads/` | bật theo `INTEGRATIONS_ADS` (CSV) |
| Module `Marketing` | `app/Modules/Marketing/` | `ad_accounts`/`ad_entities`/`ad_insight_snapshots`, jobs sync, HTTP, dashboard |
| OAuth | `/oauth/facebook_ads/callback` — token `ads_read` RIÊNG (khác page token Messenger) |

`ad_accounts.external_account_id` = `act_<id>`; insights lưu snapshot theo (entity, window).

## 1. Tạo / cấu hình Meta App

Tái dùng **Meta app hiện có** (cùng app với Messenger — Meta cho 1 app nhiều product/scope):
1. developers.facebook.com → app của bạn → **Add product → Marketing API**.
2. **Facebook Login → Settings → Valid OAuth Redirect URIs**: thêm `https://<APP_DOMAIN>/oauth/facebook_ads/callback`.
3. Scopes: `ads_read`, `business_management` (Phase 1). Phase 3 thêm `ads_management`.

> Dùng app riêng cho ads (tuỳ chọn): set `FACEBOOK_ADS_APP_ID`/`FACEBOOK_ADS_APP_SECRET`.

## 2. Biến môi trường

```dotenv
INTEGRATIONS_ADS=facebook                # bật connector (rỗng = tắt, zero tác động)
# Mặc định dùng MESSAGING_FACEBOOK_APP_ID/SECRET; chỉ set khi dùng app ads riêng:
# FACEBOOK_ADS_APP_ID=
# FACEBOOK_ADS_APP_SECRET=
# FACEBOOK_ADS_REDIRECT_URI=             # mặc định <APP_URL>/oauth/facebook_ads/callback
# FACEBOOK_ADS_SCOPES=ads_read,business_management
```

## 3. Kết nối & vận hành

1. App → **Quảng cáo** (menu Báo cáo & Kế toán) → **Kết nối Facebook Ads** → đăng nhập → chọn ad account.
2. Sau khi connect: job `SyncAdAccountEntities` kéo cây campaign/adset/ad; scheduler `ads-insights-poll`
   (mỗi 15') chạy `SyncAdInsights` cho account `active`. Nút **Làm mới** kéo ngay.
3. Quyền: `marketing.view` (xem), `marketing.connect` (kết nối/ngắt) — Owner/Admin có sẵn (wildcard).

## 4. ⚠️ Checklist production (đa tenant)

Dev/Standard Access của Marketing API rất giới hạn. Để chạy thật với ad account của seller khác:
- **App Review → Advanced Access** cho `ads_read` (+ `ads_management` ở Phase 3).
- **Standard Marketing API Access Tier** (xin nâng từ Development tier).
- **Business Verification** cho Meta Business.

## 5. Rate limit & độ tươi

- Connector đọc header `x-fb-ads-insights-throttle` (`app_id_util_pct`/`acc_id_util_pct`/`tier`);
  khi util cao, `SyncAdInsights` đặt cờ `meta.insights_throttled` + `release` để giãn nhịp (BUC).
- Snapshot trong 28 ngày có `is_finalizing=true` → dashboard hiển thị "đang hoàn tất".

## 6. Kiểm thử

Connector + jobs + OAuth + API đều shape-tested (`Http::fake`): `tests/{Unit,Feature}/{Ads,Marketing}`.
Live cần Meta app thật + App Review (mục 4). Mở rộng provider ads khác = 1 connector + 1 dòng register (ADR-0024).

## 7. Lộ trình

- **Phase 2 (AI advisory):** `AdsEvaluationService` gom metric → `Ai::analyze` → `AdRecommendation` (đề xuất, chưa ghi).
- **Phase 3 (1-click apply):** `ads_management` + `AdsActionService` (budget/pause/bid) có guardrail + `AdActionLog`.
  Spec: `docs/superpowers/specs/2026-06-04-facebook-ads-realtime-ai-design.md`.
