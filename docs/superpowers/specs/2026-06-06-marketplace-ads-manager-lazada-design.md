# Marketplace Ads Manager — v1 (Lazada, read-only + AI)

- **Trạng thái:** Draft
- **Tác giả / Ngày:** lilyrisa · 2026-06-06
- **Module backend liên quan:** Marketing (chủ sở hữu), Billing (gating), Channels (token provider — read-only contract)
- **Trục tích hợp:** `Integrations/Ads` (trục thứ 6 — đã có, ADR-0024)
- **Liên quan:** ADR-0024 (Ads Connector+Registry), spec `2026-06-04-facebook-ads-realtime-ai-design.md`, `2026-06-04-marketing-ads-manager-report-design.md`, `2026-06-05-per-campaign-ai-insight-design.md`; doc `01-architecture/extensibility-rules.md`, `05-api/conventions.md`

---

## 1. Vấn đề & mục tiêu

Người bán đa sàn đang chạy quảng cáo trên các sàn (Lazada Sponsored Discovery, sắp tới TikTok GMV Max…) nhưng phải vào từng seller center riêng để xem hiệu quả. Đã có "Trình quản lý quảng cáo Facebook" (module Marketing) cho phép đồng bộ insight + AI phân tích/khuyến nghị. Mục tiêu v1: **mở rộng trình này sang quảng cáo sàn, bắt đầu với Lazada**, theo hướng **chỉ đọc (read-only) + AI phân tích tối ưu** — chưa tạo/sửa campaign từ app.

Đây không phải module mới: tái dùng trục `Integrations/Ads` + module `Marketing` đã có, "cắm" thêm 1 connector theo luật vàng (1 connector + 1 dòng đăng ký + 1 block config), không sửa core.

### Vì sao Lazada trước (và vì sao không TikTok/Shopee trong v1)

Đối chiếu tài liệu chính thức + kho `tailieuapi_itiktok_shopee_lazada/`:

| Sàn | Quảng cáo trả phí qua API đang dùng | Tài liệu trong repo | Quyết định v1 |
|---|---|---|---|
| **Lazada** | ✅ Sponsored Solutions (`/sponsor/solutions/*`), **cùng OAuth Open Platform** với channel | Đủ (28 endpoint, có đủ read) | **Làm v1** |
| **Shopee** | ⚠️ Có (`/api/v2/ads/*`) nhưng **cần Shopee whitelist `partner_id`**, VN chưa chắc bật | Có guide | Sau (gate sẵn) |
| **TikTok Shop** | ❌ **Không** qua Partner API. Ads (GMV Max) ở **TikTok Marketing API riêng** (`business-api.tiktok.com`) — app/OAuth/`advertiser_id` riêng | **Thiếu** (Partner API chỉ có Affiliate + Promotion + Compass analytics) | Spec riêng giai đoạn sau |

## 2. Trong / ngoài phạm vi

- **Trong (v1):**
  - Connector Lazada read-only cho Sponsored Discovery: liệt kê campaign/adgroup/keyword, đồng bộ insight, đọc theo cây.
  - Tài khoản quảng cáo Lazada **suy ra từ kết nối Channels Lazada sẵn có** (không OAuth lại).
  - Dashboard Marketing đa nền tảng (Facebook | Lazada): cây report + cột metric + AI insight per-campaign.
  - Plan-gating: feature key mới `marketing_marketplace_ads`.
  - Đồng bộ qua job idempotent + nút làm mới.
- **Ngoài (làm sau / spec khác):**
  - **Ghi** (tạo/sửa campaign, chỉnh bid/ngân sách, bật/tắt), auto-monitor, wizard tạo quảng cáo cho Lazada.
  - TikTok GMV Max (Marketing API) và Shopee Ads.
  - Quản lý keyword/bid, ví/nạp tiền Lazada (`wallet/*`).
  - Các biz code Lazada khác ngoài Sponsored Discovery (Sponsored Search/Affiliate/Brand/Display).

## 3. Câu chuyện người dùng / luồng chính

