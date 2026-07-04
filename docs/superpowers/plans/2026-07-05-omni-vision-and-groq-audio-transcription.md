# Omni vision + Groq Audio Transcription Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** (1) Cho model reasoning-vision (omni) chạy được ở bước chấm ảnh bằng cách nới `max_tokens` (config-driven, mặc định 2048) + nhận diện marker vision; (2) chuyển giọng nói khách (Facebook voice) → text bằng Groq Whisper rồi nạp cho AI, cấu hình provider trong admin, lỗi retry 3 lần rồi bỏ qua.

**Architecture:** Phần 1 sửa 3 file config/connector. Phần 2 thêm interface phân tách `AudioTranscriber` (chỉ OpenAiConnector implement — Groq OpenAI-compatible), job `TranscribeInboundAudio` (tries=3) dispatch sau khi tải voice, cột `message_attachments.transcript`, màn hình admin riêng chọn provider STT (mirror "AI chấm ảnh"), và nạp transcript vào `AiSuggestionService`.

**Tech Stack:** Laravel 11 (PHP 8.3), PHPUnit, React 18 + Ant Design + TanStack Query (admin bundle), Vite, Groq OpenAI-compatible API.

## Global Constraints

- Mọi lệnh PHP/Node chạy từ `app/`. Namespace `CMBcoreSeller\` → `app/app/`.
- Dùng `config()`/`system_setting()`, không `env()` ngoài file config.
- Integration layer (`app/app/Integrations/*`) KHÔNG import `app/Modules/*`.
- Response envelope `{ "data": ... }`; endpoint admin guard `web`+`auth:admin_web`, không tenant, 401 khi chưa đăng nhập.
- UI icon `@ant-design/icons` (không emoji); ưu tiên `Radio.Group` hơn `<Select>`.
- Chuỗi hiển thị tiếng Việt; code/identifier tiếng Anh.
- Non-breaking: chưa cấu hình provider STT ⇒ hành vi y hệt hiện tại (AI thấy `[audio]`). Transcription lỗi ⇒ bỏ qua, không vỡ luồng.
- Quality gate: `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test` (lọc), `npm run typecheck && npm run build`.
- Groq: `POST {base}/v1/audio/transcriptions`, multipart `file`+`model`, `response_format=json` ⇒ `{"text": "..."}`. Base_url provider = `https://api.groq.com/openai/v1` (connector tự bỏ `/v1` đuôi).

---

## File Structure

**Phần 1:** `config/ai.php` (vision.max_tokens + models markers), `OpenAiConnector.php` + `ClaudeConnector.php` (analyzeImages max_tokens config-driven).

**Phần 2 — mới:**
- `app/app/Integrations/Ai/Contracts/AudioTranscriber.php` — interface phân tách.
- `app/app/Integrations/Ai/Exceptions/TranscriptionFailed.php` — exception.
- `app/app/Modules/Messaging/Database/Migrations/2026_07_05_100001_add_transcript_to_message_attachments.php`.
- `app/app/Modules/Messaging/Jobs/TranscribeInboundAudio.php` — job STT.
- `app/app/Modules/Messaging/Http/Controllers/AdminTranscriptionController.php` — admin.
- `app/resources/js/admin/lib/aiTranscription.tsx` + `pages/settings/AdminTranscriptionPage.tsx`.

**Phần 2 — sửa:** `OpenAiConnector.php` (implements + transcribeAudio + capability), `MessageAttachment.php` (fillable), `DownloadInboundMedia.php` (dispatch), `AiSuggestionService.php` (chèn transcript), `SystemSettingsCatalog.php` (key), `VisualSearch`… không; `Messaging/Http/routes.php` (routes admin), `AdminApp.tsx` + `AdminLayout.tsx`, `docs/05-api/endpoints.md`.

---

## Task 1: Nới max_tokens vision (config-driven) + marker omni/-vl

**Files:**
- Modify: `app/config/ai.php` (block `vision`)
- Modify: `app/app/Integrations/Ai/OpenAi/OpenAiConnector.php:246` (max_tokens)
- Modify: `app/app/Integrations/Ai/Claude/ClaudeConnector.php:257` (max_tokens)
- Test: `app/tests/Unit/Integrations/Ai/VisionModelGateTest.php` (mở rộng) + `app/tests/Feature/Messaging/AiProviderHttpTest.php` (thêm 1 test)

**Interfaces:**
- Produces: `config('ai.vision.max_tokens')` (int, default 2048); `config('ai.vision.models')` default gồm `omni`, `-vl`.

- [ ] **Step 1: Thêm test VisionModelGate cho omni/-vl (dùng config default thật)**

Thêm vào `app/tests/Unit/Integrations/Ai/VisionModelGateTest.php`:

```php
    public function test_default_config_recognizes_nvidia_multimodal_variants(): void
    {
        // KHÔNG override config ⇒ dùng default trong config/ai.php.
        $this->assertTrue(VisionModelGate::enabledFor('nvidia/nemotron-3-nano-omni-30b-a3b-reasoning'));
        $this->assertTrue(VisionModelGate::enabledFor('nvidia/nemotron-nano-12b-v2-vl'));
    }
```

- [ ] **Step 2: Chạy test — FAIL**

Run: `php artisan test --filter=test_default_config_recognizes_nvidia_multimodal_variants`
Expected: FAIL (default `ai.vision.models` chưa có `omni`/`-vl`).

- [ ] **Step 3: Sửa config/ai.php**

Trong `app/config/ai.php`, block `vision`:
- Trong chuỗi default của `AI_VISION_MODELS` (dòng ~47), thêm `,omni,-vl` vào cuối trước `'`:
  đổi `'claude-3,...,o4,gemini'` → `'claude-3,...,o4,gemini,omni,-vl'`.
- Thêm khóa `max_tokens` vào mảng `vision` (sau `inline_max_kb`):
```php
        // Trần token cho analyzeImages. Model reasoning (vd nemotron omni) tiêu token
        // "suy nghĩ" trước khi ra JSON ⇒ 300 dễ cắt cụt. Nới rộng để hoàn tất.
        'max_tokens' => (int) env('AI_VISION_MAX_TOKENS', 2048),
```

- [ ] **Step 4: Sửa 2 connector dùng config max_tokens**

`app/app/Integrations/Ai/OpenAi/OpenAiConnector.php` — trong `analyzeImages`, đổi:
```php
                'max_tokens' => 300,
```
thành:
```php
                'max_tokens' => (int) config('ai.vision.max_tokens', 2048),
```
Áp y hệt cho `app/app/Integrations/Ai/Claude/ClaudeConnector.php` trong `analyzeImages` (dòng `'max_tokens' => 300,`).

- [ ] **Step 5: Thêm test assert max_tokens=2048 gửi đi**

Thêm vào `app/tests/Feature/Messaging/AiProviderHttpTest.php`:

```php
    public function test_analyze_images_uses_configured_max_tokens(): void
    {
        config()->set('ai.vision.enabled', true);
        config()->set('ai.vision.models', ['gpt-4o']);
        config()->set('ai.vision.max_tokens', 2048);

        \Illuminate\Support\Facades\Http::fake([
            '*/chat/completions' => \Illuminate\Support\Facades\Http::response(
                ['choices' => [['message' => ['content' => '{"match":1}']]]], 200),
        ]);

        AiProvider::query()->create([
            'code' => 'vis', 'adapter' => 'openai_compatible', 'is_active' => true,
            'api_key' => 'sk-x', 'base_url' => 'https://api.openai.com', 'default_model' => 'gpt-4o',
        ]);

        app(OpenAiConnector::class)->analyzeImages(
            new AiContext(tenantId: 1, providerCode: 'vis'),
            ['data:image/png;base64,AAAA'],
            'pick one',
        );

        \Illuminate\Support\Facades\Http::assertSent(fn ($req) => ($req->data()['max_tokens'] ?? null) === 2048);
    }
