# AI Providers — Multi-Adapter + Prod Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cho phép super-admin thêm KHÔNG GIỚI HẠN nhà cung cấp AI (Claude/GPT/Gemini/DeepSeek/Qwen/OpenRouter…) qua Admin SPA, đồng thời vá 4 rủi ro prod của luồng AI-chat.

**Architecture:** Tách `code` (slug instance tự do, vẫn là PK) khỏi `adapter` (loại API: `anthropic|openai_compatible|manual`). Registry resolve connector theo `adapter` và **inject instance-code** vào connector (`container->makeWith(['code'=>$code])`) ⇒ ruột connector (`resolve($this->code())`) giữ nguyên. Gemini/DeepSeek/Qwen/OpenRouter đều là instance của adapter `openai_compatible`, khác `base_url`+`api_key`+`default_model`. Bổ sung UI admin còn thiếu + validate base_url (chống SSRF) + rate-limit theo tenant + circuit breaker intent-classify.

**Tech Stack:** Laravel 11 (PHP 8.3), PHPUnit, React 18 + Vite + Ant Design 5 + TanStack Query + react-router. Dev chạy trong Docker (theo README).

**Quy ước lệnh test (dev = Docker):**
- Backend: `docker compose exec app php artisan test --filter=<Class hoặc method>`
- Lint/Static: `docker compose exec app vendor/bin/pint --test` · `docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M`
- FE: `docker compose exec vite npm run typecheck` · `docker compose exec vite npm run lint` · `docker compose exec vite npm run build`
- (Nếu chạy ngoài Docker: bỏ tiền tố `docker compose exec app|vite`.)

**Commit:** mỗi task 1 commit. Nhánh hiện tại `feature/inbox-display-unread-phone` — nếu muốn cô lập, tạo nhánh `feature/admin-ai-providers-multi-adapter` trước Task 1.

---

## File Structure

| File | Trách nhiệm | Hành động |
|---|---|---|
| `app/app/Modules/Messaging/Database/Migrations/2026_05_21_120000_add_adapter_to_ai_providers_table.php` | Thêm cột `adapter`/`sort_order`/`notes` + backfill | Create |
| `app/app/Modules/Messaging/Models/AiProvider.php` | `$fillable` + docblock | Modify |
| `app/app/Integrations/Ai/AiAssistantRegistry.php` | Map `adapter→class`, resolve inject code | Rewrite |
| `app/app/Integrations/Ai/Claude/ClaudeConnector.php` | inject `$code` ctor | Modify (`:41-46`) |
| `app/app/Integrations/Ai/OpenAi/OpenAiConnector.php` | inject `$code` ctor (giữ tên lớp, vai trò adapter `openai_compatible`) | Modify (`:34-39`) |
| `app/app/Integrations/Ai/Manual/ManualAiAssistantConnector.php` | inject `$code` ctor | Modify |
| `app/app/Integrations/IntegrationsServiceProvider.php` | `$aiAssistantConnectors` đổi key→adapter; xoá comment cũ | Modify (`:97-106`, `:193-200`) |
| `app/app/Modules/Messaging/Rules/SafeProviderUrl.php` | Rule validate HTTPS + chống SSRF | Create |
| `app/app/Modules/Messaging/Http/Controllers/AdminAiProviderController.php` | validate adapter+slug, base_url, presets, test/present theo adapter | Modify |
| `app/app/Providers/AppServiceProvider.php` | `RateLimiter::for('ai-suggestion')` | Modify |
| `app/app/Modules/Messaging/Http/routes.php` | gắn `throttle:ai-suggestion` (`:72-81`) | Modify |
| `app/app/Modules/Messaging/Services/IntentClassifier.php` | circuit breaker + phân loại lỗi | Modify |
| `app/app/Integrations/Ai/Contracts/AiAssistantConnector.php` | docblock (config ở bảng, không system_settings) | Modify (`:16-19`) |
| `app/resources/js/admin/lib/aiProviders.tsx` | hooks CRUD + test | Create |
| `app/resources/js/admin/pages/settings/AdminAiProvidersPage.tsx` | trang quản lý provider | Create |
| `app/resources/js/admin/AdminApp.tsx` | route `/admin/ai-providers` | Modify (`:16`,`:38`) |
| `app/resources/js/admin/AdminLayout.tsx` | menu "Nhà cung cấp AI" | Modify (`:6-17`,`:21-30`) |
| Tests (8 file create-AiProvider) | thêm `'adapter'` | Modify |
| `app/tests/Feature/Messaging/AdminAiProviderTest.php` | sửa + thêm test multi-instance | Modify |
| `app/tests/Unit/Messaging/AiAssistantRegistryTest.php` | resolve adapter + inject code | Create |
| `docs/specs/0024-omnichannel-messaging.md` | ghi chú adapter model | Modify |

---

## Task 1: Migration + Model — cột `adapter`

**Files:**
- Create: `app/app/Modules/Messaging/Database/Migrations/2026_05_21_120000_add_adapter_to_ai_providers_table.php`
- Modify: `app/app/Modules/Messaging/Models/AiProvider.php`
- Test: `app/tests/Feature/Messaging/AdminAiProviderTest.php` (chạy lại sau khi sửa ở Task 6 — bước này chỉ migrate)

- [ ] **Step 1: Viết migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tách `code` (slug instance tự do) khỏi `adapter` (loại API connector).
 * adapter: anthropic | openai_compatible | manual. Cho phép NHIỀU instance cùng
 * adapter (deepseek/qwen/openrouter đều openai_compatible, khác base_url/key/model).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_providers', function (Blueprint $table) {
            $table->string('adapter', 24)->nullable()->after('code');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('is_active');
            $table->string('notes')->nullable()->after('sort_order');
        });

        // Backfill từ code cũ (rows đã có trước đây).
        DB::table('ai_providers')->where('code', 'claude')->update(['adapter' => 'anthropic']);
        DB::table('ai_providers')->where('code', 'openai')->update(['adapter' => 'openai_compatible']);
        DB::table('ai_providers')->where('code', 'manual')->update(['adapter' => 'manual']);
        DB::table('ai_providers')->whereNull('adapter')->update(['adapter' => 'openai_compatible']);

        Schema::table('ai_providers', function (Blueprint $table) {
            $table->string('adapter', 24)->nullable(false)->index()->change(); // L11 native, không cần dbal
        });
    }

    public function down(): void
    {
        Schema::table('ai_providers', function (Blueprint $table) {
            $table->dropColumn(['adapter', 'sort_order', 'notes']);
        });
    }
};
```

- [ ] **Step 2: Thêm field vào model** — `AiProvider.php`, sửa `$fillable`:

```php
    protected $fillable = [
        'code', 'adapter', 'display_name', 'api_key', 'base_url',
        'default_model', 'pricing', 'is_active', 'sort_order', 'notes', 'created_by_admin_id',
    ];
