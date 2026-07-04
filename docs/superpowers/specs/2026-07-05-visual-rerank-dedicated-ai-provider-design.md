# Visual re-rank — provider AI riêng (tách khỏi model chat)

- **Ngày:** 2026-07-05
- **Trạng thái:** Design (chờ review)
- **Bối cảnh:** SPEC 2026-06-16 (Visual training & tìm sản phẩm bằng ảnh)

## Vấn đề

Bước vision re-rank (`VisionReRanker` — cho AI xem ảnh khách + ảnh đại diện top-5 ứng
viên rồi chọn khớp nhất) hiện **dùng chung provider + model với AI chat** của hội thoại.
`AiSuggestionService::visualTrainingContext()` truyền thẳng `providerCode` +
`default_model` của provider chat (resolve qua `resolveProviderCode`) vào `VisualLookupOptions`.

Hệ quả trên prod: provider chat mặc định là `minimaxm3` (`mn/Minimax-M3`) — **không khớp**
danh sách `config('ai.vision.models')` nên `analyzeImages()` ném `UnsupportedOperation`
→ re-rank trả `NOT_RUN` → luôn rơi về quyết định ngưỡng cosine. Muốn giữ Minimax-M3 để
chat (chất lượng/giá) nhưng vẫn có AI chấm ảnh thì phải **tách model vision cho re-rank**.

## Mục tiêu

1. Re-rank dùng **provider AI riêng** do super-admin chọn, **độc lập** với provider chat.
2. Cấu hình ở **một màn hình admin riêng biệt** — không dùng lại/nhồi vào trang "Nhà cung
   cấp AI" hiện có (tránh sửa nhầm).
3. **Non-breaking:** chưa cấu hình → fallback về provider/model chat như hiện tại.

## Ngoài phạm vi (YAGNI)

- Không có ô "model override" trên màn hình mới. Muốn model vision khác → super-admin
  **tạo một provider AI riêng** qua trang "Nhà cung cấp AI" hiện có (mỗi provider = 1
  `default_model`), rồi chọn provider đó ở màn hình re-rank.
- Không đổi logic recall / ngưỡng / gộp điểm.
- Không cấu hình per-tenant: đây là cài đặt super-admin toàn hệ thống (provider AI vốn là
  global, guard `admin_web`).

## Thiết kế

### 1. Lưu cấu hình (system_setting)

Thêm **1 khóa** vào `SystemSettingsCatalog`:

- `visual_search.rerank.provider_code` — string, mặc định rỗng. Rỗng = fallback provider chat.

Đọc bằng helper `system_setting('visual_search.rerank.provider_code', '')`; ghi bằng
`SystemSettingService::set()` (như `help_assistant.*`).

### 2. Tách quyết định trong `VisionReRanker::pick()`

Đầu `pick()`, trước khi resolve connector, chèn bước override:

```php
$override = trim((string) system_setting('visual_search.rerank.provider_code', ''));
if ($override !== '' && in_array($override, $this->registry->activeProviders(), true)) {
    $providerCode = $override;
    // model = null ⇒ connector dùng default_model của provider re-rank
    $ctx = new AiContext(
        tenantId: $ctx->tenantId,
        providerCode: $override,
        model: null,
        meta: ['mode' => 'visual_rerank'],
    );
}
// ... giữ nguyên phần còn lại (canUse credit, registry->for, supports('vision.analyze'), analyzeImages)
```

- Override không active / không tồn tại → **bỏ qua override**, dùng `providerCode`/`ctx`
  do caller truyền (provider chat) → fallback đúng như hiện tại.
- Override active nhưng model không vision → `analyzeImages` ném `UnsupportedOperation`
  → `NOT_RUN` (rơi về ngưỡng). Không vỡ luồng.
- Credit: vẫn tính trên `$tenantId` (tham số riêng), 1 credit sau khi provider thành công —
  **không đổi**.

`VisionReRanker` đã có sẵn `AiAssistantRegistry` (thuộc tính `$registry`) nên có
`activeProviders()`; không cần thêm dependency.

### 3. Nhận diện "có vision" trung thực (dùng chung logic)

Badge trên UI phải khớp **đúng** điều kiện connector dùng để quyết định. Hiện logic
`visionEnabled()` bị lặp (private) trong `ClaudeConnector` và `OpenAiConnector`.

**Cải tiến nhắm đích:** trích ra `CMBcoreSeller\Integrations\Ai\Support\VisionModelGate`
với `enabledFor(string $model): bool` (đọc `config('ai.vision')`). Hai connector gọi
helper này thay cho method private; controller admin dùng cùng helper để tính badge.
→ Một nguồn sự thật, badge luôn khớp hành vi thật.

### 4. Màn hình admin mới — "AI chấm ảnh"

**Backend** — controller riêng trong module VisualSearch (module sở hữu re-rank), guard
`admin_web`, KHÔNG tenant. Cùng stack middleware `['web','auth:admin_web','throttle:60,1']`
như `AdminAiProviderController`.

`app/app/Modules/VisualSearch/Http/Controllers/AdminVisualRerankController.php`:

- `GET /api/v1/admin/ai-visual-rerank`
  → `{ data: { selected_provider_code, providers: [{code, display_name, default_model, is_active, vision}] } }`
  (liệt kê provider active từ `AiProvider`; `vision` tính qua `VisionModelGate`).