```

- [ ] **Step 6: Chạy test — PASS**

Run: `php artisan test --filter="VisionModelGateTest|AiProviderHttpTest"`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/config/ai.php app/app/Integrations/Ai/OpenAi/OpenAiConnector.php app/app/Integrations/Ai/Claude/ClaudeConnector.php app/tests/Unit/Integrations/Ai/VisionModelGateTest.php app/tests/Feature/Messaging/AiProviderHttpTest.php
git commit -m "feat(ai): analyzeImages max_tokens config-driven (2048) + marker vision omni/-vl"
```

---

## Task 2: AudioTranscriber interface + TranscriptionFailed + OpenAiConnector::transcribeAudio

**Files:**
- Create: `app/app/Integrations/Ai/Contracts/AudioTranscriber.php`
- Create: `app/app/Integrations/Ai/Exceptions/TranscriptionFailed.php`
- Modify: `app/app/Integrations/Ai/OpenAi/OpenAiConnector.php` (implements + method + capability)
- Test: `app/tests/Feature/Messaging/OpenAiTranscribeTest.php`

**Interfaces:**
- Produces: `AudioTranscriber::transcribeAudio(AiContext $ctx, string $bytes, string $mime, ?string $filename = null): string`; `OpenAiConnector` implements it; capability key `transcribe.audio`; `TranscriptionFailed::http(string $code, int $status): self`.

- [ ] **Step 1: Viết test — FAIL**

Create `app/tests/Feature/Messaging/OpenAiTranscribeTest.php`:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\Exceptions\TranscriptionFailed;
use CMBcoreSeller\Integrations\Ai\OpenAi\OpenAiConnector;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiTranscribeTest extends TestCase
{
    use RefreshDatabase;

    private function groqProvider(): void
    {
        AiProvider::query()->create([
            'code' => 'groq', 'adapter' => 'openai_compatible', 'is_active' => true,
            'api_key' => 'gsk-x', 'base_url' => 'https://api.groq.com/openai/v1',
            'default_model' => 'whisper-large-v3-turbo',
        ]);
    }

    public function test_transcribe_returns_text(): void
    {
        $this->groqProvider();
        Http::fake(['api.groq.com/*' => Http::response(['text' => 'xin chào shop'], 200)]);

        $out = app(OpenAiConnector::class)->transcribeAudio(
            new AiContext(tenantId: 1, providerCode: 'groq'), 'RAWBYTES', 'audio/mpeg', 'voice.mp3');

        $this->assertSame('xin chào shop', $out);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/v1/audio/transcriptions'));
    }

    public function test_transcribe_throws_on_http_error(): void
    {
        $this->groqProvider();
        Http::fake(['api.groq.com/*' => Http::response('nope', 500)]);

        $this->expectException(TranscriptionFailed::class);
        app(OpenAiConnector::class)->transcribeAudio(
            new AiContext(tenantId: 1, providerCode: 'groq'), 'RAW', 'audio/mpeg', 'v.mp3');
    }

    public function test_capability_advertised(): void
    {
        $this->groqProvider();
        $this->assertTrue(app(OpenAiConnector::class)->supports('transcribe.audio'));
    }
}
```

- [ ] **Step 2: Chạy — FAIL**

Run: `php artisan test --filter=OpenAiTranscribeTest`
Expected: FAIL (interface/method/exception chưa có).

- [ ] **Step 3: Tạo exception**

Create `app/app/Integrations/Ai/Exceptions/TranscriptionFailed.php`:

```php
<?php

namespace CMBcoreSeller\Integrations\Ai\Exceptions;

use RuntimeException;

/** Transcription (STT) thất bại — job retry, hết lần thì bỏ qua. */
class TranscriptionFailed extends RuntimeException
{
    public static function http(string $providerCode, int $status): self
    {
        return new self("AI provider [{$providerCode}] transcription HTTP {$status}.");
    }
}
```

- [ ] **Step 4: Tạo interface**

Create `app/app/Integrations/Ai/Contracts/AudioTranscriber.php`:

```php
<?php

