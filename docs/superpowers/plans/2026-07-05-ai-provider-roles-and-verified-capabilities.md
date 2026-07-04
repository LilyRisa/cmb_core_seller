# AI provider roles + verified capabilities + reveal API key — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tách provider AI theo vai trò (chat/vision/transcription) để không lẫn sang chat; thay badge "đoán tên" bằng xác minh THẬT (probe API, lưu cờ verified, chỉ cho chọn khi đạt); bỏ hẳn gate theo tên; hiện API key rõ ở admin. Đồng thời hoàn tất màn STT.

**Architecture:** Thêm cột `role` + `vision_verified`/`transcription_verified` vào `ai_providers`; `AiAssistantRegistry::activeProviders($role)` lọc theo vai trò; xoá `VisionModelGate` + gate tên, runtime dựa cờ verified/attempt-fallback; endpoint test (re-rank + STT) probe thật rồi ghi cờ; PUT chọn provider chặn nếu chưa verified.

**Tech Stack:** Laravel 11 (PHP 8.3), PHPUnit, React 18 + Ant Design + TanStack Query (admin bundle), Vite.

## Global Constraints
- Mọi lệnh PHP/Node từ `app/`. Namespace `CMBcoreSeller\` → `app/app/`.
- `config()`/`system_setting()`, không `env()` ngoài config. Integration layer không import `app/Modules/*`.
- Envelope `{ "data": ... }`; admin guard `web`+`auth:admin_web`, 401 khi chưa đăng nhập.
- UI icon `@ant-design/icons`; ưu tiên `Radio.Group`. Chuỗi tiếng Việt; identifier tiếng Anh.
- **Không dùng tên model làm lớp lọc ở bất kỳ đâu.** Năng lực = cờ verified (probe thật).
- Chỉ lưu provider làm re-rank/STT khi cờ verified=true cho năng lực đó (rỗng=tắt luôn cho).
- Non-breaking dữ liệu: provider cũ `role='chat'`, verified=null. Sau deploy phải test lại để bật.
- Gate cuối: `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test` (lọc), `npm run typecheck && npm run build`.

**Bối cảnh SDD đang dở (giữ nguyên, đã commit):** Transcription Tasks 1–4 (max_tokens config; AudioTranscriber + OpenAiConnector::transcribeAudio; cột `message_attachments.transcript`; job `TranscribeInboundAudio`). Plan NÀY thay cho Task 5–8 cũ và retrofit các phần đã ship.

---

## Task 1: Migration role + verified + RuntimeConfig.visionVerified

**Files:**
- Create: `app/app/Modules/Messaging/Database/Migrations/2026_07_05_100002_add_role_and_verified_to_ai_providers.php`
- Modify: `app/app/Modules/Messaging/Models/AiProvider.php` (fillable/casts/@property)
- Modify: `app/app/Integrations/Ai/DTO/AiProviderRuntimeConfig.php` (thêm `visionVerified`)
- Modify: `app/app/Modules/Messaging/Services/DbAiProviderCredentials.php` (populate)
- Test: `app/tests/Feature/Messaging/AiProviderRoleVerifiedTest.php`

**Interfaces:**
- Produces: cột `ai_providers.role` (default 'chat'), `vision_verified`/`transcription_verified` (bool null), `*_verified_at`, `*_verify_error`; `AiProviderRuntimeConfig->visionVerified: bool`.

- [ ] **Step 1: Test — FAIL**

Create `app/tests/Feature/Messaging/AiProviderRoleVerifiedTest.php`:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Ai\Contracts\AiProviderCredentials;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AiProviderRoleVerifiedTest extends TestCase
{
    use RefreshDatabase;

    public function test_columns_and_defaults(): void
    {
        foreach (['role','vision_verified','vision_verified_at','vision_verify_error','transcription_verified','transcription_verified_at','transcription_verify_error'] as $c) {
            $this->assertTrue(Schema::hasColumn('ai_providers', $c), "missing {$c}");
        }
        $p = AiProvider::query()->create(['code'=>'x','adapter'=>'openai_compatible','is_active'=>true,'base_url'=>'https://h','default_model'=>'m']);
        $this->assertSame('chat', $p->fresh()->role);
        $this->assertNull($p->fresh()->vision_verified);
    }

    public function test_runtime_config_carries_vision_verified(): void
    {
        AiProvider::query()->create(['code'=>'v','adapter'=>'openai_compatible','is_active'=>true,'role'=>'vision','base_url'=>'https://h','default_model'=>'m','vision_verified'=>true]);
        $cfg = app(AiProviderCredentials::class)->resolve('v');
        $this->assertTrue($cfg->visionVerified);
    }
}
```

- [ ] **Step 2: Chạy — FAIL**

Run: `php artisan test --filter=AiProviderRoleVerifiedTest`
Expected: FAIL (cột/thuộc tính chưa có).

- [ ] **Step 3: Migration**

Create `app/app/Modules/Messaging/Database/Migrations/2026_07_05_100002_add_role_and_verified_to_ai_providers.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_providers', function (Blueprint $table) {
            $table->string('role', 20)->default('chat')->after('adapter');
            $table->boolean('vision_verified')->nullable()->after('role');
            $table->timestamp('vision_verified_at')->nullable()->after('vision_verified');
            $table->string('vision_verify_error', 255)->nullable()->after('vision_verified_at');
            $table->boolean('transcription_verified')->nullable()->after('vision_verify_error');
            $table->timestamp('transcription_verified_at')->nullable()->after('transcription_verified');
            $table->string('transcription_verify_error', 255)->nullable()->after('transcription_verified_at');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('ai_providers', function (Blueprint $table) {
            $table->dropColumn(['role','vision_verified','vision_verified_at','vision_verify_error','transcription_verified','transcription_verified_at','transcription_verify_error']);
        });
    }
};
```

- [ ] **Step 4: Model**

Trong `app/app/Modules/Messaging/Models/AiProvider.php`:
- Thêm vào `$fillable`: `'role','vision_verified','vision_verified_at','vision_verify_error','transcription_verified','transcription_verified_at','transcription_verify_error'`.
- Trong `casts()`: thêm `'vision_verified' => 'boolean'`, `'vision_verified_at' => 'datetime'`, `'transcription_verified' => 'boolean'`, `'transcription_verified_at' => 'datetime'`.
- Thêm `@property` docblock: `string $role`, `bool|null $vision_verified`, `\Illuminate\Support\Carbon|null $vision_verified_at`, `string|null $vision_verify_error`, `bool|null $transcription_verified`, `\Illuminate\Support\Carbon|null $transcription_verified_at`, `string|null $transcription_verify_error`.

- [ ] **Step 5: RuntimeConfig + resolver**

Trong `app/app/Integrations/Ai/DTO/AiProviderRuntimeConfig.php`, thêm param cuối constructor:
```php
        public bool $visionVerified = false,
```
Trong `app/app/Modules/Messaging/Services/DbAiProviderCredentials.php`, ở `new AiProviderRuntimeConfig(...)` thêm:
```php
            visionVerified: (bool) $row->vision_verified,
```

- [ ] **Step 6: Chạy — PASS + Commit**

Run: `php artisan test --filter=AiProviderRoleVerifiedTest`
Expected: PASS (2 tests).

```bash
git add app/app/Modules/Messaging/Database/Migrations/2026_07_05_100002_add_role_and_verified_to_ai_providers.php app/app/Modules/Messaging/Models/AiProvider.php app/app/Integrations/Ai/DTO/AiProviderRuntimeConfig.php app/app/Modules/Messaging/Services/DbAiProviderCredentials.php app/tests/Feature/Messaging/AiProviderRoleVerifiedTest.php
git commit -m "feat(ai): ai_providers role + cờ verified + RuntimeConfig.visionVerified"
```

---

## Task 2: Registry lọc theo role + cập nhật mọi caller

**Files:**
- Modify: `app/app/Integrations/Ai/AiAssistantRegistry.php` (`activeProviders`)
- Modify: `app/app/Modules/Messaging/Services/AiSuggestionService.php` (`resolveProviderCode`)
- Modify: `app/app/Modules/VisualSearch/Services/VisionReRanker.php` (override → 'vision')
- Modify: `app/app/Modules/Messaging/Jobs/TranscribeInboundAudio.php` (→ 'transcription')
- Test: `app/tests/Feature/Messaging/ProviderRoleFilterTest.php`

**Interfaces:**
- Consumes: `ai_providers.role` (Task 1).
- Produces: `AiAssistantRegistry::activeProviders(string $role = 'chat'): array` (lọc theo role).

- [ ] **Step 1: Test — FAIL**

Create `app/tests/Feature/Messaging/ProviderRoleFilterTest.php`:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderRoleFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_providers_filters_by_role(): void
    {
        AiProvider::query()->create(['code'=>'chat1','adapter'=>'openai_compatible','is_active'=>true,'role'=>'chat','base_url'=>'https://h','default_model'=>'m']);
        AiProvider::query()->create(['code'=>'vis1','adapter'=>'openai_compatible','is_active'=>true,'role'=>'vision','base_url'=>'https://h','default_model'=>'m']);
        AiProvider::query()->create(['code'=>'stt1','adapter'=>'openai_compatible','is_active'=>true,'role'=>'transcription','base_url'=>'https://h','default_model'=>'m']);

        $reg = app(AiAssistantRegistry::class);
        $this->assertSame(['chat1'], $reg->activeProviders());          // default chat
        $this->assertSame(['vis1'], $reg->activeProviders('vision'));
        $this->assertSame(['stt1'], $reg->activeProviders('transcription'));
    }
}
```

- [ ] **Step 2: Chạy — FAIL**

Run: `php artisan test --filter=ProviderRoleFilterTest`
Expected: FAIL (activeProviders chưa nhận role).

- [ ] **Step 3: Registry**

Trong `app/app/Integrations/Ai/AiAssistantRegistry.php`, đổi `activeProviders()`:

```php
    public function activeProviders(string $role = 'chat'): array
    {
        try {
            return AiProvider::query()
                ->where('is_active', true)
                ->where('role', $role)
                ->orderBy('sort_order')->orderBy('code')
                ->get(['code', 'adapter'])
                ->filter(fn ($r) => isset($this->adapters[$r->adapter]))
                ->pluck('code')->values()->all();
        } catch (\Throwable) {
            return [];
        }
    }
```

- [ ] **Step 4: Cập nhật caller re-rank + STT**

`app/app/Modules/VisualSearch/Services/VisionReRanker.php`: trong `pick()`, đổi
`in_array($override, $this->registry->activeProviders(), true)` → `in_array($override, $this->registry->activeProviders('vision'), true)`.

`app/app/Modules/Messaging/Jobs/TranscribeInboundAudio.php`: đổi
`in_array($code, $registry->activeProviders(), true)` → `in_array($code, $registry->activeProviders('transcription'), true)`.

`resolveProviderCode` trong `AiSuggestionService` đã gọi `activeProviders()` (mặc định 'chat') — không cần đổi, nhưng THÊM guard: dòng kiểm `$chosen` phải thuộc `activeProviders('chat')` (đã đúng vì default). Không sửa.

- [ ] **Step 5: Test caller (thêm vào ProviderRoleFilterTest)**

```php
    public function test_chat_default_ignores_non_chat_providers(): void
    {
        // vision provider sort_order 0 KHÔNG được thành chat mặc định.
        AiProvider::query()->create(['code'=>'visA','adapter'=>'openai_compatible','is_active'=>true,'role'=>'vision','sort_order'=>0,'base_url'=>'https://h','default_model'=>'m']);
        AiProvider::query()->create(['code'=>'chatB','adapter'=>'openai_compatible','is_active'=>true,'role'=>'chat','sort_order'=>1,'base_url'=>'https://h','default_model'=>'m']);
        $this->assertSame(['chatB'], app(AiAssistantRegistry::class)->activeProviders());
    }
```

- [ ] **Step 6: Chạy — PASS + Commit**

Run: `php artisan test --filter="ProviderRoleFilterTest|VisionRerankProviderTest|TranscribeInboundAudioTest"`
Expected: PASS. (Nếu VisionRerankProviderTest/TranscribeInboundAudioTest fail vì provider test chưa set role, sửa test data thêm `'role'=>'vision'`/`'transcription'` cho provider re-rank/STT trong các file đó.)

```bash
git add app/app/Integrations/Ai/AiAssistantRegistry.php app/app/Modules/VisualSearch/Services/VisionReRanker.php app/app/Modules/Messaging/Jobs/TranscribeInboundAudio.php app/tests/Feature/Messaging/ProviderRoleFilterTest.php
git commit -m "feat(ai): activeProviders lọc theo role; chat không lẫn vision/transcription"
```

---

## Task 3: Bỏ VisionModelGate + gate tên; runtime theo verified

**Files:**
- Delete: `app/app/Integrations/Ai/Support/VisionModelGate.php`, `app/tests/Unit/Integrations/Ai/VisionModelGateTest.php`
- Modify: `app/config/ai.php` (bỏ `vision.models`)
- Modify: `app/app/Integrations/Ai/OpenAi/OpenAiConnector.php` + `.../Claude/ClaudeConnector.php`
- Test: `app/tests/Feature/Messaging/VisionNoNameGateTest.php`

**Interfaces:**
- Consumes: `AiProviderRuntimeConfig->visionVerified` (Task 1).
- Produces: `analyzeImages` không gate tên; chat `$vision` = `$cfg->visionVerified`.

- [ ] **Step 1: Test — FAIL**

Create `app/tests/Feature/Messaging/VisionNoNameGateTest.php`:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\OpenAi\OpenAiConnector;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VisionNoNameGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_analyze_images_attempts_regardless_of_model_name(): void
    {
        // Model tên KHÔNG "vision" vẫn phải được gửi ảnh (không gate tên).
        AiProvider::query()->create(['code'=>'p','adapter'=>'openai_compatible','is_active'=>true,'role'=>'vision','base_url'=>'https://api.x.com','default_model'=>'mn/Minimax-M3','vision_verified'=>true]);
        Http::fake(['api.x.com/*' => Http::response(['choices'=>[['message'=>['content'=>'{"match":1}']]]], 200)]);

        $out = app()->makeWith(OpenAiConnector::class, ['code'=>'p'])->analyzeImages(
            new AiContext(tenantId: 1, providerCode: 'p'), ['data:image/png;base64,AAAA'], 'pick');

        $this->assertStringContainsString('match', $out);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/chat/completions'));
    }
}
```

- [ ] **Step 2: Chạy — FAIL**

Run: `php artisan test --filter=VisionNoNameGateTest`
Expected: FAIL (gate tên ném UnsupportedOperation cho 'mn/Minimax-M3').

- [ ] **Step 3: Bỏ gate tên trong 2 connector**

`OpenAiConnector.php`:
- Trong `analyzeImages`: **xoá** 3 dòng `if (! $this->visionEnabled($model)) { throw UnsupportedOperation::for(...); }`.
- Trong `generateReply` (dòng ~91): đổi `$this->visionEnabled($model)` → `$this->config(false)->visionVerified` (hoặc dùng `$cfg->visionVerified` nếu `$cfg` đã có trong scope — kiểm biến local; nếu chưa, gọi `$this->config(false)->visionVerified`).
- **Xoá** method `private function visionEnabled(...)`.
- Nếu `UnsupportedOperation` không còn dùng chỗ khác trong file, để pint/phpstan tự báo — gỡ import nếu thừa.

`ClaudeConnector.php`: y hệt — xoá gate trong `analyzeImages`, `buildMessages(..., $this->visionEnabled($model))` → `buildMessages($conversation, $cfg->visionVerified)` (dùng `$cfg` đã resolve trong generateReply; nếu tên biến khác, dùng `$this->credentials->resolve($this->code())?->visionVerified ?? false`), xoá method `visionEnabled`.

- [ ] **Step 4: Xoá VisionModelGate + config models**

- Xoá file `app/app/Integrations/Ai/Support/VisionModelGate.php` và `app/tests/Unit/Integrations/Ai/VisionModelGateTest.php`.
- Trong `app/config/ai.php`: xoá khoá `'models' => [...]` trong block `vision` (giữ `enabled`, `max_tokens`, `max_images_per_message`, `inline_base64`, `inline_max_kb`).
- Grep đảm bảo không còn ai import `VisionModelGate`: `grep -rn VisionModelGate app/` — 0 kết quả (AdminVisualRerankController badge sẽ đổi ở Task 5).

Ghi chú: nếu `AdminVisualRerankController` hiện còn `use ...VisionModelGate`, tạm để Task 5 xử lý; nhưng nếu build/phpstan gãy ngay, đổi badge tạm sang `false` ở controller đó trong Task này rồi Task 5 hoàn thiện. Ưu tiên: chạy được.

- [ ] **Step 5: Chạy — PASS + Commit**

Run: `php artisan test --filter="VisionNoNameGateTest|AiProviderHttpTest"`
Expected: PASS.

```bash
git add -A app/app/Integrations/Ai app/config/ai.php app/tests/Feature/Messaging/VisionNoNameGateTest.php
git rm app/app/Integrations/Ai/Support/VisionModelGate.php app/tests/Unit/Integrations/Ai/VisionModelGateTest.php 2>/dev/null; git add -A
git commit -m "refactor(ai): bỏ VisionModelGate + gate tên; runtime vision theo cờ verified"
```

---

## Task 4: AdminAiProviderController — role trong CRUD + present verified

**Files:**
- Modify: `app/app/Modules/Messaging/Http/Controllers/AdminAiProviderController.php`
- Test: `app/tests/Feature/Messaging/AdminAiProviderRoleTest.php`

**Interfaces:**
- Produces: store/update nhận `role` (in `chat|vision|transcription`); `present()` trả `role`, `vision_verified`, `transcription_verified`, `*_verified_at`, `*_verify_error`.

- [ ] **Step 1: Test — FAIL**

Create `app/tests/Feature/Messaging/AdminAiProviderRoleTest.php`:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAiProviderRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_accepts_role_and_present_exposes_verified(): void
    {
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin, 'admin_web')->postJson('/api/v1/admin/ai-providers', [
            'code'=>'groq','adapter'=>'openai_compatible','role'=>'transcription',
            'base_url'=>'https://api.groq.com/openai/v1','default_model'=>'whisper-large-v3-turbo',
        ])->assertCreated()->assertJsonPath('data.role','transcription');

        AiProvider::query()->whereKey('groq')->update(['vision_verified'=>false,'vision_verify_error'=>'no image']);
        $row = $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/ai-providers')->json('data');
        $g = collect($row)->firstWhere('code','groq');
        $this->assertSame('transcription',$g['role']);
        $this->assertFalse($g['vision_verified']);
    }

    public function test_store_rejects_bad_role(): void
    {
        $this->actingAs(AdminUser::factory()->create(),'admin_web')->postJson('/api/v1/admin/ai-providers', [
            'code'=>'x','adapter'=>'openai_compatible','role'=>'bogus','base_url'=>'https://h','default_model'=>'m',
        ])->assertStatus(422);
    }
}
```

- [ ] **Step 2: Chạy — FAIL**

Run: `php artisan test --filter=AdminAiProviderRoleTest`
Expected: FAIL (role chưa validate/persist/present).

- [ ] **Step 3: Sửa controller**

Trong `AdminAiProviderController.php`:
- `store()` rules: thêm `'role' => ['nullable', Rule::in(['chat','vision','transcription'])]`.
- `update()` rules: thêm `'role' => ['nullable', Rule::in(['chat','vision','transcription'])]`.
- `present()`: thêm vào mảng trả về:
```php
            'role' => $p->role ?? 'chat',
            'vision_verified' => $p->vision_verified,
            'vision_verified_at' => $p->vision_verified_at?->toIso8601String(),
            'vision_verify_error' => $p->vision_verify_error,
            'transcription_verified' => $p->transcription_verified,
            'transcription_verified_at' => $p->transcription_verified_at?->toIso8601String(),
            'transcription_verify_error' => $p->transcription_verify_error,
```

- [ ] **Step 4: Chạy — PASS + Commit**

Run: `php artisan test --filter=AdminAiProviderRoleTest`
Expected: PASS (2 tests).

```bash
git add app/app/Modules/Messaging/Http/Controllers/AdminAiProviderController.php app/tests/Feature/Messaging/AdminAiProviderRoleTest.php
git commit -m "feat(admin): provider CRUD nhận role + phơi cờ verified"
```

---

## Task 5: Retrofit AdminVisualRerankController — verify thật + PUT gate

**Files:**
- Modify: `app/app/Modules/VisualSearch/Http/Controllers/AdminVisualRerankController.php`
- Test: `app/tests/Feature/VisualSearch/AdminVisualRerankTest.php` (mở rộng)

**Interfaces:**
- Consumes: `AiProvider.vision_verified`, `activeProviders('vision')`.
- Produces: GET trả provider `role=vision` + `vision_verified`/`*_at`/`*_error`; `test` ghi `vision_verified`; `update` 422 nếu chưa verified.

- [ ] **Step 1: Test — FAIL (mở rộng file test hiện có)**

Thêm vào `app/tests/Feature/VisualSearch/AdminVisualRerankTest.php`:

```php
    public function test_test_endpoint_persists_vision_verified(): void
    {
        \CMBcoreSeller\Modules\Messaging\Models\AiProvider::query()->create(['code'=>'rr','adapter'=>'openai_compatible','is_active'=>true,'role'=>'vision','api_key'=>'k','base_url'=>'https://api.x.com','default_model'=>'m']);
        \Illuminate\Support\Facades\Http::fake(['api.x.com/*' => \Illuminate\Support\Facades\Http::response(['choices'=>[['message'=>['content'=>'{"match":0}']]]],200)]);
        $this->actingAs(\CMBcoreSeller\Models\AdminUser::factory()->create(),'admin_web')
            ->postJson('/api/v1/admin/ai-visual-rerank/test',['provider_code'=>'rr'])->assertOk()->assertJsonPath('data.ok',true);
        $this->assertTrue(\CMBcoreSeller\Modules\Messaging\Models\AiProvider::find('rr')->vision_verified);
    }

    public function test_put_requires_verified(): void
    {
        \CMBcoreSeller\Modules\Messaging\Models\AiProvider::query()->create(['code'=>'rr','adapter'=>'openai_compatible','is_active'=>true,'role'=>'vision','base_url'=>'https://h','default_model'=>'m']);
        $admin = \CMBcoreSeller\Models\AdminUser::factory()->create();
        $this->actingAs($admin,'admin_web')->putJson('/api/v1/admin/ai-visual-rerank',['provider_code'=>'rr'])->assertStatus(422); // chưa verified
        \CMBcoreSeller\Modules\Messaging\Models\AiProvider::query()->whereKey('rr')->update(['vision_verified'=>true]);
        $this->actingAs($admin,'admin_web')->putJson('/api/v1/admin/ai-visual-rerank',['provider_code'=>'rr'])->assertOk();
    }
```

Lưu ý: các test cũ trong file này dùng `activeProviders()` mặc định — cập nhật provider seed thêm `'role'=>'vision'` để chúng vẫn xanh.

- [ ] **Step 2: Chạy — FAIL**

Run: `php artisan test --filter=AdminVisualRerankTest`
Expected: FAIL (test ghi cờ / PUT gate chưa có).

- [ ] **Step 3: Sửa controller**

Trong `AdminVisualRerankController.php`:
- Bỏ `use ...VisionModelGate;` nếu còn.
- `index()`: liệt kê `AiProvider::query()->where('role','vision')->orderBy('sort_order')->orderBy('code')->get()`, mỗi provider trả `code,display_name,default_model,is_active` + `vision_verified`, `vision_verified_at?->toIso8601String()`, `vision_verify_error` (KHÔNG còn field `vision` đoán tên).
- `update()`: sau kiểm active/role, thêm: nếu `$code !== ''` và `AiProvider::find($code)?->vision_verified !== true` ⇒ 422 `{error:{code:PROVIDER_NOT_VERIFIED, message:'Provider chưa xác minh vision — hãy Gửi ảnh thử tới khi thành công.'}}`. Kiểm active bằng `activeProviders('vision')`.
- `test()`: sau `analyzeImages` thành công ⇒ `AiProvider::whereKey($code)->update(['vision_verified'=>true,'vision_verified_at'=>now(),'vision_verify_error'=>null])`; khi catch lỗi ⇒ `update(['vision_verified'=>false,'vision_verify_error'=>Str::limit($e->getMessage(),240)])` rồi trả `ok:false`.

- [ ] **Step 4: Chạy — PASS + Commit**

Run: `php artisan test --filter=AdminVisualRerankTest`
Expected: PASS.

```bash
git add app/app/Modules/VisualSearch/Http/Controllers/AdminVisualRerankController.php app/tests/Feature/VisualSearch/AdminVisualRerankTest.php
git commit -m "feat(admin): re-rank verify vision thật + chốt chọn theo verified"
```

---

## Task 6: AdminTranscriptionController (mới) + routes — role + verify + PUT gate

**Files:**
- Create: `app/app/Modules/Messaging/Http/Controllers/AdminTranscriptionController.php`
- Modify: `app/app/Modules/Messaging/Http/routes.php`
- Test: `app/tests/Feature/Messaging/AdminTranscriptionTest.php`

**Interfaces:**
- Produces (guard admin_web, prefix `api/v1/admin/ai-transcription`): `GET` provider `role=transcription` + `transcription_verified`; `PUT {provider_code}` (422 nếu chưa verified); `POST test` (probe WAV mẫu, ghi `transcription_verified`).

- [ ] **Step 1: Test — FAIL**

Create `app/tests/Feature/Messaging/AdminTranscriptionTest.php`:

```php
<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminTranscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function stt(): void
    {
        AiProvider::query()->create(['code'=>'groq','adapter'=>'openai_compatible','is_active'=>true,'role'=>'transcription','api_key'=>'k','base_url'=>'https://api.groq.com/openai/v1','default_model'=>'whisper-large-v3-turbo']);
    }

    public function test_index_lists_only_transcription_role(): void
    {
        $this->stt();
        AiProvider::query()->create(['code'=>'chatx','adapter'=>'openai_compatible','is_active'=>true,'role'=>'chat','base_url'=>'https://h','default_model'=>'m']);
        $res = $this->actingAs(AdminUser::factory()->create(),'admin_web')->getJson('/api/v1/admin/ai-transcription')->assertOk()->json('data');
        $this->assertSame(['groq'], collect($res['providers'])->pluck('code')->all());
    }

    public function test_test_persists_and_put_requires_verified(): void
    {
        $this->stt();
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin,'admin_web')->putJson('/api/v1/admin/ai-transcription',['provider_code'=>'groq'])->assertStatus(422);
        Http::fake(['api.groq.com/*' => Http::response(['text'=>'ok'],200)]);
        $this->actingAs($admin,'admin_web')->postJson('/api/v1/admin/ai-transcription/test',['provider_code'=>'groq'])->assertOk()->assertJsonPath('data.ok',true);
        $this->assertTrue(AiProvider::find('groq')->transcription_verified);
        $this->actingAs($admin,'admin_web')->putJson('/api/v1/admin/ai-transcription',['provider_code'=>'groq'])->assertOk();
        $this->assertSame('groq', system_setting('messaging.transcription.provider_code'));
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

- [ ] **Step 3: Controller**

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

/** Super-admin chọn provider STT (role=transcription, phải verified). `/api/v1/admin/ai-transcription`. */
class AdminTranscriptionController extends Controller
{
    private const KEY = 'messaging.transcription.provider_code';

