# Thiết kế: Quản lý đa nhà cung cấp AI (adapter động) + vá rủi ro prod — SPEC-0024 / ADR-0018

> Trạng thái: **Thiết kế (2026-05-21)** — chờ duyệt trước khi viết plan triển khai.
> Phạm vi: module `Modules\Messaging` (admin AI provider) + `Integrations\Ai` (registry/connector) + Admin SPA (`resources/js/admin`).
> KHÔNG đụng luồng inbox FE (`MessagingPage.tsx`) ngoài 1 dòng chú thích, KHÔNG đụng connector sàn (Channels/Carriers/Payments).

## 1. Bối cảnh & phát hiện then chốt

Yêu cầu: admin có thể **thêm nhiều nhà cung cấp AI** (Claude, GPT, Gemini, DeepSeek, Qwen, OpenRouter…) và rà soát AI tích hợp chat đã đúng logic / chạy ổn prod chưa.

**Backend quản lý provider — ĐÃ ĐỦ (không thiếu API thêm):**
- `app/Modules/Messaging/Models/AiProvider.php` — PK `code`, `api_key` cast `encrypted` + `$hidden`, `pricing` JSON, `is_active`.
- `app/Modules/Messaging/Http/Controllers/AdminAiProviderController.php` — CRUD đầy đủ `index/store/update/destroy/test`.
- `app/Modules/Messaging/Http/routes.php:148-158` — `GET/POST /api/v1/admin/ai-providers`, `PATCH/DELETE /{code}`, `POST /{code}/test` (guard `admin_web`, `throttle:60,1`).
- Có test: `tests/Feature/Messaging/AdminAiProviderTest.php`, `AiProviderHttpTest.php`.

**Frontend admin — THIẾU HOÀN TOÀN (đây là nguyên nhân "không có chỗ thêm"):**
- `resources/js/admin/AdminApp.tsx` — không có route `/admin/ai-providers`.
- `resources/js/admin/AdminLayout.tsx` — không có mục menu.
- `resources/js/admin/lib/` — không có hook CRUD AI provider.
- Trớ trêu: `resources/js/pages/MessagingSettingsPage.tsx` hiển thị cảnh báo *"Quản trị viên cần thêm & bật provider trong /admin/ai-providers"* — trỏ tới trang **không tồn tại**.

**Giới hạn kiến trúc cho "nhiều nhà cung cấp" (vấn đề thật):**
- `AdminAiProviderController::store()` dòng 45 ràng buộc `Rule::in($this->registry->providers())` — `code` chỉ được là 1 connector **đăng ký cứng** trong `IntegrationsServiceProvider::$aiAssistantConnectors` (`app/Integrations/IntegrationsServiceProvider.php:97-106`): hiện chỉ `manual`, `claude`, `openai` (gemini/local_llm bị comment).
- `code` là **khoá chính** ⇒ mỗi code = 1 dòng. DeepSeek/Qwen/OpenRouter đều **tương thích OpenAI** (`/v1/chat/completions` + Bearer) nhưng dùng chung 1 ô `openai` ⇒ **không thể có DeepSeek + Qwen + OpenRouter cùng lúc**.
- Connector **hardcode lấy credential theo `$this->code()`**: `ClaudeConnector.php:71` `resolve($this->code())`; `OpenAiConnector.php:167` (qua `config()`) `resolve($this->code())`. Đây chính là điểm chặn nhiều-instance.

**Logic AI-chat — nhìn chung đúng & chắc:**
- 2 chế độ: gợi ý (sync, NV duyệt mới gửi) `AiSuggestionController::generate` + tự động (async queue `messaging-ai`) `AiAutoModeOnInbound`.
- PII redaction trước khi gửi LLM (`PiiRedactor`); intent guardrail escalate complaint/refund/urgent (`IntentClassifier`); giới hạn tháng theo gói; cost tracking (`ai_assistant_runs`); retry 2 + timeout 30s; api_key encrypted.

**Rủi ro prod đã phát hiện:**
1. `base_url` **không validate** — cho cả `http://` và host nội bộ ⇒ nguy cơ SSRF/MITM (`ClaudeConnector.php:87`, `OpenAiConnector.php:177`).
2. `IntentClassifier::classify` lỗi → trả `urgent` (`IntentClassifier.php:40`) ⇒ provider lỗi kéo dài = **escalate tất cả** vô thời hạn (không phân biệt lỗi tạm/cấu hình, không circuit breaker).
3. `ai-suggestion` chỉ có throttle **global**, chưa rate-limit **theo tenant** ⇒ 1 tenant spam ảnh hưởng cả hệ thống.
4. Nợ tài liệu sai: comment *"live HTTP call ném UnsupportedOperation cho tới khi wire (S6.1)"* (`IntegrationsServiceProvider.php:99-101`) đã CŨ (connector đã gọi HTTP thật, có test fake); docblock `AiAssistantConnector.php:16-19` còn nói config sống ở `system_settings` (thực tế đã chuyển sang bảng `ai_providers`).