namespace CMBcoreSeller\Integrations\Ai\Contracts;

use CMBcoreSeller\Integrations\Ai\DTO\AiContext;

/**
 * Năng lực PHÂN TÁCH: transcribe audio → text (STT). Chỉ connector OpenAI-compatible
 * (vd Groq whisper) implement; core kiểm `instanceof AudioTranscriber`. Tách khỏi
 * AiAssistantConnector để không ép mọi connector implement.
 */
interface AudioTranscriber
{
    /**
     * @throws \CMBcoreSeller\Integrations\Ai\Exceptions\TranscriptionFailed khi API lỗi (caller retry).
     */
    public function transcribeAudio(AiContext $ctx, string $bytes, string $mime, ?string $filename = null): string;
}
```

- [ ] **Step 5: OpenAiConnector implements + method + capability**

Trong `app/app/Integrations/Ai/OpenAi/OpenAiConnector.php`:
- Thêm import: `use CMBcoreSeller\Integrations\Ai\Contracts\AudioTranscriber;` và `use CMBcoreSeller\Integrations\Ai\Exceptions\TranscriptionFailed;`
- Đổi khai báo class: `class OpenAiConnector implements AiAssistantConnector, AudioTranscriber`
- Trong `capabilities()`, thêm dòng: `'transcribe.audio' => true,`
- Thêm method (đặt cạnh `analyzeImages`):

```php
    public function transcribeAudio(AiContext $ctx, string $bytes, string $mime, ?string $filename = null): string
    {
        $cfg = $this->config();
        $model = $ctx->model ?: $cfg->defaultModel;
        if (! $model) {
            throw new ProviderNotConfigured('OpenAI provider cần default_model (STT).');
        }

        $response = Http::withToken($cfg->apiKey)
            ->connectTimeout((int) config('ai.http.connect_timeout', 10))
            ->timeout((int) config('ai.http.reply_timeout', 60))
            ->attach('file', $bytes, $filename ?: 'audio.mp3')
            ->post($this->base($cfg).'/v1/audio/transcriptions', [
                'model' => $model,
                'response_format' => 'json',
            ]);

        if (! $response->successful()) {
            throw TranscriptionFailed::http($this->code(), $response->status());
        }

        return trim((string) $response->json('text', ''));
    }
```

- [ ] **Step 6: Chạy — PASS**

Run: `php artisan test --filter=OpenAiTranscribeTest`
Expected: PASS (3 tests).

- [ ] **Step 7: Commit**

```bash
git add app/app/Integrations/Ai/Contracts/AudioTranscriber.php app/app/Integrations/Ai/Exceptions/TranscriptionFailed.php app/app/Integrations/Ai/OpenAi/OpenAiConnector.php app/tests/Feature/Messaging/OpenAiTranscribeTest.php
git commit -m "feat(ai): AudioTranscriber + OpenAiConnector transcribeAudio (Groq whisper)"
```

---

## Task 3: Migration cột transcript + fillable

**Files:**
- Create: `app/app/Modules/Messaging/Database/Migrations/2026_07_05_100001_add_transcript_to_message_attachments.php`
- Modify: `app/app/Modules/Messaging/Models/MessageAttachment.php` (fillable)
- Test: `app/tests/Feature/Messaging/MessageAttachmentTranscriptTest.php`

**Interfaces:**
- Produces: cột `message_attachments.transcript` (nullable text); `MessageAttachment` fillable gồm `transcript`.

- [ ] **Step 1: Viết test — FAIL**

Create `app/tests/Feature/Messaging/MessageAttachmentTranscriptTest.php`:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MessageAttachmentTranscriptTest extends TestCase
{
    use RefreshDatabase;

    public function test_transcript_column_exists_and_fillable(): void
    {
        $this->assertTrue(Schema::hasColumn('message_attachments', 'transcript'));
        $att = new MessageAttachment;
        $att->fill(['transcript' => 'xin chào']);
        $this->assertSame('xin chào', $att->transcript);
    }
}
```

- [ ] **Step 2: Chạy — FAIL**

Run: `php artisan test --filter=MessageAttachmentTranscriptTest`
Expected: FAIL (cột chưa có / không fillable).

- [ ] **Step 3: Tạo migration**

Create `app/app/Modules/Messaging/Database/Migrations/2026_07_05_100001_add_transcript_to_message_attachments.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_attachments', function (Blueprint $table) {
            $table->text('transcript')->nullable()->after('filename');
        });
    }

    public function down(): void
    {
        Schema::table('message_attachments', function (Blueprint $table) {
            $table->dropColumn('transcript');
        });
    }
};
```

Ghi chú: nếu `filename` không tồn tại, bỏ `->after('filename')` (đặt cuối bảng cũng được).

- [ ] **Step 4: Thêm 'transcript' vào fillable**

Trong `app/app/Modules/Messaging/Models/MessageAttachment.php`, thêm `'transcript'` vào mảng `$fillable`.

- [ ] **Step 5: Chạy — PASS**

Run: `php artisan test --filter=MessageAttachmentTranscriptTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Messaging/Database/Migrations/2026_07_05_100001_add_transcript_to_message_attachments.php app/app/Modules/Messaging/Models/MessageAttachment.php app/tests/Feature/Messaging/MessageAttachmentTranscriptTest.php
git commit -m "feat(messaging): cột message_attachments.transcript"
```

---

## Task 4: Job TranscribeInboundAudio + dispatch

**Files:**
- Modify: `app/app/Modules/Settings/Support/SystemSettingsCatalog.php` (key)
- Create: `app/app/Modules/Messaging/Jobs/TranscribeInboundAudio.php`
- Modify: `app/app/Modules/Messaging/Jobs/DownloadInboundMedia.php` (dispatch)
- Test: `app/tests/Feature/Messaging/TranscribeInboundAudioTest.php`

