# Tạo quảng cáo Facebook (Wizard) — Design

> Tiếp nối SPEC Facebook Ads Phase 1 (đọc insights) + Đối soát/Dự báo. Bổ sung **trục GHI** cho Ads connector.
> Ngày: 2026-06-04 · Trạng thái: draft (chờ duyệt).
> ADR liên quan: ADR-0017 (Ads connector axis — đọc). **Cần ADR mới cho trục GHI (create) Ads.**

## 1. Mục tiêu
Cho người bán **không chuyên** tự lên chiến dịch quảng cáo Facebook qua **wizard 6 bước**, có **Guided Tour** hướng dẫn và **AI gợi ý**, chọn **bài viết có sẵn của Page** (kèm like/comment/share, ảnh/video) hoặc tạo nội dung mới, **xem trước thật** rồi **lưu nháp / xuất bản**. Đầy đủ như Ads Manager nhưng đơn giản hoá.

Ràng buộc: **additive, không đụng luồng khác** (báo cáo/đối soát/messaging giữ nguyên). Integration layer **không biết tên sàn** (luật ADR-0017): mọi thứ Facebook-specific nằm trong connector + config.

## 2. Quyết định đã chốt (brainstorm 2026-06-04)
- **Bố cục:** Wizard từng bước (rail bước · form · preview), **chỉ tối ưu màn hình máy tính nhỏ** (~1280px), **không làm responsive mobile**.
- **Mục tiêu v1:** `Tin nhắn (Messenger)`, `Tương tác/Nhận diện (Engagement/Awareness)`, `Truy cập website (Traffic)`. **Bán hàng/Chuyển đổi (Pixel/Catalog) → v2.**
- **Xem trước:** dùng Graph `generatepreviews` (Facebook render, chính xác từng vị trí).
- **Quyền:** code đầy đủ tới Xuất bản, nhưng **gated**: chưa có `ads_management` ⇒ chỉ **Lưu nháp + Xem trước**; bật xuất bản thật khi được duyệt (xin quyền sau).
- **AI:** tái dùng `MarketingAnalysisClient`/`LlmMarketingAnalysisClient` đã có (provider AI marketing riêng), không đụng AI messaging.

## 3. Kiến trúc tổng thể — tách **Soạn (draft)** khỏi **Xuất bản (publish)**
- **Soạn:** wizard ghi `AdDraft` (JSON từng bước) qua autosave. Chỉ cần `ads_read` + scope Page. Không gọi API ghi quảng cáo.
- **Xuất bản:** `PublishAdDraft` Job (Horizon) tạo tuần tự Campaign→AdSet→Ad. Cần `ads_management` + capability `ads.create`. Idempotent + rollback.
- Lợi: người dùng soạn/preview thoải mái khi chưa có quyền ghi; rủi ro ghi gom vào 1 job kiểm soát được.

## 4. Integration layer — trục GHI cho `AdsConnector`
Thêm capability + method mới (provider thiếu ⇒ `UnsupportedOperation`):

**Capabilities:** `ads.create`, `creative.upload`, `page.posts.read`, `targeting.search`, `preview.generate`.

**Methods (interface `AdsConnector`):**
- `createCampaign(token, accountId, CampaignSpecDTO): string` → campaign external id.
- `createAdSet(token, accountId, AdSetSpecDTO): string`.
- `createAd(token, accountId, AdSpecDTO): string`.
- `uploadImage(token, accountId, source): string` (image_hash) · `uploadVideo(token, accountId, source): string` (video_id).
- `listPages(token): list<PageRefDTO>` (id, name, có page access token nội bộ).
- `listPagePosts(token, pageId, opts): list<PagePostDTO>` — kèm media (ảnh/video, thumbnail) + engagement (likes/comments/shares).
- `searchTargeting(token, query, type): list<TargetingOptionDTO>` (interest/behavior **id**, không phải chữ).
- `estimateAudience(token, accountId, targetingSpec): AudienceSizeDTO` (delivery_estimate).
- `generatePreviews(token, accountId, creativeSpec, formats[]): list<AdPreviewDTO>` (iframe html/url theo vị trí).

**DTO mới:** `CampaignSpecDTO`, `AdSetSpecDTO`, `AdSpecDTO`, `PageRefDTO`, `PagePostDTO`, `TargetingOptionDTO`, `AudienceSizeDTO`, `AdPreviewDTO`.

