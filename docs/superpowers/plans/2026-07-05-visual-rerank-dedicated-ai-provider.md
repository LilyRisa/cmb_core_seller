# Visual re-rank — provider AI riêng: Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cho bước vision re-rank (chấm ảnh top-5) dùng một provider AI riêng do super-admin chọn ở màn hình admin độc lập, tách khỏi model chat; chưa cấu hình thì fallback model chat.

**Architecture:** `VisionReRanker::pick()` đọc `system_setting('visual_search.rerank.provider_code')`; nếu trỏ tới provider AI đang active thì thay `providerCode`/`AiContext` bằng provider đó (model = `default_model` của nó), ngược lại giữ provider chat. Một màn hình admin mới (`/admin/ai-visual-rerank`) đọc/ghi setting này qua controller riêng trong module VisualSearch và có nút gửi ảnh thử. Badge "có vision" dùng chung `VisionModelGate` với connector.

**Tech Stack:** Laravel 11 (PHP 8.3), PHPUnit, React 18 + Ant Design + TanStack Query (admin bundle), Vite.

## Global Constraints

- Mọi lệnh PHP/Node chạy từ `app/` (không phải repo root).
- Namespace `CMBcoreSeller\` → `app/app/`.
- Dùng `config()`/`system_setting()`, không `env()` ngoài file config.
- UI: icon `@ant-design/icons` (không emoji); hạn chế `<Select>`, ưu tiên `Radio.Group`.
- Chuỗi hiển thị tiếng Việt; code/identifier tiếng Anh.
- Non-breaking: không set gì ⇒ hành vi y hệt hiện tại. KHÔNG migration.
- Integration layer (`app/app/Integrations/*`) KHÔNG import `app/Modules/*`.
- Response envelope `{ "data": ... }`; endpoint admin guard `web`+`auth:admin_web`.
- Quality gate cuối phải xanh: `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test`, `npm run typecheck && npm run build`.

---

## File Structure

- **Create** `app/app/Integrations/Ai/Support/VisionModelGate.php` — 1 hàm tĩnh `enabledFor(string $model): bool` đọc `config('ai.vision')`. Nguồn sự thật duy nhất cho "model có vision".
- **Modify** `app/app/Integrations/Ai/Claude/ClaudeConnector.php` — `visionEnabled()` delegate sang gate.
- **Modify** `app/app/Integrations/Ai/OpenAi/OpenAiConnector.php` — `visionEnabled()` delegate sang gate.
- **Modify** `app/app/Modules/VisualSearch/Services/VisionReRanker.php` — override provider từ system_setting.
- **Modify** `app/app/Modules/Settings/Support/SystemSettingsCatalog.php` — thêm khóa `visual_search.rerank.provider_code`.
- **Create** `app/app/Modules/VisualSearch/Http/Controllers/AdminVisualRerankController.php` — GET/PUT/test.
- **Modify** `app/app/Modules/VisualSearch/Http/routes.php` — nhóm route admin mới.
- **Create** `app/resources/js/admin/lib/visualRerank.tsx` — hooks TanStack Query.
- **Create** `app/resources/js/admin/pages/settings/AdminVisualRerankPage.tsx` — trang UI.
- **Modify** `app/resources/js/admin/AdminApp.tsx` — thêm route.
- **Modify** `app/resources/js/admin/AdminLayout.tsx` — thêm menu item.
- **Modify** `app/config/visual_search.php` — ghi chú khóa system_setting cạnh block `rerank`.
- **Modify** `docs/05-api/endpoints.md` — 3 endpoint admin mới.
- **Create** `app/tests/Unit/Integrations/Ai/VisionModelGateTest.php`
- **Create** `app/tests/Feature/VisualSearch/VisionRerankProviderTest.php`
- **Create** `app/tests/Feature/VisualSearch/AdminVisualRerankTest.php`

---

## Task 1: VisionModelGate (nguồn sự thật "model có vision")

**Files:**
- Create: `app/app/Integrations/Ai/Support/VisionModelGate.php`
- Modify: `app/app/Integrations/Ai/Claude/ClaudeConnector.php` (method `visionEnabled`)
- Modify: `app/app/Integrations/Ai/OpenAi/OpenAiConnector.php` (method `visionEnabled`)
- Test: `app/tests/Unit/Integrations/Ai/VisionModelGateTest.php`

**Interfaces:**
- Produces: `CMBcoreSeller\Integrations\Ai\Support\VisionModelGate::enabledFor(string $model): bool`

- [ ] **Step 1: Write the failing test**

Create `app/tests/Unit/Integrations/Ai/VisionModelGateTest.php`:

```php
<?php

namespace Tests\Unit\Integrations\Ai;

use CMBcoreSeller\Integrations\Ai\Support\VisionModelGate;
use Tests\TestCase;

class VisionModelGateTest extends TestCase
{
    public function test_matches_vision_model_substrings(): void
    {
        config()->set('ai.vision.enabled', true);
        config()->set('ai.vision.models', ['gpt-5', 'gemini', 'claude-opus']);

        $this->assertTrue(VisionModelGate::enabledFor('ts/gpt-5.4-mini'));
        $this->assertTrue(VisionModelGate::enabledFor('ts/gemini-3.5-flash'));
        $this->assertFalse(VisionModelGate::enabledFor('mn/Minimax-M3'));
    }

    public function test_disabled_flag_forces_false(): void
    {
        config()->set('ai.vision.enabled', false);
        config()->set('ai.vision.models', ['gpt-5']);

        $this->assertFalse(VisionModelGate::enabledFor('ts/gpt-5.4-mini'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=VisionModelGateTest`
Expected: FAIL — class `VisionModelGate` not found.

- [ ] **Step 3: Create VisionModelGate**

Create `app/app/Integrations/Ai/Support/VisionModelGate.php`:

```php
<?php

namespace CMBcoreSeller\Integrations\Ai\Support;

/**
 * Nguồn sự thật DUY NHẤT: model có khả năng vision (theo `config('ai.vision')`)?
 * Connector (Claude/OpenAI) và admin badge cùng gọi hàm này ⇒ badge luôn khớp
 * đúng điều kiện connector thực dùng để đính ảnh. So khớp substring (lowercase).
 */