**Interfaces:**
- Consumes: `AudioTranscriber` (Task 2), `system_setting('messaging.transcription.provider_code')`, `AiCreditMeter`, `MediaStorage::disk()`, `AiAssistantRegistry::for()/activeProviders()`.
- Produces: `TranscribeInboundAudio` (job, tries=3), lưu `attachment->transcript`.

- [ ] **Step 1: Thêm catalog key**

Trong `app/app/Modules/Settings/Support/SystemSettingsCatalog.php`, cạnh khóa `visual_search.rerank.provider_code`, thêm:

```php
            'messaging.transcription.provider_code' => [
                'group' => 'ai', 'type' => 'string', 'is_secret' => false,
                'env' => 'MESSAGING_TRANSCRIPTION_PROVIDER_CODE', 'label' => 'AI chuyển giọng nói — Provider',
                'description' => 'Code provider AI (OpenAI-compatible, vd Groq whisper) để transcribe ghi âm khách. Rỗng ⇒ tắt.',
            ],
```

- [ ] **Step 2: Viết test — FAIL**

Create `app/tests/Feature/Messaging/TranscribeInboundAudioTest.php`:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter;
use CMBcoreSeller\Modules\Messaging\Jobs\TranscribeInboundAudio;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TranscribeInboundAudioTest extends TestCase
{
    use RefreshDatabase;

    private int $recorded = 0;

    private function fakeCredits(bool $canUse = true): void
    {
        $test = $this;
        $this->app->instance(AiCreditMeter::class, new class($test, $canUse) implements AiCreditMeter
        {
            public function __construct(private $t, private bool $can) {}
            public function aiEnabled(int $x): bool { return true; }
            public function canUse(int $x, int $n = 1): bool { return $this->can; }
            public function consume(int $x, int $n = 1): void {}
            public function record(int $x, int $n = 1): void { $this->t->recorded += $n; }
            public function grantPurchase(int $x, int $a): int { return $a; }
            public function summary(int $x): array { return ['enabled'=>true,'unlimited'=>true,'monthly_allowance'=>0,'period_used'=>0,'purchased_balance'=>0,'available'=>null]; }
        });
    }

    private function makeAudioAttachment(int $tenantId): MessageAttachment
    {
        Storage::fake('local');
        config()->set('messaging.media_disk', 'local');
        $conv = Conversation::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId, 'provider' => 'facebook_page', 'external_conversation_id' => 'c1',
        ]);
        $msg = Message::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId, 'conversation_id' => $conv->id, 'direction' => 'inbound',
            'kind' => 'audio', 'external_message_id' => 'm1',
        ]);
        $path = "tenants/{$tenantId}/messaging/voice.mp3";
        Storage::disk('local')->put($path, 'RAWAUDIO');

        return MessageAttachment::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId, 'message_id' => $msg->id, 'kind' => 'audio',
            'mime' => 'audio/mpeg', 'status' => 'downloaded', 'storage_path' => $path, 'filename' => 'voice.mp3',
        ]);
    }

    public function test_transcribes_and_saves_and_records_credit(): void
    {
        $tenant = Tenant::factory()->create();
        $this->fakeCredits();
        AiProvider::query()->create(['code'=>'groq','adapter'=>'openai_compatible','is_active'=>true,'api_key'=>'gsk','base_url'=>'https://api.groq.com/openai/v1','default_model'=>'whisper-large-v3-turbo']);
        app(SystemSettingService::class)->set('messaging.transcription.provider_code', 'groq');
        Http::fake(['api.groq.com/*' => Http::response(['text' => 'cho em hỏi giá'], 200)]);

        $att = $this->makeAudioAttachment($tenant->id);
        (new TranscribeInboundAudio($att->id))->handle();

        $this->assertSame('cho em hỏi giá', $att->fresh()->transcript);
        $this->assertSame(1, $this->recorded);
    }

    public function test_noop_when_provider_unset(): void
    {
        $tenant = Tenant::factory()->create();
        $this->fakeCredits();
        Http::fake();
        $att = $this->makeAudioAttachment($tenant->id);
        (new TranscribeInboundAudio($att->id))->handle();

        $this->assertNull($att->fresh()->transcript);
        Http::assertNothingSent();
    }

    public function test_throws_on_api_error_for_retry(): void
    {
        $tenant = Tenant::factory()->create();
        $this->fakeCredits();
        AiProvider::query()->create(['code'=>'groq','adapter'=>'openai_compatible','is_active'=>true,'api_key'=>'gsk','base_url'=>'https://api.groq.com/openai/v1','default_model'=>'whisper-large-v3-turbo']);
        app(SystemSettingService::class)->set('messaging.transcription.provider_code', 'groq');
        Http::fake(['api.groq.com/*' => Http::response('err', 500)]);

        $att = $this->makeAudioAttachment($tenant->id);
        $this->expectException(\CMBcoreSeller\Integrations\Ai\Exceptions\TranscriptionFailed::class);
        (new TranscribeInboundAudio($att->id))->handle();
    }
}
```

- [ ] **Step 3: Chạy — FAIL**

Run: `php artisan test --filter=TranscribeInboundAudioTest`
Expected: FAIL (job chưa có).

- [ ] **Step 4: Tạo job**

Create `app/app/Modules/Messaging/Jobs/TranscribeInboundAudio.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Jobs;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\Contracts\AudioTranscriber;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Messaging\Services\MediaStorage;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Transcribe 1 ghi âm inbound → text (Groq whisper), lưu `transcript`.
 * tries=3; hết lần vẫn lỗi ⇒ failed() log & bỏ qua (không vỡ luồng).
 */
