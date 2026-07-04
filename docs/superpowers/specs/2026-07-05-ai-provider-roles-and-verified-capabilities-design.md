# AI provider: vai trò (role) + năng lực xác minh THẬT + hiện API key

- **Ngày:** 2026-07-05
- **Trạng thái:** Design (chờ review)
- **Bối cảnh:** Retrofit sau khi ship "visual re-rank provider riêng" + đang làm "Groq transcription". Sửa 3 vấn đề người dùng nêu.

## Vấn đề

1. **Provider chấm ảnh/STT làm ô nhiễm chat.** `AiSuggestionService::resolveProviderCode()` lấy `activeProviders()[0]` làm chat mặc định; `activeProviders()` trả MỌI provider active ⇒ thêm 1 provider chỉ để chấm ảnh/STT có thể chiếm luôn chat mặc định. Màn hình re-rank/STT cũng liệt kê mọi provider ⇒ chọn nhầm.
2. **Badge "có/không vision" đoán theo TÊN.** `VisionModelGate` so khớp substring tên model — cảm tính, sai cả 2 chiều; và gate tên này còn chặn việc gửi ảnh lúc chạy.
3. **API key bị che** ở một số màn admin (Input.Password, secret `****` + reveal).

## Quyết định đã chốt
- **Bỏ HOÀN TOÀN lọc theo tên** (xóa `VisionModelGate` + `config('ai.vision.models')` + markers). Năng lực = **xác minh thật** (probe API thật, lưu kết quả).
- **Test tới khi thành công mới cho chọn**: chỉ lưu được provider làm re-rank/STT khi cờ verified=true cho năng lực đó.
- Provider tách theo **vai trò**; API key **hiện rõ** ở mọi màn admin.

## Thiết kế

### 1. Cột mới `ai_providers` (1 migration)
- `role` varchar mặc định `'chat'` — `chat` | `vision` | `transcription`.
- `vision_verified` boolean nullable (null=chưa test, true=đạt, false=trượt) + `vision_verified_at` timestamp nullable + `vision_verify_error` varchar nullable.
- `transcription_verified` boolean nullable + `transcription_verified_at` + `transcription_verify_error`.
- Fillable + `@property` cập nhật.

### 2. Registry lọc theo role
`AiAssistantRegistry::activeProviders(string $role = 'chat'): array` — lọc `where('role', $role)` (giữ orderBy sort_order/code, giữ filter adapter registered).
- `AiSuggestionService::resolveProviderCode()` (CHAT) gọi `activeProviders('chat')` ⇒ provider vision/transcription KHÔNG bao giờ thành chat mặc định.
- `resolveProviderCode` kiểm `MessagingSetting.ai_provider_code` cũng phải thuộc `activeProviders('chat')`.

### 3. Bỏ gate tên — dựa cờ verified
- **Xóa** `VisionModelGate` (class + test) và khóa `config('ai.vision.models')`. Giữ `config('ai.vision.enabled')` (công tắc tổng) và `config('ai.vision.max_tokens')`.
- `OpenAiConnector::analyzeImages` + `ClaudeConnector::analyzeImages`: **bỏ** đoạn `if (! visionEnabled(...)) throw UnsupportedOperation` — luôn thử gửi ảnh; model không nhận ⇒ API lỗi/khác ⇒ ném/`NOT_RUN` ⇒ fallback (như cũ). Xóa method `visionEnabled()` ở 2 connector.
- **Đường chat đính ảnh:** trong `generateReply`/`buildMessages`, biến `$vision` (có đính ảnh khách vào reply không) = **provider chat `vision_verified === true`** (thay cho `visionEnabled($model)`). Provider chat chưa verify ⇒ không đính ảnh (an toàn). `AiSuggestionService::imageUrlsFor` giữ `config('ai.vision.enabled')` làm công tắc tổng, thêm điều kiện provider chat verified (truyền qua).

### 4. Xác minh thật + lưu kết quả
Năng lực verify qua **probe API thật** rồi lưu cờ:
- **Vision:** gửi 1 ảnh mẫu qua `analyzeImages` (đã có ở endpoint test re-rank). Thành công (không ném) ⇒ `vision_verified=true`, `vision_verified_at=now`, error=null. Lỗi ⇒ `false` + error.
- **Transcription:** gửi clip WAV mẫu qua `transcribeAudio` (đã có ở endpoint test STT). Tương tự set `transcription_verified`.
- Endpoint test (re-rank + STT) **ghi kết quả** vào ai_providers (không chỉ trả về).
- (Tuỳ chọn gọn) Provider admin form có nút "Kiểm tra" riêng cho từng năng lực; nhưng tối thiểu: 2 màn hình re-rank/STT là nơi verify.

### 5. Chốt chọn theo verified (test-tới-khi-đạt)
- `PUT /admin/ai-visual-rerank`: nếu `provider_code` khác rỗng mà provider `vision_verified !== true` ⇒ **422** "Provider chưa xác minh vision — hãy Gửi ảnh thử tới khi thành công." Rỗng (tắt) luôn cho.
- `PUT /admin/ai-transcription`: tương tự với `transcription_verified`.
- Ngoài verified, vẫn kiểm `role` đúng (`vision`/`transcription`) và thuộc `activeProviders(role)`.