    public function __construct(private AiAssistantRegistry $registry, private SystemSettingService $settings) {}

    public function index(): JsonResponse
    {
        $providers = AiProvider::query()->where('role', 'transcription')->orderBy('sort_order')->orderBy('code')->get()
            ->map(fn (AiProvider $p) => [
                'code' => $p->code, 'display_name' => $p->display_name, 'default_model' => $p->default_model,
                'is_active' => (bool) $p->is_active, 'transcription_verified' => $p->transcription_verified,
                'transcription_verified_at' => $p->transcription_verified_at?->toIso8601String(),
                'transcription_verify_error' => $p->transcription_verify_error,
            ])->values()->all();

        return response()->json(['data' => [
            'selected_provider_code' => (string) system_setting(self::KEY, '') ?: null,
            'providers' => $providers,
        ]]);
    }

    public function update(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('provider_code', ''));
        if ($code !== '') {
            if (! in_array($code, $this->registry->activeProviders('transcription'), true)) {
                return response()->json(['error' => ['code' => 'PROVIDER_NOT_ACTIVE', 'message' => 'Provider không tồn tại hoặc chưa bật.']], 422);
            }
            if (AiProvider::find($code)?->transcription_verified !== true) {
                return response()->json(['error' => ['code' => 'PROVIDER_NOT_VERIFIED', 'message' => 'Provider chưa xác minh STT — hãy Thử transcribe tới khi thành công.']], 422);
            }
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
                AiProvider::whereKey($code)->update(['transcription_verified' => false, 'transcription_verify_error' => 'connector không hỗ trợ STT']);

                return response()->json(['data' => ['ok' => false, 'reason' => 'unsupported', 'message' => 'Provider không hỗ trợ STT.']]);
            }
            $wav = base64_decode('UklGRiQAAABXQVZFZm10IBAAAAABAAEAQB8AAIA+AAACABAAZGF0YQAAAAA=');
            $out = $connector->transcribeAudio(new AiContext(tenantId: 0, providerCode: $code, meta: ['mode' => 'transcription_test']), $wav, 'audio/wav', 'test.wav');
            AiProvider::whereKey($code)->update(['transcription_verified' => true, 'transcription_verified_at' => now(), 'transcription_verify_error' => null]);