class TranscribeInboundAudio implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $attachmentId)
    {
        $this->onQueue('messaging-media');
    }

    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(AiAssistantRegistry $registry, AiCreditMeter $credits, MediaStorage $media): void
    {
        $att = MessageAttachment::withoutGlobalScope(TenantScope::class)->find($this->attachmentId);
        if (! $att || $att->kind !== MessageAttachment::KIND_AUDIO
            || $att->status !== MessageAttachment::STATUS_DOWNLOADED
            || $att->transcript !== null || ! $att->storage_path) {
            return;
        }

        $code = trim((string) system_setting('messaging.transcription.provider_code', ''));
        if ($code === '' || ! in_array($code, $registry->activeProviders(), true)) {
            return;
        }

        $connector = $registry->for($code);
        if (! $connector instanceof AudioTranscriber) {
            return;
        }

        $tenantId = (int) $att->tenant_id;
        if (! $credits->canUse($tenantId, 1)) {
            return;
        }

        $bytes = (string) $media->disk()->get($att->storage_path);
        if ($bytes === '') {
            return;
        }

        $text = $connector->transcribeAudio(
            new AiContext(tenantId: $tenantId, providerCode: $code, model: null, meta: ['mode' => 'transcription']),
            $bytes,
            (string) ($att->mime ?: 'audio/mpeg'),
            (string) ($att->filename ?: 'audio.mp3'),
        );

        $credits->record($tenantId, 1);

        if ($text !== '') {
            $att->transcript = $text;
            $att->save();
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('messaging.transcription_failed', ['attachment_id' => $this->attachmentId, 'error' => $e->getMessage()]);
    }
}
```

- [ ] **Step 5: Chạy — PASS**

Run: `php artisan test --filter=TranscribeInboundAudioTest`
Expected: PASS (3 tests).

- [ ] **Step 6: Dispatch từ DownloadInboundMedia**

Trong `app/app/Modules/Messaging/Jobs/DownloadInboundMedia.php`, cuối `handle()` sau `$relay->relayInbound($attachment);`, thêm:

```php
        // Voice khách → transcribe (STT) nếu đã tải xong.
        $fresh = $attachment->fresh();
        if ($fresh && $fresh->kind === MessageAttachment::KIND_AUDIO && $fresh->status === MessageAttachment::STATUS_DOWNLOADED) {
            TranscribeInboundAudio::dispatch($fresh->id);
        }
```

- [ ] **Step 7: Test dispatch (thêm vào file test)**

Thêm vào `TranscribeInboundAudioTest`:

```php
    public function test_download_job_dispatches_transcription_for_audio(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $tenant = Tenant::factory()->create();
        $att = $this->makeAudioAttachment($tenant->id);

        (new \CMBcoreSeller\Modules\Messaging\Jobs\DownloadInboundMedia($att->id))
            ->handle(app(\CMBcoreSeller\Modules\Messaging\Services\MediaRelayService::class));

        \Illuminate\Support\Facades\Queue::assertPushed(TranscribeInboundAudio::class);
    }
```

Ghi chú: `relayInbound` với status đã `downloaded` là idempotent (skip re-download) nên an toàn; assert job được đẩy.

- [ ] **Step 8: Chạy — PASS + Commit**

Run: `php artisan test --filter=TranscribeInboundAudioTest`
Expected: PASS (4 tests).

```bash
git add app/app/Modules/Settings/Support/SystemSettingsCatalog.php app/app/Modules/Messaging/Jobs/TranscribeInboundAudio.php app/app/Modules/Messaging/Jobs/DownloadInboundMedia.php app/tests/Feature/Messaging/TranscribeInboundAudioTest.php
git commit -m "feat(messaging): job TranscribeInboundAudio (Groq STT, tries=3, skip on fail)"
```

---

## Task 5: Admin controller + routes chọn provider STT

**Files:**
- Create: `app/app/Modules/Messaging/Http/Controllers/AdminTranscriptionController.php`
- Modify: `app/app/Modules/Messaging/Http/routes.php`
- Test: `app/tests/Feature/Messaging/AdminTranscriptionTest.php`

**Interfaces:**
- Produces (guard admin_web, prefix `api/v1/admin/ai-transcription`): `GET` → `{selected_provider_code, providers:[{code,display_name,default_model,is_active,transcribe}]}`; `PUT {provider_code}` (422 nếu không active); `POST test {provider_code}`.

- [ ] **Step 1: Viết test — FAIL**

Create `app/tests/Feature/Messaging/AdminTranscriptionTest.php`:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTranscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function seed(): void
    {
        AiProvider::query()->create(['code'=>'groq','adapter'=>'openai_compatible','is_active'=>true,'base_url'=>'https://api.groq.com/openai/v1','default_model'=>'whisper-large-v3-turbo']);
        AiProvider::query()->create(['code'=>'claude','adapter'=>'anthropic','is_active'=>true,'default_model'=>'claude-opus-4-7']);
    }

    public function test_index_flags_transcribe_capability(): void
    {
        $this->seed();
        $res = $this->actingAs(AdminUser::factory()->create(), 'admin_web')
            ->getJson('/api/v1/admin/ai-transcription')->assertOk()->json('data');
        $byCode = collect($res['providers'])->keyBy('code');
        $this->assertTrue($byCode['groq']['transcribe']);
        $this->assertFalse($byCode['claude']['transcribe']);
    }

    public function test_put_saves_and_rejects_unknown(): void
    {
        $this->seed();
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin, 'admin_web')->putJson('/api/v1/admin/ai-transcription', ['provider_code'=>'groq'])->assertOk();
        $this->assertSame('groq', system_setting('messaging.transcription.provider_code'));
        $this->actingAs($admin, 'admin_web')->putJson('/api/v1/admin/ai-transcription', ['provider_code'=>'nope'])->assertStatus(422);
    }

    public function test_requires_admin(): void
    {
        $this->getJson('/api/v1/admin/ai-transcription')->assertStatus(401);
    }
}
```

- [ ] **Step 2: Chạy — FAIL**

Run: `php artisan test --filter=AdminTranscriptionTest`
Expected: FAIL (route 404).

