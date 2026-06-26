# API key + Public site + Tài liệu API — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Owner tạo/xem/xóa API key bên thứ 3 (gắn tenant, có hạn, toàn quyền như web); tách `/` thành public site (Home/Pricing/Tools/API-docs) + dời dashboard sang `/dashboard`; trang tài liệu API biên soạn riêng.

**Architecture:** API key = Sanctum PAT + cột `tenant_id`/`kind`; `EnsureTenant` ép tenant theo token. UI quản lý ở Settings (owner-only). Public pages dùng `PublicLayout` đặt trước catch-all auth trong `app.tsx`. 3 phase ship độc lập.

**Tech Stack:** Laravel 11 + Sanctum, PHPUnit/Pint/Larastan; React 18 + Vite + AntD + React Router + TanStack Query.

## Global Constraints

- Lệnh từ `app/`. Namespace `CMBcoreSeller\` → `app/app/`. Money = int VND; envelope `{data,meta}`/`{error}`.
- Module qua Contracts/events; UI icon `@ant-design/icons` (không emoji), hạn chế `<Select>` (Radio/Segmented).
- Owner-only api-keys: **chỉ owner** (kể cả custom roles SPEC 0031) — gate bằng `CurrentTenant::isOwner()`, KHÔNG chỉ dựa `can('*')`.
- Token plaintext trả **1 lần**; không log token; hiện `last_four`.
- Test baseline: BE chưa green toàn cục — chạy test liên quan Tenancy/Billing/Settings (memory `test-verify-baseline`).
- Có 2 shell FE: `DesktopShell` (v2) + `AppLayout` (legacy), cả hai render `appRouteElements()` — sửa route ở `appRoutes.tsx` áp cho cả hai.

---

## File Structure

**Phase 1 — API keys (BE + UI):**
- Create migration: `app/app/Modules/Tenancy/Database/Migrations/2026_06_27_000001_add_tenant_id_kind_to_personal_access_tokens.php` (kiểm thư mục migration Tenancy thực tế; nếu PAT migration ở `database/migrations/` thì đặt migration mới ở `app/database/migrations/`).
- Modify: `app/app/Modules/Tenancy/CurrentTenant.php` (thêm `isOwner()`).
- Modify: `app/app/Modules/Tenancy/Http/Middleware/EnsureTenant.php` (ép tenant theo token).
- Modify: `app/app/Modules/Tenancy/Enums/Role.php` (Admin deny `!api_keys.manage`).
- Create: `app/app/Modules/Tenancy/Http/Controllers/ApiKeyController.php`.
- Modify: `app/routes/api.php` (3 route tenant-scoped).
- Test: `app/tests/Feature/Tenancy/ApiKeyTest.php`.
- FE: `app/resources/js/lib/apiKeys.ts` (hooks); `app/resources/js/pages/settings/SettingsApiKeysPage.tsx`; modify `SettingsLayout.tsx` + `appRoutes.tsx`.

**Phase 2 — Public site + routing:**
- Create: `PublicLayout.tsx`, `PublicHeader.tsx`, `PublicFooter.tsx`, `HomePage.tsx`, `PricingPage.tsx` (public), `ToolsPage.tsx` (under `resources/js/pages/public/`).
- Modify: `app/resources/js/app.tsx` (public routes trước catch-all), `appRoutes.tsx` (dashboard→/dashboard), `AppLayout.tsx` + DesktopShell nav (root→/dashboard).
- BE: `app/app/Modules/Billing/Http/Controllers/PublicPlanController.php` + route public `GET /api/v1/public/plans`.

**Phase 3 — API docs page:**
- Create: `app/resources/docs/api-public.md` (nội dung biên soạn) + `app/resources/js/pages/public/ApiDocsPage.tsx`.

**Docs:** `docs/05-api/endpoints.md`.

---

# PHASE 1 — API KEYS

## Task 1.1: Migration + CurrentTenant::isOwner()

**Files:**
- Create migration (xem File Structure — đặt cùng nơi PAT migration gốc `database/migrations/2026_05_11_104027_create_personal_access_tokens_table.php`).
- Modify: `app/app/Modules/Tenancy/CurrentTenant.php`
- Test: `app/tests/Feature/Tenancy/ApiKeyTest.php`

**Interfaces:**
- Produces: cột `personal_access_tokens.tenant_id` (nullable, index), `personal_access_tokens.kind` (string nullable). `CurrentTenant::isOwner(): bool`.

- [ ] **Step 1: Migration**

Tìm thư mục migration của PAT: `grep -rl personal_access_tokens app/database/migrations app/app/Modules/*/Database/Migrations`. Tạo migration mới CÙNG thư mục đó:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('abilities');
            $table->string('kind', 24)->nullable()->after('tenant_id'); // 'api_key' = key bên thứ 3; null = mobile/extension
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', fn (Blueprint $table) => $table->dropColumn(['tenant_id', 'kind']));
    }
};
```

- [ ] **Step 2: isOwner() + failing test**

Tạo `app/tests/Feature/Tenancy/ApiKeyTest.php` (skeleton + first test):

```php
<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Models\TenantUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_tenant_is_owner_for_owner_membership(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::create(['name' => 'S']);
        $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $membership = TenantUser::query()->where('tenant_id', $tenant->getKey())->where('user_id', $owner->getKey())->first();
        $ct = app(CurrentTenant::class);
        $ct->set($tenant, $membership);
        $this->assertTrue($ct->isOwner());
    }
}
```

- [ ] **Step 3: Run — fails** (`cd app && php artisan test --filter=ApiKeyTest`) — `isOwner` chưa tồn tại.

- [ ] **Step 4: Thêm isOwner() vào CurrentTenant** (cạnh `roleModel()`):

```php
/** Chủ gian hàng (owner) — true nếu custom role is_owner HOẶC membership legacy role='owner'. SPEC 2026-06-26. */
public function isOwner(): bool
{
    return (bool) ($this->roleModel()?->is_owner ?? false) || $this->role() === \CMBcoreSeller\Modules\Tenancy\Enums\Role::Owner;
}
```

- [ ] **Step 5: Run — pass.** Commit:

```bash
git add app/database/migrations/*personal_access_tokens* app/app/Modules/Tenancy/CurrentTenant.php app/tests/Feature/Tenancy/ApiKeyTest.php
git commit -m "feat(tenancy): PAT tenant_id+kind + CurrentTenant::isOwner()"
```

(Đường dẫn migration: dùng đúng nơi vừa tạo ở Step 1.)

## Task 1.2: EnsureTenant ép tenant theo token + Role Admin deny

**Files:**
- Modify: `app/app/Modules/Tenancy/Http/Middleware/EnsureTenant.php`
- Modify: `app/app/Modules/Tenancy/Enums/Role.php`
- Test: `app/tests/Feature/Tenancy/ApiKeyTest.php`

**Interfaces:**
- Produces: request Bearer có token `tenant_id` ⇒ CurrentTenant = tenant đó (bỏ qua header). `Role::Admin` không có `api_keys.manage`.

- [ ] **Step 1: Sửa EnsureTenant** — thay đoạn resolve `$tenantId` (dòng 40-42):

```php
// API key bên thứ 3: token gắn cứng tenant_id ⇒ ép theo token (khóa key đúng shop, bỏ qua header). SPEC 2026-06-26.
$token = $user->currentAccessToken();
$tokenTenantId = ($token && isset($token->tenant_id)) ? $token->tenant_id : null;

$tenantId = $tokenTenantId
    ?: $request->header('X-Tenant-Id')
    ?: $request->query('X-Tenant-Id')
    ?: ($request->hasSession() ? $request->session()->get('current_tenant_id') : null);
```

(`currentAccessToken()` trả `null` với cookie SPA / `TransientToken` không có `tenant_id` ⇒ `isset` false ⇒ giữ luồng cũ.)

- [ ] **Step 2: Role Admin deny** — sửa dòng 44:

```php
self::Admin => ['*', '!tenant.delete', '!tenant.transfer', '!billing.manage', '!api_keys.manage'],
```

- [ ] **Step 3: Pint check** (`cd app && vendor/bin/pint --test app/Modules/Tenancy/Http/Middleware/EnsureTenant.php app/Modules/Tenancy/Enums/Role.php`). Commit:

```bash
git add app/app/Modules/Tenancy/Http/Middleware/EnsureTenant.php app/app/Modules/Tenancy/Enums/Role.php
git commit -m "feat(tenancy): EnsureTenant ép tenant theo API key token + api_keys.manage owner-only"
```

## Task 1.3: ApiKeyController + routes (owner-only CRUD)

**Files:**
- Create: `app/app/Modules/Tenancy/Http/Controllers/ApiKeyController.php`
- Modify: `app/routes/api.php`
- Test: `app/tests/Feature/Tenancy/ApiKeyTest.php`

**Interfaces:**
- `GET/POST /api/v1/tenant/api-keys`, `DELETE /api/v1/tenant/api-keys/{id}` — gate `isOwner()`.
- Token tạo: `tokenable=owner user`, `abilities=['*']`, `tenant_id`=current, `kind='api_key'`, `expires_at` từ input.

- [ ] **Step 1: Feature tests (Queue/HTTP)** — thêm vào `ApiKeyTest.php`:

```php
private function h(int $tenantId): array { return ['X-Tenant-Id' => (string) $tenantId]; }

public function test_owner_creates_lists_and_deletes_api_key(): void
{
    $owner = User::factory()->create();
    $tenant = Tenant::create(['name' => 'S']);
    $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
    $h = $this->h((int) $tenant->getKey());

    $create = $this->actingAs($owner)->withHeaders($h)->postJson('/api/v1/tenant/api-keys', ['name' => 'Zapier', 'expires_at' => now()->addDays(30)->toIso8601String()])
        ->assertCreated();
    $token = $create->json('data.token');
    $this->assertNotEmpty($token);
    $id = $create->json('data.id');

    $this->actingAs($owner)->withHeaders($h)->getJson('/api/v1/tenant/api-keys')
        ->assertOk()->assertJsonPath('data.0.name', 'Zapier')->assertJsonMissingPath('data.0.token');

    // token plaintext dùng được + tự khóa tenant (KHÔNG cần X-Tenant-Id)
    $this->withToken($token)->getJson('/api/v1/orders')->assertOk();

    $this->actingAs($owner)->withHeaders($h)->deleteJson("/api/v1/tenant/api-keys/{$id}")->assertOk();
    $this->withToken($token)->getJson('/api/v1/orders')->assertUnauthorized();
}

public function test_staff_cannot_manage_api_keys(): void
{
    $owner = User::factory()->create();
    $staff = User::factory()->create();
    $tenant = Tenant::create(['name' => 'S']);
    $tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
    $tenant->users()->attach($staff->getKey(), ['role' => Role::StaffOrder->value]);
    $h = $this->h((int) $tenant->getKey());
    $this->actingAs($staff)->withHeaders($h)->getJson('/api/v1/tenant/api-keys')->assertForbidden();
    $this->actingAs($staff)->withHeaders($h)->postJson('/api/v1/tenant/api-keys', ['name' => 'x'])->assertForbidden();
}
```

Lưu ý: `/orders` cần `verified` + plan middleware — nếu test 403/402 do verified/plan, dùng endpoint tenant-scoped nhẹ hơn (vd `/dashboard/summary`) hoặc set user verified + active plan (xem `AccountingTestHelpers`/seed). Điều chỉnh để test xác minh "token acts as web".

- [ ] **Step 2: Run — fails** (route chưa có).

- [ ] **Step 3: Controller**

Create `ApiKeyController.php`:

```php
<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/** Quản lý API key bên thứ 3 — CHỈ owner gian hàng. Key = Sanctum PAT gắn tenant_id, abilities ['*']. SPEC 2026-06-26. */
class ApiKeyController extends Controller
{
    public function index(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($tenant->isOwner(), 403, 'Chỉ chủ gian hàng được quản lý API key.');
        $keys = PersonalAccessToken::query()
            ->where('tenant_id', $tenant->id())->where('kind', 'api_key')
            ->orderByDesc('id')->get()
            ->map(fn (PersonalAccessToken $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'last_four' => substr((string) $t->token, -4), // token column = hash; thay bằng cột riêng nếu cần (xem ghi chú)
                'abilities' => $t->abilities,
                'expires_at' => $t->expires_at?->toIso8601String(),
                'last_used_at' => $t->last_used_at?->toIso8601String(),
                'created_at' => $t->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $keys]);
    }

