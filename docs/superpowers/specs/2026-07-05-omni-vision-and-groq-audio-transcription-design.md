# Omni vision re-rank + Groq Whisper audio transcription

- **Ngày:** 2026-07-05
- **Trạng thái:** Design (chờ review)
- **Liên quan:** SPEC 2026-07-05 visual re-rank provider riêng (đã ship); SPEC 2026-06-16 visual search.

Hai phần độc lập, đóng gói chung vì cùng mục tiêu "cho AI hiểu đầu vào phong phú hơn từ tin nhắn khách".

---

## Phần 1 — Bật model reasoning-vision (omni) cho re-rank (nhỏ)

### Vấn đề
`analyzeImages()` hardcode `max_tokens: 300`. Model reasoning (vd `nvidia/nemotron-3-nano-omni-30b-a3b-reasoning`) tiêu token cho "suy nghĩ" trước khi ra JSON `{"match":N}` ⇒ 300 dễ cắt cụt ⇒ parse fail ⇒ re-rank âm thầm rơi về cosine. Ngoài ra tên model NVIDIA đa phương thức không khớp `config('ai.vision.models')` ⇒ `VisionModelGate` coi là không-vision ⇒ re-rank từ chối gửi ảnh.

### Thay đổi
1. `config/ai.php` → block `vision`: thêm `'max_tokens' => (int) env('AI_VISION_MAX_TOKENS', 2048)`.
2. `OpenAiConnector::analyzeImages` và `ClaudeConnector::analyzeImages`: thay `max_tokens: 300` cứng bằng `(int) config('ai.vision.max_tokens', 2048)`.
3. `config/ai.php` → `vision.models` default: thêm `omni` và `-vl` vào danh sách (markers chính xác cho biến thể vision của NVIDIA; tránh khớp nhầm model nemotron text-only).

Non-breaking: model thường trả ngắn nên nới trần không đổi hành vi; markers chỉ mở thêm nhận diện vision.

---

## Phần 2 — Chuyển giọng nói khách → text (Groq Whisper)

### Mục tiêu
Tin nhắn ghi âm của khách (Facebook voice = `audio/mpeg`) hiện chỉ vào AI dưới nhãn `[audio]` (không nội dung). Thêm bước transcription: chuyển audio → text bằng **Groq `whisper-large-v3-turbo`** (OpenAI-compatible), nạp text vào ngữ cảnh AI để trợ lý "đọc" được lời khách nói.

### Quyết định đã chốt
- **Cách A**: dùng lại bảng `ai_providers` + màn hình "Nhà cung cấp AI" sẵn có (super-admin tạo 1 provider `openai_compatible`: base_url `https://api.groq.com/openai/v1`, model `whisper-large-v3-turbo`, api_key Groq). Một màn hình admin nhỏ RIÊNG để **chọn** provider nào làm STT (mirror màn hình "AI chấm ảnh").
- Lỗi transcription: **retry 3 lần**, hết vẫn lỗi ⇒ **bỏ qua** (không transcript, AI thấy `[audio]` như cũ), KHÔNG vỡ luồng.

### Groq STT API (đã đọc doc)
`POST https://api.groq.com/openai/v1/audio/transcriptions` · `Authorization: Bearer <key>` · multipart: `file`, `model=whisper-large-v3-turbo`, (optional) `response_format=json` (mặc định) ⇒ `{"text": "..."}`. Nhận mp3/mpga/m4a/ogg/wav/webm/flac/mp4. `base()` của connector tự bỏ `/v1` đuôi nên base_url `.../openai/v1` không nhân đôi.

### Kiến trúc

**a) Interface phân tách (segregated) — `AudioTranscriber`.**
`app/app/Integrations/Ai/Contracts/AudioTranscriber.php`:
```php
interface AudioTranscriber {
    /** @throws TranscriptionFailed khi API lỗi (job retry). Trả text đã transcribe. */
    public function transcribeAudio(AiContext $ctx, string $bytes, string $mime, ?string $filename = null): string;
}
```
Chỉ `OpenAiConnector implements AudioTranscriber` (Groq OpenAI-compatible). KHÔNG đụng Claude/Manual/CustomHttp (theo mẫu `InteractiveMessagingConnector instanceof`). `OpenAiConnector::capabilities()` thêm `'transcribe.audio' => true` (cho badge admin); các connector khác không có key này ⇒ false.

**b) `OpenAiConnector::transcribeAudio()`** — multipart POST `{base}/v1/audio/transcriptions`:
```php
$model = $ctx->model ?: $cfg->defaultModel; // vd whisper-large-v3-turbo
$res = Http::withToken($cfg->apiKey)
    ->connectTimeout(config('ai.http.connect_timeout',10))->timeout(config('ai.http.reply_timeout',60))
    ->attach('file', $bytes, $filename ?: 'audio.mp3')
    ->post($this->base($cfg).'/v1/audio/transcriptions', ['model' => $model, 'response_format' => 'json']);
if (! $res->successful()) throw TranscriptionFailed::http($this->code(), $res->status());
return trim((string) $res->json('text', ''));
```
`TranscriptionFailed` = exception mới trong `Integrations/Ai/Exceptions/`.

**c) Chọn provider STT (system_setting + màn hình admin riêng).**
- Catalog key `messaging.transcription.provider_code` (group `ai`, non-secret). Rỗng ⇒ transcription TẮT.
- Controller mới `AdminTranscriptionController` (module Messaging, guard `admin_web`), routes `/api/v1/admin/ai-transcription`:
  - `GET` → `{ selected_provider_code, providers:[{code,display_name,default_model,is_active,transcribe}] }` (`transcribe` = provider connector `supports('transcribe.audio')`).
  - `PUT` `{provider_code}` → validate active + `SystemSettingService::set`; 422 nếu không active.
  - `POST /test` `{provider_code}` → transcribe 1 clip WAV mẫu nhúng sẵn → `{ok,text?,reason?,message?}` (không bao giờ 500).
