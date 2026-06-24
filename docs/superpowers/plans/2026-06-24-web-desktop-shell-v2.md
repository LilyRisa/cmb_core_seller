# Giao diện v2 "Web Desktop" — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm một vỏ giao diện thay thế "Web Desktop" (tab giống trình duyệt, màn Desktop là launcher) mà người dùng bật/tắt trong Cài đặt; mặc định v1, opt-in v2; lưu lựa chọn theo tài khoản ở backend.

**Architecture:** Tách "vỏ" khỏi "nội dung trang". Các `<Route>` page hiện có được trích thành một fragment dùng chung (`appRouteElements()`). Vỏ v1 = `AppLayout` cũ (giữ nguyên hành vi). Vỏ v2 = `DesktopShell`: header dùng chung + dải tab + mỗi tab là một `MemoryRouter` độc lập chứa **toàn bộ** route page (keep-alive bằng `display:none`, mỗi tab nhớ sub-route riêng). Tab Desktop ghim hiển thị lưới icon app + `DashboardPage`. Lựa chọn shell + danh sách tab mở lưu ở bảng `user_preferences` (cấp user, không tenant_id), trả kèm trong `/auth/me`.

**Tech Stack:** Laravel 11 (PHPUnit, Eloquent), React 18 + React Router v6 (`MemoryRouter`), Ant Design 5, TanStack Query, Zustand.

## Global Constraints

- **Chạy mọi lệnh PHP/Node từ `app/`** (không từ repo root).
- PSR-4: `CMBcoreSeller\` → `app/app/`. Module Tenancy ở `app/app/Modules/Tenancy/`.
- Money/timestamp/envelope theo `docs/05-api/conventions.md`: response `{ "data": ... }` / lỗi `{ "error": { "code", "message", ... } }`.
- **UI:** icon dùng `@ant-design/icons` (không emoji); ưu tiên `Radio.Group`/`Segmented`, **không** `<Select>` cho lựa chọn nhỏ.
- **Quyền:** ẩn theo `useCan(<permission>)` đúng như sidebar v1; không hardcode tên sàn/marketplace.
- `user_preferences` **không** mang `tenant_id`, **không** dùng trait `BelongsToTenant` (ngoại lệ có chủ đích — ADR-0027). Truy vấn luôn theo `user_id` của người đăng nhập.
- **FE không có test runner JS** (xác nhận trong dự án): task FE verify bằng `npm run lint && npm run typecheck && npm run build` + kiểm tay trên trình duyệt. Task BE dùng PHPUnit (TDD thật).
- Gate chất lượng cuối: `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test`, `npm run lint && npm run typecheck && npm run build` — tất cả phải xanh.
- Mỗi task kết thúc bằng 1 commit (conventional message). Spec nguồn: `docs/specs/0037-web-desktop-shell-v2.md`; quyết định kiến trúc: `docs/01-architecture/adr/0027-swappable-app-shell-web-desktop.md`.

---

## File Structure

**Backend (module Tenancy):**
- Create `app/app/Modules/Tenancy/Database/Migrations/2026_06_24_100000_create_user_preferences_table.php` — bảng lưu preference cấp user.
- Create `app/app/Modules/Tenancy/Models/UserPreference.php` — model (cast `value` → array).
- Create `app/app/Modules/Tenancy/Services/UserPreferenceService.php` — đọc/ghi merge theo `user_id`.
- Create `app/app/Modules/Tenancy/Http/Controllers/UserPreferenceController.php` — GET/PUT.
- Create `app/app/Modules/Tenancy/Http/Requests/UpdatePreferencesRequest.php` — validate.
- Modify `app/app/Modules/Tenancy/Http/Controllers/Concerns/ResolvesAuthUserPayload.php` — thêm `preferences` vào payload `me`.
- Modify `app/routes/api.php` — 2 route trong nhóm `auth:sanctum`.
- Create `app/tests/Feature/Tenancy/UserPreferenceTest.php` — feature test.

**Frontend:**
- Create `app/resources/js/routes/appRoutes.tsx` — `appRouteElements()` (fragment route page dùng chung v1/v2).
- Create `app/resources/js/lib/preferences.tsx` — `useUserPreferences()`, `useUpdatePreferences()`.
- Create `app/resources/js/lib/desktop/appCatalog.tsx` — 9 app + sub-menu + `appForPath()`.
- Create `app/resources/js/lib/desktop/desktopShellStore.ts` — Zustand quản lý tab.
- Create `app/resources/js/components/AppHeader.tsx` — header dùng chung (trích từ AppLayout).
- Create `app/resources/js/components/desktop/DesktopShell.tsx` — vỏ v2.
- Create `app/resources/js/components/desktop/TabStrip.tsx` — dải tab.
- Create `app/resources/js/components/desktop/DesktopHome.tsx` — màn nền (icon + Dashboard).
- Create `app/resources/js/components/desktop/AppFrame.tsx` — sub-menu + nội dung trong 1 tab.
- Create `app/resources/js/pages/SettingsAppearancePage.tsx` — chọn v1/v2.
- Modify `app/resources/js/components/AppLayout.tsx` — dùng `AppHeader` (DRY).
- Modify `app/resources/js/app.tsx` — branch shell theo preference; thêm route `/settings/appearance`.
- Modify `app/resources/js/components/SettingsLayout.tsx` — thêm mục menu "Giao diện".
- Modify `docs/05-api/endpoints.md` — ghi 2 endpoint mới.

---

## Task 1: Bảng `user_preferences` + model

**Files:**
- Create: `app/app/Modules/Tenancy/Database/Migrations/2026_06_24_100000_create_user_preferences_table.php`
- Create: `app/app/Modules/Tenancy/Models/UserPreference.php`
- Test: (kiểm migration chạy được — không có test riêng, gộp vào Task 4)

**Interfaces:**
- Produces: bảng `user_preferences(id, user_id, key, value json, timestamps)` unique `(user_id, key)`; model `UserPreference` với `$fillable=['user_id','key','value']`, cast `value=>'array'`.

- [ ] **Step 1: Viết migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key', 64);
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
```

- [ ] **Step 2: Viết model**