            return response()->json(['data' => ['ok' => true, 'text' => Str::limit($out, 120)]]);
        } catch (\Throwable $e) {
            AiProvider::whereKey($code)->update(['transcription_verified' => false, 'transcription_verify_error' => Str::limit($e->getMessage(), 240)]);

            return response()->json(['data' => ['ok' => false, 'reason' => 'error', 'message' => Str::limit($e->getMessage(), 200)]]);
        }
    }
}
```

- [ ] **Step 4: Routes**

Trong `app/app/Modules/Messaging/Http/routes.php`: import `AdminTranscriptionController` và thêm nhóm (cạnh nhóm admin khác):
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
git commit -m "feat(admin): endpoint STT theo role+verified (test-tới-khi-đạt)"
```

---

## Task 7: FE — re-rank badge verified + STT page + provider form role/key

**Files:**
- Modify: `app/resources/js/admin/lib/visualRerank.tsx` + `pages/settings/AdminVisualRerankPage.tsx`
- Create: `app/resources/js/admin/lib/aiTranscription.tsx` + `pages/settings/AdminTranscriptionPage.tsx`
- Modify: `app/resources/js/admin/AdminApp.tsx` + `AdminLayout.tsx`
- Modify: `app/resources/js/admin/pages/settings/AdminAiProvidersPage.tsx` (role + Input key)