## 2. Quyết định kiến trúc (đã chốt với chủ dự án)

- **Adapter động:** tách `code` (instance, slug tự do) khỏi `adapter` (loại API). Adapter = `anthropic` \| `openai_compatible` \| `manual`.
- **Gemini = preset của `openai_compatible`** (dùng endpoint OpenAI-compat của Google: `…/v1beta/openai`), KHÔNG cần connector riêng. DeepSeek/Qwen/OpenRouter/Together cũng là preset của `openai_compatible`.
- **Phạm vi:** tính năng quản lý provider (UI + kiến trúc) **kèm** vá 4 rủi ro prod ở §1.
- **Kỹ thuật refactor tối ưu (ít churn nhất):** inject **instance-code** vào connector lúc resolve bằng `container->makeWith(['code' => $code])`. `code()` trả về slug instance ⇒ mọi `resolve($this->code())` hiện có **giữ nguyên, vẫn đúng**. Không phải viết lại ruột connector.

## 3. Mô hình dữ liệu

### 3.1 Migration mới `…_add_adapter_to_ai_providers_table.php`
- `up()`: `ALTER TABLE ai_providers` thêm:
  - `adapter` `string(24)` — sau backfill set `NOT NULL`, index. Giá trị: `anthropic|openai_compatible|manual`.
  - (tuỳ chọn) `sort_order` `unsignedSmallInteger default 0`; `notes` `string nullable` (ghi chú cho admin).
- **Backfill** trong cùng migration: `claude→adapter='anthropic'`, `openai→adapter='openai_compatible'`, `manual→adapter='manual'`, còn lại default `openai_compatible`.
- `down()`: `dropColumn(['adapter', …])`.
- **Không** đổi PK, **không** đụng FK `ai_assistant_runs.provider_code`, `messaging_settings.ai_provider_code` (vẫn trỏ `code` string).

### 3.2 Model `AiProvider`
- `$fillable` thêm `adapter` (+ `sort_order`, `notes` nếu dùng).
- Cập nhật docblock: `code` = slug instance tự do; `adapter` = loại connector.

### 3.3 Ngữ nghĩa `code`
- `code` (varchar 32) = slug instance, regex `^[a-z0-9][a-z0-9_-]{1,31}$`. Ví dụ: `claude-main`, `deepseek-prod`, `qwen-cheap`, `openrouter-fallback`, `gemini-flash`.

## 4. Registry & Connector

### 4.1 `AiAssistantRegistry` (`app/Integrations/Ai/AiAssistantRegistry.php`)
- Map đổi ngữ nghĩa: `register(string $adapter, string $class)` — key giờ là **adapter** (không phải code).
- Thêm `adapters(): list<string>` (thay vai trò `providers()` cũ trong validation; giữ `providers()` nếu chỗ khác còn dùng, hoặc đổi tên có kiểm soát).
- `for(string $code)`: load row `AiProvider::find($code)` → nếu không có/`is_active=false` ⇒ `ProviderNotConfigured`; lấy `adapter` → `container->makeWith($this->connectors[$adapter], ['code' => $code])`.
- `make(string $code)` (admin, không check active): load row → adapter → `makeWith(['code'=>$code])`. Nếu row không tồn tại nhưng cần test adapter trống → controller truyền adapter trực tiếp (xem §5.4).
- `isActive`/`activeProviders` giữ nguyên (đọc theo `code`).
- Helper `adapterFor(string $code): ?string` đọc cột `adapter`.

### 4.2 Connector — inject instance code
- Constructor thêm tham số code có default = adapter mặc định:
  - `ClaudeConnector::__construct(AiProviderCredentials $credentials, string $code = 'claude')` → `code(): string { return $this->code; }`.
  - `OpenAiConnector::__construct(AiProviderCredentials $credentials, string $code = 'openai')` → tương tự. Đổi tên lớp/nhãn thành **OpenAiCompatibleConnector** (giữ file cũ + alias, hoặc rename + cập nhật references) vì phục vụ nhiều vendor; `displayName()` giữ mặc định, tên hiển thị thực lấy từ `row.display_name` (đã có ở `present()` và `MessagingSettingsController::availableProviders` 71-90).
  - `ManualAiAssistantConnector` thêm code injectable tương tự.