```
Và cập nhật docblock lớp: `code` = slug instance tự do; `adapter` = loại connector.

- [ ] **Step 3: Chạy migrate (test DB tự migrate qua RefreshDatabase)** — verify migration không lỗi:

Run: `docker compose exec app php artisan migrate --pretend` (xem SQL) rồi `docker compose exec app php artisan test --filter=AdminAiProviderTest`
Expected: migrate hợp lệ; AdminAiProviderTest **đỏ** ở các test thiếu `adapter` (sẽ sửa Task 6) — chấp nhận tạm.

- [ ] **Step 4: Commit**

```bash
git add app/app/Modules/Messaging/Database/Migrations/2026_05_21_120000_add_adapter_to_ai_providers_table.php app/app/Modules/Messaging/Models/AiProvider.php
git commit -m "feat(ai): them cot adapter vao ai_providers (tach code/instance khoi loai API)"
```

---

## Task 2: Refactor `AiAssistantRegistry` — map theo adapter + inject code

**Files:**
- Rewrite: `app/app/Integrations/Ai/AiAssistantRegistry.php`
- Test: `app/tests/Unit/Messaging/AiAssistantRegistryTest.php` (create)

- [ ] **Step 1: Viết test thất bại** — `AiAssistantRegistryTest.php`:

```php
<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\Exceptions\ProviderNotConfigured;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiAssistantRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_two_instances_of_same_adapter_with_own_code(): void
    {
        $reg = app(AiAssistantRegistry::class); // đã register adapter ở IntegrationsServiceProvider
        AiProvider::query()->create(['code' => 'deepseek-prod', 'adapter' => 'openai_compatible', 'is_active' => true, 'default_model' => 'deepseek-chat', 'base_url' => 'https://api.deepseek.com']);
        AiProvider::query()->create(['code' => 'qwen-cheap', 'adapter' => 'openai_compatible', 'is_active' => true, 'default_model' => 'qwen-plus', 'base_url' => 'https://dashscope-intl.aliyuncs.com/compatible-mode']);

        $a = $reg->for('deepseek-prod');
        $b = $reg->for('qwen-cheap');

        $this->assertSame('deepseek-prod', $a->code());
        $this->assertSame('qwen-cheap', $b->code());
    }

    public function test_inactive_provider_throws(): void
    {
        $reg = app(AiAssistantRegistry::class);
        AiProvider::query()->create(['code' => 'gemini-flash', 'adapter' => 'openai_compatible', 'is_active' => false]);
        $this->expectException(ProviderNotConfigured::class);
        $reg->for('gemini-flash');
    }

    public function test_adapters_lists_registered_keys(): void
    {
        $reg = app(AiAssistantRegistry::class);
        $this->assertEqualsCanonicalizing(['anthropic', 'openai_compatible', 'manual'], $reg->adapters());
    }
}
```

- [ ] **Step 2: Chạy để xác nhận đỏ**

Run: `docker compose exec app php artisan test --filter=AiAssistantRegistryTest`
Expected: FAIL (`adapters()` chưa có / `for()` resolve sai).

- [ ] **Step 3: Rewrite registry**

```php
<?php

namespace CMBcoreSeller\Integrations\Ai;

use CMBcoreSeller\Integrations\Ai\Contracts\AiAssistantConnector;
use CMBcoreSeller\Integrations\Ai\Exceptions\ProviderNotConfigured;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Contracts\Container\Container;

/**
 * Registry AI assistant. Map giờ là **adapter → connector class**
 * (anthropic|openai_compatible|manual). 1 connector phục vụ NHIỀU instance
 * (rows `ai_providers`): resolve theo `adapter`, inject instance `code` để
 * connector tự lấy đúng credentials (`resolve($this->code())`).
 *
 * `for($code)`/`make($code)` vẫn nhận **code instance** (call-site không đổi).
 */
class AiAssistantRegistry
{
    /** @var array<string, class-string<AiAssistantConnector>> adapter => class */
    protected array $adapters = [];

    public function __construct(protected Container $container) {}

    /** @param class-string<AiAssistantConnector> $connectorClass */
    public function register(string $adapter, string $connectorClass): void
    {
        $this->adapters[$adapter] = $connectorClass;
    }

    public function hasAdapter(string $adapter): bool
    {
        return isset($this->adapters[$adapter]);
    }

    /** @return list<string> */
    public function adapters(): array
    {
        return array_keys($this->adapters);
    }

    /** Resolve connector cho 1 instance code (có active guard). */
    public function for(string $code): AiAssistantConnector
    {
        $row = $this->row($code);
        if (! $row || ! $row->is_active) {
            throw new ProviderNotConfigured("AI provider [{$code}] is not active.");
        }

        return $this->resolveAdapter((string) $row->adapter, $code);
    }

    /** Resolve KHÔNG check active (admin test/inspect). */
    public function make(string $code): AiAssistantConnector
    {
        $row = $this->row($code);
        if (! $row) {
            throw new ProviderNotConfigured("AI provider [{$code}] not found.");
        }

        return $this->resolveAdapter((string) $row->adapter, $code);
    }

