# ADR-0018: Trục mở rộng thứ 5 — `AiAssistantConnector` + `AiAssistantRegistry`; AI provider do super-admin cấu hình, tenant chỉ chọn

- **Trạng thái:** Proposed
- **Ngày:** 2026-05-19
- **Người quyết định:** Team (chủ dự án đã chốt 2026-05-19: "AI do super admin thêm và user chỉ có thể dùng 1 trong số các provider")
- **Liên quan:** SPEC-0024, ADR-0004, ADR-0017, SPEC-0023 (`system_settings`), `08-security-and-privacy.md`

> **REVISION 2026-05-20 (khi implement S6):** Lưu trữ provider config **CHUYỂN từ
> `system_settings` sang bảng riêng `ai_providers`** (super-admin quản, không
> tenant-scoped). Lý do: `SystemSettingsCatalog` là allowlist key **TĨNH**
> (exact-match, 38 key) theo thiết kế — không nhận key động `ai_providers.<code>.*`,
> nên `system_setting()` luôn trả default ⇒ `AiAssistantRegistry::isActive()` luôn
> false ⇒ KHÔNG provider nào active được (lỗi tiềm ẩn). Bảng riêng mô hình hoá đúng
> (record có cấu trúc: `api_key` encrypted, `pricing` json, `is_active`), không phá
> hợp đồng "single source of truth" của Settings module, và test được không cần
> system_settings. `capabilities` KHÔNG lưu DB — đọc từ connector class (super-admin
> không thể "claim" capability mà class chưa implement). Mọi điều khác trong ADR giữ
> nguyên (trục #5, tenant chọn 1 provider active, cost tracking qua `ai_assistant_runs`).

## Bối cảnh

SPEC-0024 đưa AI hỗ trợ trả lời (suggest + auto + RAG training). Câu hỏi kiến trúc: làm sao tích hợp LLM mà (a) không khoá vào 1 vendor, (b) không để tenant tự nhập API key (chi phí + bảo mật), (c) tách rõ tầng "messaging" và tầng "AI".

**Phương án đã cân nhắc:**

A. **Hard-code 1 LLM provider** (vd Anthropic Claude) — gọi trực tiếp trong `Messaging\Services\AiReplyService`.
   - ✗ Khoá vào 1 vendor; đổi sau = sửa code rải rác.
   - ✗ Không hỗ trợ self-host (`local_llm`) cho khách enterprise nhạy cảm.
   - ✗ Vi phạm "core không biết tên cụ thể" — `'claude'` rò vào core.

B. **Mỗi tenant tự nhập API key của LLM họ chọn** (BYO key).
   - ✓ Linh hoạt cao.
   - ✗ Khách thường dân khó set up.
   - ✗ Quản lý quota / cost / billing phía cmbcoreSeller phức tạp.
   - ✗ Không phải mục tiêu — user (chủ dự án) đã chốt: super-admin quản, tenant chọn.

C. **Trục mở rộng riêng `AiAssistantConnector` + `AiAssistantRegistry`** — mirror `PaymentRegistry`/`ChannelRegistry`. Provider config sống ở `system_settings` (super-admin SPA, đã có từ SPEC-0023). Tenant chỉ chọn `ai_provider_code` từ danh sách `is_active=true`.
   - ✓ Đổi vendor = thêm 1 connector, 1 dòng register, không sửa Messaging core.
   - ✓ Super-admin chịu trách nhiệm API key + giá tiền + DPA — đảm bảo PII đối tác đã ký.
   - ✓ Cost tracking đơn giản: ghi `ai_assistant_runs.cost_micro_vnd` → super-admin charge tenant qua `plan.limit:messaging_ai_replies_monthly` (đã có pattern `usage_counters`).
   - ✓ Self-host (`local_llm` connector) là 1 provider thường — bật/tắt như mọi cái khác.

## Quyết định

Chọn **phương án C**.

Tạo trục mở rộng thứ 5:
- `app/Integrations/Ai/Contracts/AiAssistantConnector.php` — interface.
- `app/Integrations/Ai/AiAssistantRegistry.php` — singleton.
- `app/Integrations/Ai/DTO/` — `AiContext`, `ConversationSnapshot`, `KnowledgeBase`, `AiReplyDTO`, `IntentDTO`, `EmbeddingDTO`.
- Connector seed: `Claude/`, `OpenAi/`, `Gemini/` (chỉ skeleton trong PR đầu, super-admin tự enable + nhập key).
- `LocalLlm/` connector cho self-host (gọi OpenAI-compatible endpoint).

Interface skeleton:
```php
interface AiAssistantConnector {
    public function code(): string;                      // 'claude'|'openai'|'gemini'|'local_llm'
    public function displayName(): string;
    public function capabilities(): array;               // 'reply.suggest','reply.auto','intent.classify','rag.training','embedding'
    public function supports(string $cap): bool;

    public function generateReply(AiContext $ctx, ConversationSnapshot $conv, ?KnowledgeBase $kb): AiReplyDTO;
    public function classifyIntent(string $text, array $candidates = []): IntentDTO;
    public function embed(string $text): EmbeddingDTO;   // cho RAG indexing
    public function pricing(): array;                    // [{kind, unit, micro_vnd_per_unit}] — super-admin nhập, dùng tính cost
}
```

Source of truth cho provider config:
- **Bảng `system_settings`** (SPEC-2026-05-17, đã có) — group `ai_providers.<code>`:
  ```
  ai_providers.claude.is_active = true
  ai_providers.claude.api_key = <encrypted>
  ai_providers.claude.base_url = https://api.anthropic.com
  ai_providers.claude.default_model = claude-opus-4-7
  ai_providers.claude.embedding_model = (n/a)
  ai_providers.claude.pricing = {"input":3000,"output":15000}   -- micro_vnd/1k tokens
  ```
- Tenant chọn: `tenant_settings.messaging.ai_provider_code` (key duy nhất ở phía tenant).
- API `/admin/ai-providers` (super-admin) CRUD `system_settings` group này.
- API `/tenant/settings/messaging` (`messaging.ai.config` permission) đọc list `is_active=true` để tenant chọn.

Rules:
- **Core (`Modules/Messaging`) không biết tên provider cụ thể.** `AiReplyOrchestrator` chỉ gọi `AiAssistantRegistry::for($tenant->ai_provider_code)`.
- Mọi prompt qua `PiiRedactor` trước khi gọi `connector.generateReply` (xem `08-security-and-privacy.md` §6).
- Mọi call ghi `ai_assistant_runs` (audit + cost).
- Provider thiếu capability ⇒ ném `UnsupportedOperation` — Messaging fallback (vd intent classify thiếu ⇒ skip guardrail intent, vẫn cho NV gửi qua suggest).
- Provider `is_active=false` sau khi tenant đã chọn ⇒ Messaging hiện banner "Provider đã ngừng — chọn lại"; AI feature tạm disable cho tenant đó.

## Hệ quả

**Tích cực:**
- Đổi vendor LLM = thêm 1 connector + 1 dòng register.
- Super-admin có quyền tuyệt đối với key & cost → bảo mật + billing rõ ràng.
- Self-host (`local_llm`) hỗ trợ ngay vì là 1 provider thường, không phải case đặc biệt.
- Cost tracking + per-tenant gate qua `plan.limit:messaging_ai_replies_monthly` — pattern đã có.
- DPA / zero-retention chỉ cần thoả thuận giữa cmbcoreSeller và LLM vendor — tenant không phải lo.

**Tiêu cực / đánh đổi:**
- Tenant không thể dùng provider "lạ" không có trong list super-admin → mất linh hoạt với khách enterprise muốn dùng provider riêng. Mitigation: cho enterprise tier có endpoint riêng nhập key (Phase sau, nếu có nhu cầu).
- Tổng cost AI nằm hoàn toàn ở cmbcoreSeller — cần dự báo & charge đúng qua gói để không lỗ. Mitigation: `messaging_ai_replies_monthly` limit chặt, default Pro=0 (chỉ Business mới có).

**Việc phải làm theo sau:**
- Cập nhật `01-architecture/extensibility-rules.md` thêm row "AI Assistant" vào bảng §1 + checklist "Thêm 1 AI provider mới" §6d.
- Cập nhật `08-security-and-privacy.md` thêm rule: prompt → PiiRedactor; super-admin key chỉ trong `system_settings`; DPA với mỗi LLM vendor.
- Cập nhật `02-data-model/overview.md`: `system_settings` group `ai_providers`, tables `ai_assistant_runs`, `ai_knowledge_*`.
- Tạo `docs/04-ai-assistant/README.md` (mirror channels) — DTO chuẩn + provider trạng thái.
- Đảm bảo middleware `plan.feature:messaging_ai` + `plan.limit:messaging_ai_replies_monthly` (Billing — đã có pattern SPEC-0018).