1. Người bán (gói **Pro**) đã kết nối gian hàng Lazada ở **Kênh bán** (module Channels) → có `ChannelAccount` Lazada active.
2. Vào **Marketing → chọn nền tảng "Lazada"**. Hệ thống hiển thị (các) gian hàng Lazada có thể bật quảng cáo (suy ra từ Channels). Bấm **"Kết nối Quảng cáo"** → tạo `AdAccount` (provider=`lazada`) trỏ tới channel account, **không OAuth lại**.
3. Hệ thống chạy job đồng bộ: lấy token từ Channels → kéo cây campaign → adgroup → keyword + insight theo cửa sổ ngày → lưu snapshot.
4. Người bán xem cây report (Chi tiêu, Hiển thị, Click, CTR, CPC, ROAS, Đơn, Doanh thu) drill-down campaign→adgroup→keyword; chọn 1 campaign → bấm **"Phân tích AI"** → nhận điểm 0–100 + đánh giá + khuyến nghị tối ưu (tăng/giảm ngân sách, từ khóa kém/tốt) dưới dạng **tư vấn** (read-only, không tự áp dụng).
5. Nếu gói chưa đủ (Trial/Starter) → API trả 402 `PLAN_FEATURE_LOCKED` → UI hiện CTA nâng cấp. Nếu app Lazada chưa được cấp quyền `sponsor` → hiện trạng thái "Chưa được Lazada cấp quyền Quảng cáo" thay vì lỗi.

## 4. Hành vi & quy tắc nghiệp vụ

- **Luật mở rộng (PR-blocking):** core/module không biết tên sàn. Mọi khác biệt Lazada nằm trong `LazadaAdsConnector`; method không hỗ trợ → `throw UnsupportedOperation`. UI/sync hỏi `capabilities()`/`supports()` trước khi gọi.
- **Capability map Lazada (v1):**
  - `insights.read=true`, `entities.list=true`, `ai.analyze=true`
  - `creatives.read=false` (Lazada Sponsored Discovery không có creative text như FB; thay bằng keyword/product)
  - `ads.create=false`, `actions.budget=false`, `actions.status=false`, `insights.async=false`, `preview.generate=false`, `targeting.search=false`
- **Auth/token:** Lazada chỉ 1 OAuth Open Platform/seller; token channel **chính là** token gọi `sponsor`. ⇒ `AdAccount` Lazada **không lưu token riêng**; lấy on-demand qua contract Channels (single source of truth = `ChannelAccount`). Refresh token do Channels lo (đã có `TokenRefresher`).
- **Idempotency:** `AdInsightSnapshot` upsert theo natural key (provider, external_id, level, window, date_start, date_stop) — chạy lại job không nhân đôi. `AdEntity` upsert theo (ad_account_id, level, external_id).
- **Tác động:** read-only, **không** đụng tồn kho/đơn/tài chính. Doanh thu quảng cáo (`storeRevenue`) chỉ để hiển thị/AI/đối soát tham khảo.
- **Phân quyền:** route gate `auth:sanctum` + `verified` + `tenant` + `plan.feature:marketing_marketplace_ads`. (Quyền chi tiết theo role kế thừa pattern Marketing hiện có.)
- **Đa-tenant chia sẻ tài khoản:** giữ pattern `AdAccount::isAutomationOwner()` đã có (dù v1 read-only, vẫn để sẵn cho giai đoạn ghi). Hai tenant cùng kết nối 1 shop Lazada vẫn đọc độc lập.

## 5. Dữ liệu

Tái dùng bảng đã có của Marketing, **không cần bảng mới**:
- `ad_accounts`: thêm dữ liệu provider=`lazada`; các cột FB-only (`business_id`, `fb_account_status`, `disable_reason`…) để null; `meta['channel_account_id']` trỏ về `ChannelAccount`; `access_token`/`refresh_token` null (token lấy qua Channels).
- `ad_entities`: level `campaign|adset(=adgroup)|ad(=keyword/product)`.
- `ad_insight_snapshots`: lưu metric Lazada (map sang cột spend/impressions/clicks/ctr/cpc/purchase_roas + raw cho revenue/orders).
- `campaign_ai_insights`: cache kết quả AI (đã có).