class VisionModelGate
{
    public static function enabledFor(string $model): bool
    {
        if (! (bool) config('ai.vision.enabled', true)) {
            return false;
        }
        $m = strtolower($model);
        foreach ((array) config('ai.vision.models', []) as $needle) {
            $n = strtolower(trim((string) $needle));
            if ($n !== '' && str_contains($m, $n)) {
                return true;
            }
        }

        return false;
    }
}
```

- [ ] **Step 4: Delegate connectors to the gate**

In `app/app/Integrations/Ai/Claude/ClaudeConnector.php`, replace the body of `visionEnabled()` (keep the private method + signature so all call sites keep working):

```php
    /** Model hiện tại có khả năng vision? Ủy quyền cho VisionModelGate (nguồn sự thật chung). */
    private function visionEnabled(string $model): bool
    {
        return \CMBcoreSeller\Integrations\Ai\Support\VisionModelGate::enabledFor($model);
    }
```

Apply the identical replacement to `app/app/Integrations/Ai/OpenAi/OpenAiConnector.php` `visionEnabled()`.

- [ ] **Step 5: Run tests to verify pass**

Run: `php artisan test --filter=VisionModelGateTest`
Expected: PASS (2 tests).

Run regression on connector wiring: `php artisan test --filter=AiProviderHttpTest`
Expected: PASS (no behavior change).

- [ ] **Step 6: Commit**

```bash
git add app/app/Integrations/Ai/Support/VisionModelGate.php app/app/Integrations/Ai/Claude/ClaudeConnector.php app/app/Integrations/Ai/OpenAi/OpenAiConnector.php app/tests/Unit/Integrations/Ai/VisionModelGateTest.php
git commit -m "refactor(ai): tách VisionModelGate làm nguồn sự thật cho model vision"
```

---

## Task 2: VisionReRanker dùng provider re-rank riêng

**Files:**
- Modify: `app/app/Modules/Settings/Support/SystemSettingsCatalog.php` (thêm 1 khóa)
- Modify: `app/app/Modules/VisualSearch/Services/VisionReRanker.php` (đầu `pick()`)
- Modify: `app/config/visual_search.php` (ghi chú)
- Test: `app/tests/Feature/VisualSearch/VisionRerankProviderTest.php`

**Interfaces:**
- Consumes: `system_setting('visual_search.rerank.provider_code', '')`; `AiAssistantRegistry::activeProviders(): array`; `AiContext(tenantId, providerCode, model, meta)`.
- Produces: hành vi `VisionReRanker::pick(...)` không đổi chữ ký, chỉ đổi provider nội bộ.

- [ ] **Step 1: Add the catalog key**

In `app/app/Modules/Settings/Support/SystemSettingsCatalog.php`, inside the `all()` array (ngay sau block `help_assistant.embedding_model`, trước dấu `];` đóng mảng), thêm:

```php
            // ── Visual re-rank (SPEC 2026-07-05) — provider AI RIÊNG cho bước chấm ảnh ─
            // Rỗng ⇒ fallback provider chat. Trỏ tới `code` một provider trong ai_providers.
            // Cấu hình ở /admin/ai-visual-rerank.
            'visual_search.rerank.provider_code' => [
                'group' => 'ai', 'type' => 'string', 'is_secret' => false,
                'env' => 'VISUAL_SEARCH_RERANK_PROVIDER_CODE', 'label' => 'AI chấm ảnh — Provider',
                'description' => 'Code provider AI dùng cho bước chấm ảnh (vision re-rank). Rỗng ⇒ dùng model chat.',
            ],