    /** @return list<string> active codes có adapter đã register */
    public function activeProviders(): array
    {
        try {
            return AiProvider::query()
                ->where('is_active', true)
                ->orderBy('sort_order')->orderBy('code')
                ->get(['code', 'adapter'])
                ->filter(fn ($r) => isset($this->adapters[$r->adapter]))
                ->pluck('code')
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function resolveAdapter(string $adapter, string $code): AiAssistantConnector
    {
        if (! isset($this->adapters[$adapter])) {
            throw new ProviderNotConfigured("AI adapter [{$adapter}] is not registered.");
        }

        return $this->container->makeWith($this->adapters[$adapter], ['code' => $code]);
    }

    private function row(string $code): ?AiProvider
    {
        try {
            return AiProvider::query()->find($code);
        } catch (\Throwable) {
            return null;
        }
    }
}
```

- [ ] **Step 4: Chạy test (sẽ vẫn đỏ tới khi Task 3 inject code vào connector + đổi map)**

Run: `docker compose exec app php artisan test --filter=AiAssistantRegistryTest`
Expected: vẫn FAIL ở `test_adapters_lists_registered_keys` (map còn key cũ) → fix ở Task 3.

- [ ] **Step 5: Commit**

```bash
git add app/app/Integrations/Ai/AiAssistantRegistry.php app/tests/Unit/Messaging/AiAssistantRegistryTest.php
git commit -m "refactor(ai): registry map theo adapter + resolve inject instance code"
```

---

## Task 3: Inject `$code` vào connector + đổi map adapter

**Files:**
- Modify: `app/app/Integrations/Ai/Claude/ClaudeConnector.php` (`:41-46`)
- Modify: `app/app/Integrations/Ai/OpenAi/OpenAiConnector.php` (`:34-39`)
- Modify: `app/app/Integrations/Ai/Manual/ManualAiAssistantConnector.php`
- Modify: `app/app/Integrations/IntegrationsServiceProvider.php` (`:97-106`, `:193-200`)
- Test: tái dùng `AiAssistantRegistryTest`

> Quyết định: **giữ tên lớp `OpenAiConnector`** (không rename) để không phá `AiProviderHttpTest`/`tests` import; nó đóng vai adapter `openai_compatible`. Chỉ thêm docblock 1 dòng làm rõ.

- [ ] **Step 1: ClaudeConnector — ctor inject code**

Thay `:41-46`:
```php
    public function __construct(private AiProviderCredentials $credentials, private string $code = 'claude') {}

    public function code(): string
    {
        return $this->code;
    }
```
(Mọi `resolve($this->code())` ở `:71,124,173` và `UnsupportedOperation::for($this->code(),…)` ở `:168` giữ nguyên — giờ trỏ instance code.)

- [ ] **Step 2: OpenAiConnector — ctor inject code**

Thay `:34-39`:
```php
    public function __construct(private AiProviderCredentials $credentials, private string $code = 'openai') {}

    public function code(): string
    {
        return $this->code;
    }
```
Thêm vào docblock lớp (`:30`): `// Adapter `openai_compatible`: dùng cho OpenAI, DeepSeek, Qwen (DashScope compat), OpenRouter, Gemini (v1beta/openai)… phân biệt qua base_url + api_key + default_model per-instance.`

- [ ] **Step 3: ManualAiAssistantConnector — ctor inject code**

Tìm ctor + `code()` hiện tại, đổi thành:
```php
    public function __construct(private string $code = 'manual') {}

    public function code(): string
    {
        return $this->code;
    }
```
(Nếu Manual ctor hiện không nhận tham số, chỉ thêm `string $code = 'manual'`.)

- [ ] **Step 4: Đổi map adapter trong IntegrationsServiceProvider** — thay `:97-106`:

```php
    protected array $aiAssistantConnectors = [
        'anthropic'         => \CMBcoreSeller\Integrations\Ai\Claude\ClaudeConnector::class,
        'openai_compatible' => \CMBcoreSeller\Integrations\Ai\OpenAi\OpenAiConnector::class,
        'manual'            => \CMBcoreSeller\Integrations\Ai\Manual\ManualAiAssistantConnector::class,
    ];
```
Singleton `:193-200` giữ nguyên (đã `foreach ($this->aiAssistantConnectors as $code => $class) $registry->register($code, $class)` — giờ `$code` chính là adapter). Cập nhật comment `:190-192`/`:99-101`: bỏ "stub until wired (S6.1)"; ghi "register adapter (anthropic/openai_compatible/manual); credentials đọc từ bảng ai_providers".

- [ ] **Step 5: Chạy test registry + connector HTTP cũ**

Run: `docker compose exec app php artisan test --filter=AiAssistantRegistryTest`
Expected: PASS (3/3).
Run: `docker compose exec app php artisan test --filter=AiProviderHttpTest`
Expected: vẫn cần `adapter` ở create() → có thể đỏ; sẽ xanh sau Task 6. (Hoặc làm Task 6 trước khi chạy AiProviderHttpTest.)

- [ ] **Step 6: Commit**

```bash
git add app/app/Integrations/Ai/Claude/ClaudeConnector.php app/app/Integrations/Ai/OpenAi/OpenAiConnector.php app/app/Integrations/Ai/Manual/ManualAiAssistantConnector.php app/app/Integrations/IntegrationsServiceProvider.php
git commit -m "refactor(ai): connector nhan instance code; map connector theo adapter"
```

---

## Task 4: Rule `SafeProviderUrl` (HTTPS + chống SSRF)

**Files:**
- Create: `app/app/Modules/Messaging/Rules/SafeProviderUrl.php`
- Test: `app/tests/Unit/Messaging/SafeProviderUrlTest.php` (create)

- [ ] **Step 1: Viết test thất bại**

```php
<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Modules\Messaging\Rules\SafeProviderUrl;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SafeProviderUrlTest extends TestCase
{
    private function fails(?string $url): bool
    {
        return Validator::make(['u' => $url], ['u' => [new SafeProviderUrl]])->fails();
    }

    public function test_accepts_public_https(): void
    {
        $this->assertFalse($this->fails('https://api.deepseek.com'));
        $this->assertFalse($this->fails(null)); // nullable: bỏ qua
    }

    public function test_rejects_http_and_internal(): void
    {
        $this->assertTrue($this->fails('http://api.openai.com'));   // không HTTPS
        $this->assertTrue($this->fails('https://localhost'));
        $this->assertTrue($this->fails('https://127.0.0.1'));
        $this->assertTrue($this->fails('https://192.168.1.10'));
        $this->assertTrue($this->fails('https://10.0.0.5'));
    }
}
```

- [ ] **Step 2: Chạy để xác nhận đỏ**

Run: `docker compose exec app php artisan test --filter=SafeProviderUrlTest`
Expected: FAIL (class chưa tồn tại).

- [ ] **Step 3: Viết rule**

```php
<?php

namespace CMBcoreSeller\Modules\Messaging\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * base_url provider phải HTTPS và KHÔNG trỏ host nội bộ (chống SSRF/MITM).
 * Null/empty ⇒ bỏ qua (dùng default endpoint của adapter).
 */
class SafeProviderUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $parts = parse_url((string) $value);
        if (($parts['scheme'] ?? '') !== 'https') {
            $fail('Địa chỉ provider phải dùng HTTPS.');

            return;
        }

        $host = strtolower($parts['host'] ?? '');
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            $fail('Địa chỉ provider không được trỏ host nội bộ.');

            return;
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (filter_var($ip, FILTER_VALIDATE_IP) && ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $fail('Địa chỉ provider không được trỏ IP nội bộ/loopback.');
        }
    }
}
```

- [ ] **Step 4: Chạy test → xanh**

Run: `docker compose exec app php artisan test --filter=SafeProviderUrlTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Messaging/Rules/SafeProviderUrl.php app/tests/Unit/Messaging/SafeProviderUrlTest.php
git commit -m "feat(ai): rule SafeProviderUrl (HTTPS + chong SSRF cho base_url)"
```

---

## Task 5: Controller — validate adapter/slug + presets + test/present theo adapter

**Files:**
- Modify: `app/app/Modules/Messaging/Http/Controllers/AdminAiProviderController.php`

- [ ] **Step 1: Thêm import + hằng presets** (đầu lớp):

```php
use CMBcoreSeller\Modules\Messaging\Rules\SafeProviderUrl;
```
Thêm hằng trong lớp:
```php
    /** Preset gợi ý cho FE auto-điền base_url/model (không bắt buộc dùng). */
    private const PRESETS = [
        'openai_compatible' => [
            ['name' => 'OpenAI',     'base_url' => 'https://api.openai.com',          'default_model' => 'gpt-4o-mini'],
            ['name' => 'DeepSeek',   'base_url' => 'https://api.deepseek.com',         'default_model' => 'deepseek-chat'],
            ['name' => 'Qwen',       'base_url' => 'https://dashscope-intl.aliyuncs.com/compatible-mode', 'default_model' => 'qwen-plus'],
            ['name' => 'OpenRouter', 'base_url' => 'https://openrouter.ai/api',        'default_model' => 'openai/gpt-4o-mini'],
            ['name' => 'Gemini',     'base_url' => 'https://generativelanguage.googleapis.com/v1beta/openai', 'default_model' => 'gemini-2.0-flash'],
        ],
        'anthropic' => [
            ['name' => 'Anthropic Claude', 'base_url' => 'https://api.anthropic.com', 'default_model' => 'claude-opus-4-7'],
        ],
        'manual' => [
            ['name' => 'Manual (test/dev)', 'base_url' => null, 'default_model' => null],
        ],
    ];
```

- [ ] **Step 2: `index()` — trả `adapters` + presets** (thay `:32-40`):

```php
    public function index(): JsonResponse
    {
        $rows = AiProvider::query()->orderBy('sort_order')->orderBy('code')->get();

        $adapters = array_map(fn (string $a) => [
            'adapter' => $a,
            'presets' => self::PRESETS[$a] ?? [],
        ], $this->registry->adapters());

        return response()->json([
            'data' => $rows->map(fn (AiProvider $p) => $this->present($p))->all(),
            'adapters' => $adapters,
        ]);
    }
```

- [ ] **Step 3: `store()` — validate adapter + slug + base_url** (thay `:42-61`):

```php
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9][a-z0-9_-]{1,31}$/', 'unique:ai_providers,code'],
            'adapter' => ['required', 'string', Rule::in($this->registry->adapters())],
            'display_name' => ['nullable', 'string', 'max:120'],
            'api_key' => ['nullable', 'string', 'max:512'],
            'base_url' => ['nullable', 'string', 'max:255', new SafeProviderUrl, 'required_if:adapter,openai_compatible'],
            'default_model' => ['nullable', 'string', 'max:64', 'required_if:adapter,openai_compatible'],
            'pricing' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $provider = AiProvider::create(array_merge($data, [
            'created_by_admin_id' => Auth::guard('admin_web')->id(),
        ]));

        AuditLog::record('messaging.ai.provider_create', null, ['code' => $provider->code, 'adapter' => $provider->adapter]);

        return response()->json(['data' => $this->present($provider)], 201);
    }
```

- [ ] **Step 4: `update()` — KHÔNG cho đổi adapter; thêm base_url rule** (thay validate `:67-74`):

```php
        $data = $request->validate([
            'display_name' => ['nullable', 'string', 'max:120'],
            'api_key' => ['nullable', 'string', 'max:512'],   // gửi rỗng = giữ nguyên
            'base_url' => ['nullable', 'string', 'max:255', new SafeProviderUrl],
            'default_model' => ['nullable', 'string', 'max:64'],
            'pricing' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
```
(Giữ nguyên logic bỏ `api_key` rỗng ở `:76-79`.)

- [ ] **Step 5: `test()` — load row để biết adapter** (thay `:106-134` phần đầu):

```php
    public function test(string $code): JsonResponse
    {
        $row = AiProvider::query()->find($code);
        if (! $row) {
            return response()->json(['error' => ['code' => 'UNKNOWN_AI_PROVIDER', 'message' => "Provider [{$code}] không tồn tại."]], 404);
        }

        $connector = $this->registry->make($code); // resolve theo adapter, inject code
        // ... giữ nguyên try/catch generateReply "hello" như cũ ...
```
(Phần `try { generateReply(...) } catch (UnsupportedOperation|ProviderNotConfigured|\Throwable)` giữ nguyên `:114-133`.)

- [ ] **Step 6: `present()` — thêm `adapter`** (sửa `:146-156`): thêm `'adapter' => $p->adapter,` và `'sort_order' => $p->sort_order, 'notes' => $p->notes,`. Capabilities đổi resolve theo adapter: `$this->registry->make($p->code)->capabilities()` (giữ nguyên — `make` giờ load row → adapter).

- [ ] **Step 7: Cập nhật docblock lớp `:18-26`**: code = slug + adapter chọn connector; bỏ câu "code phải là connector đã register".

- [ ] **Step 8: Verify build (test ở Task 6)**

Run: `docker compose exec app vendor/bin/pint --test app/app/Modules/Messaging/Http/Controllers/AdminAiProviderController.php`
Expected: không lỗi style.

- [ ] **Step 9: Commit**

```bash
git add app/app/Modules/Messaging/Http/Controllers/AdminAiProviderController.php
git commit -m "feat(ai): admin store/update theo adapter + slug + presets + base_url validate"
```

---

## Task 6: Cập nhật test cũ (thêm `adapter`) + test multi-instance

**Files (thêm `'adapter'=>…` vào mỗi `AiProvider::create`):**
- `app/tests/Feature/Messaging/AiProviderHttpTest.php` (`:46` claude→`anthropic`, `:82` claude→`anthropic`, `:98` openai→`openai_compatible`, `:127` openai→`openai_compatible`)
- `app/tests/Feature/Messaging/MessagingAiSuggestionTest.php` (`:47` manual→`manual`)
- `app/tests/Feature/Messaging/MessagingAutoModeTest.php` (`:55` manual→`manual`)
- `app/tests/Feature/Messaging/AdminAiProviderTest.php` (`:65,85` claude→`anthropic`; `:102,112` manual→`manual`)

- [ ] **Step 1: Thêm `adapter` cho mọi create** — ví dụ `AiProviderHttpTest.php:46`:

```php
        AiProvider::query()->create([
            'code' => 'claude', 'adapter' => 'anthropic', 'is_active' => true,
            'api_key' => 'sk-ant-xxx', 'default_model' => 'claude-opus-4-7',
            'pricing' => [
                ['kind' => 'input_token', 'unit' => 1000, 'micro_vnd' => 100],
                ['kind' => 'output_token', 'unit' => 1000, 'micro_vnd' => 500],
            ],
        ]);
```
Áp dụng tương tự cho `openai`→`'adapter' => 'openai_compatible'` và `manual`→`'adapter' => 'manual'` ở mọi file liệt kê.

- [ ] **Step 2: Sửa test create trong AdminAiProviderTest** — `test_admin_creates_provider_without_leaking_key` (`:31-37`) thêm `'adapter' => 'anthropic'` vào payload POST.

- [ ] **Step 3: Đổi `test_store_rejects_unregistered_code` → kiểm adapter** (thay `:53-59`):

```php
    public function test_store_rejects_unregistered_adapter(): void
    {
        $this->actingAdmin();
        $this->postJson('/api/v1/admin/ai-providers', ['code' => 'bogus', 'adapter' => 'bogus_adapter'])
            ->assertStatus(422);
    }

    public function test_store_allows_free_form_code_with_known_adapter(): void
    {
        $this->actingAdmin();
        $this->postJson('/api/v1/admin/ai-providers', [
            'code' => 'deepseek-prod', 'adapter' => 'openai_compatible',
            'base_url' => 'https://api.deepseek.com', 'default_model' => 'deepseek-chat',
            'api_key' => 'sk-ds-xxx', 'is_active' => true,
        ])->assertStatus(201)->assertJsonPath('data.adapter', 'openai_compatible');
    }
```

- [ ] **Step 4: Thêm test multi-instance + base_url reject** (cuối lớp AdminAiProviderTest):

```php
    public function test_multiple_openai_compatible_instances_coexist(): void
    {
        $this->actingAdmin();
        foreach ([
            ['deepseek-prod', 'https://api.deepseek.com', 'deepseek-chat'],
            ['qwen-cheap', 'https://dashscope-intl.aliyuncs.com/compatible-mode', 'qwen-plus'],
            ['openrouter-fb', 'https://openrouter.ai/api', 'openai/gpt-4o-mini'],
        ] as [$code, $url, $model]) {
            $this->postJson('/api/v1/admin/ai-providers', [
                'code' => $code, 'adapter' => 'openai_compatible',
                'base_url' => $url, 'default_model' => $model, 'api_key' => 'k', 'is_active' => true,
            ])->assertStatus(201);
        }

        $codes = collect($this->getJson('/api/v1/admin/ai-providers')->json('data'))->pluck('code');
        $this->assertContains('deepseek-prod', $codes);
        $this->assertContains('qwen-cheap', $codes);
        $this->assertContains('openrouter-fb', $codes);
    }

    public function test_store_rejects_non_https_base_url(): void
    {
        $this->actingAdmin();
        $this->postJson('/api/v1/admin/ai-providers', [
            'code' => 'evil', 'adapter' => 'openai_compatible',
            'base_url' => 'http://169.254.169.254', 'default_model' => 'x',
        ])->assertStatus(422);
    }
```

- [ ] **Step 5: Chạy toàn bộ test messaging**

Run: `docker compose exec app php artisan test --filter=Messaging`
Expected: PASS (gồm AiAssistantRegistryTest, AdminAiProviderTest, AiProviderHttpTest, MessagingAiSuggestionTest, MessagingAutoModeTest).

- [ ] **Step 6: Commit**

```bash
git add app/tests/Feature/Messaging/ app/tests/Unit/Messaging/
git commit -m "test(ai): adapter cho fixtures + test multi-instance openai_compatible + base_url"
```

---

## Task 7: Rate-limit AI-suggestion theo tenant

**Files:**
- Modify: `app/app/Providers/AppServiceProvider.php`
- Modify: `app/app/Modules/Messaging/Http/routes.php` (`:72-81`)
- Test: `app/tests/Feature/Messaging/AiSuggestionRateLimitTest.php` (create)

- [ ] **Step 1: Định nghĩa limiter trong `AppServiceProvider::boot()`**

Thêm import: `use Illuminate\Cache\RateLimiting\Limit; use Illuminate\Support\Facades\RateLimiter; use Illuminate\Http\Request;`
Trong `boot()`:
```php
        RateLimiter::for('ai-suggestion', function (Request $request) {
            $tenantId = $request->user()?->tenant_id ?? $request->ip();

            return Limit::perMinute(20)->by('ai-suggestion:'.$tenantId);
        });
```

- [ ] **Step 2: Gắn middleware vào nhóm route** — `routes.php:72`, đổi:

```php
    Route::middleware(['plan.feature:messaging_ai', 'throttle:ai-suggestion'])->group(function () {
```

- [ ] **Step 3: Viết test**

```php
<?php

namespace Tests\Feature\Messaging;

// ... use các model cần thiết để tạo tenant/user/conversation (mirror MessagingAiSuggestionTest setUp) ...

class AiSuggestionRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocks_after_20_requests_per_minute(): void
    {
        // Arrange: tạo tenant + user có quyền messaging.reply + conversation + provider manual active
        //          (sao chép setup từ MessagingAiSuggestionTest).
        // Act: gọi 21 lần POST /api/v1/messaging/conversations/{id}/ai-suggestion
        // Assert: lần thứ 21 trả 429.
        $this->markTestIncomplete('Điền setup theo MessagingAiSuggestionTest::setUp khi triển khai.');
    }
}
```
> Lưu ý triển khai: copy đúng helper tạo tenant/user/conversation từ `MessagingAiSuggestionTest` (đọc file đó trước). Vì giới hạn 20/phút, loop 21 request; assert response cuối `->assertStatus(429)`.

- [ ] **Step 4: Chạy test + suite suggestion**

Run: `docker compose exec app php artisan test --filter=AiSuggestionRateLimitTest`
Expected: PASS sau khi điền setup. (Nếu để `markTestIncomplete` thì test "incomplete" — phải hoàn thiện trước khi commit.)
Run: `docker compose exec app php artisan test --filter=AiSuggestion` → các test cũ vẫn PASS.

- [ ] **Step 5: Commit**

```bash
git add app/app/Providers/AppServiceProvider.php app/app/Modules/Messaging/Http/routes.php app/tests/Feature/Messaging/AiSuggestionRateLimitTest.php
git commit -m "feat(ai): rate-limit ai-suggestion 20/phut/tenant"
```

---

## Task 8: Circuit breaker cho intent-classify

**Files:**
- Modify: `app/app/Modules/Messaging/Services/IntentClassifier.php`
- Test: `app/tests/Unit/Messaging/IntentClassifierTest.php` (mở rộng — file đã tồn tại)

- [ ] **Step 1: Viết test thất bại** — thêm vào `IntentClassifierTest.php`:

```php
    public function test_circuit_opens_after_repeated_failures_and_skips_provider(): void
    {
        \Illuminate\Support\Facades\Cache::flush();

        // provider 'flaky' (openai_compatible) active nhưng API luôn lỗi 500
        \CMBcoreSeller\Modules\Messaging\Models\AiProvider::query()->create([
            'code' => 'flaky', 'adapter' => 'openai_compatible', 'is_active' => true,
            'api_key' => 'k', 'default_model' => 'm', 'base_url' => 'https://api.deepseek.com',
        ]);
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response('err', 500)]);

        $clf = app(\CMBcoreSeller\Modules\Messaging\Services\IntentClassifier::class);

        // 5 lần lỗi đầu: vẫn gọi provider, trả escalate ('urgent')
        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($clf->shouldEscalate($clf->classify(1, 'flaky', 'test')));
        }

        // Sau ngưỡng: circuit MỞ → KHÔNG gọi HTTP nữa nhưng vẫn escalate (an toàn)
        \Illuminate\Support\Facades\Http::fake(); // reset recorder
        $intent = $clf->classify(1, 'flaky', 'test');
        $this->assertTrue($clf->shouldEscalate($intent));
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }
```

- [ ] **Step 2: Chạy để xác nhận đỏ**

Run: `docker compose exec app php artisan test --filter=IntentClassifierTest`
Expected: FAIL (chưa có circuit breaker → vẫn gọi HTTP).

- [ ] **Step 3: Sửa `IntentClassifier::classify`** — thêm `use Illuminate\Support\Facades\Cache;` và:

```php
    private const FAIL_THRESHOLD = 5;

    public function classify(int $tenantId, string $providerCode, string $text): IntentDTO
    {
        $failKey = "ai:intent:fail:{$providerCode}";

        // Circuit MỞ: bỏ qua gọi provider, escalate an toàn (tránh hammer provider chết).
        if ((int) Cache::get($failKey, 0) >= self::FAIL_THRESHOLD) {
            return new IntentDTO(intent: 'urgent', confidence: 0.0);
        }

        try {
            $connector = $this->registry->for($providerCode);
            if (! $connector->supports('intent.classify')) {
                return new IntentDTO(intent: 'urgent', confidence: 0.0);
            }

            $result = $connector->classifyIntent(new AiContext($tenantId, $providerCode), $text, self::ALL);
            Cache::forget($failKey); // thành công → reset bộ đếm

            return $result;
        } catch (ProviderNotConfigured) {
            return new IntentDTO(intent: 'urgent', confidence: 0.0); // lỗi cấu hình: escalate, KHÔNG đếm
        } catch (\Throwable) {
            // lỗi tạm (timeout/5xx): đếm; mở mạch 2 phút khi đạt ngưỡng
            $count = (int) Cache::get($failKey, 0) + 1;
            Cache::put($failKey, $count, now()->addMinutes(2));

            return new IntentDTO(intent: 'urgent', confidence: 0.0);
        }
    }
```
Thêm import `use CMBcoreSeller\Integrations\Ai\Exceptions\ProviderNotConfigured;` nếu chưa có.

- [ ] **Step 4: Chạy test → xanh**

Run: `docker compose exec app php artisan test --filter=IntentClassifierTest`
Expected: PASS (gồm test cũ + mới).

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Messaging/Services/IntentClassifier.php app/tests/Unit/Messaging/IntentClassifierTest.php
git commit -m "feat(ai): circuit breaker intent-classify (ngung goi provider chet sau 5 loi/2 phut)"
```

---

## Task 9: Dọn nợ tài liệu (docblock)

**Files:**
- Modify: `app/app/Integrations/Ai/Contracts/AiAssistantConnector.php` (`:16-19`)
- Modify: `app/app/Integrations/IntegrationsServiceProvider.php` (`:190-192` — nếu chưa dọn ở Task 3)

- [ ] **Step 1: Sửa docblock contract** `:16-19`: thay đoạn nói config sống ở `system_settings` group `ai_providers.<code>` → "config sống ở **bảng `ai_providers`** (super-admin quản qua `/admin/ai-providers`); tenant chọn `messaging_settings.ai_provider_code` ∈ list `is_active=true`." Cập nhật `:35` chú thích `code()` → "instance code (slug), do registry inject".

- [ ] **Step 2: Verify static analysis**

Run: `docker compose exec app vendor/bin/phpstan analyse app/app/Integrations/Ai --memory-limit=512M`
Expected: không lỗi mới.

- [ ] **Step 3: Commit**

```bash
git add app/app/Integrations/Ai/Contracts/AiAssistantConnector.php app/app/Integrations/IntegrationsServiceProvider.php
git commit -m "docs(ai): cap nhat docblock — config o bang ai_providers, code la instance slug"
```

---

## Task 10: FE hook `aiProviders.tsx`

**Files:**
- Create: `app/resources/js/admin/lib/aiProviders.tsx`

- [ ] **Step 1: Viết hook** (mirror `lib/systemSettings.tsx`):

```tsx
// Hooks cho /api/v1/admin/ai-providers/* — quản lý đa nhà cung cấp AI (adapter động).

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export type AiAdapter = 'anthropic' | 'openai_compatible' | 'manual';

export interface AiPreset {
    name: string;
    base_url: string | null;
    default_model: string | null;
}

export interface AiProviderRow {
    code: string;
    adapter: AiAdapter;
    display_name: string | null;
    has_api_key: boolean;
    base_url: string | null;
    default_model: string | null;
    pricing: Array<{ kind: string; unit: number; micro_vnd: number }>;
    is_active: boolean;
    sort_order?: number;
    notes?: string | null;
    capabilities: Record<string, boolean>;
    updated_at: string | null;
}

export interface AiProviderPayload {
    code?: string;
    adapter?: AiAdapter;
    display_name?: string | null;
    api_key?: string | null;
    base_url?: string | null;
    default_model?: string | null;
    pricing?: Array<{ kind: string; unit: number; micro_vnd: number }>;
    is_active?: boolean;
    sort_order?: number;
    notes?: string | null;
}

export function useAiProviders() {
    return useQuery({
        queryKey: ['ai-providers'],
        queryFn: async () =>
            (await api.get<{ data: AiProviderRow[]; adapters: { adapter: AiAdapter; presets: AiPreset[] }[] }>(
                '/admin/ai-providers',
            )).data,
    });
}

export function useCreateAiProvider() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (payload: AiProviderPayload) =>
            (await api.post<{ data: AiProviderRow }>('/admin/ai-providers', payload)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['ai-providers'] }),
    });
}