Verify: `npm run typecheck && npm run build` (từ `app/`).

- [ ] **Step 1: Re-rank lib — đổi type badge sang verified**

Trong `app/resources/js/admin/lib/visualRerank.tsx`, đổi interface `RerankProvider`: thay `vision: boolean` bằng:
```tsx
    vision_verified: boolean | null;
    vision_verified_at: string | null;
    vision_verify_error: string | null;
```
Thêm hook test:
```tsx
export interface RerankTestResult { ok: boolean; sample?: string; reason?: string; message?: string }
export function useTestVisualRerank() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (providerCode: string): Promise<RerankTestResult> =>
            (await api.post<{ data: RerankTestResult }>('/admin/ai-visual-rerank/test', { provider_code: providerCode })).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['visual-rerank-config'] }),
    });
}
```
(Giữ `useVisualRerank`, `useSaveVisualRerank`.)

- [ ] **Step 2: Re-rank page — badge verified + Lưu gate**

Trong `AdminVisualRerankPage.tsx`: dùng `useTestVisualRerank`; badge theo `vision_verified`:
```tsx
                                {p.vision_verified === true
                                    ? <Tag color="green" icon={<CheckCircleOutlined />}>Đã xác minh</Tag>
                                    : p.vision_verified === false
                                        ? <Tag color="red" icon={<CloseCircleOutlined />}>Thất bại</Tag>
                                        : <Tag icon={<QuestionCircleOutlined />}>Chưa kiểm tra</Tag>}
```
(import `QuestionCircleOutlined`). Nút "Lưu": `disabled={selected !== NONE && !(data?.providers.find(p=>p.code===selected)?.vision_verified === true)}`. Nút "Gửi ảnh thử" gọi `useTestVisualRerank`. Alert nhắc "phải Gửi ảnh thử thành công mới lưu được".