```

- [ ] **Step 2: Write the failing test**

Create `app/tests/Feature/VisualSearch/VisionRerankProviderTest.php`:

```php
<?php

namespace Tests\Feature\VisualSearch;

use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualImageInput;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualItemCandidate;
use CMBcoreSeller\Modules\VisualSearch\Services\VisionReRanker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VisionRerankProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('ai.vision.enabled', true);
        config()->set('ai.vision.models', ['gpt-5', 'gemini']);
        // Credit luôn cho phép — cô lập khỏi Billing.
        $this->app->instance(AiCreditMeter::class, new class implements AiCreditMeter
        {
            public function aiEnabled(int $t): bool { return true; }
            public function canUse(int $t, int $n = 1): bool { return true; }
            public function consume(int $t, int $n = 1): void {}
            public function record(int $t, int $n = 1): void {}
            public function grantPurchase(int $t, int $a): int { return $a; }
            public function summary(int $t): array { return ['enabled' => true, 'unlimited' => true, 'monthly_allowance' => 0, 'period_used' => 0, 'purchased_balance' => 0, 'available' => null]; }
        });
    }

    private function makeProvider(string $code, string $model, string $host): void
    {
        AiProvider::query()->create([
            'code' => $code, 'adapter' => 'openai_compatible', 'is_active' => true,
            'api_key' => 'sk-test', 'base_url' => "https://{$host}", 'default_model' => $model,
        ]);
    }

    /** @return list<array{candidate:VisualItemCandidate, image:?string}> */
    private function candidates(): array
    {
        $img = 'data:image/jpeg;base64,'.base64_encode('fake-bytes');

        return [[
            'candidate' => new VisualItemCandidate(itemId: 77, name: 'Áo thun', description: null, attributes: [], confidence: 0.5),
            'image' => $img,
        ]];
    }

    public function test_uses_dedicated_rerank_provider_when_configured(): void
    {
        $this->makeProvider('chat_min', 'mn/Minimax-M3', 'chat.example.com');   // non-vision
        $this->makeProvider('rr_vis', 'ts/gemini-3.5-flash', 'rerank.example.com'); // vision
        app(SystemSettingService::class)->set('visual_search.rerank.provider_code', 'rr_vis');

        Http::fake([
            'rerank.example.com/*' => Http::response(['choices' => [['message' => ['content' => '{"match":1}']]]], 200),
            'chat.example.com/*' => Http::response([], 500),
        ]);

        $ctx = new AiContext(tenantId: 1, providerCode: 'chat_min', model: 'mn/Minimax-M3');
        $picked = app(VisionReRanker::class)->pick(1, 'chat_min', $ctx, VisualImageInput::fromBinary('cust', 'image/jpeg'), $this->candidates());

        $this->assertSame(77, $picked);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'rerank.example.com'));
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'chat.example.com'));
    }

    public function test_falls_back_to_chat_provider_when_unset(): void
    {
        $this->makeProvider('chat_vis', 'ts/gpt-5.4-mini', 'chat.example.com'); // vision chat, no rerank setting

        Http::fake([
            'chat.example.com/*' => Http::response(['choices' => [['message' => ['content' => '{"match":1}']]]], 200),
        ]);

        $ctx = new AiContext(tenantId: 1, providerCode: 'chat_vis', model: 'ts/gpt-5.4-mini');
        $picked = app(VisionReRanker::class)->pick(1, 'chat_vis', $ctx, VisualImageInput::fromBinary('cust', 'image/jpeg'), $this->candidates());

        $this->assertSame(77, $picked);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'chat.example.com'));
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