export function useUpdateAiProvider() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ code, payload }: { code: string; payload: AiProviderPayload }) =>
            (await api.patch<{ data: AiProviderRow }>(`/admin/ai-providers/${encodeURIComponent(code)}`, payload)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['ai-providers'] }),
    });
}

export function useDisableAiProvider() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (code: string) =>
            (await api.delete<{ data: { ok: boolean } }>(`/admin/ai-providers/${encodeURIComponent(code)}`)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['ai-providers'] }),
    });
}

export function useTestAiProvider() {
    return useMutation({
        mutationFn: async (code: string) =>
            (await api.post<{ data: { ok: boolean; reason?: string; sample?: string; message?: string } }>(
                `/admin/ai-providers/${encodeURIComponent(code)}/test`,
            )).data.data,
    });
}
```

- [ ] **Step 2: Typecheck**

Run: `docker compose exec vite npm run typecheck`
Expected: không lỗi type ở file mới.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/admin/lib/aiProviders.tsx
git commit -m "feat(admin-ui): hooks CRUD + test AI providers"
```

---

## Task 11: FE trang `AdminAiProvidersPage` + route + menu

**Files:**
- Create: `app/resources/js/admin/pages/settings/AdminAiProvidersPage.tsx`
- Modify: `app/resources/js/admin/AdminApp.tsx` (`:16`, `:38`)
- Modify: `app/resources/js/admin/AdminLayout.tsx` (`:6-17`, `:21-30`)

