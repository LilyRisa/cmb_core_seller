# AI Campaign Generator + Facebook Ad Spec chuẩn hoá (v25)

Ngày: 2026-06-10. Trạng thái: approved (chủ repo duyệt, uỷ quyền làm trọn + commit/push).

## Bối cảnh & vấn đề

Tạo quảng cáo Facebook đang lỗi: publish chỉ tạo **campaign**, không tạo ad set/ad.
Root cause xác nhận trên prod (draft 11): FB reject `createAdSet` vì gửi vị trí
`video_feeds` (Meta đã khai tử ở Graph v25, subcode 2490562). Ngoài ra:

- FE giữ `adsets` tách `payload.adsets`, dễ lệch; backend `AdDraftTree::normalize`
  trả `[]` khi payload thiếu adsets ⇒ publish im lặng tạo mỗi campaign (mồ côi).
- Không có lớp validate/sanitize tập trung ⇒ không chặn được combo FB reject.
- Connector Ads đang ở Graph **v19** (đã hết hạn). Nâng **v25** (mới nhất, 2026-02-18),
  cố định trong code (`FacebookAdsConnector::GRAPH_VERSION`) — bỏ env (đổi version
  vốn phải sửa code).

Đồng thời cần tính năng mới: **tạo chiến dịch bằng AI prompt** từ một bài viết page,
tối ưu ngân sách + lịch chạy theo giờ hiện tại, hỗ trợ Advantage+ / thủ công, và trả
đề xuất sau khi tạo.

## Mục tiêu

Một **nguồn sự thật version-aware (v25)** cho payload quảng cáo FB mà wizard FE,
pipeline publish và AI cùng đi qua — không lệch, không gửi giá trị FB reject, và AI
có schema để tự sinh chiến dịch hợp lệ.

## Kiến trúc

### Nền tảng (Integrations/Ads/Facebook)

**`FacebookAdsCatalog`** — danh mục giá trị hợp lệ theo v25, một chỗ duy nhất:
- `objectives` + map → optimization_goal / billing_event / destination_type /
  promoted_object / cta_options (gộp vai trò `FacebookObjectiveMap`).
- `placements`: positions hợp lệ mỗi platform; `DEPRECATED` (`video_feeds`);
  `DESKTOP_ONLY` (`right_hand_column`).
- `sanitizePlacements(positions, devices)`: gỡ vị trí khai tử + desktop-only khi
  không nhắm desktop. `FacebookAdsConnector` gọi lại (1 nguồn).
- `jsonSchema()`: JSON Schema mô tả toàn bộ trường chiến dịch cho LLM điền.

**`FacebookCampaignBlueprint`** — value-object cho cây đầy đủ
`{campaign, adsets:[{targeting, budget, schedule, placements, promoted_object, ads:[{creative}]}]}`:
- `fromArray($payload)` — từ draft payload hoặc output AI.
- `validate(): list<string>` — thiếu trường bắt buộc, combo objective×goal×billing,
  placement chết, page_id (messages)/pixel+event (conversions), budget CBO/ABO đúng chỗ,
  targeting tối thiểu (geo). Trả lỗi tiếng Việt.
- `sanitize(): self` — gỡ placement không hợp lệ, chuẩn hoá.
- `toPayload(): array` — payload chuẩn cho `AdDraftSpecMapper`.

### Tối ưu lịch & ngân sách

**`ScheduleOptimizer`** (Modules/Marketing/Services):
- Đọc timezone tài khoản QC. Tính **start_time đề xuất an toàn**: nếu hiện tại còn
  ít giờ tới nửa đêm (mốc reset daily budget của FB) ⇒ đề xuất **đầu ngày hôm sau**;
  ngược lại = ngay.
- `riskWarning(startTime)`: cảnh báo mềm nếu người dùng chọn giờ rủi ro (còn < ngưỡng
  giờ tới nửa đêm → "dễ tiêu hết ngân sách ngày trong <X giờ").
- KHÔNG ép: người dùng tự chọn ngày/giờ ở FE (pre-fill = giá trị đề xuất).