Run: `php artisan test --filter=VisionRerankProviderTest`
Expected: FAIL — `test_uses_dedicated_rerank_provider_when_configured` sends to `chat.example.com` (500) and returns NOT_RUN (-1) instead of 77.

- [ ] **Step 4: Implement the override in VisionReRanker::pick()**

In `app/app/Modules/VisualSearch/Services/VisionReRanker.php`, add `use CMBcoreSeller\Integrations\Ai\DTO\AiContext;` is already imported. Immediately after the guard block that returns `NOT_RUN` on empty candidates / no credit (right after the `if ($candidates === [] || ! $this->credits->canUse(...))` block, before `try { $connector = $this->registry->for($providerCode); }`), insert:

```php
        // Provider AI RIÊNG cho re-rank (SPEC 2026-07-05). Rỗng/không active ⇒ giữ provider chat.
        $override = trim((string) system_setting('visual_search.rerank.provider_code', ''));
        if ($override !== '' && $override !== $providerCode && in_array($override, $this->registry->activeProviders(), true)) {
            $providerCode = $override;
            $ctx = new AiContext(
                tenantId: $ctx->tenantId,
                providerCode: $override,
                model: null, // null ⇒ connector dùng default_model của provider re-rank
                meta: ['mode' => 'visual_rerank'],
            );
        }
```

- [ ] **Step 5: Run tests to verify pass**

Run: `php artisan test --filter=VisionRerankProviderTest`
Expected: PASS (2 tests).

- [ ] **Step 6: Add config note**

In `app/config/visual_search.php`, replace the `'rerank'` block with a documented version:

```php
    'rerank' => [
        'enabled' => (bool) env('VISUAL_SEARCH_RERANK', true),
        // Provider AI dùng để chấm ảnh do super-admin chọn ở /admin/ai-visual-rerank,
        // lưu tại system_setting('visual_search.rerank.provider_code'). Rỗng ⇒ dùng
        // provider/model chat của hội thoại (fallback, non-breaking).
    ],
```

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Settings/Support/SystemSettingsCatalog.php app/app/Modules/VisualSearch/Services/VisionReRanker.php app/config/visual_search.php app/tests/Feature/VisualSearch/VisionRerankProviderTest.php
git commit -m "feat(visual-search): vision re-rank dùng provider AI riêng (fallback model chat)"
```

---

## Task 3: Admin backend — controller + routes

**Files:**
- Create: `app/app/Modules/VisualSearch/Http/Controllers/AdminVisualRerankController.php`
- Modify: `app/app/Modules/VisualSearch/Http/routes.php` (nhóm route admin)
- Test: `app/tests/Feature/VisualSearch/AdminVisualRerankTest.php`

**Interfaces:**
- Consumes: `AiProvider` model, `AiAssistantRegistry`, `VisionModelGate::enabledFor`, `SystemSettingService::set`, `system_setting()`.
- Produces (HTTP, guard `admin_web`, prefix `api/v1/admin/ai-visual-rerank`):
  - `GET /` → `{ data: { selected_provider_code: string|null, providers: list<{code,display_name,default_model,is_active,vision}> } }`
  - `PUT /` body `{ provider_code: string|null }` → `{ data: { ok: true } }`; 422 nếu code không thuộc provider active.
  - `POST /test` body `{ provider_code: string }` → `{ data: { ok, sample?, reason?, message? } }`.

- [ ] **Step 1: Write the failing test**

Create `app/tests/Feature/VisualSearch/AdminVisualRerankTest.php`:

```php
<?php

