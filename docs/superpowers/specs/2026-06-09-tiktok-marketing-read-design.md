# SPEC 2026-06-09 — TikTok Marketing (Ads) read integration

**Mục tiêu:** Thêm TikTok làm provider thứ 2 của trục `Ads` cho phép seller kết nối tài khoản quảng cáo TikTok, đồng bộ campaign/ad group/ad và xem báo cáo — **read-only**. Tái dùng tối đa module `Marketing` (đã provider-agnostic). Chỉ tham khảo tài liệu `tai_lieu_tiktok_ads/` (TikTok API for Business v1.3).

Liên quan: [ADR-0025](../../01-architecture/adr/0025-ads-tiktok-integration.md), [ADR-0024](../../01-architecture/adr/0024-ads-connector-registry.md), [setup](../../04-channels/tiktok-ads-setup.md).

## Nguyên tắc chống xung đột (có agent khác làm module khác)

- **Chỉ thêm file mới** cho phần lõi: `TikTokAdsConnector`, `TikTokAdsOAuthController`, 2 file test, docs.
- **Sửa file dùng chung — chỉ thêm (additive), không đổi hành vi cũ:** `config/integrations.php` (block `ads_tiktok`), `IntegrationsServiceProvider` (1 entry + 1 bind), `routes/web.php` (1 route), `Marketing/Http/routes.php` (gate any-of + route connect-tiktok), `BillingPlanSeeder` (1 key), `.env`.
- **FE:** thêm hook + nút + feature row; sửa `MarketingDashboardPage` để nhận biết provider (ẩn thao tác write với TikTok).
- Commit **chỉ các file của mình** (git add từng đường dẫn cụ thể).

## Phạm vi

| Có | Không (Phase sau) |
|---|---|
| OAuth connect (auth_code → long-term token) | Tạo/sửa campaign/adgroup/ad (write) |
| Đồng bộ campaign / ad group(→adset) / ad | Drafts, monitors/automation |
| Báo cáo (report/integrated/get) + đối soát | AI forecast/insight cho TikTok |
| Hiển thị account & report TikTok ở FE | pixel/CRM events/audience/targeting, BC |

## Backend

### TikTokAdsConnector implements AdsConnector
`app/app/Integrations/Ads/TikTok/TikTokAdsConnector.php`, ctor `array $config = config('integrations.ads_tiktok')`.

- `code()` = `tiktok`; `displayName()` = `TikTok Ads`.
- `capabilities()`: `insights.read`,`entities.list` = true; tất cả write/creative/page/preview/targeting = false.
- `buildAuthorizationUrl(state)` → `{auth_url}?app_id&state&redirect_uri`.
- `exchangeCodeForToken(authCode)` → POST `/oauth2/access_token/` JSON; trả `['access_token'=>…, 'expires_at'=>null, 'raw'=>…]`. Ném `RuntimeException` khi `code != 0`.
- `listAdAccounts(token)` → `/oauth2/advertiser/get/` (app_id+secret) lấy id+name, rồi `/advertiser/info/` (batch ≤100) lấy currency/status/timezone/owner_bc_id → `AdAccountDTO` (externalAccountId=advertiser_id; accountStatus/disableReason/business*=null; status=chuỗi TikTok; raw chứa timezone/owner_bc_id).
- `fetchAccountStatus(token,id)` → `/advertiser/info/` → `['account_status'=>null,'disable_reason'=>null]` (TikTok không dùng mã int FB; trạng thái dạng chuỗi để ở entity status).
- `listEntities(token,id,level)` level∈{campaign,adset,ad} → `/campaign/get/` `/adgroup/get/` `/ad/get/`; lặp page_info; map `operation_status`→status, `secondary_status`→effectiveStatus, budget theo `budget_mode` (DAY→dailyBudget, TOTAL→lifetimeBudget, khác→null), `objective_type`→objective. parentExternalId: adgroup→campaign_id, ad→adgroup_id. **adgroup trả level `adset`.**
- `fetchInsights(token,id,level,query,&throttle)` → `/report/integrated/get/` report_type=BASIC, data_level=AUCTION_{CAMPAIGN|ADGROUP|AD|ADVERTISER}, dimensions=[<level>_id] (advertiser: không id-dim), metrics=[spend,impressions,clicks,ctr,cpc,cpm,reach,conversion,cost_per_conversion], start_date/end_date (mặc định hôm nay). Map `data.list[].metrics`→`AdInsightDTO`. Tiền: cast int trực tiếp (không số lẻ). `throttleOut` = `AdInsightThrottleDTO()` mặc định.
- `fetchAdCreatives` → `UnsupportedOperation`.

### TikTokAdsOAuthController
`app/app/Modules/Marketing/Http/Controllers/TikTokAdsOAuthController.php` — song song `AdsOAuthController`.
- `STATE_PROVIDER='tiktok_marketing'`, `CONNECTOR='tiktok'`.
- `start()`: Gate `marketing.connect`; abort 422 nếu chưa register/chưa cấu hình app; issue OAuthState (redirect_after `/marketing?connected=tiktok_marketing`); trả `authorize_url`.
- `callback()`: lấy **`auth_code`** (fallback `code`) + `state`; verify; exchange; listAdAccounts; upsert `AdAccount` (withTrashed+restore, provider=tiktok, token_expires_at=null); dispatch `SyncAdAccountEntities`; xóa state; AuditLog `marketing.tiktok_ads.connected`; finish redirect.

### Wiring (additive)
- `config/integrations.php`: `ads_tiktok` = app_id/app_secret/base_url/auth_url/redirect_uri/scopes.
- `IntegrationsServiceProvider`: `$adsConnectors['tiktok']=TikTokAdsConnector::class`; bind `new TikTokAdsConnector(config('integrations.ads_tiktok'))`.
- `routes/web.php`: `GET oauth/tiktok_marketing/redirect → TikTokAdsOAuthController@callback` name `marketing.tiktok_ads.callback`.
- `Marketing/Http/routes.php`: group gate → `plan.feature:marketing_facebook|marketing_tiktok`; route `POST ads/connect-tiktok → TikTokAdsOAuthController@start` (override gate `marketing_tiktok`); FB `ads/connect` override gate `marketing_facebook`.
- `BillingPlanSeeder::featureKeys()` += `marketing_tiktok`.

## Frontend
- `lib/marketing.tsx`: `useConnectTikTokAds()` POST `/marketing/ads/connect-tiktok`.
- `MarketingDashboardPage`: nút "Kết nối TikTok" (icon TikTok), `applyResult` xử lý `connected=tiktok_marketing` & `error=tiktok_marketing*`; với `selectedAccount.provider==='tiktok'` ẩn/disable inline edit + toggle status (write không hỗ trợ).
- Feature row `marketing_tiktok` ("Quảng cáo TikTok") ở `lib/billing.tsx`, `PlansPage`, `AdminPlansPage`.
- `ConnectionManagerDrawer`: icon theo provider.

## Test
- Unit: `TikTokAdsConnectorTest` (Http::fake, mẫu từ docs) — token/advertisers/campaign/adgroup/ad/report + level adgroup→adset + budget_mode.
- Feature: `TikTokAdsOAuthTest` — callback auth_code tạo AdAccount + Queue::fake dispatch sync; state sai → redirect error.

## Rủi ro
- FE write inline với TikTok account: phải disable theo provider (đã nêu).
- `refreshAccounts` (rediscover) theo luồng FB — TikTok rediscover qua OAuth lại; không phá vỡ, chỉ không áp dụng.
