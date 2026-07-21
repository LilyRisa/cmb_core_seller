# Admin Redesign — Phase 2b: AI Configuration Cluster Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** De-duplicate the 3 hand-rolled credential-input patterns across the 5-page AI-configuration
cluster onto the shared `SecretInput` component, and generalize the "test-before-save" gate that
already exists on `AdminTranscriptionPage`/`AdminVisualRerankPage` to the 3 pages that lack it
(`AdminAiProvidersPage`, `AdminAiSupportPage`, `AdminMarketingAiProvidersPage`).

**Architecture:** `SecretInput` swap is pure frontend (3 pages). The test-before-save gate cannot
be a pure frontend change: `AdminTranscriptionPage`/`AdminVisualRerankPage`'s gate works because
those pages only *select* an already-persisted `AiProvider` row (created earlier on
`AdminAiProvidersPage`) and their "Lưu" just writes a `system_setting` pointer, gated on a
`{role}_verified` boolean column already stored on that row. The 3 target pages have no such
two-tier structure — they *are* the credential-entry form, and the underlying connector classes
(`ClaudeConnector`, `OpenAiConnector`) can only resolve credentials by looking up an already-saved
DB row by `code` (see `AiAssistantRegistry::make()`/`for()`), so there is no way to test *unsaved*
form values through the existing registry. This plan adds one small, stateless backend service —
`CredentialProbe`, living in the Integration layer (`app/app/Integrations/Ai/`, the one place all
three owning modules — Messaging, Support, Marketing — may import from without violating the
module-dependency rule) — that performs the same raw-HTTP connectivity check as
`ClaudeConnector`/`OpenAiConnector`/`SupportAiClient` but against credentials passed directly in
the request body, never touching the database. Each of the 3 pages gets a small `test-draft`
route wired to this shared service, and the frontend disables "Lưu" until a test against the
*current* form values has passed.

**Tech Stack:** Laravel 11 (`Http` facade, `Http::fake()` for tests), React 18, Ant Design 5
(`Form.useWatch`), TanStack Query, TypeScript.

## Global Constraints

- Admin-only scope: nothing in `resources/js/app.tsx` (tenant-facing app) is touched.
- All 5 pages (`AdminAiProvidersPage`, `AdminAiSupportPage`, `AdminMarketingAiProvidersPage`,
  `AdminTranscriptionPage`, `AdminVisualRerankPage`) **stay separate routes/pages** — the design
  spec's §2 non-goals explicitly forbid merging them into one hub. This plan only touches page
  internals and the sidebar-adjacent icon inside `AdminMarketingAiProvidersPage.tsx`'s own Card
  title; it does not touch routing.
- **No secret-value masking is introduced anywhere in this plan.** `SecretInput` deliberately shows
  credential values in plaintext per an explicit, named project-owner decision (its own code
  comment: `[TIKTOK-REVIEW-TEMP] Theo yêu cầu chủ dự án: KHÔNG che giá trị nữa`), corrected into the
  design spec's §5.3. Every step below either reuses `SecretInput` as-is or newly exposes a
  plaintext `api_key` field server-side (Task 4) — never hides or masks one.
- User-facing strings are Vietnamese; code/DB/routes/identifiers are English (per `CLAUDE.md`).
- No visual/theme changes beyond what's needed to wire the new controls (Ant Design defaults only).
- Module dependency rule (PR-blocking, per `CLAUDE.md` / `docs/01-architecture/extensibility-rules.md`):
  modules communicate only through `Contracts/` or domain events — never `use` another module's
  `Services/` internals. The new `CredentialProbe` therefore lives in the Integration layer
  (`CMBcoreSeller\Integrations\Ai`), not inside any one module, exactly like the existing
  `AiAssistantRegistry` that `Messaging\Http\Controllers\AdminAiProviderController` already imports
  from there.
- **No JS test runner exists in this repo** (`package.json` has no vitest/jest — see
  [[test-verify-baseline]]). Every frontend task's verification step is
  `npm run typecheck && npm run lint && npm run build` (run from `app/`) plus a manual
  browser-verification script with exact numbered steps.
- Run all `npm run *`/`composer`/`artisan`/`vendor/bin/*` commands from `app/` (per `CLAUDE.md`).
- Icons from `@ant-design/icons` only, never emoji (memory `ui-use-font-icons-not-emoji`).

## Findings from reading the 5 pages (read before starting any task)