namespace Tests\Feature\VisualSearch;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminVisualRerankTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('ai.vision.enabled', true);
        config()->set('ai.vision.models', ['gpt-5', 'gemini']);
    }

    private function seedProviders(): void
    {
        AiProvider::query()->create(['code' => 'chat_min', 'adapter' => 'openai_compatible', 'is_active' => true, 'base_url' => 'https://a.example.com', 'default_model' => 'mn/Minimax-M3']);
        AiProvider::query()->create(['code' => 'rr_vis', 'adapter' => 'openai_compatible', 'is_active' => true, 'base_url' => 'https://b.example.com', 'default_model' => 'ts/gemini-3.5-flash']);
    }

    public function test_index_lists_providers_with_vision_flag(): void
    {
        $this->seedProviders();
        $admin = AdminUser::factory()->create();

        $res = $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/ai-visual-rerank')
            ->assertOk()->json('data');

        $this->assertNull($res['selected_provider_code']);
        $byCode = collect($res['providers'])->keyBy('code');
        $this->assertFalse($byCode['chat_min']['vision']);
        $this->assertTrue($byCode['rr_vis']['vision']);
    }

    public function test_put_saves_active_provider_and_rejects_unknown(): void
    {
        $this->seedProviders();
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, 'admin_web')->putJson('/api/v1/admin/ai-visual-rerank', ['provider_code' => 'rr_vis'])
            ->assertOk();
        $this->assertSame('rr_vis', system_setting('visual_search.rerank.provider_code'));

        $this->actingAs($admin, 'admin_web')->putJson('/api/v1/admin/ai-visual-rerank', ['provider_code' => 'nope'])
            ->assertStatus(422);
    }

    public function test_put_empty_clears_setting(): void
    {
        $this->seedProviders();
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, 'admin_web')->putJson('/api/v1/admin/ai-visual-rerank', ['provider_code' => 'rr_vis'])->assertOk();
        $this->actingAs($admin, 'admin_web')->putJson('/api/v1/admin/ai-visual-rerank', ['provider_code' => ''])->assertOk();

        $this->assertSame('', (string) system_setting('visual_search.rerank.provider_code', ''));
    }

    public function test_requires_admin_guard(): void
    {
        $this->getJson('/api/v1/admin/ai-visual-rerank')->assertStatus(401);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminVisualRerankTest`
Expected: FAIL — route `/api/v1/admin/ai-visual-rerank` not found (404/500).

- [ ] **Step 3: Create the controller**

Create `app/app/Modules/VisualSearch/Http/Controllers/AdminVisualRerankController.php`:

```php
<?php

namespace CMBcoreSeller\Modules\VisualSearch\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\Support\VisionModelGate;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Super-admin: chọn provider AI RIÊNG cho bước vision re-rank (chấm ảnh top-5),
 * tách khỏi provider chat. `/api/v1/admin/ai-visual-rerank/*` (guard admin_web,
 * KHÔNG tenant). Lưu tại system_setting('visual_search.rerank.provider_code').
 * Rỗng ⇒ VisionReRanker fallback provider chat. SPEC 2026-07-05.
 */
class AdminVisualRerankController extends Controller
{
    private const SETTING_KEY = 'visual_search.rerank.provider_code';

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
                'vision' => $p->default_model ? VisionModelGate::enabledFor($p->default_model) : false,
            ])->values()->all();

        return response()->json(['data' => [
            'selected_provider_code' => (string) system_setting(self::SETTING_KEY, '') ?: null,
            'providers' => $providers,
        ]]);
    }

    public function update(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('provider_code', ''));

        if ($code !== '' && ! in_array($code, $this->registry->activeProviders(), true)) {
            return response()->json(['error' => [
                'code' => 'PROVIDER_NOT_ACTIVE',
                'message' => 'Provider không tồn tại hoặc chưa bật.',
            ]], 422);
        }

        $this->settings->set(self::SETTING_KEY, $code, Auth::guard('admin_web')->id());
        AuditLog::record('visual_search.rerank.provider_set', null, ['provider_code' => $code]);

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
            // Ảnh mẫu 1x1 PNG (base64) — chỉ để kiểm provider có nhận input ảnh.
            $sample = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
            $out = $connector->analyzeImages(
                new AiContext(tenantId: 0, providerCode: $code, meta: ['mode' => 'visual_rerank_test']),
                [$sample],
                'Đây là ảnh thử. Trả về DUY NHẤT JSON {"match": 0}.',
            );

            return response()->json(['data' => ['ok' => true, 'sample' => Str::limit($out, 120)]]);
        } catch (\Throwable $e) {
            return response()->json(['data' => ['ok' => false, 'reason' => 'error', 'message' => Str::limit($e->getMessage(), 200)]]);
        }
    }
}
```

- [ ] **Step 4: Register the admin routes**

In `app/app/Modules/VisualSearch/Http/routes.php`, add the import at top (cạnh các `use` controller):

```php
use CMBcoreSeller\Modules\VisualSearch\Http\Controllers\AdminVisualRerankController;
```

Và append nhóm route admin ở cuối file (sau nhóm `visual-search` hiện có):

```php
/*
|--------------------------------------------------------------------------
| Admin — provider AI riêng cho vision re-rank (SPEC 2026-07-05)
|--------------------------------------------------------------------------
| Super-admin, guard admin_web, KHÔNG tenant — cùng stack Admin/Settings.
*/
Route::middleware(['web', 'auth:admin_web', 'throttle:60,1'])
    ->prefix('api/v1/admin/ai-visual-rerank')->group(function () {
        Route::get('/', [AdminVisualRerankController::class, 'index'])->name('admin.ai-visual-rerank.index');
        Route::put('/', [AdminVisualRerankController::class, 'update'])->name('admin.ai-visual-rerank.update');
        Route::post('test', [AdminVisualRerankController::class, 'test'])->name('admin.ai-visual-rerank.test');
    });
