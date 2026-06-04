# Marketing dashboard nâng cấp kiểu Facebook Ads Manager — Design

> Tiếp nối SPEC Facebook Ads (Phase 1 + reconciliation/forecast). ADR-0024 (Ads axis).
> Ngày: 2026-06-04 · Trạng thái: approved.

## 1. Mục tiêu
Trang `/marketing` thành báo cáo kiểu **Facebook Ads Manager**:
- Lọc ad account **theo Business Manager (BM)** để chọn.
- **3 tab**: Chiến dịch / Nhóm quảng cáo / Quảng cáo.
- Cột đầy đủ (tên, id, ngân sách, chi tiêu, loại chiến dịch/objective, CPM, CPC, CTR, impressions, reach, frequency, ROAS…), **tuỳ chỉnh cột** (ẩn/hiện, lưu localStorage).
- **Lọc theo date range**, filter theo **id**, **loại (objective)**, **tên chiến dịch**.
- **Drill-down**: tích chiến dịch → tab Nhóm QC chỉ hiện nhóm thuộc chiến dịch đã tích; tích nhóm → tab QC lọc theo nhóm.

## 2. Quyết định kiến trúc (tối ưu)
- **Metadata cây entity** (campaign/adset/ad: id, name, status, objective, budget, parent) lấy từ **DB** (`ad_entities`, sync bởi `SyncAdAccountEntities`).
- **Số liệu insights** lấy **on-demand theo date range** (`/insights` + `time_range`, level breakdown), **cache ~5'** theo (account, level, range). Không phụ thuộc snapshot 'today' → mọi range đều đúng, không đốt rate-limit.
- Drill-down lọc ở **server** (campaign_ids → adsets; adset_ids → ads).
- Reconciliation + forecast (2a/2b) **giữ nguyên** thành section/tab phụ.

## 3. Backend

**Migrations**
- `ad_accounts`: thêm `business_id` (string, nullable), `business_name` (string, nullable).
- `ad_entities`: thêm `objective` (string, nullable). (adset optimization_goal/billing_event → `meta` đã có.)

**Connector `FacebookAdsConnector`**
- `listAdAccounts`: thêm field `business{id,name}` → `AdAccountDTO` (thêm `businessId`,`businessName`).
- `listEntities`: campaign thêm `objective`; adset thêm `optimization_goal,billing_event`; map vào DTO (`objective` + `meta`).
- `fetchInsights`: nhận `time_range` (đã hỗ trợ) → dùng cho range; trả đủ field hiện có (spend,impressions,clicks,reach,ctr,cpc,cpm,frequency,purchase_roas) — đã có.

**Sync/OAuth**
- `AdsSyncService::upsertEntity` lưu `objective`. `SyncAdAccountEntities` lấy objective.
- `AdsOAuthController::callback` lưu `business_id/business_name`.

**Service `AdsReportService`** (mới)
- `report(AdAccount, level, since, until, filters): rows` — lấy entity (DB, lọc theo `campaign_ids`/`adset_ids`/`q`/`objective`/`id`), gọi `fetchInsights` cho từng/level theo range, join → rows đầy đủ cột. Cache (Cache::remember, key gồm account+level+range+filters, TTL 300s).
- Drill-down: `level=adset` + `campaign_ids[]` ⇒ chỉ adset có `parent_external_id ∈ campaign_ids`; `level=ad` + `adset_ids[]` tương tự.

**API**
- `GET /marketing/ad-accounts` → trả thêm `business_id`,`business_name` (FE gom nhóm BM).
- `GET /marketing/ad-accounts/{id}/report?level=&since=&until=&campaign_ids[]=&adset_ids[]=&q=&objective=` (Gate `marketing.view`) → `{ data: { level, rows:[...], currency } }`.

## 4. Frontend (rebuild `/marketing`)
- **BM selector** (gom `ad-accounts` theo `business_name`) → chọn account.
- **RangePicker** (mặc định 7 ngày) + **filter bar**: search tên, input ID, Segmented/Select objective.
- **Tabs** (Segmented): Chiến dịch / Nhóm QC / QC — mỗi tab gọi `report` với level tương ứng + filter + drill-down ids.
- **Bảng** AntD: cột đầy đủ; **nút "Cột"** mở Dropdown checkbox bật/tắt cột, lưu `localStorage` (`marketing.report.columns`). rowSelection (tích) → lưu selected campaign/adset ids vào state → truyền sang tab con.
- Tiền format theo currency account; money = integer minor (VND).
- Hooks `lib/marketing.tsx`: `useAdReport(accountId, level, range, filters)` (TanStack, refetchInterval 5' optional), thêm `business_*` vào `AdAccount`.

## 5. Testing
- Connector: `listAdAccounts` parse business; `listEntities` parse objective; `fetchInsights` time_range.
- `AdsReportService`: join metadata+insights đúng; drill-down lọc đúng theo campaign_ids/adset_ids; filter q/objective; cache.
- API report (RBAC, tenant scope, token không lộ).
- OAuth callback lưu business_*.
- FE typecheck/lint/build.

## 6. Build sequence
1. Migrations (business_*, objective).
2. Connector (business, objective, insights range) + DTO.
3. Sync/OAuth lưu objective + business.
4. `AdsReportService` + API report + ad-accounts trả business.
5. FE rebuild (BM selector, 3 tab, range, filter, cột tuỳ chỉnh, drill-down).
6. Quality gate.

## 7. Giới hạn
- Cache 5' ⇒ số liệu trễ tối đa 5' (chấp nhận; có nút refresh). 
- Objective adset/ad ở `meta` (không cột riêng) — đủ cho hiển thị.
- Cột tuỳ chỉnh lưu localStorage (per trình duyệt) — không đồng bộ cross-device (theo yêu cầu).