- [ ] **Step 1: Viết trang** (AntD Table + Modal Form; adapter chọn bằng `Radio.Group` — memory `ui-avoid-select-prefer-radio`; icon @ant-design/icons — memory `ui-use-font-icons-not-emoji`):

```tsx
// /admin/ai-providers — super-admin thêm/sửa/bật-tắt/test nhà cung cấp AI.
// Adapter động: anthropic | openai_compatible | manual. Nhiều instance cùng adapter.

import { useState } from 'react';
import { App, Button, Card, Form, Input, InputNumber, Modal, Radio, Space, Switch, Table, Tag } from 'antd';
import { ApiOutlined, PlusOutlined, ReloadOutlined, ThunderboltOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import {
    useAiProviders, useCreateAiProvider, useUpdateAiProvider, useDisableAiProvider, useTestAiProvider,
    type AiProviderRow, type AiAdapter, type AiPreset,
} from '../../lib/aiProviders';

const ADAPTER_LABEL: Record<AiAdapter, string> = {
    anthropic: 'Anthropic (Claude)',
    openai_compatible: 'OpenAI-compatible (GPT/DeepSeek/Qwen/OpenRouter/Gemini)',
    manual: 'Manual (test/dev)',
};

export function AdminAiProvidersPage() {
    const { data, isLoading, refetch } = useAiProviders();
    const create = useCreateAiProvider();
    const update = useUpdateAiProvider();
    const disable = useDisableAiProvider();
    const test = useTestAiProvider();
    const { message, modal } = App.useApp();
    const [form] = Form.useForm();
    const [editing, setEditing] = useState<AiProviderRow | null>(null);
    const [open, setOpen] = useState(false);

    const adapters = data?.adapters ?? [];
    const presetsFor = (a: AiAdapter): AiPreset[] => adapters.find((x) => x.adapter === a)?.presets ?? [];

    const openCreate = () => {
        setEditing(null);
        form.resetFields();
        form.setFieldsValue({ adapter: 'openai_compatible', is_active: true, sort_order: 0 });
        setOpen(true);
    };
    const openEdit = (row: AiProviderRow) => {
        setEditing(row);
        form.setFieldsValue({ ...row, api_key: '' });
        setOpen(true);
    };

    const applyPreset = (p: AiPreset) =>
        form.setFieldsValue({ base_url: p.base_url ?? '', default_model: p.default_model ?? '', display_name: p.name });

    const submit = async () => {
        const v = await form.validateFields();
        const onErr = (e: unknown) => message.error(errorMessage(e));
        if (editing) {
            update.mutate({ code: editing.code, payload: v }, {
                onSuccess: () => { message.success('Đã lưu provider.'); setOpen(false); },
                onError: onErr,
            });
        } else {
            create.mutate(v, {
                onSuccess: () => { message.success('Đã thêm provider.'); setOpen(false); },
                onError: onErr,
            });
        }
    };

    const runTest = (code: string) =>
        test.mutate(code, {
            onSuccess: (r) => r.ok
                ? message.success(`Kết nối OK: ${r.sample ?? ''}`)
                : message.warning(`Chưa OK (${r.reason}): ${r.message ?? ''}`),
            onError: (e) => message.error(errorMessage(e)),
        });

    const columns = [
        { title: 'Mã', dataIndex: 'code', key: 'code', render: (c: string) => <Tag>{c}</Tag> },
        { title: 'Loại (adapter)', dataIndex: 'adapter', key: 'adapter', render: (a: AiAdapter) => ADAPTER_LABEL[a] },
        { title: 'Tên hiển thị', dataIndex: 'display_name', key: 'display_name' },
        { title: 'Model', dataIndex: 'default_model', key: 'default_model' },
        { title: 'API key', dataIndex: 'has_api_key', key: 'has_api_key', render: (v: boolean) => v ? <Tag color="green">Đã đặt</Tag> : <Tag>Chưa</Tag> },
        { title: 'Bật', dataIndex: 'is_active', key: 'is_active', render: (v: boolean) => v ? <Tag color="blue">Đang bật</Tag> : <Tag>Tắt</Tag> },
        {
            title: 'Hành động', key: 'actions',
            render: (_: unknown, row: AiProviderRow) => (
                <Space>
                    <Button size="small" onClick={() => openEdit(row)}>Sửa</Button>
                    <Button size="small" icon={<ThunderboltOutlined />} loading={test.isPending} onClick={() => runTest(row.code)}>Test</Button>
                    <Button size="small" danger onClick={() => modal.confirm({
                        title: `Tắt provider ${row.code}?`,
                        onOk: () => disable.mutate(row.code, { onSuccess: () => message.success('Đã tắt.') }),
                    })}>Tắt</Button>
                </Space>
            ),
        },
    ];

    return (
        <Card
            title={<Space><ApiOutlined /> Nhà cung cấp AI</Space>}
            extra={
                <Space>
                    <Button icon={<ReloadOutlined />} onClick={() => refetch()}>Tải lại</Button>
                    <Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>Thêm provider</Button>
                </Space>
            }
        >
            <Table rowKey="code" loading={isLoading} dataSource={data?.data ?? []} columns={columns} pagination={false} />

            <Modal
                open={open}
                title={editing ? `Sửa ${editing.code}` : 'Thêm nhà cung cấp AI'}
                onCancel={() => setOpen(false)}
                onOk={submit}
                confirmLoading={create.isPending || update.isPending}
                destroyOnClose
            >
                <Form form={form} layout="vertical">
                    <Form.Item name="adapter" label="Loại API (adapter)" rules={[{ required: true }]}>
                        <Radio.Group disabled={!!editing} optionType="button" buttonStyle="solid">
                            {(['anthropic', 'openai_compatible', 'manual'] as AiAdapter[]).map((a) => (
                                <Radio.Button key={a} value={a}>{ADAPTER_LABEL[a]}</Radio.Button>
                            ))}
                        </Radio.Group>
                    </Form.Item>

                    <Form.Item shouldUpdate={(p, c) => p.adapter !== c.adapter} noStyle>
                        {() => {
                            const a = form.getFieldValue('adapter') as AiAdapter;
                            const presets = presetsFor(a);
                            return presets.length > 1 ? (
                                <Form.Item label="Mẫu nhanh">
                                    <Space wrap>
                                        {presets.map((p) => (
                                            <Button key={p.name} size="small" onClick={() => applyPreset(p)}>{p.name}</Button>
                                        ))}
                                    </Space>
                                </Form.Item>
                            ) : null;
                        }}
                    </Form.Item>

                    <Form.Item name="code" label="Mã (slug, duy nhất)" rules={[
                        { required: !editing, message: 'Nhập mã slug' },
                        { pattern: /^[a-z0-9][a-z0-9_-]{1,31}$/, message: 'Chỉ a-z 0-9 _ - , 2-32 ký tự' },
                    ]}>
                        <Input placeholder="vd: deepseek-prod" disabled={!!editing} />
                    </Form.Item>
                    <Form.Item name="display_name" label="Tên hiển thị"><Input placeholder="vd: DeepSeek (prod)" /></Form.Item>
                    <Form.Item name="base_url" label="Base URL" rules={[{ type: 'url' }]}>
                        <Input placeholder="https://api.deepseek.com" />
                    </Form.Item>
                    <Form.Item name="default_model" label="Model mặc định"><Input placeholder="deepseek-chat" /></Form.Item>
                    <Form.Item name="api_key" label="API key" extra={editing ? 'Để trống = giữ nguyên key cũ.' : undefined}>
                        <Input.Password placeholder="sk-..." />
                    </Form.Item>
                    <Form.Item name="sort_order" label="Thứ tự"><InputNumber min={0} max={9999} /></Form.Item>
                    <Form.Item name="is_active" label="Kích hoạt" valuePropName="checked"><Switch /></Form.Item>
                </Form>
            </Modal>
        </Card>
    );
}
```