```

- [ ] **Step 5: Run tests to verify pass**

Run: `php artisan test --filter=AdminVisualRerankTest`
Expected: PASS (4 tests).

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/VisualSearch/Http/Controllers/AdminVisualRerankController.php app/app/Modules/VisualSearch/Http/routes.php app/tests/Feature/VisualSearch/AdminVisualRerankTest.php
git commit -m "feat(admin): endpoint chọn provider AI cho vision re-rank"
```

---

## Task 4: Admin FE — trang "AI chấm ảnh" độc lập

**Files:**
- Create: `app/resources/js/admin/lib/visualRerank.tsx`
- Create: `app/resources/js/admin/pages/settings/AdminVisualRerankPage.tsx`
- Modify: `app/resources/js/admin/AdminApp.tsx` (route)
- Modify: `app/resources/js/admin/AdminLayout.tsx` (menu)

**Interfaces:**
- Consumes: `api` từ `@/lib/api` (baseURL `/api/v1`); endpoints từ Task 3.
- Produces: component `AdminVisualRerankPage`; hooks `useVisualRerank`, `useSaveVisualRerank`, `useTestVisualRerank`.

Không có test runner JS (memory `test-verify-baseline`); xác minh bằng `npm run typecheck && npm run build`.

- [ ] **Step 1: Create the hooks lib**

Create `app/resources/js/admin/lib/visualRerank.tsx`:

```tsx
// Hooks trang "AI chấm ảnh" (/admin/ai-visual-rerank). Chọn provider AI RIÊNG cho
// bước vision re-rank, tách khỏi model chat. Endpoint: /api/v1/admin/ai-visual-rerank.
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface RerankProvider {
    code: string;
    display_name: string | null;
    default_model: string | null;
    is_active: boolean;
    vision: boolean;
}

export interface RerankConfig {
    selected_provider_code: string | null;
    providers: RerankProvider[];
}

export function useVisualRerank() {
    return useQuery({
        queryKey: ['visual-rerank-config'],
        queryFn: async (): Promise<RerankConfig> =>
            (await api.get<{ data: RerankConfig }>('/admin/ai-visual-rerank')).data.data,
    });
}

export function useSaveVisualRerank() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (providerCode: string) => {
            await api.put('/admin/ai-visual-rerank', { provider_code: providerCode });
        },
        onSuccess: () => qc.invalidateQueries({ queryKey: ['visual-rerank-config'] }),
    });
}

export interface RerankTestResult {
    ok: boolean;
    sample?: string;
    reason?: string;
    message?: string;
}

export function useTestVisualRerank() {
    return useMutation({
        mutationFn: async (providerCode: string): Promise<RerankTestResult> =>
            (await api.post<{ data: RerankTestResult }>('/admin/ai-visual-rerank/test', { provider_code: providerCode })).data.data,
    });
}
```

