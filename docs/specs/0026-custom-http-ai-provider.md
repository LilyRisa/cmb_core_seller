# SPEC 0026: AI provider tùy chỉnh qua HTTP (`custom_http` adapter)

- **Trạng thái:** Draft
- **Phase:** 6.5 (Automation/Messaging nâng cao — mở rộng SPEC-0024)
- **Module backend liên quan:** Integrations/Ai, Messaging
- **Tác giả / Ngày:** Team · 2026-05-26
- **Liên quan:** ADR-0018 (trục mở rộng AI), SPEC-0024 (Omnichannel Messaging), `01-architecture/extensibility-rules.md` §6d, `08-security-and-privacy.md`

## 1. Vấn đề & mục tiêu

Trục AI hiện có 3 adapter: `anthropic` (Claude Messages API), `openai_compatible` (mọi API tương thích OpenAI Chat Completions — OpenAI/DeepSeek/Qwen/OpenRouter/Gemini/Azure/self-host), và `manual` (stub test). Nhưng một LLM **không** theo 2 định dạng đó (vd LLM nội địa với JSON request/response riêng, hoặc một proxy nội bộ có shape khác) thì super-admin **không thể** thêm — phải viết connector PHP mới + deploy.

Mục tiêu: cho super-admin **khai báo trong admin** (`/admin/ai-providers`) một provider HTTP bất kỳ — endpoint, headers, body template, đường dẫn (JSON path) lấy câu trả lời — mà **không cần code/deploy**. Đây là một adapter mới (`custom_http`), đúng pattern Connector/Registry của ADR-0018, **core không đổi**.

Liên quan triệu chứng "AI không tự động trả lời": auto-mode (`AiAutoModeOnInbound`) chỉ chạy khi có **provider active dùng được**; nếu LLM của khách không thuộc 2 adapter sẵn có thì `activeProviders()` rỗng → `AiSuggestionService::autoRespond()` ném `providerNotAvailable` (bị nuốt log) → AI không bao giờ tự gửi. `custom_http` lấp khoảng trống đó.

## 2. Trong / ngoài phạm vi của spec này

- **Trong:**
  - Adapter `custom_http` + connector `CustomHttpConnector` (reply + intent classify qua cùng endpoint).
  - Cột `adapter_config` (JSON) trên bảng `ai_providers` lưu cấu hình HTTP tuỳ chỉnh.
  - Mở rộng DTO `AiProviderRuntimeConfig` (thêm `adapterConfig`).
  - Validation + UI admin để nhập cấu hình `custom_http`.
- **Ngoài (làm sau / spec khác):**
  - `embedding` / RAG training cho `custom_http` (dùng keyword fallback của `KnowledgeRetriever`; có thể chọn provider khác để embed).
  - Body **không phải JSON** (form-urlencoded, gRPC…). v1 chỉ hỗ trợ request/response **JSON**.
  - Cho **tenant** tự thêm provider (vẫn là quyền super-admin — giữ nguyên ADR-0018).
  - Streaming response.

## 3. Câu chuyện người dùng / luồng chính

1. Super-admin vào `/admin/ai-providers` → "Thêm provider" → chọn **Loại API = Tùy chỉnh (HTTP)**.
2. Nhập: mã slug, endpoint (HTTPS), method, headers (cho phép placeholder `{{api_key}}`), **body template** (JSON với placeholder), **response path** (vd `data.reply.text`), (tuỳ chọn) đường dẫn token usage để tính chi phí, API key, pricing.
3. Bấm **Test** → hệ thống render template với hội thoại mẫu "hello", gọi endpoint, parse theo `response_path`. Trả `ok:true` + sample, hoặc `ok:false` + lý do (không 500).
4. Bật **Kích hoạt** → provider xuất hiện trong danh sách tenant chọn được.
5. Tenant ở `/settings/messaging` chọn provider này, bật **AI gợi ý** + **Tự động trả lời** (cần gói Business) → auto-mode dùng `custom_http` như mọi provider khác (qua guardrail intent).

## 4. Hành vi & quy tắc nghiệp vụ