- [ ] **Step 3: Tạo controller**

Create `app/app/Modules/Messaging/Http/Controllers/AdminTranscriptionController.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\Contracts\AudioTranscriber;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Super-admin chọn provider AI (OpenAI-compatible, vd Groq whisper) làm STT ghi âm.
 * `/api/v1/admin/ai-transcription/*` (guard admin_web). Rỗng ⇒ tắt transcription.
 */
class AdminTranscriptionController extends Controller
{
    private const KEY = 'messaging.transcription.provider_code';

    public function __construct(
        private AiAssistantRegistry $registry,
        private SystemSettingService $settings,
    ) {}

    public function index(): JsonResponse
    {
        $providers = AiProvider::query()->orderBy('sort_order')->orderBy('code')->get()
            ->map(fn (AiProvider $p) => [
                'code' => $p->code,
                'display_name' => $p->display_name,
                'default_model' => $p->default_model,
                'is_active' => (bool) $p->is_active,
                'transcribe' => $this->canTranscribe($p->code),
            ])->values()->all();

        return response()->json(['data' => [
            'selected_provider_code' => (string) system_setting(self::KEY, '') ?: null,
            'providers' => $providers,
        ]]);
    }

    public function update(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('provider_code', ''));
        if ($code !== '' && ! in_array($code, $this->registry->activeProviders(), true)) {
            return response()->json(['error' => ['code' => 'PROVIDER_NOT_ACTIVE', 'message' => 'Provider không tồn tại hoặc chưa bật.']], 422);
        }
        $this->settings->set(self::KEY, $code, Auth::guard('admin_web')->id());
        AuditLog::record('messaging.transcription.provider_set', null, ['provider_code' => $code]);

        return response()->json(['data' => ['ok' => true]]);
    }

    public function test(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('provider_code', ''));
        if ($code === '') {
            return response()->json(['data' => ['ok' => false, 'reason' => 'no_provider', 'message' => 'Chưa chọn provider.']]);
        }
        try {
            $connector = $this->registry->for($code);
            if (! $connector instanceof AudioTranscriber) {
                return response()->json(['data' => ['ok' => false, 'reason' => 'unsupported', 'message' => 'Provider không hỗ trợ STT.']]);
            }
            // WAV 16-bit mono 8kHz ~0.1s im lặng (đủ để kiểm wiring; whisper trả text rỗng).
            $wav = base64_decode('UklGRiQAAABXQVZFZm10IBAAAAABAAEAQB8AAIA+AAACABAAZGF0YQAAAAA=');
            $out = $connector->transcribeAudio(new AiContext(tenantId: 0, providerCode: $code, meta: ['mode' => 'transcription_test']), $wav, 'audio/wav', 'test.wav');

            return response()->json(['data' => ['ok' => true, 'text' => Str::limit($out, 120)]]);
        } catch (\Throwable $e) {
            return response()->json(['data' => ['ok' => false, 'reason' => 'error', 'message' => Str::limit($e->getMessage(), 200)]]);
        }
    }

    private function canTranscribe(string $code): bool
    {
        try {
            return $this->registry->for($code) instanceof AudioTranscriber;
        } catch (\Throwable) {
            return false;
        }
    }
}
```

- [ ] **Step 4: Routes**

Trong `app/app/Modules/Messaging/Http/routes.php`, thêm import `use CMBcoreSeller\Modules\Messaging\Http\Controllers\AdminTranscriptionController;` và, cạnh nhóm admin ai-providers (middleware `['web','auth:admin_web','throttle:60,1']`), thêm nhóm:

```php
Route::middleware(['web', 'auth:admin_web', 'throttle:60,1'])
    ->prefix('api/v1/admin/ai-transcription')->group(function () {
        Route::get('/', [AdminTranscriptionController::class, 'index'])->name('admin.ai-transcription.index');
        Route::put('/', [AdminTranscriptionController::class, 'update'])->name('admin.ai-transcription.update');
        Route::post('test', [AdminTranscriptionController::class, 'test'])->name('admin.ai-transcription.test');
    });
```

- [ ] **Step 5: Chạy — PASS + Commit**

Run: `php artisan test --filter=AdminTranscriptionTest`
Expected: PASS (3 tests).

```bash
git add app/app/Modules/Messaging/Http/Controllers/AdminTranscriptionController.php app/app/Modules/Messaging/Http/routes.php app/tests/Feature/Messaging/AdminTranscriptionTest.php
git commit -m "feat(admin): endpoint chọn provider STT (transcription)"
```

---

## Task 6: Nạp transcript vào ngữ cảnh AI

**Files:**
- Modify: `app/app/Modules/Messaging/Services/AiSuggestionService.php`
- Test: `app/tests/Feature/Messaging/TranscriptInSnapshotTest.php`

**Interfaces:**
- Consumes: `MessageAttachment.transcript` (Task 3).
- Produces: recentMessages body cho tin audio inbound có transcript = `[Ghi âm khách]: <transcript>`.

- [ ] **Step 1: Viết test — FAIL**

Create `app/tests/Feature/Messaging/TranscriptInSnapshotTest.php`:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;
use CMBcoreSeller\Modules\Messaging\Services\AiSuggestionService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranscriptInSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_audio_transcript_becomes_context_body(): void
    {
        $tenant = Tenant::factory()->create();
        $conv = Conversation::withoutGlobalScopes()->create(['tenant_id'=>$tenant->id,'provider'=>'facebook_page','external_conversation_id'=>'c1']);
        $msg = Message::withoutGlobalScopes()->create(['tenant_id'=>$tenant->id,'conversation_id'=>$conv->id,'direction'=>'inbound','kind'=>'audio','external_message_id'=>'m1','attachments_count'=>1]);
        MessageAttachment::withoutGlobalScopes()->create(['tenant_id'=>$tenant->id,'message_id'=>$msg->id,'kind'=>'audio','mime'=>'audio/mpeg','status'=>'downloaded','storage_path'=>'x','transcript'=>'cho em hỏi ship']);

        $text = app(AiSuggestionService::class)->transcriptFor($msg->fresh());
        $this->assertSame('[Ghi âm khách]: cho em hỏi ship', $text);
    }
}
```