**Ma trận hợp lệ (Facebook-specific, để trong integration):** `config/ads_objectives.php` (hoặc map trong connector) định nghĩa mỗi mục tiêu v1 → `objective`, `optimization_goal`, `billing_event`, `promoted_object` (page id cho Messenger), `destination_type`, danh sách `CTA` hợp lệ. Wizard chỉ cho chọn tổ hợp hợp lệ ⇒ tránh Graph từ chối.

**Tiền tệ (chống lỗi 100×):** connector tự scale ngân sách major→minor theo `ad_accounts.currency` (VND zero-decimal giữ nguyên; tiền tệ 2 chữ số ×100). Money là VND integer ở core (luật dự án); connector là nơi DUY NHẤT đổi đơn vị khi ghi.

**Page lấy từ đâu:** Ads connector tự liệt kê Page bằng ads token (scope thêm `pages_show_list,pages_read_engagement`) — giữ tính năng Ads tự chứa, **không** import Messaging. (Ad account phải được liên kết Page để dùng `object_story_id`/CTA `MESSAGE_PAGE`.)

**Bất biến objective ↔ creative (enforce ở Plan 4/wizard, KHÔNG ở connector):** connector cố tình objective-agnostic ở `createAd` (chỉ dựng đúng creative được yêu cầu). Quy tắc tương thích do orchestration layer giữ:
- `messages` → CTA phải `MESSAGE_PAGE`; ad set cần `promoted_object.page_id` (đã guard trong `createAdSet`).
- `engagement` (OUTCOME_ENGAGEMENT/POST_ENGAGEMENT) → **bắt buộc dùng bài Page có sẵn** (`pagePostId` → `object_story_id`); KHÔNG dùng `link_data` creative mới (Graph không tối ưu engagement cho link ad). Wizard chỉ cho chọn "bài có sẵn" khi mục tiêu là engagement.
- `traffic` → cần `linkUrl`; CTA `LEARN_MORE`/`SHOP_NOW`.
`cta_options` trong `FacebookObjectiveMap` là nguồn để wizard giới hạn CTA hợp lệ (sẽ tiêu thụ ở Plan 5).

## 5. Module Marketing — model / service / job / API
**Model + migration `ad_drafts`** (BelongsToTenant): `tenant_id`, `ad_account_id`, `created_by`, `name`, `status` (`draft|publishing|published|failed`), `objective`, `payload` json (budget, schedule, targeting, placements, creative{mode,page_id,post_id,image_hash|video_id,primary_text,headline,cta}), `idempotency_key`, ngoại id sau publish (`campaign_external_id`,`adset_external_id`,`ad_external_id`), `last_error`, timestamps. 1 draft = 1 quảng cáo (v1).

**`AdDraftService`:** CRUD + validate từng bước (server-side, không tin client).

**`PublishAdDraft` Job (Horizon, queue `marketing-publish`):** **resume-first, idempotent** — lưu external id sau mỗi cấp; chạy lại thì bỏ qua cấp đã có id (không tạo trùng), chỉ làm tiếp cấp còn thiếu. Tạo Campaign→AdSet→Ad (status=PAUSED mặc định). Lỗi giữa chừng ⇒ giữ nguyên entity đã tạo (PAUSED), set `status=failed`+`last_error`; người dùng sửa & bấm publish lại để **tiếp tục từ cấp lỗi**. **Rollback** (xoá entity đã tạo trên Facebook) chỉ khi người dùng **xoá draft** đang ở trạng thái `failed/publishing`. `tries`, backoff như job ads hiện có.

**API (`/api/v1/marketing`, Gate `marketing.ads.create` cho ghi, `marketing.view` cho đọc):**
- `GET ad-accounts/{id}/pages` · `GET pages/{pageId}/posts` (paginate, engagement).
- `POST ad-accounts/{id}/ad-media` (upload ảnh/video) · `POST ad-accounts/{id}/targeting-search` · `POST ad-accounts/{id}/audience-estimate` · `POST ad-accounts/{id}/ad-previews`.
- `GET/POST ad-drafts`, `GET/PATCH/DELETE ad-drafts/{id}` (autosave = PATCH debounce).
- `POST ad-drafts/{id}/publish` → enqueue (chỉ khi `ads.create` + token có `ads_management`; nếu thiếu trả 422 thân thiện). `GET ad-drafts/{id}` để poll trạng thái.

**Ability mới:** `marketing.ads.create` (Owner/Admin).