- **Core không biết `custom_http`** — chỉ là một adapter được register; `AiSuggestionService`/`IntentClassifier`/`AiAutoModeOnInbound` không đổi.
- **Capabilities** (đọc từ connector class, không lưu DB): `reply.suggest=true`, `reply.auto=true`, `intent.classify=true`, `rag.training=false`, `embedding=false`.
  - `intent.classify=true` là **bắt buộc** để auto-mode hoạt động: `IntentClassifier` mặc định **escalate** (an toàn) nếu connector không support classify ⇒ AI sẽ không bao giờ tự gửi. `CustomHttpConnector` thực hiện classify bằng cách gọi **chính endpoint đó** với prompt phân loại 1 nhãn.
- **Placeholder** thay trong `request_template` (ngữ cảnh JSON — giá trị được JSON-escape, **không** kèm dấu nháy ngoài; tác giả tự bọc `"..."`):
  - `{{model}}`, `{{system}}`, `{{last_user_message}}`, `{{buyer_name}}` — chuỗi (escape JSON).
  - `{{messages_json}}` — **mảng JSON đầy đủ** kiểu `[{"role":"user","content":"..."}]` (chèn không kèm nháy).
  - `{{api_key}}` — escape JSON (khi nhúng trong body).
- **Placeholder** thay trong **headers** và **URL** (ngữ cảnh thô, không escape): `{{api_key}}`, `{{model}}`.
- Sau khi render, body phải **parse được thành JSON**; nếu không → lỗi rõ ràng (không gửi rác lên API).
- **Chi phí**: token đọc từ `adapter_config.usage.{prompt_path,completion_path}` (mặc định 0 nếu API không trả) × `pricing` (super-admin nhập) → `ai_assistant_runs.cost_micro_vnd`. Pattern cũ giữ nguyên.
- **Idempotency**: connector stateless; idempotency của auto-reply do `AutoReplyRun`/`OutboundMessageService` đảm bảo (không đổi).
- **Phân quyền**: chỉ super-admin (`auth:admin_web`) CRUD provider; tenant chỉ chọn (permission `messaging.ai.config`).

## 5. Dữ liệu

- Bảng `ai_providers`: thêm cột **`adapter_config` JSON nullable** (cấu hình HTTP tuỳ chỉnh). Không tenant-scoped (catalog chung). Các adapter khác để `null`.
- Shape `adapter_config` (custom_http):
  ```jsonc
  {
    "method": "POST",                                  // POST | PUT | GET
    "headers": { "Authorization": "Bearer {{api_key}}" },
    "request_template": "{\"model\":\"{{model}}\",\"system\":\"{{system}}\",\"messages\":{{messages_json}}}",
    "response_path": "data.reply.text",
    "usage": { "prompt_path": "usage.prompt_tokens", "completion_path": "usage.completion_tokens" }
  }
  ```
- Migration: reversible (`down()` drop cột). Không index (JSON tra cứu không cần). Không partition.
- `api_key` vẫn ở cột `api_key` (encrypted-at-rest); `adapter_config` **không** chứa secret thật — chỉ placeholder `{{api_key}}`.
- Domain event: không thêm mới.

## 6. API & UI

- **Không thêm endpoint** — dùng lại `/api/v1/admin/ai-providers` (index/store/update/destroy/test). `endpoints.md` không đổi (chỉ thêm trường `adapter_config` vào payload/response của route đã có).
- `AdminAiProviderController`:
  - `store`/`update`: nhận `adapter_config` (array). Khi `adapter=custom_http`: bắt buộc `base_url` (endpoint, qua `SafeProviderUrl`), `adapter_config.request_template`, `adapter_config.response_path`.
  - `present()`: trả thêm `adapter_config` (an toàn — không có secret).
  - `PRESETS['custom_http']`: 1 mẫu gợi ý (endpoint/model mẫu) để FE auto-điền.