- [ ] **Step 2: Chạy — FAIL**

Run: `php artisan test --filter=TranscriptInSnapshotTest`
Expected: FAIL (method `transcriptFor` chưa có).

- [ ] **Step 3: Thêm helper + dùng khi build entry**

Trong `app/app/Modules/Messaging/Services/AiSuggestionService.php`:
- Thêm method public (cạnh `imageUrlsFor`):

```php
    /** Transcript ghi âm khách (nếu có) → text ngữ cảnh; không có ⇒ null. */
    public function transcriptFor(Message $m): ?string
    {
        if ($m->direction !== Message::DIRECTION_INBOUND) {
            return null;
        }
        $t = MessageAttachment::withoutGlobalScope(TenantScope::class)
            ->where('message_id', $m->id)
            ->where('kind', MessageAttachment::KIND_AUDIO)
            ->whereNotNull('transcript')
            ->value('transcript');

        return $t ? '[Ghi âm khách]: '.trim((string) $t) : null;
    }
```

(Đảm bảo `use CMBcoreSeller\Modules\Messaging\Models\MessageAttachment;` và `TenantScope` đã import — nếu chưa, thêm.)

- Trong vòng lặp build recentMessages (chỗ tính `$body`), sau khi có `$body` từ `snapshotMessageText`, thêm: nếu `$body` rỗng thì thử transcript:

```php
            $body = $this->snapshotMessageText($m);
            if (($body === null || $body === '')) {
                $body = $this->transcriptFor($m);
            }
```

(Đặt TRƯỚC bước redact để transcript cũng được redact.)

- [ ] **Step 4: Chạy — PASS + Commit**

Run: `php artisan test --filter=TranscriptInSnapshotTest`
Expected: PASS.

```bash
git add app/app/Modules/Messaging/Services/AiSuggestionService.php app/tests/Feature/Messaging/TranscriptInSnapshotTest.php
git commit -m "feat(messaging): nạp transcript ghi âm vào ngữ cảnh AI"
```

---

## Task 7: Admin FE — trang "AI chuyển giọng nói"

**Files:**
- Create: `app/resources/js/admin/lib/aiTranscription.tsx`
- Create: `app/resources/js/admin/pages/settings/AdminTranscriptionPage.tsx`
- Modify: `app/resources/js/admin/AdminApp.tsx` (route)
- Modify: `app/resources/js/admin/AdminLayout.tsx` (menu)

Không có JS test runner; verify bằng `npm run typecheck && npm run build` (từ `app/`).

- [ ] **Step 1: Lib hooks**

Create `app/resources/js/admin/lib/aiTranscription.tsx`:

```tsx
// Hooks trang "AI chuyển giọng nói" (/admin/ai-transcription) — chọn provider STT.
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface SttProvider {
    code: string;
    display_name: string | null;
    default_model: string | null;
    is_active: boolean;
    transcribe: boolean;
}
export interface SttConfig {
    selected_provider_code: string | null;
    providers: SttProvider[];
}
export interface SttTestResult { ok: boolean; text?: string; reason?: string; message?: string }

export function useTranscriptionConfig() {
    return useQuery({
        queryKey: ['ai-transcription-config'],
        queryFn: async (): Promise<SttConfig> =>
            (await api.get<{ data: SttConfig }>('/admin/ai-transcription')).data.data,
    });
}
export function useSaveTranscription() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (providerCode: string) => {
            await api.put('/admin/ai-transcription', { provider_code: providerCode });
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['ai-transcription-config'] }),
    });
}
export function useTestTranscription() {
    return useMutation({
        mutationFn: async (providerCode: string): Promise<SttTestResult> =>
            (await api.post<{ data: SttTestResult }>('/admin/ai-transcription/test', { provider_code: providerCode })).data.data,
    });
}
```

- [ ] **Step 2: Page**

Create `app/resources/js/admin/pages/settings/AdminTranscriptionPage.tsx`:

```tsx
// Trang admin "AI chuyển giọng nói" — chọn provider STT (Groq whisper) cho ghi âm khách.
import { useEffect, useState } from 'react';
import { Card, Radio, Space, Typography, Tag, Button, Alert, message } from 'antd';
import { AudioOutlined, CheckCircleOutlined, CloseCircleOutlined, ExperimentOutlined } from '@ant-design/icons';
import { useTranscriptionConfig, useSaveTranscription, useTestTranscription } from '../../lib/aiTranscription';

const NONE = '';

export function AdminTranscriptionPage() {
    const { data, isLoading } = useTranscriptionConfig();
    const save = useSaveTranscription();
    const test = useTestTranscription();
    const [selected, setSelected] = useState<string>(NONE);

    useEffect(() => { if (data) setSelected(data.selected_provider_code ?? NONE); }, [data]);

    const onSave = async () => { await save.mutateAsync(selected); message.success('Đã lưu provider chuyển giọng nói.'); };
    const onTest = async () => {
        if (selected === NONE) { message.warning('Chọn một provider để thử.'); return; }
        const r = await test.mutateAsync(selected);
        if (r.ok) message.success(`Thử OK. Kết quả: ${r.text || '(rỗng)'}`);
        else message.error(`Thử thất bại: ${r.message ?? r.reason ?? 'lỗi'}`);
    };

    return (
        <Card loading={isLoading} title={<Space><AudioOutlined /> AI chuyển giọng nói (STT)</Space>}>
            <Alert type="info" showIcon style={{ marginBottom: 16 }}
                message="Provider này chuyển ghi âm khách gửi thành text để AI đọc được."
                description="Chưa chọn ⇒ tắt (AI chỉ thấy nhãn [audio]). Tạo provider Groq (OpenAI-compatible, base_url https://api.groq.com/openai/v1, model whisper-large-v3-turbo) ở trang 'Nhà cung cấp AI' rồi chọn tại đây." />
            <Radio.Group value={selected} onChange={(e) => setSelected(e.target.value)}>
                <Space direction="vertical" style={{ width: '100%' }}>
                    <Radio value={NONE}><Typography.Text strong>(Không cấu hình)</Typography.Text><Typography.Text type="secondary"> — tắt chuyển giọng nói</Typography.Text></Radio>
                    {(data?.providers ?? []).filter((p) => p.is_active).map((p) => (
                        <Radio key={p.code} value={p.code}>
                            <Space>
                                <Typography.Text strong>{p.display_name || p.code}</Typography.Text>
                                <Typography.Text code>{p.default_model}</Typography.Text>
                                {p.transcribe
                                    ? <Tag color="green" icon={<CheckCircleOutlined />}>Hỗ trợ STT</Tag>
                                    : <Tag color="red" icon={<CloseCircleOutlined />}>Không hỗ trợ</Tag>}
                            </Space>
                        </Radio>
                    ))}
                </Space>
            </Radio.Group>
            <div style={{ marginTop: 24 }}>
                <Space>
                    <Button type="primary" onClick={onSave} loading={save.isPending}>Lưu</Button>
                    <Button icon={<ExperimentOutlined />} onClick={onTest} loading={test.isPending} disabled={selected === NONE}>Thử transcribe</Button>
                </Space>
            </div>
        </Card>
    );
}
```