- [ ] **Step 2: Create the page**

Create `app/resources/js/admin/pages/settings/AdminVisualRerankPage.tsx`:

```tsx
// Trang admin "AI chấm ảnh" — chọn provider AI RIÊNG cho bước vision re-rank
// (chấm ảnh top-5). Tách hoàn toàn khỏi trang "Nhà cung cấp AI". SPEC 2026-07-05.
import { useEffect, useState } from 'react';
import { Card, Radio, Space, Typography, Tag, Button, Alert, message } from 'antd';
import { PictureOutlined, CheckCircleOutlined, CloseCircleOutlined, ExperimentOutlined } from '@ant-design/icons';
import { useVisualRerank, useSaveVisualRerank, useTestVisualRerank } from '../../lib/visualRerank';

const NONE = '';

export function AdminVisualRerankPage() {
    const { data, isLoading } = useVisualRerank();
    const save = useSaveVisualRerank();
    const test = useTestVisualRerank();
    const [selected, setSelected] = useState<string>(NONE);

    useEffect(() => {
        if (data) setSelected(data.selected_provider_code ?? NONE);
    }, [data]);

    const onSave = async () => {
        await save.mutateAsync(selected);
        message.success('Đã lưu provider chấm ảnh.');
    };

    const onTest = async () => {
        if (selected === NONE) {
            message.warning('Chọn một provider để thử.');
            return;
        }
        const r = await test.mutateAsync(selected);
        if (r.ok) message.success(`Gửi ảnh thử OK. Phản hồi: ${r.sample ?? '(rỗng)'}`);
        else message.error(`Thử thất bại: ${r.message ?? r.reason ?? 'lỗi'}`);
    };

    return (
        <Card
            loading={isLoading}
            title={<Space><PictureOutlined /> AI chấm ảnh (vision re-rank)</Space>}
        >
            <Alert
                type="info"
                showIcon
                style={{ marginBottom: 16 }}
                message="Provider này chỉ dùng để chấm ảnh khi khách gửi ảnh — độc lập với model chat."
                description="Chưa chọn (Không cấu hình) ⇒ dùng model chat của hội thoại. Provider phải có model hỗ trợ vision. Muốn model vision khác: tạo provider mới ở trang 'Nhà cung cấp AI' rồi chọn tại đây."
            />

            <Radio.Group value={selected} onChange={(e) => setSelected(e.target.value)}>
                <Space direction="vertical" style={{ width: '100%' }}>
                    <Radio value={NONE}>
                        <Typography.Text strong>(Không cấu hình)</Typography.Text>
                        <Typography.Text type="secondary"> — dùng model chat</Typography.Text>
                    </Radio>
                    {(data?.providers ?? []).filter((p) => p.is_active).map((p) => (
                        <Radio key={p.code} value={p.code}>
                            <Space>
                                <Typography.Text strong>{p.display_name || p.code}</Typography.Text>
                                <Typography.Text code>{p.default_model}</Typography.Text>
                                {p.vision
                                    ? <Tag color="green" icon={<CheckCircleOutlined />}>Có vision</Tag>
                                    : <Tag color="red" icon={<CloseCircleOutlined />}>Không vision</Tag>}
                            </Space>
                        </Radio>
                    ))}
                </Space>
            </Radio.Group>

            <div style={{ marginTop: 24 }}>
                <Space>
                    <Button type="primary" onClick={onSave} loading={save.isPending}>Lưu</Button>
                    <Button icon={<ExperimentOutlined />} onClick={onTest} loading={test.isPending} disabled={selected === NONE}>
                        Gửi ảnh thử
                    </Button>
                </Space>
            </div>
        </Card>
    );
}
```

- [ ] **Step 3: Register the route**

In `app/resources/js/admin/AdminApp.tsx`, add the import cạnh các page settings khác:

```tsx
import { AdminVisualRerankPage } from './pages/settings/AdminVisualRerankPage';
```

And add the route immediately after the `ai-support` route:

```tsx
                    <Route path="ai-visual-rerank" element={<AdminVisualRerankPage />} />
```