- [ ] **Step 3: STT lib + page (mới)**

Create `app/resources/js/admin/lib/aiTranscription.tsx`:
```tsx
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface SttProvider { code: string; display_name: string | null; default_model: string | null; is_active: boolean; transcription_verified: boolean | null; transcription_verified_at: string | null; transcription_verify_error: string | null }
export interface SttConfig { selected_provider_code: string | null; providers: SttProvider[] }
export interface SttTestResult { ok: boolean; text?: string; reason?: string; message?: string }

export function useTranscriptionConfig() {
    return useQuery({ queryKey: ['ai-transcription-config'], queryFn: async (): Promise<SttConfig> => (await api.get<{ data: SttConfig }>('/admin/ai-transcription')).data.data });
}
export function useSaveTranscription() {
    const qc = useQueryClient();
    return useMutation({ mutationFn: async (c: string) => { await api.put('/admin/ai-transcription', { provider_code: c }); }, onSuccess: () => qc.invalidateQueries({ queryKey: ['ai-transcription-config'] }) });
}
export function useTestTranscription() {
    const qc = useQueryClient();
    return useMutation({ mutationFn: async (c: string): Promise<SttTestResult> => (await api.post<{ data: SttTestResult }>('/admin/ai-transcription/test', { provider_code: c })).data.data, onSuccess: () => qc.invalidateQueries({ queryKey: ['ai-transcription-config'] }) });
}
```