- **Migration:** không bảng mới. Nếu cần, thêm cột nullable/`meta` đã đủ (ưu tiên không migration). Mọi cột FB-specific giữ nullable — kiểm tra `ad_accounts` đã nullable cho các cột này (bổ sung migration reversible nếu cột nào đang NOT NULL).
- **Domain event:** tái dùng/không thêm mới ở v1 (đồng bộ chủ động qua job + on-demand).

## 6. API & UI

### Backend — DTO & connector
- `LazadaAdsConnector implements AdsConnector` (`app/app/Integrations/Ads/Lazada/`):
  - `buildAuthorizationUrl`/`exchangeCodeForToken`/`listAdAccounts`: `throw UnsupportedOperation` — Lazada không có "ad account" tách biệt và **không OAuth ads riêng**. Việc cấp `AdAccount` đi qua endpoint connect (truyền `channel_account_id`), không qua OAuth flow của trục Ads.
  - `listEntities(level)`: campaign→`searchCampaignList`; adset→`searchAdgroupList`; ad→`listKeywordByAdgroup`.
  - `fetchInsights(level)`: account→`getReportOverview`; campaign→`getDiscoveryReportCampaign`; adset→`getDiscoveryReportAdgroup`; ad→`getDiscoveryReportKeyword`. `bizCode=sponsoredSearch`. `AdInsightThrottleDTO` để null.
  - `fetchAdCreatives`: `throw UnsupportedOperation`.
- **Map metric** (`AdInsightDTO`): `spend→spend` (VND int, làm tròn từ chuỗi thập phân), `impressions/clicks/ctr/cpc` thẳng, `storeRoi→purchaseRoas`, `storeOrders→purchases/results`, `storeRevenue/productRevenue/units→raw`.
- **Đăng ký:** `IntegrationsServiceProvider::$adsConnectors['lazada'] = LazadaAdsConnector::class`; thêm `'lazada'` vào `INTEGRATIONS_ADS` (CSV) + block `ads_lazada` trong `config/integrations.php` (base url theo region, bizCode mặc định, cờ bật).
- **Contract Channels (mới, read-only):** `Channels/Contracts/MarketplaceTokenProvider` → `accessTokenFor(string $provider, string $externalShopId): string` (tự refresh nếu hết hạn). Marketing phụ thuộc Contract này — **không** `use` Services của Channels (tránh vi phạm luật module). Lazada token cũng dùng cho việc resolve `external_account_id ↔ channel_account_id`.

### Backend — endpoints (nhất quán `05-api/conventions.md`, cập nhật `05-api/endpoints.md`)
Group mới trong `Modules/Marketing/Http/routes.php`, prefix `api/v1/marketing`, middleware `…,plan.feature:marketing_marketplace_ads`:
- `GET  /marketing/marketplace/ad-accounts?provider=lazada` — liệt kê (gồm gian hàng Lazada suy ra từ Channels + đã kết nối).
- `POST /marketing/marketplace/ad-accounts/connect` — body `{provider:'lazada', channel_account_id}` → tạo `AdAccount`.
- `POST /marketing/marketplace/ad-accounts/{id}/refresh` — kích hoạt đồng bộ.
- `GET  /marketing/ad-accounts/{id}/report` & `/insights` — tái dùng controller hiện có, làm **provider-aware**.
- `POST /marketing/ad-accounts/{id}/campaigns/{campaignId}/ai-insight` (+ GET show/history) — tái dùng.
- Lỗi đặc thù: 402 `PLAN_FEATURE_LOCKED`; khi thiếu quyền `sponsor` → trả trạng thái rõ (vd `meta.ads_permission='missing'`) thay vì 500.

### Frontend (nhất quán `06-frontend/overview.md`)
- `MarketingDashboardPage`: thêm **bộ chọn nền tảng** (Segmented/Radio, không Select — theo memory) Facebook | Lazada.
- Tab Lazada: danh sách ad account, cây report campaign→adgroup→keyword, cột VN (Chi tiêu, Hiển thị, Click, CTR, CPC, ROAS, Đơn, Doanh thu), drawer AI per-campaign.
- **Read-only:** ẩn wizard/monitor/live-edit cho Lazada — điều khiển bằng capability map (component dumb, logic ở hook). Icon dùng `@ant-design/icons`, không emoji.
- Không route-guard FE; dựa 402 để hiện CTA nâng cấp (giống FB).