- [ ] **Step 2: Thêm route** — `AdminApp.tsx`: import `:16` thêm `import { AdminAiProvidersPage } from './pages/settings/AdminAiProvidersPage';`, và `:38` sau `<Route path="settings" …/>` thêm:
```tsx
                    <Route path="ai-providers" element={<AdminAiProvidersPage />} />
```

- [ ] **Step 3: Thêm menu** — `AdminLayout.tsx`: import `:6-17` thêm `ApiOutlined`, và `SIDEBAR_ITEMS` `:21-30` thêm (sau Hệ thống):
```tsx
    { key: '/admin/ai-providers', icon: <ApiOutlined />, label: 'Nhà cung cấp AI' },
```

- [ ] **Step 4: Typecheck + lint + build**

Run: `docker compose exec vite npm run typecheck`
Run: `docker compose exec vite npm run lint`
Run: `docker compose exec vite npm run build`
Expected: tất cả PASS (không lỗi).

- [ ] **Step 5: Smoke thủ công** — mở `http://localhost:8000/admin/ai-providers` (đăng nhập admin): thấy menu, bảng, nút Thêm; tạo `deepseek-prod` (openai_compatible), Test connection trả kết quả; tạo thêm `qwen-cheap` cùng adapter để xác nhận coexist.

- [ ] **Step 6: Commit**