Create `app/resources/js/admin/pages/settings/AdminTranscriptionPage.tsx` — giống `AdminVisualRerankPage` nhưng dùng hooks STT, tiêu đề "AI chuyển giọng nói (STT)", icon `AudioOutlined`, badge theo `transcription_verified`, nút "Thử transcribe", Lưu gate theo `transcription_verified===true`, radio "(Không cấu hình)"=tắt. (Toàn bộ code render giống page re-rank ở Step 2, thay field.)

- [ ] **Step 4: Route + menu**

`AdminApp.tsx`: import + `<Route path="ai-transcription" element={<AdminTranscriptionPage />} />` (sau ai-visual-rerank).
`AdminLayout.tsx`: import `AudioOutlined`; thêm item `{ key:'/admin/ai-transcription', icon:<AudioOutlined/>, label:'AI chuyển giọng nói' }` sau ai-visual-rerank.

- [ ] **Step 5: Provider form — role + Input key**

`AdminAiProvidersPage.tsx`:
- Thêm `Form.Item name="role"` (mặc định 'chat') dùng `Radio.Group`: options Chat/Chấm ảnh/Chuyển giọng nói (values chat/vision/transcription). initialValues/onFinish gửi `role`.
- Đổi ô api_key từ `Input.Password` → `Input` (hiện rõ). (Tìm Form.Item api_key trong file.)
- Thêm cột `role` vào bảng.