**`AdBudgetGuardrails`**: khoảng daily_budget hợp lệ theo VND + chế độ Test/Scale
(Test = ngân sách nhỏ học tập; Scale = lớn hơn). Validate đề xuất AI nằm trong khoảng.

### Ngữ cảnh bài viết cho AI

- Mở rộng connector đọc **post detail**: ảnh (`full_picture`), caption (`message`),
  **engagement** (likes/comments/shares summary), CTA hiện có + link.
- **`LandingPageReader`**: fetch URL landing (CTA bài viết hoặc nhập tay) → trích text
  (tái dùng pattern strip_tags như IndexKnowledgeDoc) làm ngữ cảnh AI.

### Sinh chiến dịch bằng AI

**`AiCampaignGenerator`** (Modules/Marketing/Services):
- Input: account, page_post (caption + engagement + ảnh + CTA/link), landing text,
  objective, mode (test|scale), placement mode (advantage_plus|manual), user prompt,
  thời điểm hiện tại + tz tài khoản.
- Prompt chi tiết (tiếng Việt) hướng AI trả **JSON đúng `FacebookAdsCatalog::jsonSchema()`**:
  budget/bid/optimization/targeting/schedule theo Test vs Scale + danh sách
  `recommendations` (khuyến nghị scale, audience, sáng tạo, cảnh báo).
- Output → `FacebookCampaignBlueprint::fromArray` → `validate`/`sanitize` →
  `ScheduleOptimizer` áp start an toàn (nếu AI để "ngay" gần nửa đêm) → tạo `AdDraft`.
- Degrade an toàn: AI lỗi/hết quota ⇒ trả lỗi rõ, không tạo draft hỏng.
- Gate sau feature **`marketing_ai`**.

### Endpoint

`POST /marketing/ad-accounts/{id}/ai-campaign` (plan-gated `marketing_ai`):
body { page_post_id, landing_url?, objective, mode, placement_mode, prompt, start_time? }
→ tạo AdDraft (draft) + trả `{ draft, recommendations[] }`. Publish dùng pipeline hiện có.

### Publish (sửa tối thiểu)

`PublishAdDraft`: build Blueprint từ payload → `validate()`; lỗi ⇒ `failed` +
`last_error` rõ **trước khi tạo campaign** (hết campaign mồ côi) → `sanitize()` → mapper.

### Frontend (P8)

Wizard "Tạo bằng AI": chọn page → **post picker** (tên+id+avatar page; bài viết: ảnh,
tiêu đề/caption, like/comment/share, link/CTA nếu có); nếu không có link/CTA và mục
tiêu = conversions (COMPLETE_REGISTRATION) → cho nhập link + CTA; toggle
**Advantage+ / thủ công**; chọn **Test/Scale**; **ô chọn ngày/giờ bắt đầu** (pre-fill
đề xuất + cảnh báo mềm); ô prompt; nút "AI tạo chiến dịch"; hiển thị **recommendations**
sau khi tạo; mở draft trong wizard hiện có để chỉnh/publish.

## Test (TDD)

- Catalog: combo hợp lệ; sanitize bỏ video_feeds/right_hand_column; jsonSchema đủ enum.
- Blueprint: validate bắt thiếu page_id/pixel/budget/targeting; sanitize; toPayload.
- ScheduleOptimizer: gần nửa đêm → đề xuất hôm sau; ban ngày → ngay; cảnh báo rủi ro.
- BudgetGuardrails: Test/Scale trong/ngoài khoảng.
- LandingPageReader: fetch + trích text; URL lỗi → null.
- AiCampaignGenerator: ngữ cảnh → blueprint hợp lệ + recommendations; AI lỗi → lỗi rõ.
- Endpoint: tạo draft + recommendations; plan-gate chặn khi thiếu feature.
- PublishAdDraft: payload thiếu nhóm/ad → failed + last_error, KHÔNG tạo campaign.

## Ngoài phạm vi (YAGNI)

Lifetime budget nâng cao, A/B test, Advantage+ Shopping, catalog/DPA, audience tuỳ chỉnh
phức tạp (chỉ geo/age/gender/interests cơ bản + advantage+ placements).