    public function store(Request $request, CurrentTenant $tenant): JsonResponse
    {
        abort_unless($tenant->isOwner(), 403, 'Chỉ chủ gian hàng được tạo API key.');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
        ]);
        $owner = $request->user();
        $expiresAt = ! empty($data['expires_at']) ? \Illuminate\Support\Carbon::parse($data['expires_at']) : null;
        $new = $owner->createToken($data['name'], ['*'], $expiresAt);
        // Gắn tenant + kind cho token vừa tạo.
        $new->accessToken->forceFill(['tenant_id' => $tenant->id(), 'kind' => 'api_key'])->save();

        return response()->json(['data' => [
            'id' => $new->accessToken->id,
            'name' => $data['name'],
            'token' => $new->plainTextToken,   // CHỈ TRẢ 1 LẦN
            'expires_at' => $expiresAt?->toIso8601String(),
        ]], 201);
    }

    public function destroy(Request $request, CurrentTenant $tenant, int $id): JsonResponse
    {
        abort_unless($tenant->isOwner(), 403, 'Chỉ chủ gian hàng được xóa API key.');
        $deleted = PersonalAccessToken::query()
            ->where('id', $id)->where('tenant_id', $tenant->id())->where('kind', 'api_key')
            ->delete();
        abort_if($deleted === 0, 404, 'Không tìm thấy API key.');

        return response()->json(['data' => ['deleted' => true]]);
    }
}
```

GHI CHÚ `last_four`: cột `token` của Sanctum là HASH (không phải plaintext) ⇒ `substr(token,-4)` KHÔNG phải 4 ký tự cuối key thật. Để hiện gợi nhớ đúng, lưu thêm cột `last_four` khi tạo (lấy 4 ký tự cuối `plainTextToken` sau dấu `|`). → Thêm vào migration Task 1.1 cột `last_four` (string,4,nullable) và set ở `store()`: `substr($new->plainTextToken, -4)`. Cập nhật index() trả `$t->last_four`. (Điều chỉnh khi code.)

- [ ] **Step 4: Routes** — trong `routes/api.php`, nhóm tenant-scoped (cùng chỗ `/tenant/roles`,`/tenant/members`), thêm:

```php
Route::get('tenant/api-keys', [\CMBcoreSeller\Modules\Tenancy\Http\Controllers\ApiKeyController::class, 'index'])->name('tenant.api-keys.index');
Route::post('tenant/api-keys', [\CMBcoreSeller\Modules\Tenancy\Http\Controllers\ApiKeyController::class, 'store'])->name('tenant.api-keys.store');
Route::delete('tenant/api-keys/{id}', [\CMBcoreSeller\Modules\Tenancy\Http\Controllers\ApiKeyController::class, 'destroy'])->whereNumber('id')->name('tenant.api-keys.destroy');
```

- [ ] **Step 5: Run — pass** (`cd app && php artisan test --filter=ApiKeyTest`). Pint + phpstan trên file mới.

- [ ] **Step 6: Commit**

```bash
git add app/app/Modules/Tenancy/Http/Controllers/ApiKeyController.php app/routes/api.php app/tests/Feature/Tenancy/ApiKeyTest.php app/database/migrations/*personal_access_tokens*
git commit -m "feat(tenancy): API key owner-only (tạo/list/xóa, gắn tenant, abilities ['*'])"
```

## Task 1.4: FE — Settings API keys page (owner-only)

**Files:**
- Create: `app/resources/js/lib/apiKeys.ts`
- Create: `app/resources/js/pages/settings/SettingsApiKeysPage.tsx`
- Modify: `app/resources/js/components/SettingsLayout.tsx`, `app/resources/js/routes/appRoutes.tsx`

**Interfaces:**
- Hooks: `useApiKeys()`, `useCreateApiKey()`, `useDeleteApiKey()`.

- [ ] **Step 1: Hooks** — `apiKeys.ts` (theo mẫu `lib/customers.tsx` `useScopedApi`/`useCurrentTenantId`):

```ts
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo } from 'react';
import { tenantApi } from './api';
import { useCurrentTenantId } from './tenant';

export interface ApiKey { id: number; name: string; last_four: string | null; abilities: string[]; expires_at: string | null; last_used_at: string | null; created_at: string | null }
function useScopedApi() { const t = useCurrentTenantId(); return useMemo(() => (t == null ? null : tenantApi(t)), [t]); }

export function useApiKeys() {
  const api = useScopedApi(); const t = useCurrentTenantId();
  return useQuery({ queryKey: ['api-keys', t], enabled: api != null, queryFn: async () => (await api!.get<{ data: ApiKey[] }>('/tenant/api-keys')).data.data });
}
export function useCreateApiKey() {
  const api = useScopedApi(); const qc = useQueryClient(); const t = useCurrentTenantId();
  return useMutation({
    mutationFn: async (v: { name: string; expires_at?: string | null }) => (await api!.post<{ data: { id: number; name: string; token: string; expires_at: string | null } }>('/tenant/api-keys', v)).data.data,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['api-keys', t] }),
  });
}
export function useDeleteApiKey() {
  const api = useScopedApi(); const qc = useQueryClient(); const t = useCurrentTenantId();
  return useMutation({ mutationFn: async (id: number) => { await api!.delete(`/tenant/api-keys/${id}`); }, onSuccess: () => qc.invalidateQueries({ queryKey: ['api-keys', t] }) });
}
```

- [ ] **Step 2: Page** — `SettingsApiKeysPage.tsx`: bảng key (name, last_four, expires_at, last_used_at, nút Xóa có `Popconfirm`); nút "Tạo API key" mở `Modal` (Input tên + `Segmented` preset hạn 30/90/365 ngày / Không hết hạn + DatePicker custom); sau tạo hiện token 1 lần trong `Alert`/`Typography.Paragraph copyable` + cảnh báo "giữ bí mật, chỉ hiện 1 lần". Dùng `useCan('api_keys.manage')` — nếu false hiện `Result 403`. Icon `ApiOutlined`, `DeleteOutlined`, `PlusOutlined`.

- [ ] **Step 3: SettingsLayout** — thêm vào `SECTIONS` (nhóm Kết nối): `{ key: '/settings/api-keys', label: 'API & Tích hợp', icon: <ApiOutlined /> }` (khớp shape item hiện có); chỉ render khi owner — bọc bằng kiểm `useCan('api_keys.manage')` trong logic build menu (xem cách SettingsLayout lọc theo quyền).

- [ ] **Step 4: Route** — `appRoutes.tsx` dưới `<Route path="settings">`: `<Route path="api-keys" element={<SettingsApiKeysPage />} />`.

- [ ] **Step 5: typecheck + lint + build** (`cd app && npm run typecheck && npm run lint && npm run build`).

- [ ] **Step 6: Commit**

```bash
git add app/resources/js/lib/apiKeys.ts app/resources/js/pages/settings/SettingsApiKeysPage.tsx app/resources/js/components/SettingsLayout.tsx app/resources/js/routes/appRoutes.tsx
git commit -m "feat(settings): UI quản lý API key owner-only (tạo/list/xóa, token 1 lần)"
```

---

# PHASE 2 — PUBLIC SITE + ROUTING

## Task 2.1: Public plans endpoint

**Files:**
- Create: `app/app/Modules/Billing/Http/Controllers/PublicPlanController.php`
- Modify: `app/routes/api.php`
- Test: `app/tests/Feature/Billing/PublicPlansTest.php`

- [ ] **Step 1: Test** — `PublicPlansTest.php`:

```php
<?php
namespace Tests\Feature\Billing;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class PublicPlansTest extends TestCase
{
    use RefreshDatabase;
    public function test_public_plans_no_auth(): void
    {
        $this->seed(BillingPlanSeeder::class);
        $this->getJson('/api/v1/public/plans')->assertOk()->assertJsonStructure(['data' => [['code', 'name', 'price_monthly', 'price_yearly', 'currency', 'features', 'limits']]]);
    }
}
```

- [ ] **Step 2: Run — fails.**

- [ ] **Step 3: Controller**

```php
<?php
namespace CMBcoreSeller\Modules\Billing\Http\Controllers;
use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use Illuminate\Http\JsonResponse;
class PublicPlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Plan::query()->where('is_active', true)->orderBy('sort_order')->get()
            ->map(fn (Plan $p) => [
                'code' => $p->code, 'name' => $p->name, 'description' => $p->description,
                'price_monthly' => (int) $p->price_monthly, 'price_yearly' => (int) $p->price_yearly,
                'currency' => $p->currency, 'trial_days' => (int) $p->trial_days,
                'features' => $p->features ?? [], 'limits' => $p->limits ?? [],
            ]);
        return response()->json(['data' => $plans]);
    }
}
```

- [ ] **Step 4: Route** — trong `routes/api.php`, ở nhóm PUBLIC (cùng chỗ `/auth/register`,`/tracking` public — KHÔNG trong group auth/tenant):

```php
Route::get('public/plans', [\CMBcoreSeller\Modules\Billing\Http\Controllers\PublicPlanController::class, 'index'])->name('public.plans');
```

- [ ] **Step 5: Run — pass. Commit.**

```bash
git add app/app/Modules/Billing/Http/Controllers/PublicPlanController.php app/routes/api.php app/tests/Feature/Billing/PublicPlansTest.php
git commit -m "feat(billing): endpoint công khai GET /public/plans cho trang bảng giá"
```

## Task 2.2: PublicLayout + Home/Pricing/Tools + dời dashboard

**Files:**
- Create: `app/resources/js/pages/public/PublicLayout.tsx`, `PublicHeader.tsx`, `PublicFooter.tsx`, `HomePage.tsx`, `PricingPage.tsx`, `ToolsPage.tsx`
- Modify: `app/resources/js/app.tsx`, `app/resources/js/routes/appRoutes.tsx`, `app/resources/js/components/AppLayout.tsx` (+ DesktopShell nav nếu có root link)

**Interfaces:**
- Public routes render KHÔNG cần auth; dashboard ở `/dashboard`.

- [ ] **Step 1: PublicLayout + Header/Footer** — `PublicLayout.tsx`:

```tsx
import { Outlet } from 'react-router-dom';
import { PublicHeader } from './PublicHeader';
import { PublicFooter } from './PublicFooter';
export function PublicLayout() {
  return (<div style={{ minHeight: '100vh', display: 'flex', flexDirection: 'column' }}>
    <PublicHeader /><div style={{ flex: 1 }}><Outlet /></div><PublicFooter /></div>);
}
```

`PublicHeader.tsx`: AntD `Menu` mode horizontal + logo + nút phải. Items: Trang chủ (`/`), Bảng giá (`/pricing`), Tài liệu API (`/api-docs`), "Phần mềm phụ trợ" (SubMenu → Chrome extension `/tools#extension`, App mobile `/download`). Nút phải: dùng `useAuth()` — nếu đăng nhập → `<Link to="/dashboard">Vào ứng dụng</Link>`, chưa → `<Link to="/login">Đăng nhập</Link>`. Icons `@ant-design/icons`.

- [ ] **Step 2: Pages** — `HomePage.tsx` (hero giới thiệu phần mềm + tính năng chính + CTA "Bắt đầu"/"Xem bảng giá"), `PricingPage.tsx` (fetch `/api/v1/public/plans` qua axios trần `axios.get('/api/v1/public/plans')` — KHÔNG cần tenant; hiển thị card từng plan: tên, giá tháng/năm, features list), `ToolsPage.tsx` (2 mục: Chrome extension + App mobile — tái dùng nội dung/CTA `DownloadAppPage`, link tải). Nội dung tiếng Việt, AntD components.

- [ ] **Step 3: app.tsx** — thêm public routes TRƯỚC route shell `/*`:

```tsx
<Route element={<PublicLayout />}>
  <Route path="/" element={<HomePage />} />
  <Route path="/pricing" element={<PricingPage />} />
  <Route path="/tools" element={<ToolsPage />} />
  <Route path="/api-docs" element={<ApiDocsPage />} />
</Route>
```

(Đặt sau các route `/login`,`/tracking`,`/download` và TRƯỚC block `shell==='v2' ? DesktopShell : AppLayout` ở `/*`. Import các page + PublicLayout. `/download` giữ nguyên route cũ.)

- [ ] **Step 4: Dời dashboard** — `appRoutes.tsx`: đổi dòng 76 `<Route index element={<DashboardPage />} />` thành:

```tsx
<Route index element={<Navigate to="/dashboard" replace />} />
<Route path="dashboard" element={<DashboardPage />} />
```

(Import `Navigate` từ `react-router-dom` nếu chưa.) — áp cho cả AppLayout & DesktopShell (cùng dùng appRouteElements).

- [ ] **Step 5: Sidebar nav root → /dashboard** — `AppLayout.tsx` dòng ~47 `{ key: '/', label: <Link to="/">Bảng điều khiển</Link> }` → `{ key: '/dashboard', label: <Link to="/dashboard">Bảng điều khiển</Link> }`. Kiểm DesktopShell có item tương tự → sửa cùng.

- [ ] **Step 6: typecheck + lint + build.** Verify thủ công: `/` ra Home (không cần login), `/dashboard` ra dashboard (cần login), `/orders` vẫn chạy.

- [ ] **Step 7: Commit**

```bash
git add app/resources/js/pages/public app/resources/js/app.tsx app/resources/js/routes/appRoutes.tsx app/resources/js/components/AppLayout.tsx
git commit -m "feat(public): public site (Home/Pricing/Tools) + header menu, dời dashboard sang /dashboard"
```

---

# PHASE 3 — TÀI LIỆU API

## Task 3.1: ApiDocsPage (markdown biên soạn)

**Files:**
- Create: `app/resources/docs/api-public.md`
- Create: `app/resources/js/pages/public/ApiDocsPage.tsx`

- [ ] **Step 1: Nội dung** — `api-public.md`: các mục (tiếng Việt) — Giới thiệu; **Xác thực** (tạo API key ở Settings → API & Tích hợp, chỉ owner; `Authorization: Bearer <key>`; key đã gắn shop nên KHÔNG cần `X-Tenant-Id`; thời hạn + thu hồi); **Quy ước** (base URL `/api/v1`, envelope `{data,meta}`/`{error}`, tiền=int VND, thời gian ISO-8601 UTC, rate limit 120/phút); **Endpoint chính** với ví dụ `curl`: `GET /orders`, `GET /orders/{id}`, `POST /orders`, `POST /orders/{id}/ship`, `GET /products`, `GET /inventory`, `GET /shipments`. (Lấy chi tiết từ `docs/05-api/endpoints.md`, biên soạn gọn.)

- [ ] **Step 2: Render** — kiểm `react-markdown` có trong deps: `cd app && grep -E "react-markdown|markdown-to-jsx" package.json`.
  - Nếu CÓ: `ApiDocsPage.tsx` import md (`import md from '@/../docs/api-public.md?raw'` — Vite `?raw`) + render `<ReactMarkdown>{md}</ReactMarkdown>` trong container nội dung.
  - Nếu KHÔNG: render cấu trúc React thủ công (các `<Typography>` + `<pre>` code block) với cùng nội dung — KHÔNG thêm dependency mới trừ khi cần (theo CLAUDE.md). (Chốt khi thực thi.)

- [ ] **Step 3: typecheck + lint + build.** Commit:

```bash
git add app/resources/docs/api-public.md app/resources/js/pages/public/ApiDocsPage.tsx
git commit -m "feat(public): trang tài liệu API biên soạn cho bên thứ 3 (/api-docs)"
```

## Task 3.2: Docs + quality gate tổng

- [ ] **Step 1: endpoints.md** — thêm mục API keys (`/tenant/api-keys` GET/POST/DELETE, owner-only) + `GET /public/plans` + ghi chú "Bearer API key gắn tenant không cần X-Tenant-Id".
- [ ] **Step 2: Gate BE** — `cd app && vendor/bin/pint --test app/Modules/Tenancy app/Modules/Billing/Http && vendor/bin/phpstan analyse app/Modules/Tenancy/Http/Controllers/ApiKeyController.php app/Modules/Billing/Http/Controllers/PublicPlanController.php app/Modules/Tenancy/CurrentTenant.php app/Modules/Tenancy/Http/Middleware/EnsureTenant.php && php artisan test --filter="ApiKey|PublicPlans"` — xanh / không thêm lỗi baseline.
- [ ] **Step 3: Gate FE** — `npm run typecheck && npm run lint && npm run build`.
- [ ] **Step 4: Commit** `docs(api): endpoints API keys + public plans`.

---

## Self-Review

**Spec coverage:** §A API key (tạo/list/xóa owner-only, gắn tenant, hết hạn, full quyền) → Task 1.1-1.4 + EnsureTenant; "chỉ owner, nhân viên không tạo/xem/xóa" → isOwner() gate + test staff 403 (1.3). §B public site + dời dashboard → Task 2.1-2.2. §C tài liệu API → Task 3.1. Public plans → 2.1. Docs → 3.2. Edge cases spec §7: token hết hạn/xóa→401 (test 1.3), cross-tenant khóa (EnsureTenant ép token tenant), staff 403, token mobile/extension không lẫn (kind='api_key' filter), logged-in vào `/` thấy Home+Vào ứng dụng (PublicHeader).

**Placeholder scan:** không TBD. Các chỗ "kiểm đúng thư mục migration / cách SettingsLayout lọc quyền / react-markdown có sẵn / nội dung trang Home" là chỉ-dẫn-xác-minh-tại-chỗ + scaffold thật, không phải code thiếu. last_four: đã nêu rõ phải lưu cột riêng (Sanctum token=hash) — chốt ở 1.1/1.3.

**Type consistency:** `isOwner()` dùng nhất quán 1.1↔1.3. `ApiKey` interface (1.4) khớp index() response (1.3). Routes `tenant/api-keys` khớp controller + hooks. `public/plans` khớp controller + PricingPage. `kind='api_key'`/`tenant_id` nhất quán migration↔controller↔EnsureTenant.

**ĐIỂM CẦN CHỐT KHI THỰC THI (đã ghi trong task):** (1) cột `last_four` thêm vào migration 1.1 + set ở store(); (2) cách SettingsLayout ẩn mục theo quyền; (3) DesktopShell có link root cần sửa không; (4) react-markdown.