- [ ] **Step 6: Verify + Commit**

Run: `cd app && npm run typecheck && npm run build`
Expected: PASS.

```bash
git add app/resources/js/admin/lib/visualRerank.tsx app/resources/js/admin/pages/settings/AdminVisualRerankPage.tsx app/resources/js/admin/lib/aiTranscription.tsx app/resources/js/admin/pages/settings/AdminTranscriptionPage.tsx app/resources/js/admin/AdminApp.tsx app/resources/js/admin/AdminLayout.tsx app/resources/js/admin/pages/settings/AdminAiProvidersPage.tsx
git commit -m "feat(admin-ui): badge verified + trang STT + role + hiện API key ở provider"
```

---

## Task 8: FE — bỏ che API key ở settings/support/marketing

**Files:**
- Modify: `app/resources/js/admin/components/SettingRow.tsx` (+ `SecretInput` nếu dùng password)
- Modify: `app/resources/js/admin/lib/aiSupport.tsx` (bỏ bước reveal, hiện thẳng)
- Modify: `app/resources/js/admin/pages/settings/AdminMarketingAiProvidersPage.tsx` (Input.Password → Input)

- [ ] **Step 1: SecretInput hiện rõ**

Tìm component `SecretInput` (dùng ở `SettingRow.tsx:35`). Nếu nó render `Input.Password`, đổi sang `Input` (hiện plaintext). Giá trị đã truyền plaintext (`row.value ?? row.env_fallback`).