- **UI** (`AdminAiProvidersPage.tsx`): khi adapter=`custom_http` hiện thêm field: Method, Headers (JSON), Body template (textarea + chú thích placeholder), Response path, Usage paths. `base_url` đổi nhãn thành "Endpoint URL (đầy đủ)".
- **Job**: không job mới. Auto-mode chạy trên queue `messaging-ai` (đã có, supervisor `messaging-bg`).
- Connector dùng `AiAssistantConnector` chuẩn; **không** thêm `if ($adapter==='custom_http')` ở core.

## 7. Edge case & lỗi

- `request_template` render ra JSON không hợp lệ (placeholder làm hỏng cú pháp) → `RuntimeException` "không tạo JSON hợp lệ" (ghi `ai_assistant_runs` error, auto-mode nuốt log).
- Thiếu `request_template`/`response_path`/`base_url` → `ProviderNotConfigured` → endpoint test trả `ok:false reason:not_configured` (không 500).
- Endpoint trả non-2xx → `RuntimeException` kèm status + trích body (giới hạn 200 ký tự).
- `response_path` không khớp (rỗng) → `RuntimeException` "không tìm thấy nội dung tại response_path".
- API không trả usage → token=0 → cost=0 (không lỗi).
- Endpoint trỏ host/IP nội bộ hoặc non-HTTPS → chặn ở `SafeProviderUrl` (chống SSRF).
- Classify lỗi/timeout → `IntentClassifier` mở circuit + escalate an toàn (đã có).

## 8. Bảo mật & dữ liệu cá nhân

- Body hội thoại đã qua `PiiRedactor` ở `AiSuggestionService` **trước** khi tới connector (giữ nguyên §8.4). `custom_http` không bỏ qua bước này.
- `api_key` encrypted-at-rest, `$hidden` khỏi response; chỉ chèn vào request lúc gọi. `adapter_config` không lưu key thật.
- `SafeProviderUrl` chặn endpoint nội bộ/loopback/non-HTTPS.
- **Lưu ý vận hành (DPA)**: super-admin tự chịu trách nhiệm thoả thuận xử lý dữ liệu với endpoint tuỳ chỉnh họ trỏ tới (như mọi provider khác — ADR-0018).

## 9. Kiểm thử (nhất quán với `09-process/testing-strategy.md`)

- **Feature/Contract** (`Tests/Feature/Messaging/AiProviderHttpTest.php`, `Http::fake`):
  - `generateReply` render template (model/system/messages_json/api_key), gửi đúng method+url+headers, parse `response_path`, đọc usage, tính cost.
  - `classifyIntent` trả về 1 nhãn trong tập candidate.
  - Thiếu `request_template`/`response_path` → `ProviderNotConfigured`.
  - Template render ra JSON hỏng → `RuntimeException`.
  - `capabilities()['intent.classify'] === true`.
- **Feature** (`Tests/Feature/Messaging/AdminAiProviderTest.php`): store `custom_http` thiếu request_template/response_path → 422; tạo đủ → 201 + `adapter_config` trong response; test endpoint `ok:true` với `Http::fake`.
- **FE**: form hiện field `custom_http` khi chọn adapter (kiểm thủ công + typecheck/build CI).

## 10. Tiêu chí hoàn thành (Acceptance criteria)

- [ ] Super-admin tạo được provider `custom_http` trong `/admin/ai-providers`, bấm Test thấy reply mẫu.
- [ ] Tenant (gói Business) chọn provider đó → AI tự trả lời tin "an toàn", escalate tin nhạy cảm.
- [ ] Core (`AiSuggestionService`/`IntentClassifier`/`AiAutoModeOnInbound`) **không** thay đổi.
- [ ] `pint --test`, `phpstan`, `php artisan test`, `npm run lint/typecheck/build` xanh.
- [ ] Tài liệu cập nhật: ADR-0018 (revision), `extensibility-rules.md` §6d.

## 11. Câu hỏi mở

- Có cần hỗ trợ body không-JSON (form-urlencoded) ở v2? → chờ nhu cầu thực tế.
- Có cần endpoint classify riêng (khác endpoint reply) cho LLM rẻ hơn? → hiện dùng chung endpoint; tách sau nếu cần.