- **Nội bộ connector gần như không đổi**: `resolve($this->code())` giờ tự trỏ đúng instance.
- `IntegrationsServiceProvider::$aiAssistantConnectors` đổi thành map adapter:
  ```php
  'anthropic'          => Claude\ClaudeConnector::class,
  'openai_compatible'  => OpenAi\OpenAiCompatibleConnector::class,
  'manual'             => Manual\ManualAiAssistantConnector::class,
  ```
  Singleton `AiAssistantRegistry` (dòng 193-200) register theo adapter. **Xoá comment "stub until wired (S6.1)" sai**.

### 4.3 Capabilities & embedding
- `capabilities()` theo adapter: `anthropic` → embedding=false; `openai_compatible` → reply/intent=true, embedding tùy endpoint. Để an toàn: **embedding mặc định bật cho `openai_compatible`** nhưng `KnowledgeIndexer` đã có fallback keyword khi `embed()` ném `UnsupportedOperation`/lỗi (giữ hành vi hiện tại). Gemini/OpenRouter qua compat endpoint không có embedding chuẩn → để cost/RAG fallback xử lý, không chặn reply.

## 5. Backend Controller `AdminAiProviderController`

### 5.1 `store()`
- Validate đổi: bỏ `Rule::in(registry->providers())` cho `code`; thêm:
  - `code`: `required`, regex slug, `unique:ai_providers,code`.
  - `adapter`: `required`, `Rule::in($registry->adapters())`.
  - `base_url`: `nullable`, **`https`-only + chống SSRF** (xem §6.1) — bắt buộc với `openai_compatible` (cần biết vendor); với `anthropic` mặc định `https://api.anthropic.com`.
  - `default_model`: bắt buộc với `openai_compatible` (OpenAI-compat không đoán được model).
- Tạo row kèm `adapter`, `created_by_admin_id`.

### 5.2 `update()` — giữ logic không ghi đè `api_key` rỗng (dòng 76-79). Cho sửa `display_name/base_url/default_model/pricing/is_active/notes`; **không** cho đổi `adapter` (đổi adapter = tạo provider mới — tránh lệch credential format).

### 5.3 `index()` — trả thêm:
- mỗi row: `adapter`.
- `adapters`: danh sách `{adapter, label, presets:[{name, base_url, default_model}]}` để FT auto-điền (vd preset `openai_compatible`: OpenAI / DeepSeek `https://api.deepseek.com` / Qwen DashScope compat / OpenRouter `https://openrouter.ai/api` / Gemini `https://generativelanguage.googleapis.com/v1beta/openai`). Thay/bổ sung cho `registered_codes`.

### 5.4 `test()` — load `AiProvider::find($code)` để biết adapter; resolve `registry->make($code)` (đã inject code) rồi gọi `generateReply` "hello". Giữ nguyên xử lý `UnsupportedOperation`/`ProviderNotConfigured` → 200 `{ok:false,…}` (không 500).

### 5.5 `present()` — thêm `adapter`; capabilities đọc qua `registry->make($code)->capabilities()` (đã có, chỉ đổi cách resolve theo adapter).

## 6. Vá rủi ro prod

### 6.1 Validate `base_url` (chống SSRF/MITM)
- Rule dùng chung (FormRequest hoặc closure) ở `store/update`: bắt buộc `scheme=https`; chặn host `localhost`, `*.local`, IP loopback/private (`127.0.0.0/8`, `10/8`, `172.16/12`, `192.168/16`, `169.254/16`, `::1`, `fc00::/7`). Có thể whitelist domain biết trước (anthropic/openai/deepseek/openrouter/googleapis…) + cho phép domain khác miễn HTTPS & không phải IP nội bộ.

### 6.2 Rate-limit theo tenant cho AI
- Route `conversations/{id}/ai-suggestion` (`routes.php:72-81`): thêm limiter `throttle:ai-suggestion` định nghĩa theo `tenant_id` (vd 20/phút/tenant) trong `RouteServiceProvider`/`AppServiceProvider::boot` `RateLimiter::for('ai-suggestion', …)`. Giữ throttle global hiện có.

### 6.3 Circuit breaker / phân loại lỗi intent-classify
- `IntentClassifier::classify` (`IntentClassifier.php:19-48`): phân biệt
  - lỗi **cấu hình** (`ProviderNotConfigured`) → escalate (an toàn) + cảnh báo admin.
  - lỗi **tạm** (timeout/5xx) → đếm lỗi gần đây (cache key theo provider_code, vd 5 lỗi/2 phút) ⇒ mở mạch tạm: bỏ qua bước classify (cho reply tự động chạy bình thường với guardrail mặc định, hoặc tạm dừng auto-mode tùy chọn) thay vì escalate-all vô thời hạn.
- Quyết định mặc định khi mở mạch: **tạm dừng auto-reply, đánh dấu `requires_human`** (an toàn hơn gửi sai), nhưng không spam cùng lỗi.