### Job (cập nhật `07-infra/queues-and-scheduler.md`)
- Tái dùng/khái quát `SyncAdAccountEntities` + `SyncAdInsights` thành provider-generic: resolve token qua Contract Channels → `listEntities` 3 mức → upsert cây → `fetchInsights` từng mức theo cửa sổ ngày → upsert snapshot. Lịch poll ngày (cấu hình); on-demand qua nút refresh. Lazada không có throttle header.

## 7. Edge case & lỗi

- **App chưa được duyệt category `sponsor`:** call `sponsor/*` lỗi quyền → connector bắt và surface `ads_permission=missing`; UI hiện "Chưa được Lazada cấp quyền Quảng cáo Lazada". Gate thêm bằng `INTEGRATIONS_ADS` (không có `lazada` ⇒ ẩn hẳn).
- **Token hết hạn / channel bị revoke:** Contract Channels refresh; nếu fail → `AdAccount` không sync được, hiện trạng thái cần kết nối lại Kênh bán (không tự xử ở Marketing).
- **Rate limit Lazada (LazOP QPS):** backoff trong `LazadaClient` (đã có); job retry.
- **Dữ liệu realtime hôm nay:** Lazada trả `campaignName=null` khi gồm hôm nay/hôm qua ⇒ lấy tên từ `searchCampaignList`/`getCampaign` và cache (không phụ thuộc report cho tên). Dùng `useRtTable=true` khi cần số realtime.
- **Tiền tệ:** giả định VND nguyên; chuỗi metric có thể là `'-'` (không data) ⇒ map về 0/null.
- **Đa-tenant cùng shop:** đọc độc lập; ownership giữ pattern sẵn cho giai đoạn ghi.

## 8. Plan-gating (chi tiết — xác minh đã khớp Facebook)

Thêm feature key **`marketing_marketplace_ads`** đồng bộ ở 4 nơi:
1. `BillingPlanSeeder::$allOff` (thêm `'marketing_marketplace_ads' => false`). Vì `$pro = array_map(fn()=>true, $allOff)` ⇒ **tự bật ở Pro** (và business), **tự tắt ở trial/starter** — **khớp y hệt `marketing_facebook`**, không cần sửa từng gói.
2. FE `lib/billing.tsx` `PlanFeatures` (thêm field).
3. Admin `AdminPlansPage.tsx` `KNOWN_FEATURES`.
4. `PlansPage.tsx` `FEATURE_ROWS` — nhãn "Quảng cáo sàn (Lazada…)".
- Sửa mô tả gói Pro (BillingPlanSeeder dòng ~81) "quảng cáo Facebook" → "quảng cáo Facebook + sàn".
- Migration `upsert_spec_0032_plans` chạy seeder `updateOrCreate` khi deploy ⇒ cập nhật `features` JSON cho gói hiện có (idempotent).
- Route group Lazada ads gate `plan.feature:marketing_marketplace_ads` (tách `marketing_facebook` để giá/gói linh hoạt).

## 9. Tài liệu cần cập nhật (đổi docs trước khi code)

- ADR-0024: ghi chú provider read-only + auth qua Channels (không OAuth ads riêng cho sàn dùng chung OAuth Open Platform).
- `config/integrations.php` block `ads_lazada` + mô tả env `INTEGRATIONS_ADS`.
- `05-api/endpoints.md`: thêm endpoint marketplace ads.
- `07-infra/queues-and-scheduler.md`: job sync provider-generic.

## 10. Mở rộng tương lai (đa-sàn thiết kế sẵn)

- **TikTok GMV Max:** connector mới `Integrations/Ads/TikTok/` dùng **Marketing API** (`business-api.tiktok.com`, OAuth riêng, `advertiser_id`) — cần bổ sung tài liệu vào repo + app thứ hai. Cùng `AdsConnector`/DTO/UI đa-sàn ⇒ không sửa core.
- **Shopee Ads:** connector dùng `/api/v2/ads/*` (cùng OAuth channel như Lazada) — gate chờ Shopee whitelist `partner_id` + xác nhận VN.
- **Ghi (write) cho sàn:** bật dần `actions.budget/status` qua `AdsWriteConnector` + guardrail, khi đã chắc luồng đọc.