```php
<?php

namespace CMBcoreSeller\Modules\Tenancy\Models;

use CMBcoreSeller\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Preference cấp NGƯỜI DÙNG (không theo tenant) — vd lựa chọn vỏ giao diện v1/v2.
 * Ngoại lệ có chủ đích với BelongsToTenant: xem ADR-0027 / SPEC-0037.
 */
class UserPreference extends Model
{
    protected $fillable = ['user_id', 'key', 'value'];

    protected $casts = ['value' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 3: Chạy migrate kiểm tra**

Run: `cd app && php artisan migrate`
Expected: in ra `... create_user_preferences_table ... DONE`, không lỗi.

- [ ] **Step 4: Commit**

```bash
git add app/app/Modules/Tenancy/Database/Migrations/2026_06_24_100000_create_user_preferences_table.php app/app/Modules/Tenancy/Models/UserPreference.php
git commit -m "feat(tenancy): bảng user_preferences + model (SPEC-0037)"
```

---

## Task 2: `UserPreferenceService`

**Files:**
- Create: `app/app/Modules/Tenancy/Services/UserPreferenceService.php`
- Test: `app/tests/Feature/Tenancy/UserPreferenceServiceTest.php`

**Interfaces:**
- Consumes: `UserPreference` model (Task 1).
- Produces:
  - `all(int $userId): array<string,mixed>` — map `key => value` của user (mặc định rỗng).
  - `putMany(int $userId, array<string,mixed> $values): array<string,mixed>` — `updateOrCreate` từng key (merge, không xoá key khác), trả map mới sau ghi.

- [ ] **Step 1: Viết failing test**

```php
<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Tenancy\Services\UserPreferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_put_many_merges_without_wiping_other_keys(): void
    {
        $user = User::factory()->create();
        $svc = app(UserPreferenceService::class);

        $svc->putMany($user->id, ['ui_shell' => 'v2']);
        $svc->putMany($user->id, ['ui_active_tab' => 'sales']);

        $all = $svc->all($user->id);
        $this->assertSame('v2', $all['ui_shell']);
        $this->assertSame('sales', $all['ui_active_tab']);
    }

    public function test_all_is_isolated_per_user(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $svc = app(UserPreferenceService::class);

        $svc->putMany($a->id, ['ui_shell' => 'v2']);

        $this->assertSame([], $svc->all($b->id));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `cd app && php artisan test --filter=UserPreferenceServiceTest`
Expected: FAIL — `Class "CMBcoreSeller\Modules\Tenancy\Services\UserPreferenceService" not found`.

- [ ] **Step 3: Viết service tối thiểu**

```php
<?php

namespace CMBcoreSeller\Modules\Tenancy\Services;

use CMBcoreSeller\Modules\Tenancy\Models\UserPreference;

class UserPreferenceService
{
    /** @return array<string,mixed> */
    public function all(int $userId): array
    {
        return UserPreference::query()
            ->where('user_id', $userId)
            ->pluck('value', 'key')
            ->all();
    }

    /**
     * @param  array<string,mixed>  $values
     * @return array<string,mixed>
     */
    public function putMany(int $userId, array $values): array
    {
        foreach ($values as $key => $value) {
            UserPreference::updateOrCreate(
                ['user_id' => $userId, 'key' => $key],
                ['value' => $value],
            );
        }

        return $this->all($userId);
    }
}
```

- [ ] **Step 4: Chạy test để xác nhận PASS**

Run: `cd app && php artisan test --filter=UserPreferenceServiceTest`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Tenancy/Services/UserPreferenceService.php app/tests/Feature/Tenancy/UserPreferenceServiceTest.php
git commit -m "feat(tenancy): UserPreferenceService get/putMany theo user (SPEC-0037)"
```

---

## Task 3: Endpoint `GET/PUT /me/preferences`

**Files:**
- Create: `app/app/Modules/Tenancy/Http/Requests/UpdatePreferencesRequest.php`
- Create: `app/app/Modules/Tenancy/Http/Controllers/UserPreferenceController.php`
- Modify: `app/routes/api.php` (nhóm `auth:sanctum`, cạnh `auth/me`)
- Test: `app/tests/Feature/Tenancy/UserPreferenceTest.php`

**Interfaces:**
- Consumes: `UserPreferenceService` (Task 2).
- Produces:
  - `GET /api/v1/me/preferences` ⇒ `{ data: { ui_shell, ui_open_tabs, ui_active_tab } }` (giá trị thiếu → default: `ui_shell="v1"`, `ui_open_tabs=[]`, `ui_active_tab=null`).
  - `PUT /api/v1/me/preferences` body `{ ui_shell?, ui_open_tabs?, ui_active_tab? }` ⇒ `{ data: {...} }`. `ui_shell` ∉ {v1,v2} ⇒ 422.

- [ ] **Step 1: Viết failing test**

```php
<?php

namespace Tests\Feature\Tenancy;

use CMBcoreSeller\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_when_empty(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user)->getJson('/api/v1/me/preferences');

        $res->assertOk()->assertJsonPath('data.ui_shell', 'v1')
            ->assertJsonPath('data.ui_active_tab', null);
        $this->assertSame([], $res->json('data.ui_open_tabs'));
    }

    public function test_put_then_get_roundtrip(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/v1/me/preferences', [
            'ui_shell' => 'v2',
            'ui_open_tabs' => [['appKey' => 'sales', 'path' => '/orders']],
            'ui_active_tab' => 'sales',
        ])->assertOk()->assertJsonPath('data.ui_shell', 'v2');

        $this->actingAs($user)->getJson('/api/v1/me/preferences')
            ->assertJsonPath('data.ui_active_tab', 'sales')
            ->assertJsonPath('data.ui_open_tabs.0.appKey', 'sales');
    }

    public function test_invalid_shell_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/v1/me/preferences', ['ui_shell' => 'v9'])
            ->assertStatus(422);
    }

    public function test_me_includes_preferences(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->putJson('/api/v1/me/preferences', ['ui_shell' => 'v2']);

        $this->actingAs($user)->getJson('/api/v1/auth/me')
            ->assertJsonPath('data.preferences.ui_shell', 'v2');
    }
}
```

- [ ] **Step 2: Chạy test xác nhận FAIL**

Run: `cd app && php artisan test --filter=UserPreferenceTest`
Expected: FAIL — route `/api/v1/me/preferences` chưa tồn tại (404). `test_me_includes_preferences` cũng fail (chưa có key `preferences` — sẽ hoàn tất ở Task 4; chấp nhận đỏ tới Task 4).

- [ ] **Step 3: Viết FormRequest**

```php
<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'ui_shell' => ['sometimes', 'in:v1,v2'],
            'ui_active_tab' => ['sometimes', 'nullable', 'string', 'max:64'],
            'ui_open_tabs' => ['sometimes', 'array', 'max:30'],
            'ui_open_tabs.*.appKey' => ['required', 'string', 'max:64'],
            'ui_open_tabs.*.path' => ['required', 'string', 'max:255'],
        ];
    }
}
```

- [ ] **Step 4: Viết controller**

```php
<?php

namespace CMBcoreSeller\Modules\Tenancy\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Tenancy\Http\Requests\UpdatePreferencesRequest;
use CMBcoreSeller\Modules\Tenancy\Services\UserPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    public function __construct(private readonly UserPreferenceService $prefs) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->shape($this->prefs->all((int) $request->user()->getKey()))]);
    }

    public function update(UpdatePreferencesRequest $request): JsonResponse
    {
        $saved = $this->prefs->putMany((int) $request->user()->getKey(), $request->validated());

        return response()->json(['data' => $this->shape($saved)]);
    }

    /**
     * @param  array<string,mixed>  $all
     * @return array<string,mixed>
     */
    private function shape(array $all): array
    {
        return [
            'ui_shell' => $all['ui_shell'] ?? 'v1',
            'ui_open_tabs' => $all['ui_open_tabs'] ?? [],
            'ui_active_tab' => $all['ui_active_tab'] ?? null,
        ];
    }
}
```

- [ ] **Step 5: Thêm route** — trong `app/routes/api.php`, ngay sau dòng `Route::patch('auth/profile', ...)` (dòng ~81), thêm:

```php
        Route::get('me/preferences', [\CMBcoreSeller\Modules\Tenancy\Http\Controllers\UserPreferenceController::class, 'show'])->name('me.preferences.show');     // SPEC 0037
        Route::put('me/preferences', [\CMBcoreSeller\Modules\Tenancy\Http\Controllers\UserPreferenceController::class, 'update'])->name('me.preferences.update'); // SPEC 0037
```

- [ ] **Step 6: Chạy test** (trừ `test_me_includes_preferences` còn đỏ tới Task 4)

Run: `cd app && php artisan test --filter=UserPreferenceTest`
Expected: 3 PASS (`test_defaults_when_empty`, `test_put_then_get_roundtrip`, `test_invalid_shell_rejected`); `test_me_includes_preferences` FAIL (hoàn tất ở Task 4).

- [ ] **Step 7: Commit**

```bash
git add app/app/Modules/Tenancy/Http/Requests/UpdatePreferencesRequest.php app/app/Modules/Tenancy/Http/Controllers/UserPreferenceController.php app/routes/api.php app/tests/Feature/Tenancy/UserPreferenceTest.php
git commit -m "feat(tenancy): endpoint GET/PUT /me/preferences (SPEC-0037)"
```

---

## Task 4: Trả `preferences` trong `/auth/me`

**Files:**
- Modify: `app/app/Modules/Tenancy/Http/Controllers/Concerns/ResolvesAuthUserPayload.php`
- Test: dùng lại `test_me_includes_preferences` (Task 3).

**Interfaces:**
- Consumes: `UserPreferenceService` (Task 2).
- Produces: `userPayload()` thêm khoá `preferences => { ui_shell, ui_open_tabs, ui_active_tab }`.

- [ ] **Step 1: Sửa trait** — thêm import + thân hàm. Thêm `use CMBcoreSeller\Modules\Tenancy\Services\UserPreferenceService;` ở đầu file. Trong `userPayload()`, trước `return [`, thêm:

```php
        /** @var UserPreferenceService $prefsSvc */
        $prefsSvc = app(UserPreferenceService::class);
        $prefs = $prefsSvc->all((int) $user->getKey());
        $preferences = [
            'ui_shell' => $prefs['ui_shell'] ?? 'v1',
            'ui_open_tabs' => $prefs['ui_open_tabs'] ?? [],
            'ui_active_tab' => $prefs['ui_active_tab'] ?? null,
        ];
```

và thêm `'preferences' => $preferences,` vào mảng `return [ ... ]` (sau `'tenants' => $tenants,`).

- [ ] **Step 2: Chạy test xác nhận PASS toàn bộ**

Run: `cd app && php artisan test --filter=UserPreferenceTest`
Expected: 4 PASS.

- [ ] **Step 3: Gate BE**

Run: `cd app && vendor/bin/pint --test && vendor/bin/phpstan analyse`
Expected: không lỗi (nếu pint báo format → chạy `vendor/bin/pint` rồi commit lại).

- [ ] **Step 4: Commit**

```bash
git add app/app/Modules/Tenancy/Http/Controllers/Concerns/ResolvesAuthUserPayload.php
git commit -m "feat(tenancy): /auth/me trả kèm preferences (SPEC-0037)"
```

---

## Task 5: FE — hook `useUserPreferences` + cập nhật type `AuthUser`

**Files:**
- Create: `app/resources/js/lib/preferences.tsx`
- Modify: `app/resources/js/lib/auth.tsx` (thêm `preferences` vào `AuthUser`)

**Interfaces:**
- Consumes: `useAuth()` (auth.tsx) — `user.preferences`.
- Produces:
  - type `OpenTab = { appKey: string; path: string }`, `UiPreferences = { ui_shell: 'v1'|'v2'; ui_open_tabs: OpenTab[]; ui_active_tab: string | null }`.
  - `useUserPreferences(): UiPreferences` — đọc từ `me` (default v1/[]/null).
  - `useUpdatePreferences()` — mutation `PUT /me/preferences`, cập nhật cache `['me']`.

- [ ] **Step 1: Thêm `preferences` vào `AuthUser`** — trong `auth.tsx`, sau dòng `tenants: TenantSummary[];` thêm:

```ts
    /** SPEC 0037 — lựa chọn giao diện + phiên tab (Web Desktop v2). */
    preferences?: {
        ui_shell: 'v1' | 'v2';
        ui_open_tabs: { appKey: string; path: string }[];
        ui_active_tab: string | null;
    };
```

- [ ] **Step 2: Viết `lib/preferences.tsx`**

```tsx
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api, ensureCsrf } from './api';
import { useAuth, type AuthUser } from './auth';