### 6.4 Dọn nợ tài liệu
- Xoá/cập nhật comment `IntegrationsServiceProvider.php:99-101`.
- Cập nhật docblock `AiAssistantConnector.php:16-19` (config ở bảng `ai_providers`, không phải `system_settings`).
- Cập nhật docblock `AdminAiProviderController.php:22-24` (code = slug + adapter).

## 7. Frontend Admin

### 7.1 Hook `resources/js/admin/lib/aiProviders.tsx`
- React Query (theo pattern `admin/lib/admin.tsx`): `useAdminAiProviders()` (GET, trả `data` + `adapters`), `useAdminCreateAiProvider`, `useAdminUpdateAiProvider`, `useAdminDeleteAiProvider`, `useAdminTestAiProvider`.

### 7.2 Trang `resources/js/admin/pages/settings/AdminAiProvidersPage.tsx`
- Bảng: `code`, `adapter`, `display_name`, `default_model`, `has_api_key`, `is_active`, capabilities, actions (Sửa / Test / Tắt).
- Form thêm/sửa (modal/drawer): chọn **adapter bằng `Radio.Group`/`Segmented`** (KHÔNG `<Select>` — theo memory user); chọn **preset** (auto điền `base_url`+`default_model`); nhập `code` slug (khoá khi sửa), `display_name`, `base_url`, `default_model`, `api_key` (placeholder "để trống = giữ nguyên"), bảng dòng `pricing` ({kind,unit,micro_vnd}), toggle `is_active`.
- Nút **Test connection** → hiển thị `{ok, reason, sample}`.
- Icon dùng **@ant-design/icons** (không emoji — theo memory user). Toàn bộ Việt hoá.

### 7.3 Đấu nối
- `AdminApp.tsx`: thêm `<Route path="ai-providers" element={<AdminAiProvidersPage/>} />`.
- `AdminLayout.tsx`: thêm mục menu "Nhà cung cấp AI" (icon @ant-design/icons) → `/admin/ai-providers`.
- `MessagingSettingsPage.tsx`: giữ nguyên text (link giờ đã trỏ trang thật) — chỉ rà lại cho khớp.

## 8. Test

- **Unit:** `AiAssistantRegistry` resolve theo adapter + inject code (provider `deepseek-prod`/`qwen-cheap` cùng adapter `openai_compatible` resolve credential khác nhau); base_url validation (reject `http://`, IP nội bộ).
- **Feature (`AdminAiProviderTest` mở rộng):** tạo **đồng thời** `deepseek-prod` + `qwen-cheap` + `openrouter-fallback` (cùng `openai_compatible`) → `index` thấy đủ; `test()` từng cái với `Http::fake` theo `base_url`; `store` từ chối `adapter` lạ / `base_url` http / `code` trùng.
- **Feature hardening:** rate-limit ai-suggestion theo tenant; intent-classify timeout → không escalate-all.
- Giữ `AiProviderHttpTest` xanh (connector nội bộ gần như không đổi).
- FE: smoke nếu có hạ tầng test (build/lint/typecheck tối thiểu).

## 9. Trình tự build (mỗi bước giữ test xanh)

1. Migration + model (`adapter` col + backfill).
2. Registry + connector inject-code (`makeWith`) + đổi map sang adapter — chạy test cũ.
3. Tổng quát hoá `OpenAiCompatibleConnector` + preset (Gemini/DeepSeek/Qwen/OpenRouter).
4. Controller validation (`store/update/index/test/present`).
5. FE admin (hook + page + route + menu) + rà `MessagingSettingsPage`.
6. Prod-hardening (base_url validate, rate-limit tenant, circuit breaker intent, dọn comment/docblock).
7. Test toàn bộ + cập nhật `docs/specs/0024-omnichannel-messaging.md` (mục AI providers) & ADR-0018 nếu cần.

## 10. Rủi ro & lưu ý

- Đổi ngữ nghĩa key map registry (`code`→`adapter`) đụng mọi nơi gọi `registry->providers()`/`for()`/`make()` — phải rà: `AdminAiProviderController`, `MessagingSettingsController`, `AiSuggestionService::resolveProviderCode` (179-195), `IntentClassifier`. `for($code)`/`make($code)` giữ chữ ký (vẫn nhận code) nên call-site ít đổi.
- `makeWith` resolve transient (connector không phải singleton) — đúng với hiện trạng `container->make` trong `for()`.
- Backfill migration phải chạy trước khi FE/list dùng `adapter`; trên prod chạy `migrate --force` có kiểm soát (theo README).
- Đổi adapter của 1 provider đang chạy không cho phép (tránh lệch format credential) — muốn đổi thì tạo provider mới + tắt cái cũ (đã có soft-disable).