## 6. Frontend — wizard (desktop-only)
`features/marketing/adWizard/` (mirror module). State qua **Zustand** (draft hiện tại) + **autosave** (debounce PATCH); server state qua TanStack Query.
- **Khung:** AntD `Steps` (rail trái) · form giữa · preview phải (iframe `generatepreviews`). 3 cột cố định cho màn nhỏ, **không** breakpoint mobile.
- **Bước:** 1 Mục tiêu · 2 Ngân sách & Lịch · 3 Đối tượng · 4 Vị trí · 5 Nội dung · 6 Xem trước & Xuất bản.
- **Quy ước UI dự án:** icon **@ant-design/icons** (không emoji); dùng `Segmented`/`Radio.Group` cho tập nhỏ (mục tiêu, kiểu ngân sách, placement), `Select` chỉ cho danh sách dài (sở thích, bài viết); validate-by-disable + báo lỗi inline; lỗi Graph (tiếng Anh) map sang thông báo VN.
- **Entry:** tab mới **"Quảng cáo của tôi"** trong `/marketing` + danh sách nháp/đang chạy + nút "Tạo quảng cáo".
- **Modal/popup:** Chọn bài Page (lưới ảnh/video + 👍💬↗, lọc Ảnh/Video), Upload media, Tệp đã lưu/Lookalike, Trợ lý AI (slide-over), Guided Tour.

## 7. Guided Tour
Dùng AntD **`Tour`** (coachmark theo từng bước). Welcome modal hỏi "loại shop" → cấu hình gợi ý sẵn cho các bước. Bỏ qua được; không chặn thao tác; trạng thái "đã xem" lưu localStorage + có nút "Hướng dẫn".

## 8. AI gợi ý
Slide-over panel gọi `POST ad-drafts/ai-suggest` (context = bước hiện tại + insights/đối soát của account) → `MarketingAnalysisClient->analyze()` (provider AI marketing đã có, cooldown/stub như forecast). Trả gợi ý áp dụng được (ngân sách/đối tượng/bài viết). **Không** thêm provider mới, **không** đụng AI messaging.

## 9. Không đụng luồng khác
Thêm trục ghi vào `AdsConnector` (method mới, capability mới) — `fetchInsights`/`listEntities` cũ không đổi. Marketing thêm model/endpoint mới; Orders/Messaging không đụng. Publish off cho tới khi user bấm + có quyền. Page lấy qua ads token (không import Messaging).

## 10. Testing
- **Connector (Http::fake):** create Campaign/AdSet/Ad gửi đúng payload (objective matrix, **budget scaling theo currency**, `object_story_id` cho bài có sẵn vs `object_story_spec` cho mới, CTA). `generatePreviews`/`searchTargeting`/`listPagePosts` parse đúng (engagement, media). `UnsupportedOperation` khi provider thiếu.
- **PublishAdDraft Job:** thành công tạo 3 cấp PAUSED; **idempotent/resume** (chạy lại không tạo trùng — bỏ qua cấp đã có external id, làm tiếp cấp thiếu); lỗi ở Ad ⇒ giữ Campaign/AdSet, `status=failed`; **rollback chỉ khi xoá draft** failed.
- **AdDraft API:** RBAC (`marketing.ads.create`), tenant scope, autosave PATCH, publish **gated** trả 422 khi thiếu quyền.
- **AI suggest:** dùng stub (0 quota), không gọi AI ngoài on-demand.
- **FE:** typecheck/lint/build; wizard state + autosave.

## 11. Build sequence
1. ADR trục ghi Ads → connector write axis + DTO + capability + `config/ads_objectives.php` + currency scaling (TDD, Http::fake) — không UI.
2. `ad_drafts` migration + model + `AdDraftService` + CRUD API + autosave.
3. Read endpoints hỗ trợ: pages, page posts (engagement), media upload, targeting-search, audience-estimate, ad-previews.
4. `PublishAdDraft` job (idempotent+rollback) + publish endpoint + gating + ability.
5. FE wizard 6 bước (desktop-only) + Zustand + autosave + entry tab + danh sách nháp.
6. Modal: chọn bài Page + upload media + audience builder + placements + preview iframe.
7. Guided Tour (AntD Tour) + AI slide-over (reuse marketing AI).

## 12. Giới hạn / v2
- v2: mục tiêu **Bán hàng/Chuyển đổi** (Pixel + Catalog + sự kiện mua hàng), A/B test, nhiều quảng cáo/creative trong 1 nhóm (carousel), dayparting nâng cao, responsive mobile.
- Quảng cáo đã tạo tự đồng bộ chỉ số qua `SyncAdInsights` sẵn có (không cần luồng mới).
- v1: 1 draft = 1 campaign/1 adset/1 ad (đơn giản hoá); chưa hỗ trợ nhiều adset.