export type OpenTab = { appKey: string; path: string };
export interface UiPreferences {
    ui_shell: 'v1' | 'v2';
    ui_open_tabs: OpenTab[];
    ui_active_tab: string | null;
}

const DEFAULTS: UiPreferences = { ui_shell: 'v1', ui_open_tabs: [], ui_active_tab: null };

/** Đọc preference giao diện từ `me` (đã kèm trong payload auth). */
export function useUserPreferences(): UiPreferences {
    const { data: user } = useAuth();
    return { ...DEFAULTS, ...(user?.preferences ?? {}) };
}

/** Ghi preference (PUT /me/preferences) và cập nhật cache `me` để FE phản ứng ngay. */
export function useUpdatePreferences() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (patch: Partial<UiPreferences>) => {
            await ensureCsrf();
            const { data } = await api.put<{ data: UiPreferences }>('/me/preferences', patch);
            return data.data;
        },
        onSuccess: (prefs) => {
            qc.setQueryData<AuthUser | null>(['me'], (prev) => (prev ? { ...prev, preferences: prefs } : prev));
        },
    });
}
```

- [ ] **Step 3: Verify build/typecheck**

Run: `cd app && npm run typecheck`
Expected: không lỗi type.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/lib/preferences.tsx app/resources/js/lib/auth.tsx
git commit -m "feat(fe): useUserPreferences + AuthUser.preferences (SPEC-0037)"
```

---

## Task 6: FE — trích `appRouteElements()` dùng chung (refactor giữ nguyên v1)

**Files:**
- Create: `app/resources/js/routes/appRoutes.tsx`
- Modify: `app/resources/js/app.tsx` (thay khối route con bằng `{appRouteElements()}`)

**Interfaces:**
- Produces: `export function appRouteElements(): React.ReactNode` — fragment chứa **toàn bộ** `<Route>` page hiện đang nằm trong `<Route element={<RequireAuth><AppLayout/></RequireAuth>}>` (gồm `index`, mọi page, và cụm `settings` lồng), **không** gồm route public và **không** gồm phần tử layout.

- [ ] **Step 1: Tạo `routes/appRoutes.tsx`** — chuyển toàn bộ import page từ `app.tsx` sang đây và export fragment. Nội dung (di chuyển nguyên các import dòng 16–88 của `app.tsx` có liên quan tới page; giữ `React.lazy` cho 2 editor ảnh):

```tsx
import React, { Suspense } from 'react';
import { Navigate, Route } from 'react-router-dom';
import { Spin } from 'antd';

import { DashboardPage } from '@/pages/DashboardPage';
import { OrdersPage } from '@/pages/OrdersPage';
import { OrderDetailPage } from '@/pages/OrderDetailPage';
import { ReturnsPage } from '@/pages/ReturnsPage';
import { ChannelsPage } from '@/pages/ChannelsPage';
import { CopiedProductsPage } from '@/pages/marketplace/CopiedProductsPage';
import { OnChannelPage } from '@/pages/marketplace/OnChannelPage';
import { MarketplaceEditPage } from '@/pages/marketplace/MarketplaceEditPage';
import { ListingDraftEditorPage } from '@/pages/marketplace/ListingDraftEditorPage';
import { PublishablePage } from '@/pages/marketplace/PublishablePage';
import { PromotionsPage } from '@/pages/marketplace/PromotionsPage';
import { PromotionEditPage } from '@/pages/marketplace/PromotionEditPage';
import { SyncLogsPage } from '@/pages/SyncLogsPage';
import { SupportCenterPage } from '@/pages/SupportCenterPage';
import { CustomersPage } from '@/pages/CustomersPage';
import { CustomerDetailPage } from '@/pages/CustomerDetailPage';
import { MessagingPage } from '@/pages/MessagingPage';
import { MessagingTemplatesPage } from '@/pages/MessagingTemplatesPage';
import { MessagingUtilityTemplatesPage } from '@/pages/MessagingUtilityTemplatesPage';
import { MessagingAutoRulesPage } from '@/pages/MessagingAutoRulesPage';
import { MessagingFlowsPage } from '@/pages/MessagingFlowsPage';
import { MessagingFlowEditorPage } from '@/pages/MessagingFlowEditorPage';
import { MessagingAiTrainingPage } from '@/pages/MessagingAiTrainingPage';
import { MessagingSettingsPage } from '@/pages/MessagingSettingsPage';
import { MessagingChannelsPage } from '@/pages/MessagingChannelsPage';
import { MarketingDashboardPage } from '@/pages/MarketingDashboardPage';
import { TikTokAdsDashboardPage } from '@/pages/TikTokAdsDashboardPage';
import { AdWizardPage } from '@/pages/AdWizardPage';
import { AiCampaignPage } from '@/pages/AiCampaignPage';
import { InventoryPage } from '@/pages/InventoryPage';
import { CreateSkuPage } from '@/pages/CreateSkuPage';
import { CreateOrderPage } from '@/pages/CreateOrderPage';
import { CarrierAccountsPage } from '@/pages/CarrierAccountsPage';
import { SettingsLayout } from '@/components/SettingsLayout';
import { SettingsMembersPage } from '@/pages/SettingsMembersPage';
import { SettingsProfilePage } from '@/pages/SettingsProfilePage';
import { SettingsWorkspacePage } from '@/pages/SettingsWorkspacePage';
import { SettingsOrdersPage } from '@/pages/SettingsOrdersPage';
import { SettingsPlanPage } from '@/pages/SettingsPlanPage';
import { SettingsPrintPage } from '@/pages/SettingsPrintPage';
import { SuppliersPage } from '@/pages/SuppliersPage';
import { PurchaseOrdersPage } from '@/pages/PurchaseOrdersPage';
import { DemandPlanningPage } from '@/pages/DemandPlanningPage';
import { ReportsPage } from '@/pages/ReportsPage';
import { OverviewReportPage } from '@/pages/OverviewReportPage';
import { ShopReportPage } from '@/pages/ShopReportPage';
import { SettlementsPage } from '@/pages/SettlementsPage';
import { AccountingDashboardPage } from '@/pages/accounting/AccountingDashboardPage';
import { JournalsPage } from '@/pages/accounting/JournalsPage';
import { ChartOfAccountsPage } from '@/pages/accounting/ChartOfAccountsPage';
import { PeriodsPage } from '@/pages/accounting/PeriodsPage';
import { BalancesPage } from '@/pages/accounting/BalancesPage';
import { ArPage } from '@/pages/accounting/ArPage';
import { ApPage } from '@/pages/accounting/ApPage';
import { CashPage } from '@/pages/accounting/CashPage';
import { AccountingReportsPage } from '@/pages/accounting/ReportsPage';
import { AccountingPostRulesPage } from '@/pages/settings/AccountingPostRulesPage';
import { SettingsShippingLabelsPage } from '@/pages/SettingsShippingLabelsPage';
import { ShippingLabelEditorPage } from '@/pages/ShippingLabelEditorPage';
import { SettingsAppearancePage } from '@/pages/SettingsAppearancePage';
import { ComingSoon } from '@/components/ComingSoon';

const AdvancedImageEditorPage = React.lazy(() => import('@/pages/marketplace/AdvancedImageEditorPage'));
const MarketplaceImageEditorPage = React.lazy(() => import('@/pages/marketplace/MarketplaceImageEditorPage'));

const lazy = (node: React.ReactNode) => <Suspense fallback={<Spin style={{ margin: 48 }} />}>{node}</Suspense>;

/** Toàn bộ route page (không gồm public, không gồm layout). Dùng chung vỏ v1 & v2. */
export function appRouteElements(): React.ReactNode {
    return (
        <>
            <Route index element={<DashboardPage />} />
            <Route path="orders" element={<OrdersPage />} />
            <Route path="orders/new" element={<CreateOrderPage />} />
            <Route path="orders/:id/edit" element={<CreateOrderPage />} />
            <Route path="orders/:id" element={<OrderDetailPage />} />
            <Route path="returns" element={<ReturnsPage />} />
            <Route path="channels" element={<ChannelsPage />} />
            <Route path="listings" element={<Navigate to="/marketplace/products" replace />} />
            <Route path="marketplace" element={<Navigate to="/marketplace/products" replace />} />
            <Route path="marketplace/products" element={<CopiedProductsPage />} />
            <Route path="marketplace/on-channel" element={<OnChannelPage />} />
            <Route path="marketplace/on-channel/:id/edit" element={<MarketplaceEditPage />} />
            <Route path="marketplace/on-channel/:id/images/edit" element={lazy(<MarketplaceImageEditorPage />)} />
            <Route path="marketplace/to-push" element={<PublishablePage />} />
            <Route path="marketplace/promotions" element={<PromotionsPage />} />
            <Route path="marketplace/promotions/:id/edit" element={<PromotionEditPage />} />
            <Route path="marketplace/listings/:id/edit" element={<ListingDraftEditorPage />} />
            <Route path="marketplace/listings/:id/images/edit" element={lazy(<AdvancedImageEditorPage />)} />
            <Route path="customers" element={<CustomersPage />} />
            <Route path="customers/:id" element={<CustomerDetailPage />} />
            <Route path="messaging" element={<MessagingPage />} />
            <Route path="messaging/channels" element={<MessagingChannelsPage />} />
            <Route path="messaging/templates" element={<MessagingTemplatesPage />} />
            <Route path="messaging/utility-templates" element={<MessagingUtilityTemplatesPage />} />
            <Route path="messaging/auto-rules" element={<MessagingAutoRulesPage />} />
            <Route path="messaging/flows" element={<MessagingFlowsPage />} />
            <Route path="messaging/flows/:id/edit" element={<MessagingFlowEditorPage />} />
            <Route path="messaging/knowledge" element={<MessagingAiTrainingPage />} />
            <Route path="marketing" element={<MarketingDashboardPage />} />
            <Route path="marketing/tiktok" element={<TikTokAdsDashboardPage />} />
            <Route path="marketing/ads/new" element={<AdWizardPage />} />
            <Route path="marketing/ads/ai" element={<AiCampaignPage />} />
            <Route path="marketing/ads/:draftId/edit" element={<AdWizardPage />} />
            <Route path="products" element={<Navigate to="/inventory?tab=skus" replace />} />
            <Route path="inventory" element={<InventoryPage />} />
            <Route path="inventory/skus/new" element={<CreateSkuPage />} />
            <Route path="inventory/skus/:id/edit" element={<CreateSkuPage />} />
            <Route path="fulfillment" element={<Navigate to="/orders?tab=pending" replace />} />
            <Route path="procurement" element={<Navigate to="/procurement/suppliers" replace />} />
            <Route path="procurement/suppliers" element={<SuppliersPage />} />
            <Route path="procurement/purchase-orders" element={<PurchaseOrdersPage />} />
            <Route path="procurement/demand-planning" element={<DemandPlanningPage />} />
            <Route path="reports/overview" element={<OverviewReportPage />} />
            <Route path="reports" element={<ReportsPage />} />
            <Route path="shop-report" element={<ShopReportPage />} />
            <Route path="finance" element={<Navigate to="/finance/settlements" replace />} />
            <Route path="finance/settlements" element={<SettlementsPage />} />
            <Route path="accounting" element={<Navigate to="/accounting/dashboard" replace />} />
            <Route path="accounting/dashboard" element={<AccountingDashboardPage />} />
            <Route path="accounting/journals" element={<JournalsPage />} />
            <Route path="accounting/chart-of-accounts" element={<ChartOfAccountsPage />} />
            <Route path="accounting/periods" element={<PeriodsPage />} />
            <Route path="accounting/balances" element={<BalancesPage />} />
            <Route path="accounting/ar" element={<ArPage />} />
            <Route path="accounting/ap" element={<ApPage />} />
            <Route path="accounting/cash" element={<CashPage />} />
            <Route path="accounting/reports" element={<AccountingReportsPage />} />
            <Route path="sync-logs" element={<SyncLogsPage />} />
            <Route path="support" element={<SupportCenterPage />} />
            <Route path="settings" element={<SettingsLayout />}>
                <Route index element={<Navigate to="/settings/profile" replace />} />
                <Route path="profile" element={<SettingsProfilePage />} />
                <Route path="appearance" element={<SettingsAppearancePage />} />
                <Route path="workspace" element={<SettingsWorkspacePage />} />
                <Route path="members" element={<SettingsMembersPage />} />
                <Route path="carriers" element={<CarrierAccountsPage />} />
                <Route path="orders" element={<SettingsOrdersPage />} />
                <Route path="messaging" element={<MessagingSettingsPage />} />
                <Route path="plan" element={<SettingsPlanPage />} />
                <Route path="print" element={<SettingsPrintPage />} />
                <Route path="shipping-labels" element={<SettingsShippingLabelsPage />} />
                <Route path="shipping-labels/new" element={<ShippingLabelEditorPage />} />
                <Route path="shipping-labels/:id" element={<ShippingLabelEditorPage />} />
                <Route path="accounting/post-rules" element={<AccountingPostRulesPage />} />
                <Route path="*" element={<ComingSoon title="Phần này đang được xây dựng" phase="SPEC 0007 / 0011" />} />
            </Route>
        </>
    );
}
```