- FE: màn hình `/admin/ai-transcription` "AI chuyển giọng nói" (Radio provider + badge "Hỗ trợ STT ✓/✗" + nút "Thử transcribe" + lưu). Menu riêng, tách khỏi màn hình khác.

**d) Luồng transcribe khi nhận voice.**
- `message_attachments` thêm cột `transcript` (nullable text) — **migration**.
- Job mới `TranscribeInboundAudio` (`tries=3`, `backoff [30,120,600]`, queue `messaging-media`):
  - Load attachment (`withoutGlobalScope(TenantScope)`); guard: `kind==audio`, `status==downloaded`, chưa có `transcript`.
  - Resolve provider từ `system_setting('messaging.transcription.provider_code')`; rỗng/không active/không `instanceof AudioTranscriber` ⇒ return (no-op).
  - Credit: `AiCreditMeter::canUse(tenantId,1)` false ⇒ return; đọc bytes qua `MediaStorage::disk()->get($storage_path)`; gọi `transcribeAudio(...)`; lưu `transcript`; `record(tenantId,1)` SAU khi thành công (nhất quán luật "1 lượt/response").
  - Lỗi ⇒ throw ⇒ job retry 3 lần; `failed()` chỉ `Log::warning` (bỏ qua, KHÔNG vỡ).
- Dispatch: trong `DownloadInboundMedia::handle()` SAU `relayInbound`, nếu `attachment->kind==audio && status==downloaded` ⇒ `TranscribeInboundAudio::dispatch($attachment->id)`. (Tách job để retry/idempotent độc lập với tải media.)
- `runAs`/tenant: job dùng `withoutGlobalScope` + truyền `tenantId` từ attachment (mẫu như DownloadInboundMedia). `AiContext(tenantId, providerCode, model:null)`.

**e) Nạp transcript vào AI.**
`AiSuggestionService`: khi dựng recentMessages, với tin INBOUND có attachment audio kèm `transcript`, đặt `body = '[Ghi âm khách]: '.$transcript` (thay nhãn `[audio]`). Điểm chèn: `snapshotMessageText()` (hoặc chỗ build `$entry`). Chỉ áp khi có transcript; không có ⇒ giữ nguyên hành vi.

### Không làm (YAGNI)
- Không transcribe video (chỉ audio). Không TTS (chiều ngược). Không dịch. Không gửi audio native cho model omni (transcription đủ và rẻ hơn). Không transcribe outbound.

### Xử lý lỗi
- Provider chưa cấu hình / không active / không phải AudioTranscriber ⇒ job no-op, AI thấy `[audio]`.
- API lỗi ⇒ retry 3 ⇒ bỏ qua (failed() log).
- Hết credit ⇒ no-op.
- Test endpoint không bao giờ 500.

### Kiểm thử
- **Phần 1:** unit `analyzeImages` dùng `config('ai.vision.max_tokens')` (Http::fake assert body `max_tokens=2048`); VisionModelGate nhận `.../omni...` và `...-vl...` = vision.
- **Phần 2:**
  - `OpenAiConnector::transcribeAudio` (Http::fake `/audio/transcriptions` → `{text:'xin chào'}`; assert multipart gửi tới đúng URL, trả 'xin chào'; HTTP 500 ⇒ `TranscriptionFailed`).
  - `TranscribeInboundAudio`: provider cấu hình ⇒ lưu transcript + record credit; provider rỗng ⇒ no-op; lỗi API ⇒ throw (retry) và `failed()` không ném.
  - Admin endpoint: GET liệt kê + cờ `transcribe`; PUT lưu/422; test endpoint gọn.
  - `AiSuggestionService`: tin audio có transcript ⇒ body `[Ghi âm khách]: ...` vào snapshot.

### Tương thích & triển khai
- **CẦN migration** (`message_attachments.transcript`). Không seed.
- INERT tới khi super-admin: (1) tạo provider Groq ở "Nhà cung cấp AI", (2) vào "AI chuyển giọng nói" chọn provider + "Thử transcribe", (3) khách gửi voice ⇒ tự transcribe.
- Docs: `docs/05-api/endpoints.md` (3 endpoint admin STT).

### Các file đụng tới
**Phần 1:** `config/ai.php`, `OpenAiConnector.php`, `ClaudeConnector.php`.
**Phần 2 (mới):** `Integrations/Ai/Contracts/AudioTranscriber.php`, `Integrations/Ai/Exceptions/TranscriptionFailed.php`, `Modules/Messaging/Jobs/TranscribeInboundAudio.php`, `Modules/Messaging/Http/Controllers/AdminTranscriptionController.php`, `resources/js/admin/lib/aiTranscription.tsx`, `resources/js/admin/pages/settings/AdminTranscriptionPage.tsx`, migration `..._add_transcript_to_message_attachments.php`.
**Phần 2 (sửa):** `Integrations/Ai/OpenAi/OpenAiConnector.php` (implements + method + capability), `Modules/Messaging/Jobs/DownloadInboundMedia.php` (dispatch), `Modules/Messaging/Services/AiSuggestionService.php` (chèn transcript), `Modules/Settings/Support/SystemSettingsCatalog.php` (key), `Modules/Messaging/Http/routes.php` (routes admin), `resources/js/admin/AdminApp.tsx` + `AdminLayout.tsx` (route+menu), `docs/05-api/endpoints.md`.
</content>