```bash
git add app/resources/js/admin/pages/settings/AdminAiProvidersPage.tsx app/resources/js/admin/AdminApp.tsx app/resources/js/admin/AdminLayout.tsx
git commit -m "feat(admin-ui): trang quan ly nha cung cap AI (them/sua/test, adapter dong)"
```

---

## Task 12: Chạy full suite + cập nhật tài liệu

**Files:**
- Modify: `docs/specs/0024-omnichannel-messaging.md`

- [ ] **Step 1: Full backend suite + pint + phpstan**

Run: `docker compose exec app php artisan test`
Run: `docker compose exec app vendor/bin/pint --test`
Run: `docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M`
Expected: tất cả xanh (coverage ≥ 60% như CI yêu cầu).

- [ ] **Step 2: Full FE checks**

Run: `docker compose exec vite npm run lint && docker compose exec vite npm run typecheck && docker compose exec vite npm run build`
Expected: xanh.

- [ ] **Step 3: Cập nhật SPEC-0024** — thêm mục ngắn vào `docs/specs/0024-omnichannel-messaging.md`: mô hình adapter động (`code`=instance slug, `adapter`∈{anthropic,openai_compatible,manual}), nhiều instance cùng adapter, presets, base_url HTTPS-only, rate-limit ai-suggestion theo tenant, circuit breaker intent-classify. Tham chiếu spec `docs/superpowers/specs/2026-05-21-ai-providers-multi-adapter-and-hardening-design.md`.