> Lưu ý: `ComingSoon` và `SettingsAppearancePage` được dùng ở đây. Tách `ComingSoon` ra `components/ComingSoon.tsx` (Step 2) và tạo `SettingsAppearancePage` ở Task 11. Nếu thực thi tuần tự, tạm stub `SettingsAppearancePage` để build xanh, hoàn thiện ở Task 11.

- [ ] **Step 2: Trích `ComingSoon` ra file riêng** — Create `app/resources/js/components/ComingSoon.tsx`:

```tsx
import { Result, Typography } from 'antd';
import { ToolOutlined } from '@ant-design/icons';

/** Placeholder cho trang module chưa xây (Phase 2+). */
export function ComingSoon({ title, phase }: { title: string; phase?: string }) {
    return (
        <Result
            icon={<ToolOutlined style={{ fontSize: 48, color: '#2563EB' }} />}
            title={title}
            subTitle={<Typography.Text type="secondary">Tính năng này sẽ được xây dựng theo roadmap{phase ? ` (${phase})` : ''}.</Typography.Text>}
        />
    );
}
```

- [ ] **Step 3: Tạo stub `SettingsAppearancePage`** (hoàn thiện ở Task 11) — Create `app/resources/js/pages/SettingsAppearancePage.tsx`:

```tsx
export function SettingsAppearancePage() {
    return null;
}
```

- [ ] **Step 4: Sửa `app.tsx`** — xoá các import page đã chuyển sang `appRoutes.tsx` (dòng ~16–88, GIỮ lại import của các trang public: Login/Register/EmailVerified/ForgotPassword/ResetPassword/PublicTracking/DownloadApp/Plans/NotFound, và `RequireAuth`, `AppLayout`). Xoá `ComingSoon` local (dòng 96–105). Thêm `import { appRouteElements } from '@/routes/appRoutes';`. Thay khối route con của `AppLayout` bằng `{appRouteElements()}`:

```tsx
            <Route element={<RequireAuth><AppLayout /></RequireAuth>}>
                {appRouteElements()}
            </Route>
```

(giữ nguyên các route public `/login` … `/download`, `/plans`, `/404`, catch-all).

- [ ] **Step 5: Verify v1 không hồi quy**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: build xanh. Sau đó chạy `composer dev`, mở app, kiểm: sidebar v1 hiển thị đủ, vào `/orders`, `/settings/profile`, `/accounting/dashboard` đều OK.

- [ ] **Step 6: Commit**

```bash
git add app/resources/js/routes/appRoutes.tsx app/resources/js/components/ComingSoon.tsx app/resources/js/pages/SettingsAppearancePage.tsx app/resources/js/app.tsx
git commit -m "refactor(fe): trích appRouteElements + ComingSoon dùng chung (SPEC-0037)"
```

---

## Task 7: FE — `appCatalog` (9 app + sub-menu + appForPath)

**Files:**
- Create: `app/resources/js/lib/desktop/appCatalog.tsx`

**Interfaces:**
- Produces:
  - type `AppDef = { key: string; label: string; icon: React.ReactNode; permission?: string; defaultPath: string; prefixes: string[]; menu: { key: string; label: string; children?: { key: string; label: string }[] }[] }`.
  - `APP_CATALOG: AppDef[]` (9 app, đúng path/nhãn sidebar v1).
  - `appForPath(pathname: string): AppDef | undefined` — khớp app theo `prefixes` (chọn prefix dài nhất).

- [ ] **Step 1: Viết catalog**