- [ ] **Step 2: aiSupport hiện thẳng key**

Trong `app/resources/js/admin/lib/aiSupport.tsx`: bỏ nhánh `reveal()` (gọi endpoint reveal) — hiển thị `str(chatApiKey)`/`str(embeddingApiKey)` trực tiếp. Nếu backend index trả `****` cho secret, giữ 1 lần reveal là chấp nhận; nhưng mục tiêu là KHÔNG che khi hiển thị trên form. Đảm bảo ô nhập api_key là `Input` (không Password) ở `AdminAiSupportPage.tsx`.

- [ ] **Step 3: Marketing provider Input**

`AdminMarketingAiProvidersPage.tsx:78`: đổi `<Input.Password placeholder="sk-..." />` → `<Input placeholder="sk-..." />`.

- [ ] **Step 4: Verify + Commit**

Run: `cd app && npm run typecheck && npm run build`
Expected: PASS.

```bash
git add app/resources/js/admin/components/SettingRow.tsx app/resources/js/admin/lib/aiSupport.tsx app/resources/js/admin/pages/settings/AdminAiSupportPage.tsx app/resources/js/admin/pages/settings/AdminMarketingAiProvidersPage.tsx
git commit -m "feat(admin-ui): hiện API key rõ (bỏ mask Password) ở settings/support/marketing"
```

---

## Task 9: Docs + quality gate

**Files:**
- Modify: `docs/05-api/endpoints.md`

- [ ] **Step 1: Docs**

Cập nhật `docs/05-api/endpoints.md`: bổ sung `role` + `*_verified` vào response provider; ghi 3 endpoint STT `/api/v1/admin/ai-transcription`; ghi chú re-rank/STT PUT yêu cầu verified; ghi chú deploy (migrate + test lại provider).

- [ ] **Step 2: Quality gate**

Từ `app/`:
```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test --filter="AiProviderRoleVerifiedTest|ProviderRoleFilterTest|VisionNoNameGateTest|AiProviderHttpTest|AdminAiProviderRoleTest|AdminVisualRerankTest|AdminTranscriptionTest|OpenAiTranscribeTest|TranscribeInboundAudioTest|MessageAttachmentTranscriptTest"
npm run typecheck && npm run build
```
Expected: file của tính năng pint sạch (fix file của ta nếu cần); phpstan không lỗi MỚI của ta; test PASS; build OK. Pre-existing baseline fails không liên quan.

- [ ] **Step 3: Manual verify (controller làm)**

`/admin` → "Nhà cung cấp AI" tạo provider vai trò Chấm ảnh (omni) + Chuyển giọng nói (Groq), thấy API key rõ. Vào "AI chấm ảnh"/"AI chuyển giọng nói": bấm test tới khi ✓ mới Lưu được; provider vision/STT KHÔNG xuất hiện làm chat mặc định.

- [ ] **Step 4: Commit**

```bash
git add docs/05-api/endpoints.md
git commit -m "docs(api): provider role + verified + endpoint STT"
```

---

## Self-Review
- **Spec coverage:** role cột+registry (T1,T2) ✓; bỏ VisionModelGate+gate tên+chat theo verified (T3) ✓; provider CRUD role+present verified (T4) ✓; re-rank verify thật+PUT gate (T5) ✓; STT controller role+verify+PUT gate (T6) ✓; FE badge verified+STT+role+key (T7) ✓; bỏ mask key (T8) ✓; docs+deploy (T9) ✓; chat resolveProviderCode role=chat (T2) ✓.
- **Placeholder scan:** không TBD; code/lệnh đầy đủ (T7 STT page nói "giống page re-rank" — implementer lặp lại code từ Step 2, đã nêu rõ field thay).
- **Type consistency:** `activeProviders(string $role='chat')` T2 dùng ở T5/T6; `vision_verified`/`transcription_verified` nhất quán DTO/controller/FE; `AiProviderRuntimeConfig->visionVerified` T1 dùng T3; hooks STT tên khớp lib↔page.
</content>