- `PUT /api/v1/admin/ai-visual-rerank`
  body `{ provider_code: string|null }` — validate: rỗng (clear) **hoặc** thuộc
  `activeProviders()`. Ghi `SystemSettingService::set('visual_search.rerank.provider_code', ...)`.
  Ghi `AuditLog::record('visual_search.rerank.provider_set', ...)`.
- `POST /api/v1/admin/ai-visual-rerank/test` body `{ provider_code }`
  → gửi **1 ảnh mẫu nhúng sẵn** + instruction rút gọn qua `analyzeImages`, trả
  `{ ok, sample, reason, message }`. Mượn pattern `probe()` của `AdminAiProviderController`
  (bắt mọi lỗi → `ok:false`, không 500). Mục đích: verify provider **thật sự** nhận ảnh
  (cảnh báo gateway `ts/`/`mn/` qua cổng có thể không nhận input ảnh dù tên model khớp).

Routes đặt trong `app/app/Modules/VisualSearch/Http/routes.php` (nếu chưa load admin group
thì thêm; provider đã `loadRoutesFrom`).

**Frontend** — page + lib + menu **riêng**, không import trang cũ:

- Menu: thêm mục `/admin/ai-visual-rerank` — "AI chấm ảnh" (icon `PictureOutlined`) vào
  `AdminLayout.tsx`, cạnh nhóm AI hiện có.
- Route: `AdminApp.tsx` thêm route trỏ page mới.
- Page: `resources/js/admin/pages/settings/AdminVisualRerankPage.tsx`.
- Lib: `resources/js/admin/lib/visualRerank.tsx` (hooks TanStack Query gọi 3 endpoint).
- UI: **Radio.Group** (theo UI rule — tránh `<Select>`) liệt kê provider active; mỗi dòng
  hiện `display_name` + `default_model` + badge **"Có vision ✓ / Không ✗"**; nút **"Gửi
  ảnh thử"** gọi endpoint test hiện kết quả; nút Lưu. Có mục chọn **"(Không cấu hình) —
  dùng model chat"** (giá trị rỗng) là mặc định. Icon dùng `@ant-design/icons` (không emoji).

## Luồng dữ liệu

```
Khách gửi ảnh
  → AiSuggestionService.visualTrainingContext(providerChat, modelChat)
    → VisualMatcher.lookup(... opts{providerCode=chat, aiContext=chat})
      → recall CLIP + group-by-item → top-5
      → VisionReRanker.pick():
          override = system_setting('visual_search.rerank.provider_code')
          nếu override active ⇒ providerCode/ctx := override (model=default_model của nó)
          nếu rỗng/không active ⇒ giữ provider chat (fallback)
          → connector(providerCode).analyzeImages(...) chọn ảnh
```

## Xử lý lỗi

- Override không active/không tồn tại ⇒ fallback provider chat (không lỗi).
- Provider override không vision / gateway không nhận ảnh ⇒ `NOT_RUN` ⇒ ngưỡng cosine.
- Hết credit ⇒ `NOT_RUN` (như hiện tại).
- Endpoint test không bao giờ 500 (probe bắt mọi lỗi).

## Kiểm thử

- **Unit `VisionReRanker`:**
  - Có `system_setting` provider active ⇒ gọi connector của provider override (không phải
    provider chat truyền vào).
  - Setting rỗng ⇒ dùng provider chat (fallback).
  - Setting trỏ provider đã tắt ⇒ fallback provider chat.
- **Unit `VisionModelGate`:** khớp/không khớp các tên model tiêu biểu (`mn/Minimax-M3` ✗,
  `ts/gpt-5.4-mini` ✓, `ts/gemini-3.5-flash` ✓); `config('ai.vision.enabled')=false` ⇒ luôn ✗.
- **Feature admin endpoint:** GET trả danh sách + badge; PUT lưu & đọc lại; PUT provider
  không active ⇒ 422; test endpoint trả `ok:false` gọn khi provider chưa cấu hình.

## Tương thích & triển khai

- **Non-breaking:** không set gì ⇒ hành vi y hệt hiện tại.
- **Không cần migrate** (chỉ dùng `system_settings` sẵn có + thêm khóa vào catalog).
- Sau deploy, super-admin: (1) tạo provider AI vision cho re-rank ở trang "Nhà cung cấp AI"
  (vd adapter openai_compatible, `default_model` = model vision như `ts/gemini-3.5-flash`),
  (2) vào "AI chấm ảnh" chọn provider đó, (3) bấm "Gửi ảnh thử" xác nhận vision chạy thật.
- Docs: cập nhật `docs/05-api/endpoints.md` (3 endpoint admin mới).

## Các file đụng tới

- `app/app/Integrations/Ai/Support/VisionModelGate.php` (mới)
- `app/app/Integrations/Ai/Claude/ClaudeConnector.php` · `.../OpenAi/OpenAiConnector.php` (dùng gate)
- `app/app/Modules/VisualSearch/Services/VisionReRanker.php` (override provider)
- `app/app/Modules/VisualSearch/Http/Controllers/AdminVisualRerankController.php` (mới)
- `app/app/Modules/VisualSearch/Http/routes.php` (admin group)
- `app/app/Modules/Settings/Support/SystemSettingsCatalog.php` (thêm khóa)
- `app/resources/js/admin/AdminApp.tsx` · `AdminLayout.tsx` (route + menu)
- `app/resources/js/admin/pages/settings/AdminVisualRerankPage.tsx` (mới)
- `app/resources/js/admin/lib/visualRerank.tsx` (mới)
- `app/config/visual_search.php` (ghi chú khóa system_setting cạnh block `rerank`)
- `docs/05-api/endpoints.md`
</content>