```tsx
import {
    ShoppingOutlined, MessageOutlined, ShopOutlined, InboxOutlined,
    FacebookFilled, TikTokOutlined, PieChartOutlined, CalculatorOutlined, SettingOutlined,
} from '@ant-design/icons';
import type { ReactNode } from 'react';

export interface AppMenuItem { key: string; label: string; children?: { key: string; label: string }[] }
export interface AppDef {
    key: string;
    label: string;
    icon: ReactNode;
    /** ability string (useCan); bỏ trống = mọi vai trò thấy. */
    permission?: string;
    defaultPath: string;
    /** path-prefix thuộc app này (để khớp tab/sub-menu); chọn prefix dài nhất. */
    prefixes: string[];
    menu: AppMenuItem[];
}

export const APP_CATALOG: AppDef[] = [
    {
        key: 'sales', label: 'Bán hàng', icon: <ShoppingOutlined />, permission: 'orders.view',
        defaultPath: '/orders', prefixes: ['/orders', '/returns', '/customers'],
        menu: [
            { key: '/orders', label: 'Đơn hàng' },
            { key: '/returns', label: 'Hoàn & Hủy' },
            { key: '/customers', label: 'Khách hàng' },
        ],
    },
    {
        key: 'messaging', label: 'Tin nhắn', icon: <MessageOutlined />, permission: 'messaging.view',
        defaultPath: '/messaging', prefixes: ['/messaging'],
        menu: [
            { key: '/messaging', label: 'Hộp thư' },
            { key: '/messaging/channels', label: 'Kết nối kênh' },
            { key: '/messaging/templates', label: 'Mẫu tin' },
            { key: '/messaging/utility-templates', label: 'Tin tiện ích' },
            { key: '/messaging/auto-rules', label: 'Tự động trả lời' },
            { key: '/messaging/flows', label: 'Kịch bản tự động' },
            { key: '/messaging/knowledge', label: 'AI training' },
        ],
    },
    {
        key: 'listing', label: 'Đăng bán sàn', icon: <ShopOutlined />, permission: 'products.view',
        defaultPath: '/marketplace/products', prefixes: ['/marketplace', '/channels', '/listings'],
        menu: [
            { key: '/marketplace/products', label: 'Sao chép sản phẩm' },
            { key: '/marketplace/to-push', label: 'Chờ đẩy lên sàn' },
            { key: '/marketplace/on-channel', label: 'Đã có trên sàn' },
            { key: '/marketplace/promotions', label: 'Chiến dịch giảm giá' },
            { key: '/channels', label: 'Gian hàng' },
        ],
    },
    {
        key: 'warehouse', label: 'Kho', icon: <InboxOutlined />, permission: 'inventory.view',
        defaultPath: '/inventory', prefixes: ['/inventory', '/procurement', '/products'],
        menu: [
            { key: '/inventory', label: 'Tồn kho' },
            { key: '/products', label: 'Sản phẩm & SKU' },
            { key: '/procurement/demand-planning', label: 'Đề xuất nhập hàng' },
            { key: '/procurement/suppliers', label: 'Nhà cung cấp' },
            { key: '/procurement/purchase-orders', label: 'Đơn mua hàng' },
        ],
    },
    {
        key: 'ads_facebook', label: 'Quảng cáo Facebook', icon: <FacebookFilled />, permission: 'marketing.view',
        defaultPath: '/marketing', prefixes: ['/marketing/ads', '/marketing'],
        menu: [
            { key: '/marketing', label: 'Tổng quan' },
            { key: '/marketing/ads/new', label: 'Tạo quảng cáo' },
            { key: '/marketing/ads/ai', label: 'Quảng cáo bằng AI' },
        ],
    },
    {
        key: 'ads_tiktok', label: 'Quảng cáo TikTok', icon: <TikTokOutlined />, permission: 'marketing.view',
        defaultPath: '/marketing/tiktok', prefixes: ['/marketing/tiktok'],
        menu: [{ key: '/marketing/tiktok', label: 'Tổng quan' }],
    },
    {
        key: 'reports', label: 'Báo cáo', icon: <PieChartOutlined />, permission: 'reports.view',
        defaultPath: '/reports/overview', prefixes: ['/reports', '/shop-report', '/finance'],
        menu: [
            { key: '/reports/overview', label: 'Báo cáo tổng thể' },
            { key: '/reports', label: 'Báo cáo bán hàng' },
            { key: '/shop-report', label: 'Báo cáo sàn' },
            { key: '/finance/settlements', label: 'Đối soát sàn' },
        ],
    },
    {
        key: 'accounting', label: 'Kế toán', icon: <CalculatorOutlined />, permission: 'accounting.view',
        defaultPath: '/accounting/dashboard', prefixes: ['/accounting'],
        menu: [
            { key: '/accounting/dashboard', label: 'Tổng quan kế toán' },
            { key: 'acc-books', label: 'Sổ sách', children: [
                { key: '/accounting/journals', label: 'Sổ nhật ký chung' },
                { key: '/accounting/chart-of-accounts', label: 'Hệ thống tài khoản' },
                { key: '/accounting/balances', label: 'Cân đối phát sinh' },
                { key: '/accounting/periods', label: 'Kỳ kế toán' },
            ] },
            { key: 'acc-money', label: 'Công nợ & Tiền', children: [
                { key: '/accounting/ar', label: 'Công nợ phải thu' },
                { key: '/accounting/ap', label: 'Công nợ phải trả' },
                { key: '/accounting/cash', label: 'Quỹ & Ngân hàng' },
            ] },
            { key: '/accounting/reports', label: 'Báo cáo tài chính & Thuế' },
        ],
    },
    {
        key: 'settings', label: 'Cài đặt hệ thống', icon: <SettingOutlined />,
        defaultPath: '/settings/profile', prefixes: ['/settings', '/sync-logs', '/support'],
        menu: [
            { key: '/settings/profile', label: 'Cài đặt' },
            { key: '/sync-logs', label: 'Nhật ký đồng bộ' },
            { key: '/support', label: 'Trung tâm trợ giúp' },
        ],
    },
];

/** Khớp app theo prefix dài nhất; '/' → undefined (thuộc Desktop home). */
export function appForPath(pathname: string): AppDef | undefined {
    let best: AppDef | undefined;
    let bestLen = -1;
    for (const app of APP_CATALOG) {
        for (const p of app.prefixes) {
            if ((pathname === p || pathname.startsWith(p + '/') || pathname.startsWith(p + '?') || pathname.startsWith(p)) && p.length > bestLen) {
                best = app; bestLen = p.length;
            }
        }
    }
    return best;
}
```

- [ ] **Step 2: Verify typecheck**

Run: `cd app && npm run typecheck`
Expected: không lỗi.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/lib/desktop/appCatalog.tsx
git commit -m "feat(fe): appCatalog 9 app Web Desktop + appForPath (SPEC-0037)"
```

---

## Task 8: FE — Zustand store quản lý tab

**Files:**
- Create: `app/resources/js/lib/desktop/desktopShellStore.ts`

**Interfaces:**
- Consumes: type `OpenTab` (preferences.tsx).
- Produces store `useDesktopShell` với state `{ tabs: OpenTab[]; activeKey: string }` (activeKey `'desktop'` cho màn nền) và actions:
  - `openApp(appKey: string, path: string): void` — nếu app đã mở → focus + cập nhật path; chưa → thêm tab cuối + active.
  - `setActive(key: string): void`.
  - `closeTab(appKey: string): void` — active về tab trái kề / `'desktop'`.
  - `setTabPath(appKey: string, path: string): void` — cập nhật `lastPath` (cho persist).
  - `hydrate(tabs: OpenTab[], active: string | null): void`.

- [ ] **Step 1: Viết store**

```ts
import { create } from 'zustand';
import type { OpenTab } from '@/lib/preferences';

export const DESKTOP_KEY = 'desktop';

interface DesktopShellState {
    tabs: OpenTab[];
    activeKey: string;
    openApp: (appKey: string, path: string) => void;
    setActive: (key: string) => void;
    closeTab: (appKey: string) => void;
    setTabPath: (appKey: string, path: string) => void;
    hydrate: (tabs: OpenTab[], active: string | null) => void;
}

export const useDesktopShell = create<DesktopShellState>((set) => ({
    tabs: [],
    activeKey: DESKTOP_KEY,
    openApp: (appKey, path) => set((s) => {
        const existing = s.tabs.find((t) => t.appKey === appKey);
        if (existing) {
            return { activeKey: appKey, tabs: s.tabs.map((t) => (t.appKey === appKey ? { ...t, path } : t)) };
        }
        return { tabs: [...s.tabs, { appKey, path }], activeKey: appKey };
    }),
    setActive: (key) => set({ activeKey: key }),
    closeTab: (appKey) => set((s) => {
        const idx = s.tabs.findIndex((t) => t.appKey === appKey);
        const tabs = s.tabs.filter((t) => t.appKey !== appKey);
        let activeKey = s.activeKey;
        if (s.activeKey === appKey) {
            const left = idx > 0 ? tabs[idx - 1] : tabs[0];
            activeKey = left ? left.appKey : DESKTOP_KEY;
        }
        return { tabs, activeKey };
    }),
    setTabPath: (appKey, path) => set((s) => ({
        tabs: s.tabs.map((t) => (t.appKey === appKey ? { ...t, path } : t)),
    })),
    hydrate: (tabs, active) => set({ tabs, activeKey: active && tabs.some((t) => t.appKey === active) ? active : DESKTOP_KEY }),
}));
```

- [ ] **Step 2: Verify typecheck**

Run: `cd app && npm run typecheck`
Expected: không lỗi.

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/lib/desktop/desktopShellStore.ts
git commit -m "feat(fe): desktopShellStore quản lý tab (open/focus/close/persist) (SPEC-0037)"
```

---

## Task 9: FE — `AppHeader` dùng chung (trích từ AppLayout)

**Files:**
- Create: `app/resources/js/components/AppHeader.tsx`
- Modify: `app/resources/js/components/AppLayout.tsx` (dùng `<AppHeader/>` thay khối `<Header>`, bỏ phần collapse toggle khỏi AppHeader — giữ toggle ở AppLayout)

**Interfaces:**
- Produces: `<AppHeader left?: React.ReactNode />` — render phần phải header (billing, chrome ext, mobile, NotificationBell, user dropdown) + tenant selector ở giữa-trái; `left` là slot để AppLayout truyền nút collapse, DesktopShell truyền logo.

- [ ] **Step 1: Viết `AppHeader.tsx`** (trích nguyên các phần tử header hiện có của AppLayout, dòng 160–194; `left` thay cho nút collapse):