- [ ] **Step 4: Add the menu item**

In `app/resources/js/admin/AdminLayout.tsx`, `PictureOutlined` đã có trong import icons (đang dùng cho "Hình nền Desktop") — không cần thêm import. Add vào `SIDEBAR_ITEMS` ngay sau dòng `ai-support`:

```tsx
    { key: '/admin/ai-visual-rerank', icon: <PictureOutlined />, label: 'AI chấm ảnh' },
```

- [ ] **Step 5: Verify typecheck + build**

Run: `npm run typecheck && npm run build`
Expected: PASS (no TS errors; Vite build succeeds).

- [ ] **Step 6: Commit**

```bash
git add app/resources/js/admin/lib/visualRerank.tsx app/resources/js/admin/pages/settings/AdminVisualRerankPage.tsx app/resources/js/admin/AdminApp.tsx app/resources/js/admin/AdminLayout.tsx
git commit -m "feat(admin-ui): trang AI chấm ảnh chọn provider vision re-rank"
```

---

## Task 5: Docs + quality gate + verify

**Files:**
- Modify: `docs/05-api/endpoints.md`

- [ ] **Step 1: Document the endpoints**

In `docs/05-api/endpoints.md`, add under the admin section (giữ đúng format bảng/heading của file):

```markdown
### AI chấm ảnh (vision re-rank) — super-admin (`auth:admin_web`)

- `GET /api/v1/admin/ai-visual-rerank` — provider đang chọn + danh sách provider (kèm cờ `vision`).
- `PUT /api/v1/admin/ai-visual-rerank` — body `{ provider_code }` (rỗng = dùng model chat); 422 nếu provider chưa bật.
- `POST /api/v1/admin/ai-visual-rerank/test` — body `{ provider_code }`, gửi 1 ảnh thử để kiểm vision thật.
```

- [ ] **Step 2: Run the full quality gate**

Run (from `app/`):
```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test --filter="VisionModelGateTest|VisionRerankProviderTest|AdminVisualRerankTest|AiProviderHttpTest"
npm run typecheck && npm run build
```
Expected: pint clean (nếu báo lỗi format → chạy `vendor/bin/pint` rồi commit lại); phpstan không lỗi mới; các test PASS; FE build OK.

Ghi chú: `php artisan test` toàn cục có 7 test GHN/fulfillment fail sẵn trên main (memory `test-verify-baseline`) — không liên quan task này.

- [ ] **Step 3: Manual verify (đường thật)**

Chạy app admin (`composer dev` hoặc stack docker), đăng nhập `/admin`, mở menu **AI chấm ảnh**:
- Danh sách provider hiện đúng cờ "Có vision / Không vision".
- Chọn một provider vision → **Lưu** → reload thấy vẫn chọn.
- **Gửi ảnh thử** → thông báo OK/thất bại phản ánh đúng provider.
- Đặt lại "(Không cấu hình)" → Lưu (fallback model chat).

- [ ] **Step 4: Commit**

```bash
git add docs/05-api/endpoints.md
git commit -m "docs(api): endpoint admin AI chấm ảnh (vision re-rank)"
```

---

## Self-Review

- **Spec coverage:**
  - Provider re-rank riêng + fallback model chat → Task 2. ✓
  - Màn hình admin riêng (không đụng trang cũ) → Task 3 (backend) + Task 4 (FE). ✓
  - Badge "có vision" khớp connector → Task 1 (VisionModelGate dùng chung). ✓
  - Nút "gửi ảnh thử" verify vision thật → Task 3 `test` endpoint + Task 4 nút. ✓
  - Không migrate, non-breaking → Task 2 (system_setting + fallback). ✓
  - Docs endpoints → Task 5. ✓
  - Bỏ model override (tạo provider mới) → phản ánh ở UI Task 4 (Alert hướng dẫn), không có field override. ✓
- **Placeholder scan:** không còn TBD/TODO; mọi step có code/lệnh cụ thể. ✓
- **Type consistency:** `VisionModelGate::enabledFor` (Task 1) dùng nhất quán ở Task 3; `provider_code` key nhất quán controller/lib/page; hook names `useVisualRerank/useSaveVisualRerank/useTestVisualRerank` khớp giữa lib và page. ✓
</content>