### 6. Badge từ verified (không đoán tên)
Card ở màn re-rank/STT:
- `verified === null` → **"Chưa kiểm tra"** (xám).
- `true` → **"Đã xác minh ✓"** (xanh) + thời gian.
- `false` → **"Kiểm tra thất bại ✗"** (đỏ) + lý do.
Nút "Lưu" disabled trừ khi provider đang chọn verified=true (hoặc chọn rỗng/tắt). Nút test luôn bật để verify.

### 7. Form "Nhà cung cấp AI": role + không che key
- Thêm ô **Vai trò** (Radio: Chat / Chấm ảnh / Chuyển giọng nói), mặc định Chat; lưu `role`. Provider cũ (migration) = `chat`.
- Danh sách provider nhóm/hiện cột `role`.

### 8. Hiện API key mọi nơi
- Đổi `Input.Password` → `Input` cho ô api_key: `AdminAiProvidersPage`, `AdminMarketingAiProvidersPage`.
- `SettingRow.tsx` + `aiSupport.tsx` + `systemSettings.tsx`: bỏ mask `****`/bước reveal, hiển thị plaintext trực tiếp (backend admin đã trả clear — xác minh & chỉnh nếu còn che).

## Ngoài phạm vi (YAGNI)
- Không auto re-verify định kỳ (verify khi bấm test). Không verify embedding lại. Không đổi luồng chat/re-rank/STT ngoài phần gate.

## Xử lý lỗi
- Provider sai role/chưa verified ⇒ PUT 422, không lưu.
- Runtime re-rank/chat: model không nhận ảnh ⇒ lỗi API ⇒ NOT_RUN/không đính ⇒ fallback, không vỡ.
- Test endpoint không bao giờ 500.

## Kiểm thử
- Migration: cột role + *_verified tồn tại; default role='chat'.
- `activeProviders('chat')` không trả provider role=vision/transcription; `resolveProviderCode` bỏ qua provider non-chat.
- `analyzeImages` gửi ảnh KHÔNG cần model tên-vision (bỏ gate) — Http::fake trả JSON, assert gọi được; model lỗi ⇒ ném (fallback ở caller).
- Chat `$vision` bật theo `vision_verified` provider chat (verified ⇒ có image block; chưa ⇒ không).
- Test endpoint re-rank/STT ghi `*_verified` = true khi 200, false khi lỗi (+error).
- PUT re-rank/STT: provider verified=true ⇒ OK; null/false ⇒ 422.
- FE: build; api key hiển thị plain (không Password).

## Tương thích & triển khai
- **CẦN migration** (`ai_providers`: role + 4-6 cột verified). Provider cũ role='chat', verified=null.
- **SAU deploy:** super-admin phải **bấm Kiểm tra** lại các provider để set verified (đường chat đính ảnh + re-rank + STT tạm không hoạt động tới khi verified). Đây là chủ ý (xác minh thật).
- Gỡ `VisionModelGate` + test + config models: cập nhật mọi nơi import (OpenAi/Claude connector, AdminVisualRerankController badge).
- Docs: cập nhật endpoints (verified trong response) + ghi chú deploy.

## Quan hệ với công việc đang dở
- Transcription SDD (Tasks 1–4 đã commit) giữ nguyên; job/exception/interface/cột transcript dùng lại. Task 5 (STT admin controller) + Task 7 (FE) sẽ theo mô hình role+verified này. Task 1 đã thêm marker vision (sẽ bị gỡ cùng VisionModelGate) — phần max_tokens giữ lại.

## Các file đụng tới (chính)
- Migration `..._add_role_and_verified_to_ai_providers.php` (mới).
- `AiProvider.php` (fillable/casts/@property), `AiAssistantRegistry.php` (activeProviders role).
- `OpenAiConnector.php` + `ClaudeConnector.php` (bỏ visionEnabled/gate; chat $vision theo verified).
- **Xóa** `Integrations/Ai/Support/VisionModelGate.php` + test; `config/ai.php` (bỏ `vision.models`).
- `AiSuggestionService.php` (resolveProviderCode role=chat; imageUrlsFor/$vision theo verified).
- `AdminAiProviderController.php` (role field + verify persist + present role/verified), `AdminVisualRerankController.php` (badge verified + PUT gate + test ghi cờ), `AdminTranscriptionController.php` (mới, role+verified).
- FE: `AdminAiProvidersPage.tsx` (role + Input key), `AdminMarketingAiProvidersPage.tsx` (Input key), `AdminVisualRerankPage.tsx` + lib (badge verified + Lưu gate), `AdminTranscriptionPage.tsx` + lib (mới), `SettingRow.tsx`/`aiSupport.tsx`/`systemSettings.tsx` (bỏ mask), `AdminApp.tsx`/`AdminLayout.tsx`.
- `docs/05-api/endpoints.md`.
</content>