```tsx
import { Link, useNavigate } from 'react-router-dom';
import { Avatar, Button, Dropdown, Space, Tooltip, Typography } from 'antd';
import {
    ChromeOutlined, LogoutOutlined, MobileOutlined, SettingOutlined, ShopOutlined, UserOutlined,
} from '@ant-design/icons';
import { Select } from 'antd';
import { getCurrentTenantId, setCurrentTenantId, useAuth, useLogout } from '@/lib/auth';
import { NotificationBell } from '@/components/NotificationBell';
import { HeaderBillingActions } from '@/components/HeaderBillingActions';
import { CHROME_EXTENSION_URL } from '@/lib/extension';

export function AppHeader({ left, onOpenSettings }: { left?: React.ReactNode; onOpenSettings?: () => void }) {
    const { data: user } = useAuth();
    const logout = useLogout();
    const navigate = useNavigate();
    const currentTenantId = getCurrentTenantId() ?? user?.tenants[0]?.id ?? null;

    const settingsItem = onOpenSettings
        ? { key: 'settings', icon: <SettingOutlined />, label: 'Cài đặt', onClick: onOpenSettings }
        : { key: 'settings', icon: <SettingOutlined />, label: <Link to="/settings/members">Cài đặt</Link> };

    return (
        <div style={{ background: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '0 16px 0 8px', borderBottom: '1px solid #f0f0f0', height: 56 }}>
            <Space>
                {left}
                <ShopOutlined style={{ color: '#8c8c8c' }} />
                <Select
                    size="middle" variant="borderless" style={{ minWidth: 200, fontWeight: 500 }}
                    value={currentTenantId ?? undefined}
                    options={(user?.tenants ?? []).map((t) => ({ value: t.id, label: `${t.name} · ${t.role}` }))}
                    onChange={(v) => { setCurrentTenantId(v); navigate(0); }}
                />
            </Space>
            <Space size="middle">
                <HeaderBillingActions />
                <Tooltip title="Cài tiện ích Chrome để sao chép sản phẩm">
                    <Button type="text" href={CHROME_EXTENSION_URL} target="_blank" icon={<ChromeOutlined />} />
                </Tooltip>
                <Tooltip title="Tải ứng dụng di động">
                    <Button type="text" href="/download" target="_blank" icon={<MobileOutlined />} />
                </Tooltip>
                <NotificationBell />
                <Dropdown menu={{ items: [
                    { key: 'who', disabled: true, label: <span>{user?.name}<br /><Typography.Text type="secondary" style={{ fontSize: 12 }}>{user?.email}</Typography.Text></span> },
                    { type: 'divider' },
                    settingsItem,
                    { key: 'logout', icon: <LogoutOutlined />, label: 'Đăng xuất', onClick: () => logout.mutate(undefined, { onSuccess: () => navigate('/login') }) },
                ] }}>
                    <Space style={{ cursor: 'pointer' }}>
                        <Avatar size="small" style={{ background: 'linear-gradient(135deg, #2563EB 0%, #1E40AF 100%)' }} icon={<UserOutlined />} />
                        <span style={{ fontWeight: 500 }}>{user?.name}</span>
                    </Space>
                </Dropdown>
            </Space>
        </div>
    );
}
```

> Lưu ý: `Select` cho **chọn shop** giữ nguyên như bản gốc (đây không phải lựa chọn nhỏ kiểu Radio — danh sách shop động; ngoại lệ chấp nhận, đúng như AppLayout v1 hiện tại).

- [ ] **Step 2: Sửa `AppLayout.tsx`** — thay nguyên khối `<Header>...</Header>` (dòng 160–194) bằng:

```tsx
                <AppHeader left={<Button type="text" icon={collapsed ? <MenuUnfoldOutlined /> : <MenuFoldOutlined />} onClick={() => setCollapsed((c) => !c)} />} />
```

Thêm `import { AppHeader } from '@/components/AppHeader';`. Xoá các import giờ chỉ AppHeader dùng (Avatar, Dropdown, Tooltip, Typography, ChromeOutlined, MobileOutlined, LogoutOutlined, UserOutlined, NotificationBell, HeaderBillingActions, CHROME_EXTENSION_URL, getCurrentTenantId/setCurrentTenantId nếu không còn dùng) — chạy lint để biết import thừa. Giữ `Header` khỏi `Layout` destructure nếu không còn dùng thì bỏ.

- [ ] **Step 3: Verify v1 không hồi quy**

Run: `cd app && npm run lint && npm run typecheck && npm run build`
Expected: xanh. Mở app v1, header vẫn đủ: chọn shop, chuông, menu user, đăng xuất chạy đúng.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/components/AppHeader.tsx app/resources/js/components/AppLayout.tsx
git commit -m "refactor(fe): trích AppHeader dùng chung cho v1 & v2 (SPEC-0037)"
```

---

## Task 10: FE — `DesktopHome`, `AppFrame`, `TabStrip`, `DesktopShell`

**Files:**
- Create: `app/resources/js/components/desktop/DesktopHome.tsx`
- Create: `app/resources/js/components/desktop/AppFrame.tsx`
- Create: `app/resources/js/components/desktop/TabStrip.tsx`
- Create: `app/resources/js/components/desktop/DesktopShell.tsx`

**Interfaces:**
- Consumes: `APP_CATALOG`, `appForPath` (Task 7); `useDesktopShell`, `DESKTOP_KEY` (Task 8); `appRouteElements` (Task 6); `AppHeader` (Task 9); `useUserPreferences`, `useUpdatePreferences` (Task 5); `useCan` (`lib/tenant`).
- Produces: `<DesktopShell/>` — vỏ hoàn chỉnh v2.

- [ ] **Step 1: `DesktopHome.tsx`** — lưới icon (lọc theo quyền) + Dashboard nhúng:

```tsx
import { Card, Typography } from 'antd';
import { APP_CATALOG } from '@/lib/desktop/appCatalog';
import { useDesktopShell } from '@/lib/desktop/desktopShellStore';
import { useCan } from '@/lib/tenant';
import { DashboardPage } from '@/pages/DashboardPage';

export function DesktopHome() {
    const openApp = useDesktopShell((s) => s.openApp);
    const can = useCan;
    return (
        <div style={{ padding: 24 }}>
            <Typography.Title level={4} style={{ marginBottom: 16 }}>Ứng dụng</Typography.Title>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(132px, 1fr))', gap: 16, marginBottom: 32 }}>
                {APP_CATALOG.map((app) => {
                    // eslint-disable-next-line react-hooks/rules-of-hooks
                    const allowed = !app.permission || can(app.permission);
                    if (!allowed) return null;
                    return (
                        <Card key={app.key} hoverable styles={{ body: { padding: 20, textAlign: 'center' } }}
                            onClick={() => openApp(app.key, app.defaultPath)}>
                            <div style={{ fontSize: 32, color: '#2563EB', marginBottom: 8 }}>{app.icon}</div>
                            <div style={{ fontWeight: 500 }}>{app.label}</div>
                        </Card>
                    );
                })}
            </div>
            <Typography.Title level={4} style={{ marginBottom: 16 }}>Tổng quan</Typography.Title>
            <DashboardPage />
        </div>
    );
}
```

> Lưu ý ESLint hooks: `useCan` là hook — gọi trong `.map` vi phạm rule. Sửa: tính quyền ngoài map bằng cách gọi `useCan` cho từng app ở top-level không khả thi (số lượng động nhưng cố định 9). Vì `APP_CATALOG` độ dài cố định, gọi hook theo thứ tự ổn định là an toàn về runtime nhưng ESLint vẫn báo. Thay vào: tạo `usePermittedApps()` trả danh sách app được phép, gọi `useCan` cho 9 key cố định, không trong vòng lặp động. Cài đặt ở Step 1b.

- [ ] **Step 1b: `usePermittedApps` trong `appCatalog.tsx`** — thêm cuối file `appCatalog.tsx`:

```tsx
import { useCan } from '@/lib/tenant';

/** Lọc app theo quyền — gọi useCan cho mọi app (số lượng cố định, đúng rule hooks). */
export function usePermittedApps(): AppDef[] {
    // Hooks gọi cố định theo thứ tự khai báo (APP_CATALOG bất biến) — an toàn.
    return APP_CATALOG.filter((app) => {
        // eslint-disable-next-line react-hooks/rules-of-hooks
        return !app.permission || useCan(app.permission);
    });
}
```

Sửa `DesktopHome` dùng `usePermittedApps()` thay vòng lặp `can()`:

```tsx
import { usePermittedApps } from '@/lib/desktop/appCatalog';
// ...
const apps = usePermittedApps();
// render `apps.map(...)` bỏ check allowed.
```

> Nếu lint vẫn cảnh báo rule-of-hooks-in-loop, chấp nhận disable có chú thích (mảng cố định, không đổi giữa render) — đã có `eslint-disable-next-line`.

- [ ] **Step 2: `AppFrame.tsx`** — sub-menu (từ catalog) + nội dung tab; dùng trong router của tab:

```tsx
import { useMemo } from 'react';
import { Link, Routes, useLocation } from 'react-router-dom';
import { Layout, Menu } from 'antd';
import type { MenuProps } from 'antd';
import type { AppDef } from '@/lib/desktop/appCatalog';
import { appRouteElements } from '@/routes/appRoutes';

const { Sider, Content } = Layout;

function toItems(app: AppDef): MenuProps['items'] {
    return app.menu.map((m) => m.children
        ? { key: m.key, label: m.label, children: m.children.map((c) => ({ key: c.key, label: <Link to={c.key}>{c.label}</Link> })) }
        : { key: m.key, label: <Link to={m.key}>{m.label}</Link> });
}