1. **`SecretInput`'s real contract** (`app/resources/js/admin/components/SecretInput.tsx`):
   `{ value: string | null; onSave: (newValue: string) => void }`. When not editing it renders the
   plaintext `value` (or `(chưa đặt)` if empty/null) in a read-only `Input` plus an "Đặt giá trị"
   button; clicking it opens an empty draft field, and `onSave` only fires (with the non-empty
   draft) when the admin clicks "Lưu" inside that inline control — it **cannot** save an empty
   string (blocks with "Giá trị trống."). There is no way to explicitly blank out a secret through
   `SecretInput` — the only place in the codebase that clears a secret is `SettingRow`'s separate
   "Khôi phục từ env" `Popconfirm`. This plan accepts that same limitation on all 3 pages (matches
   existing `SecretInput` semantics everywhere else it's used — not a new restriction).
2. **`AdminTranscriptionPage` and `AdminVisualRerankPage` have NO credential input at all** — they
   only `Radio`-select among already-created `AiProvider` rows (filtered by `role=transcription` /
   `role=vision`), which were created on `AdminAiProvidersPage`. Their gate mechanism is verified
   **identical** between the two pages: `useTest{Transcription,VisualRerank}`'s mutation
   `onSuccess` invalidates the config query, whose refetch brings back the just-updated
   `{transcription,vision}_verified` boolean (written server-side by
   `AdminTranscriptionController::test()` / `AdminVisualRerankController::test()`), and "Lưu" is
   `disabled={selected !== NONE && providers.find(p => p.code === selected)?.{x}_verified !== true}`.
   No divergence between the two — **no code changes needed on either page in this phase.**
3. **`AdminMarketingAiProviderController::safe()`** (`app/app/Modules/Marketing/Http/Controllers/AdminMarketingAiProviderController.php`)
   never includes `api_key` in its response at all — only a `has_key: bool`. This is unlike
   `AdminAiProviderController::present()` (Messaging), which explicitly returns the decrypted
   plaintext `api_key`. Since `SecretInput` needs an actual value to display, Task 4 below adds
   `api_key` to `safe()`'s response, matching the Messaging page's already-established,
   deliberately-plaintext convention for this super-admin-only surface.
4. **The connector classes require a persisted DB row.** `AiAssistantRegistry::make($code)` /
   `::for($code)` both call `AiProvider::query()->find($code)` internally — there is no path to
   resolve a connector from in-memory/unsaved credentials. This is why the test-before-save gate
   for the 3 target pages needs a *separate*, stateless prober rather than literally reusing
   `AdminAiProviderController::test()` (which tests an already-saved row) — see Architecture above.

---

### Task 1: Shared `CredentialProbe` service (Integration layer)

**Files:**
- Create: `app/app/Integrations/Ai/CredentialProbe.php`
- Test: `app/tests/Unit/Integrations/Ai/CredentialProbeTest.php`

**Interfaces:**
- Consumes: `Illuminate\Support\Facades\Http` (`Http::fake()` in tests), `config('ai.http.connect_timeout')`
  (already used with the same default by `ClaudeConnector`/`OpenAiConnector` — reused here for
  consistency, safe even if unset since `config()` falls back to the literal default).
- Produces (for Tasks 2-4 to consume):
  ```php
  namespace CMBcoreSeller\Integrations\Ai;

  class CredentialProbe
  {
      /** @return array{ok:bool, message:?string} */
      public function probeChat(string $adapter, ?string $baseUrl, ?string $apiKey, ?string $model): array;

      /** @return array{ok:bool, message:?string} */
      public function probeEmbedding(?string $baseUrl, ?string $apiKey, ?string $model): array;
  }
  ```
  `probeChat` supports `adapter` values `anthropic` and `openai_compatible` only (the two adapters
  with a fixed, well-known request/response shape — mirrors `ClaudeConnector::generateReply()` and
  `OpenAiConnector::generateReply()` exactly, verified by reading both files). Any other adapter
  value returns `{ok:false, message:"Adapter [...] không hỗ trợ test nháp."}` — callers (Tasks 2-4)
  are responsible for not offering the gate for `custom_http`/`manual` adapters in the first place.
  `probeEmbedding` mirrors `OpenAiConnector::embed()` / `SupportAiClient::embed()` (both raw HTTP
  `POST {base}/v1/embeddings`, OpenAI-compatible only — there is no Anthropic embedding API).

- [ ] **Step 1: Write the failing unit test**

Create `app/tests/Unit/Integrations/Ai/CredentialProbeTest.php`:

```php
<?php

namespace Tests\Unit\Integrations\Ai;

use CMBcoreSeller\Integrations\Ai\CredentialProbe;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CredentialProbeTest extends TestCase
{
    public function test_probe_chat_openai_compatible_reports_ok_on_success(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response(
                ['choices' => [['message' => ['content' => 'pong']]]],
                200,
            ),
        ]);

        $result = (new CredentialProbe())->probeChat('openai_compatible', null, 'sk-test', 'gpt-4o-mini');

        $this->assertTrue($result['ok']);
    }

    public function test_probe_chat_missing_api_key_fails_without_any_http_call(): void
    {
        Http::fake();

        $result = (new CredentialProbe())->probeChat('openai_compatible', null, null, 'gpt-4o-mini');

        $this->assertFalse($result['ok']);
        Http::assertNothingSent();
    }

    public function test_probe_chat_anthropic_surfaces_provider_error_message(): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response(
                ['error' => ['message' => 'invalid x-api-key']],
                401,
            ),
        ]);

        $result = (new CredentialProbe())->probeChat('anthropic', null, 'bad-key', 'claude-opus-4-7');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('invalid x-api-key', (string) $result['message']);
    }

    public function test_probe_chat_rejects_unprobeable_adapter(): void
    {
        Http::fake();

        $result = (new CredentialProbe())->probeChat('custom_http', null, 'sk-test', 'model');

        $this->assertFalse($result['ok']);
        Http::assertNothingSent();
    }

    public function test_probe_embedding_reports_dimension_on_success(): void
    {
        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response(
                ['data' => [['embedding' => [0.1, 0.2, 0.3]]]],
                200,
            ),
        ]);

        $result = (new CredentialProbe())->probeEmbedding(null, 'sk-test', 'text-embedding-3-small');

        $this->assertTrue($result['ok']);
        $this->assertStringContainsString('dim 3', (string) $result['message']);
    }

    public function test_probe_embedding_missing_model_fails_without_http_call(): void
    {
        Http::fake();

        $result = (new CredentialProbe())->probeEmbedding(null, 'sk-test', null);

        $this->assertFalse($result['ok']);
        Http::assertNothingSent();
    }
}
```

- [ ] **Step 2: Run the test and confirm it fails**

```bash
php artisan test tests/Unit/Integrations/Ai/CredentialProbeTest.php
```
Expected: FAIL — class `CMBcoreSeller\Integrations\Ai\CredentialProbe` not found.

- [ ] **Step 3: Implement `CredentialProbe`**

Create `app/app/Integrations/Ai/CredentialProbe.php`:

```php
<?php

namespace CMBcoreSeller\Integrations\Ai;

use Illuminate\Support\Facades\Http;

/**
 * Test kết nối "nháp" (draft) — gọi thẳng API provider bằng credentials ĐANG NHẬP trên
 * form admin, KHÔNG cần lưu trước. Dùng để gate nút "Lưu" ở 3 trang cấu hình AI chưa có
 * test-before-save (Nhà cung cấp AI / AI Trợ giúp / AI Marketing) — xem
 * docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md §5.4.
 *
 * KHÁC với AdminAiProviderController::test() (Messaging) / AdminTranscriptionController::test()
 * / AdminVisualRerankController::test(): những controller đó test provider ĐÃ LƯU, resolve qua
 * AiAssistantRegistry (bắt buộc có row DB theo `code`). Class này test THẲNG giá trị chưa lưu,
 * nên không thể đi qua registry — tự làm request HTTP tối giản, mirror đúng request/response
 * shape của ClaudeConnector::generateReply()/OpenAiConnector::generateReply()/embed().
 *
 * Sống ở tầng Integrations (không thuộc module nào) vì được 3 module dùng chung
 * (Messaging, Support, Marketing) — modules không được `use` Services/ của nhau
 * (docs/01-architecture/extensibility-rules.md), nhưng module nào cũng được phép import
 * tầng Integrations, đúng pattern AiAssistantRegistry đã dùng.
 *
 * Chỉ hỗ trợ adapter có request/response shape CỐ ĐỊNH: anthropic, openai_compatible.
 * `custom_http` (template do admin tự định nghĩa) và `manual` (stub, không có backend thật)
 * KHÔNG probe được chung ⇒ trả ok:false kèm lý do; trang gọi phải tự bỏ qua gate cho 2 loại đó.
 */
class CredentialProbe
{
    /** @return array{ok:bool, message:?string} */
    public function probeChat(string $adapter, ?string $baseUrl, ?string $apiKey, ?string $model): array
    {
        if (! $apiKey) {
            return ['ok' => false, 'message' => 'Chưa nhập API key.'];
        }

        return match ($adapter) {
            'anthropic' => $this->probeAnthropicChat($baseUrl, $apiKey, $model),
            'openai_compatible' => $this->probeOpenAiChat($baseUrl, $apiKey, $model),
            default => ['ok' => false, 'message' => "Adapter [{$adapter}] không hỗ trợ test nháp."],
        };
    }

    /** @return array{ok:bool, message:?string} */
    public function probeEmbedding(?string $baseUrl, ?string $apiKey, ?string $model): array
    {
        if (! $apiKey) {
            return ['ok' => false, 'message' => 'Chưa nhập API key.'];
        }
        if (! $model) {
            return ['ok' => false, 'message' => 'Chưa nhập model embedding.'];
        }

        $base = $this->openAiBase($baseUrl);

        try {
            $response = Http::withToken($apiKey)
                ->connectTimeout((int) config('ai.http.connect_timeout', 10))
                ->timeout(20)
                ->post($base.'/v1/embeddings', ['model' => $model, 'input' => 'ping']);

            if (! $response->successful()) {
                $error = (array) $response->json('error');

                return ['ok' => false, 'message' => 'Lỗi '.$response->status().': '.($error['message'] ?? $response->body())];
            }

            $dim = count((array) $response->json('data.0.embedding', []));
            if ($dim === 0) {
                return ['ok' => false, 'message' => 'Provider trả vector rỗng — kiểm tra lại model embedding.'];
            }

            return ['ok' => true, 'message' => "OK (dim {$dim})"];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Lỗi kết nối: '.$e->getMessage()];
        }
    }

    /** @return array{ok:bool, message:?string} */
    private function probeAnthropicChat(?string $baseUrl, string $apiKey, ?string $model): array
    {
        if (! $model) {
            return ['ok' => false, 'message' => 'Chưa nhập model.'];
        }
        $base = rtrim($baseUrl ?: 'https://api.anthropic.com', '/');

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
                ->connectTimeout((int) config('ai.http.connect_timeout', 10))
                ->timeout(20)
                ->post($base.'/v1/messages', [
                    'model' => $model,
                    'max_tokens' => 8,
                    'messages' => [['role' => 'user', 'content' => 'ping']],
                ]);

            if (! $response->successful()) {
                $error = (array) $response->json('error');

                return ['ok' => false, 'message' => 'Lỗi '.$response->status().': '.($error['message'] ?? $response->body())];
            }

            return ['ok' => true, 'message' => 'Kết nối OK.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Lỗi kết nối: '.$e->getMessage()];
        }
    }

    /** @return array{ok:bool, message:?string} */
    private function probeOpenAiChat(?string $baseUrl, string $apiKey, ?string $model): array
    {
        if (! $model) {
            return ['ok' => false, 'message' => 'Chưa nhập model.'];
        }
        $base = $this->openAiBase($baseUrl);

        try {
            $response = Http::withToken($apiKey)
                ->connectTimeout((int) config('ai.http.connect_timeout', 10))
                ->timeout(20)
                ->post($base.'/v1/chat/completions', [
                    'model' => $model,
                    'max_tokens' => 8,
                    'messages' => [['role' => 'user', 'content' => 'ping']],
                ]);

            if (! $response->successful()) {
                $error = (array) $response->json('error');

                return ['ok' => false, 'message' => 'Lỗi '.$response->status().': '.($error['message'] ?? $response->body())];
            }

            return ['ok' => true, 'message' => 'Kết nối OK.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Lỗi kết nối: '.$e->getMessage()];
        }
    }

    private function openAiBase(?string $baseUrl): string
    {
        $base = rtrim($baseUrl ?: 'https://api.openai.com', '/');
        if (str_ends_with($base, '/v1')) {
            $base = substr($base, 0, -3);
        }

        return $base;
    }
}
```

- [ ] **Step 4: Run the test and confirm it passes**

```bash
php artisan test tests/Unit/Integrations/Ai/CredentialProbeTest.php
```
Expected: PASS (6 tests green).

- [ ] **Step 5: Static analysis and format check**

```bash
vendor/bin/pint --test app/Integrations/Ai/CredentialProbe.php tests/Unit/Integrations/Ai/CredentialProbeTest.php
vendor/bin/phpstan analyse app/Integrations/Ai/CredentialProbe.php
```
Expected: both succeed (run `vendor/bin/pint` without `--test` to auto-fix if it reports diffs,
then re-run `--test`).

- [ ] **Step 6: Commit**

```bash
git add app/Integrations/Ai/CredentialProbe.php tests/Unit/Integrations/Ai/CredentialProbeTest.php
git commit -m "feat(ai): thêm CredentialProbe — test kết nối AI bằng credentials chưa lưu"
```

---

### Task 2: Messaging backend — `/admin/ai-providers/test-draft`

**Files:**
- Modify: `app/app/Modules/Messaging/Http/Controllers/AdminAiProviderController.php`
- Modify: `app/app/Modules/Messaging/Http/routes.php`
- Test: `app/tests/Feature/Admin/AdminAiProviderTestDraftTest.php`

**Interfaces:**
- Consumes: `CMBcoreSeller\Integrations\Ai\CredentialProbe::probeChat()` (Task 1).
- Produces: `POST /api/v1/admin/ai-providers/test-draft` — body
  `{ adapter: 'anthropic'|'openai_compatible', base_url?: string|null, api_key?: string|null, default_model?: string|null }`,
  response `{ "data": { "ok": bool, "message": string|null } }`. 422 if `adapter` is not one of the
  two probeable values (frontend, Task 5, only shows the gate for those two anyway).

- [ ] **Step 1: Write the failing feature test**

Create `app/tests/Feature/Admin/AdminAiProviderTestDraftTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminAiProviderTestDraftTest extends TestCase
{
    public function test_draft_test_reports_ok_on_successful_probe(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');

        Http::fake([
            'https://api.deepseek.com/v1/chat/completions' => Http::response(
                ['choices' => [['message' => ['content' => 'pong']]]],
                200,
            ),
        ]);

        $resp = $this->postJson('/api/v1/admin/ai-providers/test-draft', [
            'adapter' => 'openai_compatible',
            'base_url' => 'https://api.deepseek.com',
            'api_key' => 'sk-test',
            'default_model' => 'deepseek-chat',
        ])->assertOk();

        $resp->assertJsonPath('data.ok', true);
    }

    public function test_draft_test_rejects_unprobeable_adapter(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');

        $this->postJson('/api/v1/admin/ai-providers/test-draft', [
            'adapter' => 'custom_http',
            'api_key' => 'sk-test',
        ])->assertStatus(422);
    }
}
```

- [ ] **Step 2: Run the test and confirm it fails**

```bash
php artisan test tests/Feature/Admin/AdminAiProviderTestDraftTest.php
```
Expected: FAIL — route `test-draft` doesn't exist yet (404).

- [ ] **Step 3: Add the controller method**

In `app/app/Modules/Messaging/Http/Controllers/AdminAiProviderController.php`, add the import
(alphabetical, near the other `use CMBcoreSeller\...` lines, after
`use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;`):

```php
use CMBcoreSeller\Integrations\Ai\CredentialProbe;
```

Add the method after `test()` (before `private function probe(...)`):

```php
    /**
     * Test kết nối bằng credentials ĐANG NHẬP trên form (chưa lưu) — gate nút "Lưu" ở modal
     * tạo/sửa provider (spec 2026-07-21 §5.4). Chỉ hỗ trợ adapter anthropic/openai_compatible
     * (xem CredentialProbe); custom_http/manual không gate, FE tự bỏ qua nút Test cho 2 loại đó.
     */
    public function testDraft(Request $request, CredentialProbe $probe): JsonResponse
    {
        $data = $request->validate([
            'adapter' => ['required', 'string', Rule::in(['anthropic', 'openai_compatible'])],
            'base_url' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:512'],
            'default_model' => ['nullable', 'string', 'max:64'],
        ]);

        return response()->json(['data' => $probe->probeChat(
            $data['adapter'],
            $data['base_url'] ?? null,
            $data['api_key'] ?? null,
            $data['default_model'] ?? null,
        )]);
    }
```

- [ ] **Step 4: Register the route**

In `app/app/Modules/Messaging/Http/routes.php`, inside the existing `ai-providers` group (right
after the `test` route, before the closing `});` at line 271):

```php
        Route::post('{code}/test', [AdminAiProviderController::class, 'test'])
            ->where('code', '[a-z0-9][a-z0-9_-]*')->name('admin.ai-providers.test');
        Route::post('test-draft', [AdminAiProviderController::class, 'testDraft'])
            ->name('admin.ai-providers.test-draft');
    });
```

- [ ] **Step 5: Run the test and confirm it passes**

```bash
php artisan test tests/Feature/Admin/AdminAiProviderTestDraftTest.php
```
Expected: PASS (2 tests green).

- [ ] **Step 6: Static analysis, format check, regression**

```bash
vendor/bin/pint --test app/Modules/Messaging/Http/Controllers/AdminAiProviderController.php app/Modules/Messaging/Http/routes.php tests/Feature/Admin/AdminAiProviderTestDraftTest.php
vendor/bin/phpstan analyse app/Modules/Messaging/Http/Controllers/AdminAiProviderController.php
php artisan test --filter=AdminAiProvider
```
Expected: all succeed; no new failures beyond the pre-existing baseline ([[test-verify-baseline]]).

- [ ] **Step 7: Commit**

```bash
git add app/Modules/Messaging/Http/Controllers/AdminAiProviderController.php app/Modules/Messaging/Http/routes.php tests/Feature/Admin/AdminAiProviderTestDraftTest.php
git commit -m "feat(admin): endpoint test-draft cho Nhà cung cấp AI (gate Lưu theo spec 5.4)"
```

---

### Task 3: Support backend — `/admin/ai-support/test-draft`

**Files:**
- Create: `app/app/Modules/Support/Http/Controllers/AdminAiSupportController.php`
- Modify: `app/app/Modules/Support/Http/routes.php`
- Test: `app/tests/Feature/Admin/AdminAiSupportTestDraftTest.php`

**Interfaces:**
- Consumes: `CMBcoreSeller\Integrations\Ai\CredentialProbe::probeChat('openai_compatible', ...)` /
  `::probeEmbedding()` (Task 1) — `AdminAiSupportPage` is documented as OpenAI-compatible-only
  (its own page text: "OpenAI-compatible. Base URL = GỐC host..."), matching `SupportAiClient`'s
  hardcoded shape (verified by reading `app/app/Modules/Support/Services/SupportAiClient.php`).
- Produces: `POST /api/v1/admin/ai-support/test-draft` — body
  `{ kind: 'chat'|'embedding', base_url?: string, api_key?: string, model?: string }`, response
  `{ "data": { "ok": bool, "message": string|null } }`.

- [ ] **Step 1: Write the failing feature test**

Create `app/tests/Feature/Admin/AdminAiSupportTestDraftTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminAiSupportTestDraftTest extends TestCase
{
    public function test_chat_probe_surfaces_provider_error_message(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response(
                ['error' => ['message' => 'invalid api key']],
                401,
            ),
        ]);

        $resp = $this->postJson('/api/v1/admin/ai-support/test-draft', [
            'kind' => 'chat',
            'base_url' => 'https://openrouter.ai/api',
            'api_key' => 'sk-bad',
            'model' => 'openai/gpt-4o-mini',
        ])->assertOk();

        $resp->assertJsonPath('data.ok', false);
        $this->assertStringContainsString('invalid api key', (string) $resp->json('data.message'));
    }

    public function test_embedding_probe_reports_ok_with_dimension(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');

        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response(
                ['data' => [['embedding' => [0.1, 0.2]]]],
                200,
            ),
        ]);

        $resp = $this->postJson('/api/v1/admin/ai-support/test-draft', [
            'kind' => 'embedding',
            'base_url' => 'https://api.openai.com',
            'api_key' => 'sk-test',
            'model' => 'text-embedding-3-small',
        ])->assertOk();

        $resp->assertJsonPath('data.ok', true);
    }
}
```

- [ ] **Step 2: Run the test and confirm it fails**

```bash
php artisan test tests/Feature/Admin/AdminAiSupportTestDraftTest.php
```
Expected: FAIL — route doesn't exist yet (404).

- [ ] **Step 3: Create the controller**

Create `app/app/Modules/Support/Http/Controllers/AdminAiSupportController.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Support\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Integrations\Ai\CredentialProbe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Test kết nối "nháp" (chưa lưu) cho form cấu hình AI Trợ giúp (Hỏi AI). Trang FE
 * (`/admin/ai-support`) đọc/ghi thẳng `/admin/system-settings` (không có controller CRUD
 * riêng) — route/controller NÀY chỉ phục vụ gate "Lưu" theo docs/superpowers/specs/
 * 2026-07-21-admin-panel-ux-redesign-design.md §5.4, không đụng tới system_setting.
 * Chat/embedding của Support luôn OpenAI-compatible (xem SupportAiClient) ⇒ không cần
 * tham số adapter như trang Nhà cung cấp AI.
 */
class AdminAiSupportController extends Controller
{
    public function testDraft(Request $request, CredentialProbe $probe): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', 'string', Rule::in(['chat', 'embedding'])],
            'base_url' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:512'],
            'model' => ['nullable', 'string', 'max:64'],
        ]);

        $result = $data['kind'] === 'chat'
            ? $probe->probeChat('openai_compatible', $data['base_url'] ?? null, $data['api_key'] ?? null, $data['model'] ?? null)
            : $probe->probeEmbedding($data['base_url'] ?? null, $data['api_key'] ?? null, $data['model'] ?? null);

        return response()->json(['data' => $result]);
    }
}
```

- [ ] **Step 4: Register the route**

In `app/app/Modules/Support/Http/routes.php`, add the import at the top (alphabetical, near the
other `Http\Controllers` imports):

```php
use CMBcoreSeller\Modules\Support\Http\Controllers\AdminAiSupportController;
```

Add a new route group, right after the existing `admin/support-conversations` group closes:

```php
Route::middleware(['web', 'auth:admin_web', 'throttle:60,1'])
    ->prefix('api/v1/admin/ai-support')->group(function () {
        Route::post('test-draft', [AdminAiSupportController::class, 'testDraft'])
            ->name('admin.ai-support.test-draft');
    });
```

- [ ] **Step 5: Run the test and confirm it passes**

```bash
php artisan test tests/Feature/Admin/AdminAiSupportTestDraftTest.php
```
Expected: PASS (2 tests green).

- [ ] **Step 6: Static analysis, format check, regression**

```bash
vendor/bin/pint --test app/Modules/Support/Http/Controllers/AdminAiSupportController.php app/Modules/Support/Http/routes.php tests/Feature/Admin/AdminAiSupportTestDraftTest.php
vendor/bin/phpstan analyse app/Modules/Support/Http/Controllers/AdminAiSupportController.php
php artisan test --filter=Support
```
Expected: all succeed; no new failures beyond baseline.

- [ ] **Step 7: Commit**

```bash
git add app/Modules/Support/Http/Controllers/AdminAiSupportController.php app/Modules/Support/Http/routes.php tests/Feature/Admin/AdminAiSupportTestDraftTest.php
git commit -m "feat(admin): endpoint test-draft cho AI Trợ giúp (gate Lưu theo spec 5.4)"
```

---

### Task 4: Marketing backend — expose plaintext `api_key` + `/admin/marketing-ai-providers/test-draft`

**Files:**
- Modify: `app/app/Modules/Marketing/Http/Controllers/AdminMarketingAiProviderController.php`
- Modify: `app/app/Modules/Marketing/Http/routes.php`
- Test: `app/tests/Feature/Admin/AdminMarketingAiProviderTestDraftTest.php`

**Interfaces:**
- Consumes: `CMBcoreSeller\Integrations\Ai\CredentialProbe::probeChat()` (Task 1).
- Produces: `GET /api/v1/admin/marketing-ai-providers` response rows now include `api_key: string|null`
  (plaintext — see Finding 3 above); `POST /api/v1/admin/marketing-ai-providers/test-draft` — same
  shape as Task 2's endpoint (`adapter`/`base_url`/`api_key`/`default_model` → `{ok, message}`).

- [ ] **Step 1: Write the failing feature test**

Create `app/tests/Feature/Admin/AdminMarketingAiProviderTestDraftTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Modules\Marketing\Models\MarketingAiProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminMarketingAiProviderTestDraftTest extends TestCase
{
    public function test_index_exposes_plaintext_api_key_for_super_admin(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');

        MarketingAiProvider::query()->create([
            'code' => 'forecast-openai',
            'display_name' => 'Forecast',
            'adapter' => 'openai_compatible',
            'api_key' => 'sk-plain-test',
            'base_url' => 'https://api.openai.com',
            'default_model' => 'gpt-4o-mini',
            'is_active' => true,
        ]);

        $resp = $this->getJson('/api/v1/admin/marketing-ai-providers')->assertOk();

        $resp->assertJsonPath('data.0.api_key', 'sk-plain-test');
    }

    public function test_draft_test_reports_ok_on_successful_probe(): void
    {
        $this->actingAs(AdminUser::factory()->create(), 'admin_web');

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response(
                ['choices' => [['message' => ['content' => 'pong']]]],
                200,
            ),
        ]);

        $resp = $this->postJson('/api/v1/admin/marketing-ai-providers/test-draft', [
            'adapter' => 'openai_compatible',
            'base_url' => 'https://api.openai.com',
            'api_key' => 'sk-test',
            'default_model' => 'gpt-4o-mini',
        ])->assertOk();

        $resp->assertJsonPath('data.ok', true);
    }
}
```

- [ ] **Step 2: Run the test and confirm it fails**

```bash
php artisan test tests/Feature/Admin/AdminMarketingAiProviderTestDraftTest.php
```
Expected: FAIL — `data.0.api_key` assertion fails (field absent) and/or `test-draft` route 404.

- [ ] **Step 3: Expose plaintext `api_key` in `safe()`**

In `app/app/Modules/Marketing/Http/Controllers/AdminMarketingAiProviderController.php`, change:

```php
    private function safe(MarketingAiProvider $p): array
    {
        return [
            'code' => $p->code,
            'display_name' => $p->display_name,
            'adapter' => $p->adapter,
            'base_url' => $p->base_url,
            'default_model' => $p->default_model,
            'is_active' => (bool) $p->is_active,
            'has_key' => $p->getRawOriginal('api_key') !== null,
        ];
    }
```
to:
```php
    private function safe(MarketingAiProvider $p): array
    {
        return [
            'code' => $p->code,
            'display_name' => $p->display_name,
            'adapter' => $p->adapter,
            'base_url' => $p->base_url,
            'default_model' => $p->default_model,
            'is_active' => (bool) $p->is_active,
            'has_key' => $p->getRawOriginal('api_key') !== null,
            // [TIKTOK-REVIEW-TEMP] Cùng quy ước với AdminAiProviderController (Messaging,
            // present()): trang chỉ super-admin (guard admin_web) truy cập ⇒ hiển thị thẳng
            // key để dùng chung SecretInput (spec 2026-07-21 §5.3). Trước đây field này
            // không tồn tại trong response ⇒ FE phải dùng input "để trống = giữ nguyên".
            'api_key' => $p->api_key,
        ];
    }
```

- [ ] **Step 4: Add the `testDraft` method**

In the same file, add the import at the top (after `use CMBcoreSeller\Modules\Marketing\Models\MarketingAiProvider;`):

```php
use CMBcoreSeller\Integrations\Ai\CredentialProbe;
```

Add the method after `destroy()`:

```php
    public function testDraft(Request $request, CredentialProbe $probe): JsonResponse
    {
        $data = $request->validate([
            'adapter' => ['required', 'in:anthropic,openai_compatible'],
            'base_url' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string'],
            'default_model' => ['nullable', 'string', 'max:64'],
        ]);

        return response()->json(['data' => $probe->probeChat(
            $data['adapter'],
            $data['base_url'] ?? null,
            $data['api_key'] ?? null,
            $data['default_model'] ?? null,
        )]);
    }
```

- [ ] **Step 5: Register the route**

In `app/app/Modules/Marketing/Http/routes.php`, inside the `marketing-ai-providers` group (after
the `destroy` route, before the closing `});`):

```php
        Route::delete('{code}', [AdminMarketingAiProviderController::class, 'destroy'])
            ->where('code', '[a-z0-9][a-z0-9_-]*')->name('admin.marketing-ai-providers.destroy');
        Route::post('test-draft', [AdminMarketingAiProviderController::class, 'testDraft'])
            ->name('admin.marketing-ai-providers.test-draft');
    });
```

- [ ] **Step 6: Run the test and confirm it passes**

```bash
php artisan test tests/Feature/Admin/AdminMarketingAiProviderTestDraftTest.php
```
Expected: PASS (2 tests green).

- [ ] **Step 7: Static analysis, format check, regression**

```bash
vendor/bin/pint --test app/Modules/Marketing/Http/Controllers/AdminMarketingAiProviderController.php app/Modules/Marketing/Http/routes.php tests/Feature/Admin/AdminMarketingAiProviderTestDraftTest.php
vendor/bin/phpstan analyse app/Modules/Marketing/Http/Controllers/AdminMarketingAiProviderController.php
php artisan test --filter=MarketingAiProvider
```
Expected: all succeed; no new failures beyond baseline.

- [ ] **Step 8: Commit**

```bash
git add app/Modules/Marketing/Http/Controllers/AdminMarketingAiProviderController.php app/Modules/Marketing/Http/routes.php tests/Feature/Admin/AdminMarketingAiProviderTestDraftTest.php
git commit -m "feat(admin): lộ api_key rõ + endpoint test-draft cho AI Marketing (spec 5.3/5.4)"
```

---

### Task 5: Frontend — `AdminAiProvidersPage` (SecretInput + test-before-save gate)

**Files:**
- Modify: `app/resources/js/admin/lib/aiProviders.tsx`
- Modify: `app/resources/js/admin/pages/settings/AdminAiProvidersPage.tsx` (full rewrite)

**Interfaces:**
- Consumes: `SecretInput` (`{ value: string | null; onSave: (v: string) => void }`, Task 0 finding),
  `POST /admin/ai-providers/test-draft` (Task 2).
- Produces: nothing consumed by later tasks (leaf page).

- [ ] **Step 1: Add the draft-test hook**

In `app/resources/js/admin/lib/aiProviders.tsx`, add after `useTestAiProvider()` (end of file):

```tsx
export interface AiProviderDraftTestResult {
    ok: boolean;
    message?: string;
}

export interface AiProviderDraftTestPayload {
    adapter: AiAdapter;
    base_url: string | null;
    api_key: string | null;
    default_model: string | null;
}

/** Test kết nối bằng credentials ĐANG NHẬP trên form (chưa lưu) — chỉ hỗ trợ adapter
 * anthropic/openai_compatible (custom_http/manual không gate, xem AdminAiProvidersPage). */
export function useTestAiProviderDraft() {
    return useMutation({
        mutationFn: async (payload: AiProviderDraftTestPayload) =>
            (await api.post<{ data: AiProviderDraftTestResult }>('/admin/ai-providers/test-draft', payload)).data.data,
    });
}
```

- [ ] **Step 2: Rewrite the page**

Replace the full content of `app/resources/js/admin/pages/settings/AdminAiProvidersPage.tsx`:

```tsx
// /admin/ai-providers — super-admin thêm/sửa/bật-tắt/test nhà cung cấp AI.
// Adapter động: anthropic | openai_compatible | manual. Nhiều instance cùng adapter
// (DeepSeek/Qwen/OpenRouter/Gemini đều openai_compatible, khác base_url/key/model).
//
// api_key dùng chung SecretInput (hiển thị plaintext, "Đặt giá trị" để đổi — xem
// docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md §5.3), thay Input
// hand-rolled trước đây. "Lưu" trong modal khoá tới khi Test kết nối PASS với đúng
// (adapter, base_url, model, api_key) đang có trên form — chỉ áp dụng adapter
// anthropic/openai_compatible (shape request/response cố định); custom_http (template
// tự định nghĩa) và manual (stub) không có shape cố định để test "nháp" ⇒ giữ hành vi
// Lưu ngay như trước (§5.4).

import { useState } from 'react';
import { App, Button, Card, Form, Input, InputNumber, Modal, Radio, Select, Space, Switch, Table, Tag } from 'antd';
import { ApiOutlined, PlusOutlined, ReloadOutlined, ThunderboltOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import {
    useAiProviders, useCreateAiProvider, useUpdateAiProvider, useDisableAiProvider, useTestAiProvider,
    useTestAiProviderDraft,
    type AiProviderRow, type AiAdapter, type AiRole, type AiPreset, type CustomHttpConfig,
} from '../../lib/aiProviders';
import { SecretInput } from '../../components/SecretInput';

const ADAPTER_LABEL: Record<AiAdapter, string> = {
    anthropic: 'Anthropic (Claude)',
    openai_compatible: 'OpenAI-compatible (GPT/DeepSeek/Qwen/OpenRouter/Gemini)',
    custom_http: 'Tùy chỉnh (HTTP)',
    manual: 'Manual (test/dev)',
};

const ROLE_LABEL: Record<AiRole, string> = {
    chat: 'Chat',
    vision: 'Chấm ảnh',
    transcription: 'Chuyển giọng nói',
};

// Fallback khi API chưa trả; NGUỒN CHÍNH là data.adapters (registry BE) để FE
// không lệch với adapter đã đăng ký ⇒ không chọn được adapter mà BE từ chối (422).
const ADAPTERS_FALLBACK: AiAdapter[] = ['anthropic', 'openai_compatible', 'custom_http', 'manual'];

// Chỉ 2 adapter có request/response shape CỐ ĐỊNH mới test "nháp" (chưa lưu) được.
const PROBEABLE_ADAPTERS: AiAdapter[] = ['anthropic', 'openai_compatible'];

export function AdminAiProvidersPage() {
    const { data, isLoading, refetch } = useAiProviders();
    const create = useCreateAiProvider();
    const update = useUpdateAiProvider();
    const disable = useDisableAiProvider();
    const test = useTestAiProvider();
    const testDraft = useTestAiProviderDraft();
    const { message, modal } = App.useApp();
    const [form] = Form.useForm();
    const [editing, setEditing] = useState<AiProviderRow | null>(null);
    const [open, setOpen] = useState(false);
    // Code provider ĐANG test — để CHỈ nút của dòng đó quay spinner (test.isPending
    // dùng chung mọi dòng ⇒ trước đây bấm 1 provider thì tất cả nút Test đều loading).
    const [testingCode, setTestingCode] = useState<string | null>(null);
    // api_key nằm NGOÀI Form (SecretInput không phát onChange theo keystroke chuẩn của
    // AntD Form) — track riêng, merge vào payload lúc submit.
    const [apiKeyDraft, setApiKeyDraft] = useState<string | null>(null);
    // Chữ ký (adapter|base_url|default_model|api_key) đã Test PASS gần nhất — đổi field
    // nào trong 4 field này cũng buộc test lại trước khi Lưu được mở khoá.
    const [verifiedSignature, setVerifiedSignature] = useState<string | null>(null);

    const watchedAdapter = Form.useWatch('adapter', form) as AiAdapter | undefined;
    const watchedBaseUrl = Form.useWatch('base_url', form) as string | undefined;
    const watchedModel = Form.useWatch('default_model', form) as string | undefined;

    const adapters = data?.adapters ?? [];
    const presetsFor = (a: AiAdapter): AiPreset[] => adapters.find((x) => x.adapter === a)?.presets ?? [];
    // Chỉ hiển thị adapter mà BE thực sự đăng ký (registry) ⇒ FE luôn khớp BE,
    // tránh chọn adapter không tồn tại khiến tạo provider bị 422.
    const adapterChoices: AiAdapter[] = adapters.length ? adapters.map((x) => x.adapter) : ADAPTERS_FALLBACK;

    const currentAdapter = (editing?.adapter ?? watchedAdapter) as AiAdapter | undefined;
    const probeSupported = !!currentAdapter && PROBEABLE_ADAPTERS.includes(currentAdapter);
    const currentSignature = JSON.stringify({
        adapter: currentAdapter ?? '',
        base_url: watchedBaseUrl ?? '',
        default_model: watchedModel ?? '',
        api_key: apiKeyDraft ?? '',
    });
    const needsTest = probeSupported && currentSignature !== verifiedSignature;

    const openCreate = () => {
        setEditing(null);
        form.resetFields();
        form.setFieldsValue({ adapter: 'openai_compatible', role: 'chat', is_active: true, sort_order: 0 });
        setApiKeyDraft(null);
        setVerifiedSignature(null);
        setOpen(true);
    };
    const openEdit = (row: AiProviderRow) => {
        setEditing(row);
        form.setFieldsValue({
            ...row,
            headers_json: row.adapter_config?.headers ? JSON.stringify(row.adapter_config.headers, null, 2) : '',
        });
        setApiKeyDraft(row.api_key ?? null);
        setVerifiedSignature(null);
        setOpen(true);
    };

    const applyPreset = (p: AiPreset) =>
        form.setFieldsValue({ base_url: p.base_url ?? '', default_model: p.default_model ?? '', display_name: p.name });

    const runDraftTest = async () => {
        if (!currentAdapter) return;
        const r = await testDraft.mutateAsync({
            adapter: currentAdapter,
            base_url: watchedBaseUrl ?? null,
            api_key: apiKeyDraft,
            default_model: watchedModel ?? null,
        });
        if (r.ok) {
            message.success(r.message ?? 'Kết nối OK.');
            setVerifiedSignature(currentSignature);
        } else {
            message.error(r.message ?? 'Kết nối thất bại.');
        }
    };

    const submit = async () => {
        const v = await form.validateFields();
        const onErr = (e: unknown) => message.error(errorMessage(e));

        v.api_key = apiKeyDraft;

        // adapter_config chỉ áp dụng cho custom_http; headers nhập dạng JSON text → parse.
        const headersJson = v.headers_json as string | undefined;
        delete v.headers_json;
        const adapter = (editing?.adapter ?? v.adapter) as AiAdapter;
        if (adapter === 'custom_http') {
            const cfg: CustomHttpConfig = { ...(v.adapter_config ?? {}) };
            if (headersJson && headersJson.trim()) {
                try {
                    cfg.headers = JSON.parse(headersJson);
                } catch {
                    message.error('Headers JSON không hợp lệ.');
                    return;
                }
            }
            v.adapter_config = cfg;
        } else {
            delete v.adapter_config;
        }

        if (editing) {
            update.mutate(
                { code: editing.code, payload: v },
                { onSuccess: () => { message.success('Đã lưu provider.'); setOpen(false); }, onError: onErr },
            );
        } else {
            create.mutate(v, {
                onSuccess: () => { message.success('Đã thêm provider.'); setOpen(false); },
                onError: onErr,
            });
        }
    };

    const runTest = (code: string) => {
        setTestingCode(code);
        test.mutate(code, {
            onSuccess: (r) => {
                // Tóm tắt từng năng lực: Chat / Embedding (embedding cần cho trợ lý Hỏi AI / Support).
                const parts: string[] = [];
                if (r.results?.chat) parts.push(`Chat: ${r.results.chat.ok ? 'OK' : `LỖI (${r.results.chat.reason ?? ''}${r.results.chat.message ? ' — ' + r.results.chat.message : ''})`}`);
                if (r.results?.embedding) parts.push(`Embedding: ${r.results.embedding.ok ? `OK (dim ${r.results.embedding.dimension ?? '?'})` : `LỖI (${r.results.embedding.reason ?? ''}${r.results.embedding.message ? ' — ' + r.results.embedding.message : ''})`}`);
                const detail = parts.join(' · ') || (r.message ?? '');
                // Ghi RÕ provider nào để không nhầm khi có nhiều provider.
                if (r.ok) message.success(`[${code}] Kết nối OK — ${detail}`);
                else message.warning(`[${code}] Chưa OK — ${detail}`);
            },
            onError: (e) => message.error(`[${code}] ${errorMessage(e)}`),
            onSettled: () => setTestingCode(null),
        });
    };

    const columns = [
        { title: 'Mã', dataIndex: 'code', key: 'code', render: (c: string) => <Tag>{c}</Tag> },
        { title: 'Loại (adapter)', dataIndex: 'adapter', key: 'adapter', render: (a: AiAdapter) => ADAPTER_LABEL[a] },
        { title: 'Vai trò', dataIndex: 'role', key: 'role', render: (r: AiRole) => <Tag color="blue">{ROLE_LABEL[r] ?? r}</Tag> },
        { title: 'Tên hiển thị', dataIndex: 'display_name', key: 'display_name' },
        { title: 'Model', dataIndex: 'default_model', key: 'default_model' },
        {
            title: 'API key', dataIndex: 'has_api_key', key: 'has_api_key',
            render: (v: boolean) => (v ? <Tag color="green">Đã đặt</Tag> : <Tag>Chưa</Tag>),
        },
        {
            title: 'Bật', dataIndex: 'is_active', key: 'is_active',
            render: (v: boolean) => (v ? <Tag color="blue">Đang bật</Tag> : <Tag>Tắt</Tag>),
        },
        {
            title: 'Hành động', key: 'actions',
            render: (_: unknown, row: AiProviderRow) => (
                <Space>
                    <Button size="small" onClick={() => openEdit(row)}>Sửa</Button>
                    <Button size="small" icon={<ThunderboltOutlined />} loading={testingCode === row.code} disabled={test.isPending && testingCode !== row.code} onClick={() => runTest(row.code)}>Test</Button>
                    <Button
                        size="small"
                        danger
                        onClick={() => modal.confirm({
                            title: `Tắt provider ${row.code}?`,
                            onOk: () => disable.mutate(row.code, { onSuccess: () => message.success('Đã tắt.') }),
                        })}
                    >
                        Tắt
                    </Button>
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
                okButtonProps={{ disabled: needsTest }}
                destroyOnClose
            >
                <Form form={form} layout="vertical">
                    <Form.Item name="adapter" label="Loại API (adapter)" rules={[{ required: true }]}>
                        <Radio.Group disabled={!!editing} optionType="button" buttonStyle="solid">
                            {adapterChoices.map((a) => (
                                <Radio.Button key={a} value={a}>{ADAPTER_LABEL[a] ?? a}</Radio.Button>
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

                    <Form.Item
                        name="code"
                        label="Mã (slug, duy nhất)"
                        rules={[
                            { required: !editing, message: 'Nhập mã slug' },
                            { pattern: /^[a-z0-9][a-z0-9_-]{1,31}$/, message: 'Chỉ a-z 0-9 _ - , 2-32 ký tự' },
                        ]}
                    >
                        <Input placeholder="vd: deepseek-prod" disabled={!!editing} />
                    </Form.Item>
                    <Form.Item name="display_name" label="Tên hiển thị"><Input placeholder="vd: DeepSeek (prod)" /></Form.Item>

                    <Form.Item name="role" label="Vai trò" rules={[{ required: true }]} initialValue="chat">
                        <Radio.Group optionType="button" buttonStyle="solid">
                            {(Object.keys(ROLE_LABEL) as AiRole[]).map((r) => (
                                <Radio.Button key={r} value={r}>{ROLE_LABEL[r]}</Radio.Button>
                            ))}
                        </Radio.Group>
                    </Form.Item>

                    {/* base_url + default_model: required theo adapter (khớp validate BE).
                        SafeProviderUrl bắt buộc HTTPS + host công khai (chặn http/localhost/LAN). */}
                    <Form.Item shouldUpdate={(p, c) => p.adapter !== c.adapter} noStyle>
                        {() => {
                            const a = (editing?.adapter ?? form.getFieldValue('adapter')) as AiAdapter;
                            const needsBaseUrl = a === 'openai_compatible' || a === 'custom_http';
                            const needsModel = a === 'openai_compatible';
                            return (
                                <>
                                    <Form.Item
                                        name="base_url"
                                        label="Base URL / Endpoint"
                                        rules={[
                                            { type: 'url', message: 'URL không hợp lệ' },
                                            { required: needsBaseUrl, message: 'Nhập Base URL' },
                                        ]}
                                        extra={
                                            a === 'custom_http'
                                                ? 'Nhập URL endpoint ĐẦY ĐỦ (vd https://llm.vn/v1/chat). Phải HTTPS + host công khai.'
                                                : 'Nhập GỐC host, KHÔNG kèm /v1 (connector tự thêm /v1/chat/completions hoặc /v1/messages). Vd: https://api.deepseek.com. Phải HTTPS + host công khai (không http/localhost/IP nội bộ).'
                                        }
                                    >
                                        <Input placeholder="https://api.deepseek.com" />
                                    </Form.Item>
                                    <Form.Item
                                        name="default_model"
                                        label="Model mặc định"
                                        rules={[{ required: needsModel, message: 'Nhập model mặc định' }]}
                                    >
                                        <Input placeholder="deepseek-chat" />
                                    </Form.Item>
                                </>
                            );
                        }}
                    </Form.Item>

                    {/* Trang admin: hiện thẳng key qua SecretInput dùng chung toàn hệ thống
                        (không che — spec §5.3); nằm ngoài Form vì SecretInput tự quản draft. */}
                    <Form.Item label="API key">
                        <SecretInput value={apiKeyDraft} onSave={(v) => setApiKeyDraft(v)} />
                    </Form.Item>

                    {probeSupported && (
                        <Form.Item label="Xác minh kết nối">
                            <Space>
                                <Button icon={<ThunderboltOutlined />} loading={testDraft.isPending} onClick={runDraftTest}>
                                    Test kết nối
                                </Button>
                                {currentSignature === verifiedSignature
                                    ? <Tag color="green">Đã xác minh</Tag>
                                    : <Tag color="orange">Chưa xác minh — Lưu bị khoá tới khi Test pass</Tag>}
                            </Space>
                        </Form.Item>
                    )}

                    {/* Cấu hình riêng adapter custom_http (SPEC-0026). */}
                    <Form.Item shouldUpdate={(p, c) => p.adapter !== c.adapter} noStyle>
                        {() => (form.getFieldValue('adapter') as AiAdapter) === 'custom_http' ? (
                            <>
                                <Form.Item name={['adapter_config', 'method']} label="HTTP method" initialValue="POST">
                                    <Select options={['POST', 'PUT', 'GET'].map((m) => ({ value: m, label: m }))} />
                                </Form.Item>
                                <Form.Item name="headers_json" label="Headers (JSON)" extra="Có thể dùng {{api_key}} / {{model}}.">
                                    <Input.TextArea rows={3} placeholder={'{"Authorization":"Bearer {{api_key}}"}'} />
                                </Form.Item>
                                <Form.Item
                                    name={['adapter_config', 'request_template']}
                                    label="Body template (JSON)"
                                    rules={[{ required: true, message: 'Nhập body template' }]}
                                    extra="Placeholder: {{model}} {{system}} {{messages_json}} {{last_user_message}} {{buyer_name}} {{api_key}}"
                                >
                                    <Input.TextArea rows={5} placeholder={'{"model":"{{model}}","system":"{{system}}","messages":{{messages_json}}}'} />
                                </Form.Item>
                                <Form.Item
                                    name={['adapter_config', 'response_path']}
                                    label="Đường dẫn trả lời (JSON path)"
                                    rules={[{ required: true, message: 'Nhập response path' }]}
                                >
                                    <Input placeholder="data.reply.text" />
                                </Form.Item>
                                <Form.Item name={['adapter_config', 'usage', 'prompt_path']} label="JSON path token vào (tuỳ chọn)">
                                    <Input placeholder="usage.prompt_tokens" />
                                </Form.Item>
                                <Form.Item name={['adapter_config', 'usage', 'completion_path']} label="JSON path token ra (tuỳ chọn)">
                                    <Input placeholder="usage.completion_tokens" />
                                </Form.Item>
                            </>
                        ) : null}
                    </Form.Item>

                    <Form.Item name="sort_order" label="Thứ tự"><InputNumber min={0} max={9999} /></Form.Item>
                    <Form.Item name="is_active" label="Kích hoạt" valuePropName="checked"><Switch /></Form.Item>
                </Form>
            </Modal>
        </Card>
    );
}
```

- [ ] **Step 3: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: all succeed with no new errors.

- [ ] **Step 4: Manual browser verification**

With the backend from Tasks 1-2 running, log into `/admin/ai-providers`:
1. Click "Thêm provider". Confirm the API key field is now a read-only `(chưa đặt)` box with an
   "Đặt giá trị" button (not a free-typing `Input`) — click it, type a fake key, click "Lưu" inside
   that inline control, confirm it collapses back showing the plaintext value.
2. With adapter left at the default "OpenAI-compatible", confirm a "Xác minh kết nối" row appears
   with a "Test kết nối" button and an orange "Chưa xác minh" tag, and the modal's "OK"/"Lưu"
   button is disabled.
3. Fill Base URL + Model with a real or intentionally-wrong endpoint, click "Test kết nối" — confirm
   either a green "Đã xác minh" tag + enabled Lưu button (success) or a red error toast + Lưu stays
   disabled (failure), matching whichever the credentials actually produce.
4. Change the Model field after a successful test — confirm the tag reverts to orange "Chưa xác
   minh" and Lưu re-disables (signature changed).
5. Switch adapter to "Manual (test/dev)" — confirm the "Xác minh kết nối" row disappears and Lưu is
   immediately enabled (ungated, as documented for non-probeable adapters).
6. Switch adapter to "Tùy chỉnh (HTTP)" — same check, Lưu enabled without a test gate.
7. Open an existing provider via "Sửa" — confirm the API key box shows its current plaintext value
   (not blank), and that editing only `display_name` (not touching adapter/base_url/model/api_key)
   still requires clicking Test kết nối before Lưu unlocks (documented trade-off — see Task 5's
   code comment).
8. Confirm the existing per-row "Test" button in the table (unrelated to this task) still works as
   before.

- [ ] **Step 5: Commit**

```bash
git add app/resources/js/admin/lib/aiProviders.tsx app/resources/js/admin/pages/settings/AdminAiProvidersPage.tsx
git commit -m "feat(admin): SecretInput + gate Test-trước-khi-Lưu cho Nhà cung cấp AI"
```

---

### Task 6: Frontend — `AdminAiSupportPage` (SecretInput ×2 + test-before-save gate ×2)

**Files:**
- Modify: `app/resources/js/admin/lib/aiSupport.tsx`
- Modify: `app/resources/js/admin/pages/settings/AdminAiSupportPage.tsx` (full rewrite)

**Interfaces:**
- Consumes: `SecretInput`, `POST /admin/ai-support/test-draft` (Task 3).
- Produces: nothing consumed by later tasks (leaf page).

- [ ] **Step 1: Add the draft-test hook**

In `app/resources/js/admin/lib/aiSupport.tsx`, add at the end of the file:

```tsx
export interface AiSupportDraftTestResult {
    ok: boolean;
    message?: string;
}

export interface AiSupportDraftTestPayload {
    kind: 'chat' | 'embedding';
    base_url: string;
    api_key: string;
    model: string;
}

/** Test kết nối bằng credentials ĐANG NHẬP trên form (chưa lưu qua system-settings). */
export function useTestAiSupportDraft() {
    return useMutation({
        mutationFn: async (payload: AiSupportDraftTestPayload) =>
            (await api.post<{ data: AiSupportDraftTestResult }>('/admin/ai-support/test-draft', payload)).data.data,
    });
}
```

- [ ] **Step 2: Rewrite the page**

Replace the full content of `app/resources/js/admin/pages/settings/AdminAiSupportPage.tsx`:

```tsx
// /admin/ai-support — trang RIÊNG cấu hình trợ lý "Hỏi AI" (module Support).
// TỰ CHỨA: credentials riêng (base_url + api_key + model), KHÔNG dùng chung bảng
// "Nhà cung cấp AI" của messaging. Tách CHAT và EMBEDDING độc lập → có thể dùng cùng
// provider hay khác provider cho chat và embedding (tuỳ base_url/model).
//
// api_key dùng chung SecretInput (hiển thị plaintext, "Đặt giá trị" để đổi — xem
// docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md §5.3), thay Input
// hand-rolled trước đây. "Lưu" mỗi khối (chat/embedding) khoá tới khi Test kết nối khối đó
// pass với đúng giá trị đang có trên form (§5.4) — đổi field bất kỳ sau khi test ⇒ phải
// test lại. Khối embedding vẫn giữ lối thoát "để trống Base URL = tắt vector" — không cần
// test khi tắt hẳn.

import { useEffect, useState } from 'react';
import { App, Alert, Button, Card, Input, Space, Spin, Tag, Typography } from 'antd';
import { CustomerServiceOutlined, SaveOutlined, ThunderboltOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { useSupportAiConfig, useSaveSupportSetting, useTestAiSupportDraft, SUPPORT_KEYS } from '../../lib/aiSupport';
import { SecretInput } from '../../components/SecretInput';

const { Text, Paragraph } = Typography;

export function AdminAiSupportPage() {
    const { message } = App.useApp();
    const { data: cfg, isLoading } = useSupportAiConfig();
    const save = useSaveSupportSetting();
    const testDraft = useTestAiSupportDraft();

    const [chatBaseUrl, setChatBaseUrl] = useState('');
    const [chatKey, setChatKey] = useState('');
    const [chatModel, setChatModel] = useState('');
    const [embBaseUrl, setEmbBaseUrl] = useState('');
    const [embKey, setEmbKey] = useState('');
    const [embModel, setEmbModel] = useState('');

    // Chữ ký (base_url|api_key|model) đã Test PASS gần nhất cho từng khối — Lưu chỉ mở
    // khoá khi chữ ký hiện tại khớp; đổi field nào cũng buộc test lại (spec §5.4).
    const [chatVerifiedSig, setChatVerifiedSig] = useState<string | null>(null);
    const [embVerifiedSig, setEmbVerifiedSig] = useState<string | null>(null);

    useEffect(() => {
        if (cfg) {
            setChatBaseUrl(cfg.chat_base_url);
            setChatModel(cfg.chat_model);
            setEmbBaseUrl(cfg.embedding_base_url);
            setEmbModel(cfg.embedding_model);
            setChatKey(cfg.chat_api_key);
            setEmbKey(cfg.embedding_api_key);
        }
    }, [cfg]);

    const chatSig = `${chatBaseUrl}|${chatKey}|${chatModel}`;
    const embSig = `${embBaseUrl}|${embKey}|${embModel}`;

    const saveOne = (key: string, value: string, label: string) =>
        new Promise<void>((resolve) => {
            save.mutate({ key, value }, {
                onSuccess: () => { message.success(`Đã lưu: ${label}`); resolve(); },
                onError: (e) => { message.error(`${label}: ${errorMessage(e)}`); resolve(); },
            });
        });

    const saveChat = async () => {
        await saveOne(SUPPORT_KEYS.chatBaseUrl, chatBaseUrl.trim(), 'Chat Base URL');
        await saveOne(SUPPORT_KEYS.chatModel, chatModel.trim(), 'Chat Model');
        await saveOne(SUPPORT_KEYS.chatApiKey, chatKey.trim(), 'Chat API key');
    };
    const saveEmbedding = async () => {
        await saveOne(SUPPORT_KEYS.embeddingBaseUrl, embBaseUrl.trim(), 'Embedding Base URL');
        await saveOne(SUPPORT_KEYS.embeddingModel, embModel.trim(), 'Embedding Model');
        await saveOne(SUPPORT_KEYS.embeddingApiKey, embKey.trim(), 'Embedding API key');
    };

    const testChat = async () => {
        const r = await testDraft.mutateAsync({ kind: 'chat', base_url: chatBaseUrl.trim(), api_key: chatKey.trim(), model: chatModel.trim() });
        if (r.ok) { message.success(r.message ?? 'Kết nối OK.'); setChatVerifiedSig(chatSig); }
        else message.error(r.message ?? 'Kết nối thất bại.');
    };
    const testEmbedding = async () => {
        const r = await testDraft.mutateAsync({ kind: 'embedding', base_url: embBaseUrl.trim(), api_key: embKey.trim(), model: embModel.trim() });
        if (r.ok) { message.success(r.message ?? 'Kết nối OK.'); setEmbVerifiedSig(embSig); }
        else message.error(r.message ?? 'Kết nối thất bại.');
    };

    if (isLoading) return <Card><Spin /></Card>;

    return (
        <Space direction="vertical" size={16} style={{ width: '100%' }}>
            <Card title={<Space><CustomerServiceOutlined /> Cấu hình AI cho Trợ giúp (Hỏi AI)</Space>}>
                <Paragraph type="secondary" style={{ marginBottom: 0 }}>
                    Trợ lý "Hỏi AI" dùng cấu hình RIÊNG (base URL + API key + model), KHÔNG liên quan
                    mục <Text strong>Nhà cung cấp AI</Text> (trả lời tin nhắn). Đổi base URL/model embedding
                    rồi chạy lại <Text code>php artisan help:index --fresh</Text> để tạo lại vector.
                </Paragraph>
            </Card>

            {/* 1. CHAT */}
            <Card size="small" title="1. Chat — sinh câu trả lời">
                <Paragraph type="secondary" style={{ fontSize: 12 }}>
                    OpenAI-compatible. <Text code>Base URL</Text> = GỐC host, KHÔNG kèm <Text code>/v1</Text>
                    (vd OpenRouter: <Text code>https://openrouter.ai/api</Text>).
                </Paragraph>
                <Space direction="vertical" size={10} style={{ width: '100%', maxWidth: 560 }}>
                    <div>
                        <Text strong>Base URL</Text>
                        <Input value={chatBaseUrl} onChange={(e) => setChatBaseUrl(e.target.value)} placeholder="https://openrouter.ai/api" />
                    </div>
                    <div>
                        <Text strong>API key</Text>
                        <SecretInput value={chatKey || null} onSave={setChatKey} />
                    </div>
                    <div>
                        <Text strong>Model</Text>
                        <Input value={chatModel} onChange={(e) => setChatModel(e.target.value)} placeholder="google/gemini-2.0-flash-lite-001" />
                    </div>
                    <Space>
                        <Button icon={<ThunderboltOutlined />} loading={testDraft.isPending} onClick={testChat}>Test kết nối</Button>
                        {chatSig === chatVerifiedSig
                            ? <Tag color="green">Đã xác minh</Tag>
                            : <Tag color="orange">Chưa xác minh — cần Test trước khi Lưu</Tag>}
                    </Space>
                    <Button type="primary" icon={<SaveOutlined />} loading={save.isPending} disabled={chatSig !== chatVerifiedSig} onClick={saveChat}>
                        Lưu cấu hình chat
                    </Button>
                </Space>
            </Card>

            {/* 2. EMBEDDING */}
            <Card size="small" title="2. Embedding — tạo vector cho tìm kiếm ngữ nghĩa (RAG)">
                <Alert
                    type="info" showIcon style={{ marginBottom: 12 }}
                    message="Phải dùng MODEL embedding hợp lệ của provider"
                    description="Có thể dùng cùng provider với chat hoặc provider khác. Quan trọng: Model phải là model EMBEDDING (vd openai/text-embedding-3-small trên OpenRouter, text-embedding-3-small trên OpenAI) — KHÔNG phải model chat. Để TRỐNG Base URL ⇒ tắt vector, trợ lý chạy tìm kiếm từ khoá."
                />
                <Space direction="vertical" size={10} style={{ width: '100%', maxWidth: 560 }}>
                    <div>
                        <Text strong>Base URL</Text>
                        <Input value={embBaseUrl} onChange={(e) => setEmbBaseUrl(e.target.value)} placeholder="https://api.openai.com (trống = tắt vector)" />
                    </div>
                    <div>
                        <Text strong>API key</Text>
                        <SecretInput value={embKey || null} onSave={setEmbKey} />
                    </div>
                    <div>
                        <Text strong>Model</Text>
                        <Input value={embModel} onChange={(e) => setEmbModel(e.target.value)} placeholder="text-embedding-3-small" />
                    </div>
                    <Space>
                        <Button icon={<ThunderboltOutlined />} loading={testDraft.isPending} onClick={testEmbedding} disabled={embBaseUrl.trim() === ''}>
                            Test kết nối
                        </Button>
                        {embBaseUrl.trim() === ''
                            ? <Tag>Đang tắt vector — không cần test</Tag>
                            : embSig === embVerifiedSig
                                ? <Tag color="green">Đã xác minh</Tag>
                                : <Tag color="orange">Chưa xác minh — cần Test trước khi Lưu</Tag>}
                    </Space>
                    <Button
                        type="primary" icon={<SaveOutlined />} loading={save.isPending}
                        disabled={embBaseUrl.trim() !== '' && embSig !== embVerifiedSig}
                        onClick={saveEmbedding}
                    >
                        Lưu cấu hình embedding
                    </Button>
                </Space>
                <Paragraph type="secondary" style={{ fontSize: 12, marginTop: 12, marginBottom: 0 }}>
                    Sau khi lưu embedding, chạy <Text code>php artisan help:index --fresh</Text> để tạo lại vector tài liệu.
                </Paragraph>
            </Card>
        </Space>
    );
}
```

- [ ] **Step 3: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: all succeed with no new errors.

- [ ] **Step 4: Manual browser verification**

With the backend from Tasks 1 and 3 running, log into `/admin/ai-support`:
1. Confirm both "API key" fields (Chat, Embedding) render as the plaintext-display `SecretInput`
   control (not a free-typing password-style `Input`) and show the currently-saved key if one exists.
2. Confirm both "Lưu cấu hình..." buttons start disabled (orange "Chưa xác minh" tag) if the loaded
   config's fields don't happen to already match a verified signature (they won't, on first load).
3. Fill in Chat Base URL/API key/Model, click "Test kết nối" under the chat block — confirm success
   flips the tag green and enables "Lưu cấu hình chat"; a deliberately wrong key shows a red error
   toast with the provider's own message and keeps Lưu disabled.
4. Clear the Embedding Base URL entirely — confirm the tag changes to "Đang tắt vector — không cần
   test", the Test button disables, and "Lưu cấu hình embedding" becomes enabled without testing
   (turning embedding off doesn't require a live probe).
5. Type a Base URL back into Embedding — confirm the gate re-engages (orange tag, Lưu disabled)
   until Test kết nối passes again.
6. Save the chat block successfully, then edit the Model field afterward — confirm the tag reverts
   to orange and Lưu re-disables (signature changed, matches the code comment).

- [ ] **Step 5: Commit**

```bash
git add app/resources/js/admin/lib/aiSupport.tsx app/resources/js/admin/pages/settings/AdminAiSupportPage.tsx
git commit -m "feat(admin): SecretInput + gate Test-trước-khi-Lưu cho AI Trợ giúp"
```

---

### Task 7: Frontend — `AdminMarketingAiProvidersPage` (SecretInput + gate + icon fix)

**Files:**
- Modify: `app/resources/js/admin/lib/marketingAiProviders.tsx`
- Modify: `app/resources/js/admin/pages/settings/AdminMarketingAiProvidersPage.tsx` (full rewrite)

**Interfaces:**
- Consumes: `SecretInput`, `POST /admin/marketing-ai-providers/test-draft` (Task 4), the now-exposed
  `api_key` field on `MarketingAiProviderRow` (Task 4).
- Produces: nothing consumed by later tasks (leaf page).

- [ ] **Step 1: Update the hook file**

In `app/resources/js/admin/lib/marketingAiProviders.tsx`, change:

```tsx
export interface MarketingAiProviderRow {
    code: string;
    display_name: string | null;
    adapter: MarketingAiAdapter;
    base_url: string | null;
    default_model: string | null;
    is_active: boolean;
    has_key: boolean;
}
```
to:
```tsx
export interface MarketingAiProviderRow {
    code: string;
    display_name: string | null;
    adapter: MarketingAiAdapter;
    base_url: string | null;
    default_model: string | null;
    is_active: boolean;
    has_key: boolean;
    /** Plaintext key (đã giải mã) — trang admin hiển thị thẳng qua SecretInput. */
    api_key: string | null;
}
```

Then add at the end of the file, after `useDeleteMarketingAiProvider()`:

```tsx
export interface MarketingAiProviderDraftTestResult {
    ok: boolean;
    message?: string;
}

export interface MarketingAiProviderDraftTestPayload {
    adapter: MarketingAiAdapter;
    base_url: string | null;
    api_key: string | null;
    default_model: string | null;
}

/** Test kết nối bằng credentials ĐANG NHẬP trên form (chưa lưu) — chỉ hỗ trợ adapter
 * anthropic/openai_compatible (manual không gate, xem AdminMarketingAiProvidersPage). */
export function useTestMarketingAiProviderDraft() {
    return useMutation({
        mutationFn: async (payload: MarketingAiProviderDraftTestPayload) =>
            (await api.post<{ data: MarketingAiProviderDraftTestResult }>('/admin/marketing-ai-providers/test-draft', payload)).data.data,
    });
}
```

- [ ] **Step 2: Rewrite the page**

Replace the full content of `app/resources/js/admin/pages/settings/AdminMarketingAiProvidersPage.tsx`:

```tsx
// /admin/marketing-ai-providers — provider AI RIÊNG cho phân tích marketing (tách AI messaging).
//
// api_key dùng chung SecretInput (hiển thị plaintext, "Đặt giá trị" để đổi — xem
// docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md §5.3), thay Input
// "để trống = giữ nguyên" trước đây (mơ hồ, dễ nhầm với xoá key). "Lưu" trong modal khoá
// tới khi Test kết nối PASS với đúng (adapter, base_url, model, api_key) đang có trên form
// — chỉ áp dụng adapter anthropic/openai_compatible; manual (stub, không có backend thật)
// giữ hành vi Lưu ngay như trước (§5.4). Icon Card title đổi ApiOutlined → RiseOutlined để
// khớp icon sidebar "AI Marketing" đã cố định ở Phase 0 (tránh trùng icon với trang
// "Nhà cung cấp AI" — spec §4).

import { useState } from 'react';
import { App as AntApp, Button, Card, Form, Input, Modal, Popconfirm, Segmented, Space, Switch, Table, Tag, Typography } from 'antd';
import { DeleteOutlined, PlusOutlined, RiseOutlined, ThunderboltOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import {
    type MarketingAiAdapter, type MarketingAiProviderInput, type MarketingAiProviderRow,
    useDeleteMarketingAiProvider, useMarketingAiProviders, useSaveMarketingAiProvider, useTestMarketingAiProviderDraft,
} from '../../lib/marketingAiProviders';
import { SecretInput } from '../../components/SecretInput';

const { Text, Paragraph } = Typography;

// Chỉ 2 adapter có request/response shape cố định mới test "nháp" được (khớp
// AdminAiProvidersPage.tsx); 'manual' là stub, không có backend thật để test.
const PROBEABLE_ADAPTERS: MarketingAiAdapter[] = ['anthropic', 'openai_compatible'];

/** /admin/marketing-ai-providers — provider AI RIÊNG cho phân tích marketing (tách AI messaging). */
export function AdminMarketingAiProvidersPage() {
    const { message } = AntApp.useApp();
    const { data: rows, isLoading } = useMarketingAiProviders();
    const save = useSaveMarketingAiProvider();
    const del = useDeleteMarketingAiProvider();
    const testDraft = useTestMarketingAiProviderDraft();
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<MarketingAiProviderRow | null>(null);
    const [form] = Form.useForm<MarketingAiProviderInput>();
    const [apiKeyDraft, setApiKeyDraft] = useState<string | null>(null);
    // Chữ ký (adapter|base_url|default_model|api_key) đã Test PASS gần nhất.
    const [verifiedSignature, setVerifiedSignature] = useState<string | null>(null);

    const watchedAdapter = Form.useWatch('adapter', form);
    const watchedBaseUrl = Form.useWatch('base_url', form);
    const watchedModel = Form.useWatch('default_model', form);

    const probeSupported = !!watchedAdapter && PROBEABLE_ADAPTERS.includes(watchedAdapter);
    const currentSignature = JSON.stringify({
        adapter: watchedAdapter ?? '',
        base_url: watchedBaseUrl ?? '',
        default_model: watchedModel ?? '',
        api_key: apiKeyDraft ?? '',
    });
    const needsTest = probeSupported && currentSignature !== verifiedSignature;

    const openNew = () => {
        setEditing(null);
        form.resetFields();
        form.setFieldsValue({ adapter: 'openai_compatible', is_active: true });
        setApiKeyDraft(null);
        setVerifiedSignature(null);
        setOpen(true);
    };
    const openEdit = (r: MarketingAiProviderRow) => {
        setEditing(r);
        form.setFieldsValue({
            code: r.code, display_name: r.display_name ?? undefined, adapter: r.adapter,
            base_url: r.base_url ?? undefined, default_model: r.default_model ?? undefined, is_active: r.is_active,
        });
        setApiKeyDraft(r.api_key ?? null);
        setVerifiedSignature(null);
        setOpen(true);
    };

    const runDraftTest = async () => {
        if (!watchedAdapter) return;
        const r = await testDraft.mutateAsync({
            adapter: watchedAdapter, base_url: watchedBaseUrl ?? null, api_key: apiKeyDraft, default_model: watchedModel ?? null,
        });
        if (r.ok) { message.success(r.message ?? 'Kết nối OK.'); setVerifiedSignature(currentSignature); }
        else message.error(r.message ?? 'Kết nối thất bại.');
    };

    const submit = async () => {
        const input = await form.validateFields();
        input.api_key = apiKeyDraft;
        save.mutate({ input, isNew: editing === null }, {
            onSuccess: () => { setOpen(false); message.success('Đã lưu provider.'); },
            onError: (e) => message.error(errorMessage(e, 'Không lưu được.')),
        });
    };

    return (
        <div>
            <Card
                title={<><RiseOutlined /> Provider AI Marketing (phân tích quảng cáo)</>}
                extra={<Button type="primary" icon={<PlusOutlined />} onClick={openNew}>Thêm provider</Button>}
            >
                <Paragraph type="secondary">
                    Provider AI <Text strong>riêng</Text> dùng cho dự báo/chiến lược quảng cáo — tách hoàn toàn với AI messaging.
                    Chỉ một provider <Text strong>đang dùng</Text> tại một thời điểm.
                </Paragraph>
                <Table<MarketingAiProviderRow>
                    rowKey="code"
                    loading={isLoading}
                    dataSource={rows ?? []}
                    pagination={false}
                    columns={[
                        { title: 'Code', dataIndex: 'code', key: 'code' },
                        { title: 'Tên', dataIndex: 'display_name', key: 'name', render: (v: string | null) => v ?? '—' },
                        { title: 'Adapter', dataIndex: 'adapter', key: 'adapter', render: (v: string) => <Tag>{v}</Tag> },
                        { title: 'Model', dataIndex: 'default_model', key: 'model', render: (v: string | null) => v ?? '—' },
                        { title: 'API key', dataIndex: 'has_key', key: 'key', render: (v: boolean) => v ? <Tag color="green">đã có</Tag> : <Tag>trống</Tag> },
                        { title: 'Đang dùng', dataIndex: 'is_active', key: 'active', render: (v: boolean) => v ? <Tag color="blue">active</Tag> : '—' },
                        {
                            title: '', key: 'actions', render: (_: unknown, r: MarketingAiProviderRow) => (
                                <Space>
                                    <Button size="small" onClick={() => openEdit(r)}>Sửa</Button>
                                    <Popconfirm title="Xoá provider?" okText="Xoá" cancelText="Huỷ" okButtonProps={{ danger: true }} onConfirm={() => del.mutate(r.code, { onSuccess: () => message.success('Đã xoá.') })}>
                                        <Button size="small" danger icon={<DeleteOutlined />} />
                                    </Popconfirm>
                                </Space>
                            ),
                        },
                    ]}
                />
            </Card>

            <Modal
                open={open}
                title={editing ? `Sửa ${editing.code}` : 'Thêm provider AI marketing'}
                onCancel={() => setOpen(false)}
                onOk={submit}
                confirmLoading={save.isPending}
                okButtonProps={{ disabled: needsTest }}
                okText="Lưu" cancelText="Huỷ"
            >
                <Form form={form} layout="vertical">
                    <Form.Item name="code" label="Code" rules={[{ required: true, pattern: /^[a-z0-9][a-z0-9_-]*$/, message: 'chữ thường/số/-/_' }]}>
                        <Input disabled={editing !== null} placeholder="forecast-openai" />
                    </Form.Item>
                    <Form.Item name="display_name" label="Tên hiển thị"><Input placeholder="Forecast GPT" /></Form.Item>
                    <Form.Item name="adapter" label="Adapter" rules={[{ required: true }]}>
                        <Segmented options={[{ label: 'OpenAI-compatible', value: 'openai_compatible' }, { label: 'Anthropic', value: 'anthropic' }, { label: 'Manual (stub)', value: 'manual' }]} />
                    </Form.Item>
                    {/* Trang admin: hiện thẳng key qua SecretInput dùng chung (spec §5.3) — thay
                        Input "để trống = giữ nguyên" cũ, vốn dễ nhầm với xoá key. */}
                    <Form.Item label="API key">
                        <SecretInput value={apiKeyDraft} onSave={(v) => setApiKeyDraft(v)} />
                    </Form.Item>
                    <Form.Item name="base_url" label="Base URL (tuỳ chọn)"><Input placeholder="https://api.openai.com/v1" /></Form.Item>
                    <Form.Item name="default_model" label="Model"><Input placeholder="gpt-4o-mini" /></Form.Item>
                    {probeSupported && (
                        <Form.Item label="Xác minh kết nối">
                            <Space>
                                <Button icon={<ThunderboltOutlined />} loading={testDraft.isPending} onClick={runDraftTest}>
                                    Test kết nối
                                </Button>
                                {currentSignature === verifiedSignature
                                    ? <Tag color="green">Đã xác minh</Tag>
                                    : <Tag color="orange">Chưa xác minh — Lưu bị khoá tới khi Test pass</Tag>}
                            </Space>
                        </Form.Item>
                    )}
                    <Form.Item name="is_active" label="Đang dùng" valuePropName="checked"><Switch /></Form.Item>
                </Form>
            </Modal>
        </div>
    );
}
```

- [ ] **Step 3: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: all succeed with no new errors.

- [ ] **Step 4: Manual browser verification**

With the backend from Tasks 1 and 4 running, log into `/admin/marketing-ai-providers`:
1. Confirm the Card title now shows the same rising-chart icon (`RiseOutlined`) as the sidebar's
   "AI Marketing" entry (Phase 0), not the API-plug icon shared with "Nhà cung cấp AI".
2. Click "Thêm provider" — confirm the API key field is the plaintext-display `SecretInput` control
   (not a "để trống = giữ nguyên" `Input`), and the modal's "Lưu" starts disabled with an orange
   "Chưa xác minh" tag for the default `openai_compatible` adapter.
3. Fill Base URL/API key/Model, click "Test kết nối" — confirm success unlocks Lưu (green tag) and
   failure keeps it locked with an error toast.
4. Switch adapter to "Manual (stub)" — confirm the "Xác minh kết nối" row disappears and Lưu is
   enabled immediately (ungated).
5. Open an existing provider via "Sửa" — confirm the API key box shows the actual saved plaintext
   key (this requires Task 4's backend `safe()` change; before that change this field would render
   `(chưa đặt)` even for a provider with a key already set — confirm it does NOT do that now).
6. Confirm "Xoá provider?" `Popconfirm` still works unchanged (out of scope for this phase per the
   task brief — standard-tier action, no `ReasonConfirmModal` needed).

- [ ] **Step 5: Cross-page regression spot-check**

Navigate to `/admin/ai-transcription` and `/admin/ai-visual-rerank` (unaffected by this phase per
Finding 2) and confirm both still load, list their role-filtered providers, and their existing
Radio-select + Test + Lưu flow still behaves exactly as before this plan's changes (no shared
hook/component in this plan touches either page).

- [ ] **Step 6: Commit**

```bash
git add app/resources/js/admin/lib/marketingAiProviders.tsx app/resources/js/admin/pages/settings/AdminMarketingAiProvidersPage.tsx
git commit -m "feat(admin): SecretInput + gate Test-trước-khi-Lưu + icon riêng cho AI Marketing"
```

---

## Phase 2b self-review checklist (for whoever executes this plan)

- All 5 pages remain on their existing separate routes — no route file (`AdminApp.tsx` or module
  `routes.php` beyond the 3 new `test-draft` endpoints) merges or removes a page.
- **No step in this plan adds masking, hiding, or obscuring of any secret/credential value.**
  `SecretInput` is reused exactly as it already behaves (plaintext display, click "Đặt giá trị" to
  overwrite); the one backend change to a *hidden* field (`AdminMarketingAiProviderController::safe()`,
  Task 4) makes a value **more** visible (plaintext, matching the Messaging page's existing
  convention), never less.
- `AdminTranscriptionPage.tsx` and `AdminVisualRerankPage.tsx` have zero modifications in this
  plan — confirmed in the Findings section that neither has a credential field and their existing
  gate mechanism is already correct and identical between the two.
- Every "Lưu"/"Lưu cấu hình..." button gated by this plan (`AdminAiProvidersPage`'s modal OK,
  `AdminAiSupportPage`'s two save buttons, `AdminMarketingAiProvidersPage`'s modal OK) has a
  precisely-defined disabled condition tied to a signature of the exact fields that matter
  (adapter/base_url/model/api_key, or base_url/api_key/model for Support) — not a vague "some
  field changed" check.
- `custom_http` and `manual` adapters are explicitly, consistently exempted from the gate on both
  `AdminAiProvidersPage` and `AdminMarketingAiProvidersPage` (documented reason: no fixed
  request/response shape to probe generically) — verify `PROBEABLE_ADAPTERS` is defined identically
  in both files.
- `CredentialProbe` lives in `app/app/Integrations/Ai/`, not inside Messaging/Support/Marketing —
  cross-check Tasks 2-4's controllers only `use CMBcoreSeller\Integrations\Ai\CredentialProbe;`,
  never each other's `Http\Controllers\*` or `Services\*`.
- All Vietnamese user-facing strings introduced (tags, button labels, error messages) read
  naturally to a Vietnamese admin user — spot-check against the rest of each page's existing copy.