- [ ] **Step 3: Route**

Trong `app/resources/js/admin/AdminApp.tsx`: thêm import `import { AdminTranscriptionPage } from './pages/settings/AdminTranscriptionPage';` và route sau `ai-visual-rerank`:
```tsx
                    <Route path="ai-transcription" element={<AdminTranscriptionPage />} />
```

- [ ] **Step 4: Menu**

Trong `app/resources/js/admin/AdminLayout.tsx`: thêm `AudioOutlined` vào import icons (`@ant-design/icons`), và item vào `SIDEBAR_ITEMS` sau `ai-visual-rerank`:
```tsx
    { key: '/admin/ai-transcription', icon: <AudioOutlined />, label: 'AI chuyển giọng nói' },
```

- [ ] **Step 5: Verify + Commit**

Run: `cd app && npm run typecheck && npm run build`
Expected: PASS.

```bash
git add app/resources/js/admin/lib/aiTranscription.tsx app/resources/js/admin/pages/settings/AdminTranscriptionPage.tsx app/resources/js/admin/AdminApp.tsx app/resources/js/admin/AdminLayout.tsx
git commit -m "feat(admin-ui): trang AI chuyển giọng nói chọn provider STT"
```

---

## Task 8: Docs + quality gate

**Files:**
- Modify: `docs/05-api/endpoints.md`

- [ ] **Step 1: Docs**

Trong `docs/05-api/endpoints.md`, thêm dưới mục admin (theo format sẵn có):

```markdown
### AI chuyển giọng nói (STT) — super-admin (`auth:admin_web`)

- `GET /api/v1/admin/ai-transcription` — provider đang chọn + danh sách provider (cờ `transcribe`).
- `PUT /api/v1/admin/ai-transcription` — body `{ provider_code }` (rỗng = tắt); 422 nếu provider chưa bật.
- `POST /api/v1/admin/ai-transcription/test` — body `{ provider_code }`, gửi clip mẫu kiểm STT.
```

- [ ] **Step 2: Quality gate**

Chạy từ `app/` (repo KHÔNG globally green — có lỗi pre-existing không liên quan):
```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test --filter="VisionModelGateTest|AiProviderHttpTest|OpenAiTranscribeTest|MessageAttachmentTranscriptTest|TranscribeInboundAudioTest|AdminTranscriptionTest|TranscriptInSnapshotTest"
npm run typecheck && npm run build
```
Expected: file của tính năng này pint sạch (nếu pint báo file của ta → `vendor/bin/pint <file>` rồi commit); phpstan không lỗi MỚI tham chiếu file của ta (baseline pre-existing OK); các test PASS; FE build OK.

Ghi chú: pre-existing fails (GHN/fulfillment, Billing price) là baseline (`test-verify-baseline`), không liên quan.

- [ ] **Step 3: Manual verify (controller làm riêng — không phải subagent)**

Tạo provider Groq ở `/admin` → "Nhà cung cấp AI"; vào "AI chuyển giọng nói" chọn nó → "Thử transcribe"; gửi 1 voice FB → kiểm `message_attachments.transcript` có text + AI reply dựa trên nội dung.

- [ ] **Step 4: Commit**

```bash
git add docs/05-api/endpoints.md
git commit -m "docs(api): endpoint admin AI chuyển giọng nói (STT)"
```

---

## Self-Review

- **Spec coverage:** Phần 1 max_tokens+markers → Task 1 ✓. AudioTranscriber+exception+transcribeAudio → Task 2 ✓. Cột transcript+migration → Task 3 ✓. Job tries=3+dispatch+credit+skip → Task 4 ✓. Catalog key → Task 4 ✓. Admin controller+routes+test endpoint → Task 5 ✓. Nạp transcript vào AI → Task 6 ✓. FE màn hình riêng → Task 7 ✓. Docs → Task 8 ✓.
- **Placeholder scan:** không TBD/TODO; mọi step có code/lệnh.
- **Type consistency:** `transcribeAudio(AiContext,string,string,?string):string` nhất quán Task 2/4/5; `system_setting('messaging.transcription.provider_code')` nhất quán Task 4/5; `transcriptFor(Message):?string` Task 6; capability key `transcribe.audio` Task 2/5; hooks `useTranscriptionConfig/useSaveTranscription/useTestTranscription` khớp lib↔page Task 7.
</content>