const flatKeys = (app: AppDef): string[] =>
    app.menu.flatMap((m) => (m.children ? m.children.map((c) => c.key) : [m.key]));

export function AppFrame({ app }: { app: AppDef }) {
    const location = useLocation();
    const items = useMemo(() => toItems(app), [app]);
    const selectedKey = useMemo(() => {
        const keys = flatKeys(app);
        return keys.filter((k) => location.pathname === k || location.pathname.startsWith(k + '/'))
            .sort((a, b) => b.length - a.length)[0] ?? keys[0];
    }, [app, location.pathname]);

    return (
        <Layout style={{ height: '100%' }}>
            <Sider theme="light" width={220} style={{ borderRight: '1px solid #f0f0f0', overflowY: 'auto' }}>
                <Menu mode="inline" selectedKeys={[selectedKey]} defaultOpenKeys={['acc-books', 'acc-money']} items={items} style={{ borderInlineEnd: 'none' }} />
            </Sider>
            <Content style={{ padding: 16, overflow: 'auto' }}>
                <Routes>{appRouteElements()}</Routes>
            </Content>
        </Layout>
    );
}
```

- [ ] **Step 3: `TabStrip.tsx`** — Desktop ghim + tab app:

```tsx
import { Tabs } from 'antd';
import { AppstoreOutlined, CloseOutlined } from '@ant-design/icons';
import { APP_CATALOG } from '@/lib/desktop/appCatalog';
import { useDesktopShell, DESKTOP_KEY } from '@/lib/desktop/desktopShellStore';

export function TabStrip() {
    const { tabs, activeKey, setActive, closeTab } = useDesktopShell();
    const items = [
        { key: DESKTOP_KEY, label: <span><AppstoreOutlined /> Desktop</span>, closable: false },
        ...tabs.map((t) => {
            const app = APP_CATALOG.find((a) => a.key === t.appKey);
            return { key: t.appKey, label: <span>{app?.icon} {app?.label ?? t.appKey}</span>, closable: true };
        }),
    ];
    return (
        <Tabs
            type="editable-card" hideAdd size="small"
            activeKey={activeKey}
            onChange={setActive}
            onEdit={(key, action) => { if (action === 'remove' && typeof key === 'string') closeTab(key); }}
            items={items}
            style={{ padding: '4px 8px 0', background: '#fff', borderBottom: '1px solid #f0f0f0' }}
            removeIcon={<CloseOutlined />}
        />
    );
}
```

- [ ] **Step 4: `DesktopShell.tsx`** — vỏ: header + TabStrip + viewport keep-alive (MemoryRouter mỗi tab) + hydrate/persist + URL sync:

```tsx
import { useEffect, useRef } from 'react';
import { MemoryRouter, useLocation } from 'react-router-dom';
import { Layout } from 'antd';
import { AppHeader } from '@/components/AppHeader';
import { TabStrip } from '@/components/desktop/TabStrip';
import { DesktopHome } from '@/components/desktop/DesktopHome';
import { AppFrame } from '@/components/desktop/AppFrame';
import { APP_CATALOG, appForPath } from '@/lib/desktop/appCatalog';
import { useDesktopShell, DESKTOP_KEY } from '@/lib/desktop/desktopShellStore';
import { useUserPreferences, useUpdatePreferences } from '@/lib/preferences';

/** Cầu nối: tab active mirror path nội bộ ra URL trình duyệt + báo path cho store (persist). */
function TabBridge({ appKey, active }: { appKey: string; active: boolean }) {
    const location = useLocation();
    const setTabPath = useDesktopShell((s) => s.setTabPath);
    useEffect(() => {
        const full = location.pathname + location.search;
        setTabPath(appKey, full);
        if (active) window.history.replaceState(null, '', full);
    }, [appKey, active, location.pathname, location.search, setTabPath]);
    return null;
}

export function DesktopShell() {
    const prefs = useUserPreferences();
    const update = useUpdatePreferences();
    const { tabs, activeKey, hydrate, openApp, setActive } = useDesktopShell();
    const hydrated = useRef(false);

    // Hydrate một lần từ preference; nếu rỗng, seed từ URL hiện tại.
    useEffect(() => {
        if (hydrated.current) return;
        hydrated.current = true;
        if (prefs.ui_open_tabs.length) {
            hydrate(prefs.ui_open_tabs, prefs.ui_active_tab);
        } else {
            const app = appForPath(window.location.pathname);
            if (app) openApp(app.key, window.location.pathname + window.location.search);
            else setActive(DESKTOP_KEY);
        }
    }, [prefs.ui_open_tabs, prefs.ui_active_tab, hydrate, openApp, setActive]);

    // Persist (debounce) khi tabs/active đổi.
    useEffect(() => {
        if (!hydrated.current) return;
        const id = setTimeout(() => {
            update.mutate({ ui_open_tabs: tabs, ui_active_tab: activeKey === DESKTOP_KEY ? null : activeKey });
        }, 800);
        return () => clearTimeout(id);
    }, [tabs, activeKey]); // eslint-disable-line react-hooks/exhaustive-deps

    return (
        <Layout style={{ minHeight: '100vh' }}>
            <AppHeader
                left={<img src="/images/logocmb.png" alt="CMB Core" style={{ width: 28, height: 28, objectFit: 'contain' }} />}
                onOpenSettings={() => openApp('settings', '/settings/profile')}
            />
            <TabStrip />
            <div style={{ position: 'relative', flex: 1, minHeight: 0 }}>
                {/* Desktop home */}
                <div style={{ position: 'absolute', inset: 0, overflow: 'auto', display: activeKey === DESKTOP_KEY ? 'block' : 'none' }}>
                    <DesktopHome />
                </div>
                {/* Mỗi tab = MemoryRouter độc lập, keep-alive bằng display. */}
                {tabs.map((t) => {
                    const app = APP_CATALOG.find((a) => a.key === t.appKey);
                    if (!app) return null;
                    return (
                        <div key={t.appKey} style={{ position: 'absolute', inset: 0, display: activeKey === t.appKey ? 'block' : 'none' }}>
                            <MemoryRouter initialEntries={[t.path]}>
                                <TabBridge appKey={t.appKey} active={activeKey === t.appKey} />
                                <AppFrame app={app} />
                            </MemoryRouter>
                        </div>
                    );
                })}
            </div>
        </Layout>
    );
}
```

- [ ] **Step 5: Verify typecheck/build**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: xanh (sửa import thừa nếu lint báo).

- [ ] **Step 6: Commit**

```bash
git add app/resources/js/components/desktop/ app/resources/js/lib/desktop/appCatalog.tsx
git commit -m "feat(fe): DesktopShell + TabStrip + DesktopHome + AppFrame (keep-alive tab) (SPEC-0037)"
```

---

## Task 11: FE — trang Cài đặt "Giao diện" (chọn v1/v2)

**Files:**
- Modify: `app/resources/js/pages/SettingsAppearancePage.tsx` (thay stub Task 6)
- Modify: `app/resources/js/components/SettingsLayout.tsx` (thêm mục menu "Giao diện")

**Interfaces:**
- Consumes: `useUserPreferences`, `useUpdatePreferences` (Task 5).
- Produces: trang `SettingsAppearancePage` với `Radio.Group` v1/v2; lưu xong reload.

- [ ] **Step 1: Viết `SettingsAppearancePage.tsx`**

```tsx
import { useState } from 'react';
import { Card, Radio, Button, Space, Typography, App } from 'antd';
import { useUserPreferences, useUpdatePreferences } from '@/lib/preferences';

export function SettingsAppearancePage() {
    const prefs = useUserPreferences();
    const update = useUpdatePreferences();
    const { message } = App.useApp();
    const [shell, setShell] = useState<'v1' | 'v2'>(prefs.ui_shell);

    const save = () => update.mutate({ ui_shell: shell }, {
        onSuccess: () => { message.success('Đã đổi giao diện, đang tải lại…'); setTimeout(() => window.location.assign('/'), 600); },
        onError: () => message.error('Không lưu được lựa chọn giao diện.'),
    });

    return (
        <Card title="Giao diện" style={{ maxWidth: 560 }}>
            <Typography.Paragraph type="secondary">
                Chọn kiểu giao diện. "Web Desktop" sắp xếp các phần theo ứng dụng dạng tab giống trình duyệt.
            </Typography.Paragraph>
            <Radio.Group value={shell} onChange={(e) => setShell(e.target.value)}>
                <Space direction="vertical">
                    <Radio value="v1">Cổ điển — thanh điều hướng bên trái (mặc định)</Radio>
                    <Radio value="v2">Web Desktop — ứng dụng theo tab</Radio>
                </Space>
            </Radio.Group>
            <div style={{ marginTop: 20 }}>
                <Button type="primary" loading={update.isPending} disabled={shell === prefs.ui_shell} onClick={save}>Lưu</Button>
            </div>
        </Card>
    );
}
```

- [ ] **Step 2: Thêm mục menu trong `SettingsLayout.tsx`** — trong nhóm *Tài khoản*, thêm item sau "Hồ sơ cá nhân":

```tsx
            { key: '/settings/appearance', label: <Link to="/settings/appearance">Giao diện</Link> },