- [ ] **Step 4: Commit**

```bash
git add docs/specs/0024-omnichannel-messaging.md
git commit -m "docs(messaging): ghi nhan adapter dong AI providers + hardening (SPEC-0024)"
```

---

## Self-Review (đã rà soát)

**Spec coverage:** §3 data model→Task 1; §4 registry/connector→Task 2,3; §5 controller→Task 5; §6.1 base_url→Task 4,5; §6.2 rate-limit→Task 7; §6.3 circuit breaker→Task 8; §6.4 docblock→Task 3,9; §7 FE→Task 10,11; §8 test→Task 2,4,6,7,8; §9 build sequence→thứ tự Task 1-12. Gemini = preset openai_compatible (Task 5 PRESETS) — không connector riêng (khớp quyết định).

**Placeholder scan:** Task 7 Step 3 cố ý để `markTestIncomplete` + ghi chú đọc `MessagingAiSuggestionTest::setUp` để copy setup (không dựng được fixture chính xác mà không đọc file đó lúc thực thi) — đây là chỉ dẫn rõ ràng, không phải placeholder mơ hồ. Mọi step code khác đã đầy đủ.

**Type consistency:** `adapter` ∈ {anthropic, openai_compatible, manual} nhất quán BE↔FE; `code()` connector trả instance code (Task 3) khớp registry `makeWith(['code'=>$code])` (Task 2); hook `AiProviderRow`/`AiProviderPayload` (Task 10) khớp `present()` (Task 5); `useAiProviders` trả `{data, adapters}` khớp `index()` (Task 5 Step 2).