```

(khớp cấu trúc item hiện có trong file; nếu file dùng mảng `items` cho `<Menu>`, chèn đúng nhóm Tài khoản.)

- [ ] **Step 3: Verify**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: xanh.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/pages/SettingsAppearancePage.tsx app/resources/js/components/SettingsLayout.tsx
git commit -m "feat(fe): trang Cài đặt > Giao diện chọn v1/v2 (SPEC-0037)"
```

---

## Task 12: FE — branch shell theo preference trong `app.tsx`

**Files:**
- Modify: `app/resources/js/app.tsx`

**Interfaces:**
- Consumes: `useUserPreferences` (Task 5), `DesktopShell` (Task 10), `AppLayout`, `appRouteElements` (Task 6).
- Produces: khi đăng nhập & `ui_shell==='v2'` → render `DesktopShell`; ngược lại giữ `AppLayout` + routes v1.

- [ ] **Step 1: Thêm component branch trong `app.tsx`** — thêm import:

```tsx
import { DesktopShell } from '@/components/desktop/DesktopShell';
import { useUserPreferences } from '@/lib/preferences';
import { useAuth } from '@/lib/auth';
import { Spin } from 'antd';
```

Thêm component:

```tsx
/** Chọn vỏ theo preference. Chờ `me` resolve để tránh nháy v1→v2. */
function AuthenticatedShell() {
    const { isLoading } = useAuth();
    const prefs = useUserPreferences();
    if (isLoading) return <Spin style={{ display: 'block', margin: '20vh auto' }} />;
    return prefs.ui_shell === 'v2' ? <DesktopShell /> : null; // null ⇒ dùng nhánh route v1 bên dưới
}
```

> Vì v1 dùng route lồng (`AppLayout` + `appRouteElements`) còn v2 là một component bao trùm, dùng cách branch ở cấp `<Routes>`: nếu v2, route bao trùm `path="/*"` render `DesktopShell`; nếu v1, giữ route lồng. Cài đặt ở Step 2.

- [ ] **Step 2: Sửa `Root()`** — bọc quyết định shell. Thay khối authenticated:

```tsx
function Root() {
    const { isLoading } = useAuth();
    const prefs = useUserPreferences();
    const shell = prefs.ui_shell;
    return (
        <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route path="/register" element={<RegisterPage />} />
            <Route path="/email-verified" element={<EmailVerifiedPage />} />
            <Route path="/forgot-password" element={<ForgotPasswordPage />} />
            <Route path="/password-reset" element={<ResetPasswordPage />} />
            <Route path="/tracking" element={<PublicTrackingPage />} />
            <Route path="/download" element={<DownloadAppPage />} />
            <Route path="/plans" element={<RequireAuth><PlansPage /></RequireAuth>} />
            {shell === 'v2' && !isLoading ? (
                <Route path="/*" element={<RequireAuth><DesktopShell /></RequireAuth>} />
            ) : (
                <Route element={<RequireAuth><AppLayout /></RequireAuth>}>
                    {appRouteElements()}
                </Route>
            )}
            <Route path="404" element={<NotFoundPage />} />
            <Route path="*" element={<Navigate to="/404" replace />} />
        </Routes>
    );
}
```

> `useAuth`/`useUserPreferences` đọc cache `['me']`; `RequireAuth` đã chặn lúc chưa đăng nhập. Khi chưa load xong `me`, `shell` mặc định `v1` (an toàn — chỉ là khung, RequireAuth giữ spinner/redirect). Sau khi `me` về với `v2`, React re-render đổi sang `DesktopShell`.

- [ ] **Step 3: Verify cả 2 shell**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: xanh.

Kiểm tay (`composer dev`):
- Tài khoản mặc định (`owner@demo.local`): thấy v1 như cũ.
- Vào Cài đặt > Giao diện → chọn Web Desktop → Lưu → reload → thấy Desktop (lưới 9 icon theo quyền + Dashboard).
- Bấm "Bán hàng" → mở tab, sub-menu Đơn/Hoàn&Hủy/Khách hàng, nội dung `/orders` đúng.
- Bấm lại "Bán hàng" trên Desktop → KHÔNG mở tab thứ 2 (focus tab cũ).
- Mở thêm "Kho", nhập dở 1 ô lọc ở Bán hàng, chuyển sang Kho rồi quay lại → ô lọc còn nguyên (keep-alive).
- Đóng tab Bán hàng → active nhảy về tab/Desktop kề; Desktop không có nút đóng.
- F5 (reload) → các tab mở được khôi phục, tab active đúng.
- URL bar đổi theo tab active (vd `/inventory`).
- Cài đặt > Giao diện → chọn Cổ điển → Lưu → reload → về v1, không hồi quy.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/app.tsx
git commit -m "feat(fe): branch shell v1/AppLayout vs v2/DesktopShell theo preference (SPEC-0037)"
```

---

## Task 13: Docs + gate cuối + đánh dấu spec Implemented

**Files:**
- Modify: `docs/05-api/endpoints.md` (ghi 2 endpoint mới)
- Modify: `docs/specs/0037-web-desktop-shell-v2.md` (Trạng thái → Implemented)

- [ ] **Step 1: Ghi endpoint** — trong `docs/05-api/endpoints.md`, thêm vào nhóm auth:

```markdown
- `GET /api/v1/me/preferences` (sanctum) — đọc preference giao diện `{ ui_shell, ui_open_tabs, ui_active_tab }`. SPEC-0037.
- `PUT /api/v1/me/preferences` (sanctum) — ghi (merge) preference giao diện. `ui_shell` ∈ {v1,v2}. SPEC-0037.
```

- [ ] **Step 2: Cập nhật trạng thái spec** — đổi dòng `- **Trạng thái:** Draft (2026-06-24)` thành `- **Trạng thái:** Implemented (2026-06-24)`.

- [ ] **Step 3: Gate chất lượng đầy đủ (mirror CI)**

Run:
```bash
cd app && vendor/bin/pint --test && vendor/bin/phpstan analyse && php artisan test && npm run lint && npm run typecheck && npm run build
```
Expected: tất cả xanh. (Lưu ý baseline: 7 test GHN/fulfillment đỏ sẵn trên `main` — không liên quan task này; xác nhận không phát sinh đỏ MỚI.)

- [ ] **Step 4: Commit**

```bash
git add docs/05-api/endpoints.md docs/specs/0037-web-desktop-shell-v2.md
git commit -m "docs: endpoints /me/preferences + đánh dấu SPEC-0037 Implemented"
```

---

## Self-Review

**1. Spec coverage:**
- §2 vỏ DesktopShell + header dùng chung → Task 9, 10, 12. ✓
- §2 tab strip (Desktop ghim) → Task 10 (TabStrip). ✓
- §2 màn Desktop lưới 9 icon + Dashboard → Task 10 (DesktopHome). ✓
- §2 bộ quản lý tab keep-alive + focus-không-nhân-đôi → Task 8 (store) + Task 10 (MemoryRouter display). ✓
- §2 appCatalog 9 app + sub-menu → Task 7. ✓
- §2 mục Cài đặt Giao diện (Radio) → Task 11. ✓
- §2 BE bảng user_preferences (no tenant_id) → Task 1. ✓
- §2 service + endpoint GET/PUT /me/preferences → Task 2, 3. ✓
- §2 me trả preferences → Task 4. ✓
- §2 useUserPreferences debounce persist → Task 5 + Task 10 (debounce). ✓
- §3 URL nguồn thật + hydrate/seed + keep-alive + quyền ẩn icon + đổi shell reload → Task 7/10/11/12. ✓
- §4 files BE/FE → phủ trong File Structure + tasks. ✓
- §5 test BE PHPUnit + kiểm tay FE → Task 2/3 + Task 12 Step 3. ✓

**2. Placeholder scan:** Không có "TBD/TODO". Stub `SettingsAppearancePage` ở Task 6 là tạm có chủ đích, hoàn thiện ở Task 11 (đã nêu rõ). `endpoints.md` Step 1 — nếu nhóm auth khác cấu trúc, chèn đúng mục tương ứng.

**3. Type consistency:**
- `OpenTab = {appKey,path}` dùng nhất quán: preferences.tsx (Task 5), store (Task 8), DesktopShell persist (Task 10), BE request `ui_open_tabs.*.{appKey,path}` (Task 3). ✓
- `ui_shell`/`ui_open_tabs`/`ui_active_tab` đồng nhất giữa BE shape (Task 3/4) và FE `UiPreferences` (Task 5). ✓
- `useDesktopShell` actions (`openApp/setActive/closeTab/setTabPath/hydrate`) khai ở Task 8, dùng đúng tên ở Task 10. ✓
- `appForPath`/`usePermittedApps`/`APP_CATALOG`/`AppDef` khai Task 7, dùng Task 10. ✓
- `appRouteElements` khai Task 6, dùng Task 6 (v1) + Task 10 (AppFrame). ✓

**Gotcha đã lường:** ESLint `rules-of-hooks` khi gọi `useCan` theo app — xử lý bằng `usePermittedApps` + disable có chú thích (mảng cố định). Nếu lint chặn cứng (CI), thay bằng đọc toàn bộ permission một lần qua `useTenant()`/`useCan` cho 9 key tường minh (không loop).
