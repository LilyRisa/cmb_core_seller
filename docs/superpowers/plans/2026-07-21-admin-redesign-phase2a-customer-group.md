# Admin Redesign — Phase 2a: Customer Group Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Retrofit the "Tenants / Users / Vouchers / Plans / Invoices" page cluster
(`docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md` §7 "Phase 2a") onto the
Phase 0 interaction primitives: risk-tiered confirmation (`useReasonConfirm`) for high-impact
actions, the Drawer-for-multi-field / Modal-for-small-create convention (§5.2), `Radio.Group` for
status filters (§5.5), and Vietnamese `Empty` states (§5.5).

**Architecture:** This phase assumes Phase 0
(`docs/superpowers/plans/2026-07-21-admin-redesign-phase0-foundation.md`) has already landed —
`app/resources/js/admin/components/ReasonConfirmModal.tsx` exists and exports `useReasonConfirm()`
with the exact signature reproduced in "Interfaces: Consumes" below. Two of the six audited pages
(`AdminTenantDetailPage.tsx`, `AdminUserFormDrawer.tsx`/`TenantUserDrawer.tsx`) need a small,
contained **backend** change first: `AdminUserController::suspend/reactivate` and
`AdminAdminUserController::suspend/reactivate` currently accept no `reason` at all, so promoting
their frontend confirms to the high-impact tier would otherwise collect a reason the backend
silently discards. Task 1 closes that gap the same way `AdminTenantService::suspend/changePlan`
already does it (validate `reason` ≥10 chars, write it into `AuditLog::record()`'s `changes`).
Tenant-level suspend/change-plan/delete-channel/AI-credit-adjust already have full `reason` support
server-side — those tasks are frontend-only.

**Tech Stack:** React 18, Ant Design 5 (`Form` declarative `rules`, `Drawer`, `Radio.Group`,
`Empty`), TypeScript, TanStack Query — Laravel 11 (`Request::validate`, `AuditLog::record`) for the
one backend task. No new dependencies.

## Global Constraints

- Admin-only: do not touch `resources/js/app.tsx` or tenant-facing components.
- No visual/theme changes — Ant Design defaults, existing navy/red admin theme untouched.
- User-facing strings are Vietnamese; code/identifiers are English (per `CLAUDE.md`).
- Icons from `@ant-design/icons` only, never emoji (memory `ui-use-font-icons-not-emoji`).
- Money fields stay integer VND; this phase does not add or change money computation.
- **No JS test runner exists in this repo** (`package.json` has no vitest/jest — see
  [[test-verify-baseline]]). Every frontend task's "test" step is
  `npm run typecheck && npm run lint && npm run build` (run from `app/`) plus a manual
  browser-verification script with exact numbered steps.
- Backend task (Task 1) follows normal TDD: PHPUnit feature test first, `vendor/bin/pint --test`,
  `vendor/bin/phpstan analyse`.
- Run all `npm run *` / `composer` / `php artisan` / `vendor/bin/*` commands from `app/` (per
  `CLAUDE.md`).
- `useReasonConfirm`'s API is frozen by Phase 0 — do not modify
  `ReasonConfirmModal.tsx` or its `ReasonConfirmOptions` interface in this phase. If a call site
  seems to need a capability it doesn't have (e.g. pre-filled reason), redesign the call site
  instead (see Task 3's `AiCreditTab` note).

---

### Task 1: Backend — require + audit-log `reason` on user-account suspend/reactivate

**Files:**
- Modify: `app/app/Modules/Admin/Http/Controllers/AdminAdminUserController.php`
- Modify: `app/app/Modules/Admin/Http/Controllers/AdminUserController.php`
- Modify: `app/tests/Feature/Admin/AdminUserCrudTest.php`
- Modify: `app/tests/Feature/Admin/TenantUserCrudTest.php`
- Modify: `docs/05-api/endpoints.md` (document the now-required `reason` param, per `CLAUDE.md`'s
  "new endpoints must be added to `docs/05-api/endpoints.md`" rule — this isn't a new endpoint but
  the existing doc rows for these 4 endpoints go stale if left as-is)

**Interfaces:**
- Consumes: `CMBcoreSeller\Modules\Tenancy\Models\AuditLog::record(string $action, ?Model
  $auditable, ?array $changes)` (unchanged signature, already used by both controllers).
- Produces (for Task 2 to consume): `POST /api/v1/admin/admin-users/{id}/suspend`,
  `.../reactivate`, `POST /api/v1/admin/users/{id}/suspend`, `.../reactivate` now all require body
  `{ reason: string, min 10, max 500 }`; on success the `reason` is persisted in the corresponding
  `audit_logs.changes` JSON as `{"reason": "..."}`. Missing/short `reason` → `422` (standard Laravel
  validation error shape, already handled by the shared `errorMessage()` FE helper).

No route changes — `Route::post('admin-users/{id}/suspend', ...)` etc. (routes.php:139-157) already
route to these controller methods; adding a `Request $request` parameter to a controller action does
not require any route definition change (Laravel resolves it by type-hint).

- [ ] **Step 1: Write the failing test assertions**

In `app/tests/Feature/Admin/AdminUserCrudTest.php`, make these changes:

Change (existing test, now needs a `reason` to reach the business-rule check it's testing):
```php
    public function test_suspend_works_for_other_admin(): void
    {
        $this->actingAdmin();
        AdminUser::factory()->create();
        $target = AdminUser::factory()->create();
        $this->postJson("/api/v1/admin/admin-users/{$target->id}/suspend")
            ->assertOk()->assertJsonPath('data.is_active', false);
    }
```
to:
```php
    public function test_suspend_works_for_other_admin(): void
    {
        $this->actingAdmin();
        AdminUser::factory()->create();
        $target = AdminUser::factory()->create();
        $this->postJson("/api/v1/admin/admin-users/{$target->id}/suspend", ['reason' => 'Vi phạm điều khoản sử dụng dịch vụ.'])
            ->assertOk()->assertJsonPath('data.is_active', false);
    }
```

Change:
```php
        $me = $this->actingAdmin();
        $target = AdminUser::factory()->create();
        // Deactivate $me in DB while session stays.
        $me->forceFill(['is_active' => false])->save();
        // Now only $target is active. Acting as $me (session), suspend $target → LAST_ACTIVE_ADMIN.
        $this->postJson("/api/v1/admin/admin-users/{$target->id}/suspend")
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'LAST_ACTIVE_ADMIN');
```
to:
```php
        $me = $this->actingAdmin();
        $target = AdminUser::factory()->create();
        // Deactivate $me in DB while session stays.
        $me->forceFill(['is_active' => false])->save();
        // Now only $target is active. Acting as $me (session), suspend $target → LAST_ACTIVE_ADMIN.
        // Reason validation runs before the LAST_ACTIVE_ADMIN business check, so a valid reason
        // must be sent for this test to actually exercise that check (not just fail on 422).
        $this->postJson("/api/v1/admin/admin-users/{$target->id}/suspend", ['reason' => 'Test lý do đủ dài.'])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'LAST_ACTIVE_ADMIN');
```

Change:
```php
    public function test_reactivate(): void
    {
        $this->actingAdmin();
        $other = AdminUser::factory()->inactive()->create();
        $this->postJson("/api/v1/admin/admin-users/{$other->id}/reactivate")->assertOk();
        $this->assertTrue($other->fresh()->is_active);
    }
}
```
to:
```php
    public function test_reactivate(): void
    {
        $this->actingAdmin();
        $other = AdminUser::factory()->inactive()->create();
        $this->postJson("/api/v1/admin/admin-users/{$other->id}/reactivate", ['reason' => 'Khách yêu cầu mở lại tài khoản admin.'])
            ->assertOk();
        $this->assertTrue($other->fresh()->is_active);
    }

    public function test_suspend_requires_reason(): void
    {
        $this->actingAdmin();
        $target = AdminUser::factory()->create();
        $this->postJson("/api/v1/admin/admin-users/{$target->id}/suspend", ['reason' => 'ngắn'])
            ->assertStatus(422);
        $this->postJson("/api/v1/admin/admin-users/{$target->id}/suspend")
            ->assertStatus(422);
    }

    public function test_reactivate_requires_reason(): void
    {
        $this->actingAdmin();
        $other = AdminUser::factory()->inactive()->create();
        $this->postJson("/api/v1/admin/admin-users/{$other->id}/reactivate")
            ->assertStatus(422);
    }

    public function test_suspend_writes_reason_to_audit_log(): void
    {
        $this->actingAdmin();
        $target = AdminUser::factory()->create();
        $this->postJson("/api/v1/admin/admin-users/{$target->id}/suspend", ['reason' => 'Vi phạm điều khoản sử dụng dịch vụ.'])
            ->assertOk();

        $log = \CMBcoreSeller\Modules\Tenancy\Models\AuditLog::query()
            ->where('action', 'admin.admin_user.suspend')
            ->where('auditable_id', $target->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($log);
        $this->assertSame('Vi phạm điều khoản sử dụng dịch vụ.', $log->changes['reason'] ?? null);
    }
}
```

In `app/tests/Feature/Admin/TenantUserCrudTest.php`, make these changes:

Change:
```php
    public function test_suspend_user_sets_suspended_at(): void
    {
        $this->bootstrap();
        $u = User::factory()->create();
        $this->postJson("/api/v1/admin/users/{$u->id}/suspend")->assertOk();
        $this->assertNotNull($u->fresh()->suspended_at);
    }

    public function test_reactivate_clears_suspended_at(): void
    {
        $this->bootstrap();
        $u = User::factory()->create(['suspended_at' => now()]);
        $this->postJson("/api/v1/admin/users/{$u->id}/reactivate")->assertOk();
        $this->assertNull($u->fresh()->suspended_at);
    }
```
to:
```php
    public function test_suspend_user_sets_suspended_at(): void
    {
        $this->bootstrap();
        $u = User::factory()->create();
        $this->postJson("/api/v1/admin/users/{$u->id}/suspend", ['reason' => 'Nghi ngờ gian lận đơn hàng.'])
            ->assertOk();
        $this->assertNotNull($u->fresh()->suspended_at);
    }

    public function test_reactivate_clears_suspended_at(): void
    {
        $this->bootstrap();
        $u = User::factory()->create(['suspended_at' => now()]);
        $this->postJson("/api/v1/admin/users/{$u->id}/reactivate", ['reason' => 'Đã xác minh lại danh tính khách hàng.'])
            ->assertOk();
        $this->assertNull($u->fresh()->suspended_at);
    }

    public function test_suspend_requires_reason(): void
    {
        $this->bootstrap();
        $u = User::factory()->create();
        $this->postJson("/api/v1/admin/users/{$u->id}/suspend", ['reason' => 'ngắn'])
            ->assertStatus(422);
        $this->postJson("/api/v1/admin/users/{$u->id}/suspend")
            ->assertStatus(422);
    }

    public function test_reactivate_requires_reason(): void
    {
        $this->bootstrap();
        $u = User::factory()->create(['suspended_at' => now()]);
        $this->postJson("/api/v1/admin/users/{$u->id}/reactivate")
            ->assertStatus(422);
    }
```

Change:
```php
    public function test_suspend_writes_audit_log(): void
    {
        $this->bootstrap();
        $u = User::factory()->create();
        $this->postJson("/api/v1/admin/users/{$u->id}/suspend")->assertOk();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.user.suspend',
            'auditable_id' => $u->id,
        ]);
    }
}
```
to:
```php
    public function test_suspend_writes_audit_log(): void
    {
        $this->bootstrap();
        $u = User::factory()->create();
        $this->postJson("/api/v1/admin/users/{$u->id}/suspend", ['reason' => 'Nghi ngờ gian lận đơn hàng.'])
            ->assertOk();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.user.suspend',
            'auditable_id' => $u->id,
        ]);

        $log = \CMBcoreSeller\Modules\Tenancy\Models\AuditLog::query()
            ->where('action', 'admin.user.suspend')
            ->where('auditable_id', $u->id)
            ->latest('id')
            ->first();
        $this->assertSame('Nghi ngờ gian lận đơn hàng.', $log->changes['reason'] ?? null);
    }
}
```

- [ ] **Step 2: Run the tests and confirm the new/changed ones fail**

```bash
php artisan test tests/Feature/Admin/AdminUserCrudTest.php tests/Feature/Admin/TenantUserCrudTest.php
```
Expected: `test_suspend_requires_reason`, `test_reactivate_requires_reason`,
`test_suspend_writes_reason_to_audit_log` (AdminUserCrudTest) and the equivalents in
TenantUserCrudTest FAIL (endpoints currently accept no `reason` at all, so a 422-expecting test gets
200, and the audit-log `reason` key doesn't exist). The other changed tests should still PASS since
they only added an extra unused body field, harmless until Step 3/4 land — confirm this is the case,
not a mass failure from a typo.

- [ ] **Step 3: Add `reason` validation + audit logging to `AdminAdminUserController`**

In `app/app/Modules/Admin/Http/Controllers/AdminAdminUserController.php`, change:
```php
    public function suspend(int $id): JsonResponse
    {
        if ($conflict = $this->refuseSelf($id)) {
            return $conflict;
        }
        $admin = AdminUser::query()->findOrFail($id);

        if ($admin->is_active && AdminUser::query()->where('is_active', true)->count() <= 1) {
            return $this->conflict('LAST_ACTIVE_ADMIN', 'Không thể vô hiệu hoá admin đang hoạt động cuối cùng.');
        }

        $admin->forceFill(['is_active' => false])->save();
        AuditLog::record('admin.admin_user.suspend', $admin);

        return response()->json(['data' => $this->present($admin)]);
    }

    public function reactivate(int $id): JsonResponse
    {
        $admin = AdminUser::query()->findOrFail($id);
        $admin->forceFill(['is_active' => true])->save();
        AuditLog::record('admin.admin_user.reactivate', $admin);

        return response()->json(['data' => $this->present($admin)]);
    }
```
to:
```php
    public function suspend(Request $request, int $id): JsonResponse
    {
        if ($conflict = $this->refuseSelf($id)) {
            return $conflict;
        }
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']]);
        $admin = AdminUser::query()->findOrFail($id);

        if ($admin->is_active && AdminUser::query()->where('is_active', true)->count() <= 1) {
            return $this->conflict('LAST_ACTIVE_ADMIN', 'Không thể vô hiệu hoá admin đang hoạt động cuối cùng.');
        }

        $admin->forceFill(['is_active' => false])->save();
        AuditLog::record('admin.admin_user.suspend', $admin, ['reason' => $data['reason']]);

        return response()->json(['data' => $this->present($admin)]);
    }

    public function reactivate(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']]);
        $admin = AdminUser::query()->findOrFail($id);
        $admin->forceFill(['is_active' => true])->save();
        AuditLog::record('admin.admin_user.reactivate', $admin, ['reason' => $data['reason']]);

        return response()->json(['data' => $this->present($admin)]);
    }
```
`Request` is already imported at the top of this file (`use Illuminate\Http\Request;`) — no new
import needed.

- [ ] **Step 4: Add `reason` validation + audit logging to `AdminUserController`**

In `app/app/Modules/Admin/Http/Controllers/AdminUserController.php`, change:
```php
    /** POST /api/v1/admin/users/{id}/suspend */
    public function suspend(int $id): JsonResponse
    {
        $u = User::query()->findOrFail($id);
        if ($u->suspended_at === null) {
            $u->forceFill(['suspended_at' => now()])->save();
        }
        AuditLog::record('admin.user.suspend', $u);

        return response()->json(['data' => [
            'id' => $u->id,
            'suspended_at' => $u->suspended_at?->toIso8601String(),
        ]]);
    }

    /** POST /api/v1/admin/users/{id}/reactivate */
    public function reactivate(int $id): JsonResponse
    {
        $u = User::query()->findOrFail($id);
        if ($u->suspended_at !== null) {
            $u->forceFill(['suspended_at' => null])->save();
        }
        AuditLog::record('admin.user.reactivate', $u);

        return response()->json(['data' => ['id' => $u->id, 'suspended_at' => null]]);
    }
```
to:
```php
    /** POST /api/v1/admin/users/{id}/suspend */
    public function suspend(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']]);
        $u = User::query()->findOrFail($id);
        if ($u->suspended_at === null) {
            $u->forceFill(['suspended_at' => now()])->save();
        }
        AuditLog::record('admin.user.suspend', $u, ['reason' => $data['reason']]);

        return response()->json(['data' => [
            'id' => $u->id,
            'suspended_at' => $u->suspended_at?->toIso8601String(),
        ]]);
    }

    /** POST /api/v1/admin/users/{id}/reactivate */
    public function reactivate(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']]);
        $u = User::query()->findOrFail($id);
        if ($u->suspended_at !== null) {
            $u->forceFill(['suspended_at' => null])->save();
        }
        AuditLog::record('admin.user.reactivate', $u, ['reason' => $data['reason']]);

        return response()->json(['data' => ['id' => $u->id, 'suspended_at' => null]]);
    }
```
`Request` is already imported at the top of this file — no new import needed.

- [ ] **Step 5: Run the tests and confirm they pass**

```bash
php artisan test tests/Feature/Admin/AdminUserCrudTest.php tests/Feature/Admin/TenantUserCrudTest.php
```
Expected: all PASS.

- [ ] **Step 6: Update the API docs**

In `docs/05-api/endpoints.md`, change line 344-345 (Admin Users management table):
```
| POST | `/api/v1/admin/admin-users/{id}/suspend` | web + `auth:admin_web` | Vô hiệu hoá (is_active=false). 409 `CANNOT_SELF_MUTATE` / `LAST_ACTIVE_ADMIN`. Audit `admin.admin_user.suspend`. |
| POST | `/api/v1/admin/admin-users/{id}/reactivate` | web + `auth:admin_web` | Kích hoạt lại. Audit `admin.admin_user.reactivate`. |
```
to:
```
| POST | `/api/v1/admin/admin-users/{id}/suspend` | web + `auth:admin_web` | `{ reason: string ≥10 }`. Vô hiệu hoá (is_active=false). 409 `CANNOT_SELF_MUTATE` / `LAST_ACTIVE_ADMIN`. Audit `admin.admin_user.suspend` (changes.reason). (2026-07-21) |
| POST | `/api/v1/admin/admin-users/{id}/reactivate` | web + `auth:admin_web` | `{ reason: string ≥10 }`. Kích hoạt lại. Audit `admin.admin_user.reactivate` (changes.reason). (2026-07-21) |
```

And change line 356-357 (Tenant Users management table):
```
| POST | `/api/v1/admin/users/{id}/suspend` | Set `users.suspended_at`. EnsureTenant middleware chặn 403 `USER_SUSPENDED` ở route nghiệp vụ tenant. Audit `admin.user.suspend`. |
| POST | `/api/v1/admin/users/{id}/reactivate` | Clear `suspended_at`. Audit `admin.user.reactivate`. |
```
to:
```
| POST | `/api/v1/admin/users/{id}/suspend` | `{ reason: string ≥10 }`. Set `users.suspended_at`. EnsureTenant middleware chặn 403 `USER_SUSPENDED` ở route nghiệp vụ tenant. Audit `admin.user.suspend` (changes.reason). (2026-07-21) |
| POST | `/api/v1/admin/users/{id}/reactivate` | `{ reason: string ≥10 }`. Clear `suspended_at`. Audit `admin.user.reactivate` (changes.reason). (2026-07-21) |
```

- [ ] **Step 7: Static analysis and format check**

```bash
vendor/bin/pint --test app/Modules/Admin/Http/Controllers/AdminAdminUserController.php app/Modules/Admin/Http/Controllers/AdminUserController.php tests/Feature/Admin/AdminUserCrudTest.php tests/Feature/Admin/TenantUserCrudTest.php
vendor/bin/phpstan analyse app/Modules/Admin/Http/Controllers/AdminAdminUserController.php app/Modules/Admin/Http/Controllers/AdminUserController.php
```
Expected: both succeed (if Pint reports diffs, run without `--test` to auto-fix, then re-run
`--test`).

- [ ] **Step 8: Full regression check on the Admin module**

```bash
php artisan test --filter=Admin
```
Expected: no new failures beyond the pre-existing baseline (see [[test-verify-baseline]]).

- [ ] **Step 9: Commit**

```bash
git add app/Modules/Admin/Http/Controllers/AdminAdminUserController.php app/Modules/Admin/Http/Controllers/AdminUserController.php tests/Feature/Admin/AdminUserCrudTest.php tests/Feature/Admin/TenantUserCrudTest.php docs/05-api/endpoints.md
git commit -m "feat(admin): bắt buộc + ghi audit log lý do khi khoá/mở tài khoản user"
```

---

### Task 2: Frontend data layer — thread `reason` through the 4 mutation hooks

**Files:**
- Modify: `app/resources/js/admin/lib/adminUsers.tsx`
- Modify: `app/resources/js/admin/lib/tenantUsers.tsx`

**Interfaces:**
- Consumes: Task 1's now-`reason`-required endpoints.
- Produces (for Task 4/5 to consume): `useSuspendAdminUser()` / `useReactivateAdminUser()` /
  `useSuspendTenantUser()` / `useReactivateTenantUser()` all change their `mutationFn` input from
  `(id: number)` to `(vars: { id: number; reason: string })`. Confirmed via grep that these 4 hooks
  are only ever called from `AdminUserFormDrawer.tsx` and `TenantUserDrawer.tsx` (Task 4/5) — no
  other call sites to update.

- [ ] **Step 1: Update `adminUsers.tsx`**

In `app/resources/js/admin/lib/adminUsers.tsx`, change:
```ts
export function useSuspendAdminUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) =>
            (await api.post<{ data: AdminRow }>(`/admin/admin-users/${id}/suspend`)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
    });
}

export function useReactivateAdminUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) =>
            (await api.post<{ data: AdminRow }>(`/admin/admin-users/${id}/reactivate`)).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
    });
}
```
to:
```ts
export function useSuspendAdminUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, reason }: { id: number; reason: string }) =>
            (await api.post<{ data: AdminRow }>(`/admin/admin-users/${id}/suspend`, { reason })).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
    });
}

export function useReactivateAdminUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, reason }: { id: number; reason: string }) =>
            (await api.post<{ data: AdminRow }>(`/admin/admin-users/${id}/reactivate`, { reason })).data.data,
        onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
    });
}
```

- [ ] **Step 2: Update `tenantUsers.tsx`**

In `app/resources/js/admin/lib/tenantUsers.tsx`, change:
```ts
export function useSuspendTenantUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) =>
            (await api.post<{ data: { id: number; suspended_at: string } }>(`/admin/users/${id}/suspend`)).data.data,
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['tenant-users'] });
            qc.invalidateQueries({ queryKey: ['tenant-user'] });
        },
    });
}

export function useReactivateTenantUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async (id: number) =>
            (await api.post<{ data: { id: number; suspended_at: null } }>(`/admin/users/${id}/reactivate`)).data.data,
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['tenant-users'] });
            qc.invalidateQueries({ queryKey: ['tenant-user'] });
        },
    });
}
```
to:
```ts
export function useSuspendTenantUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, reason }: { id: number; reason: string }) =>
            (await api.post<{ data: { id: number; suspended_at: string } }>(`/admin/users/${id}/suspend`, { reason })).data.data,
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['tenant-users'] });
            qc.invalidateQueries({ queryKey: ['tenant-user'] });
        },
    });
}

export function useReactivateTenantUser() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: async ({ id, reason }: { id: number; reason: string }) =>
            (await api.post<{ data: { id: number; suspended_at: null } }>(`/admin/users/${id}/reactivate`, { reason })).data.data,
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['tenant-users'] });
            qc.invalidateQueries({ queryKey: ['tenant-user'] });
        },
    });
}
```

- [ ] **Step 3: Typecheck**

```bash
npm run typecheck
```
Expected: **fails** at this point — `AdminUserFormDrawer.tsx` and `TenantUserDrawer.tsx` still call
`suspend.mutate(editing.id, ...)` / `suspend.mutate(userId, ...)` with the old `number` signature.
This is expected; Tasks 4 and 5 fix the call sites. Do not attempt to make typecheck pass in
isolation for this task — commit only after Task 5 lands, or combine Tasks 2/4/5 into one commit if
executing sequentially without intermediate commits (see Step 4).

- [ ] **Step 4: Commit (after Task 5 makes the build green again)**

```bash
git add app/resources/js/admin/lib/adminUsers.tsx app/resources/js/admin/lib/tenantUsers.tsx
git commit -m "feat(admin): hook suspend/reactivate user nhận reason"
```
(If running this plan task-by-task with a commit after every task, hold this commit until Task 5's
Step 3 typecheck/lint/build passes — Tasks 2, 4, 5 are one atomic unit of work even though split
into 3 tasks for readability. An executor following superpowers:subagent-driven-development should
treat Tasks 2+4+5 as a single dependency chain before any of them is considered "done".)

---

### Task 3: `AdminTenantDetailPage.tsx` — migrate to `useReasonConfirm` / declarative reason validation

**Files:**
- Modify: `app/resources/js/admin/pages/tenants/AdminTenantDetailPage.tsx`

**Interfaces:**
- Consumes:
  ```ts
  export interface ReasonConfirmOptions {
      title: ReactNode;
      danger?: boolean;               // default false → okType='primary'; true → okType='danger'
      warningText?: ReactNode;        // optional warning paragraph shown above the reason field
      okText?: string;                // default 'Xác nhận'
      reasonLabel?: string;           // default 'Lý do (≥10 ký tự — sẽ ghi vào audit log)'
      reasonPlaceholder?: string;
      onConfirm: (reason: string) => Promise<void>;
  }
  export function useReasonConfirm(): (opts: ReasonConfirmOptions) => void
  ```
  from `@admin/components/ReasonConfirmModal` (Phase 0). `useAdminSuspendTenant`,
  `useAdminDeleteChannel`, `useAdminTenantAiCreditAdjust` (all already accept `{ ..., reason:
  string }` — no change needed, see `app/resources/js/admin/lib/admin.tsx:265-326`).
- Produces: nothing consumed elsewhere — this page is a leaf.

**Four confirm call sites in this file, four different outcomes** (read the file yourself before
touching it — this plan's line numbers are from the file as of this writing and may have shifted):

1. `OverviewTab`'s `onSuspend` → migrate to `useReasonConfirm` (backend already requires reason).
2. `OverviewTab`'s `onReactivate` → **leave unchanged**. `AdminTenantService::reactivate()`
   (`app/app/Modules/Admin/Services/AdminTenantService.php:169`) takes no `reason` parameter at all
   — unlike user-account reactivate (Task 1), tenant reactivate was never designed to require one.
   Do not invent a reason field the backend can't receive.
3. `OverviewTab`'s "Đổi gói" (change plan) `Modal` → **stays a bespoke `Modal`** (it has `plan_code`
   and `cycle` fields beyond `reason`, so per spec §5.2 it isn't a `useReasonConfirm` candidate) —
   but its `reason` field's validation moves from the imperative
   `if (reason.trim().length < 10) { message.error(...); return; }` to a declarative `Form` `rules`
   check, matching how `useReasonConfirm` validates internally.
4. `ChannelsTab`'s `onDelete` → migrate to `useReasonConfirm` (backend already requires reason via
   `AdminTenantService::forceDeleteChannelAccount`).
5. `AiCreditTab`'s `onApply` → migrate to `useReasonConfirm`, **with a UX sequencing change**: today
   the admin types both `amount` and `reason` on the page, clicks "Áp dụng", and gets a plain
   yes/no `modal.confirm` summarizing what they typed. `useReasonConfirm`'s modal always collects
   its own `reason` (Phase 0 froze that API — no "pre-filled reason" option exists), so collecting
   it twice would be redundant. This task removes the on-page `reason` `Input.TextArea` and
   `reason` state entirely; the admin now types `amount` on the page, clicks "Áp dụng", and types
   the reason inside the `useReasonConfirm` modal that opens (same two-step flow as every other
   high-impact action on this page, at the cost of `amount` and `reason` no longer being visible
   together before commit — accepted as the smaller regression vs. two reason inputs).

- [ ] **Step 1: Add the import**

In `app/resources/js/admin/pages/tenants/AdminTenantDetailPage.tsx`, change:
```tsx
import { errorMessage } from '@/lib/api';
import {
    useAdminChangePlan, useAdminDeleteChannel, useAdminPlans, useAdminReactivateTenant, useAdminSuspendTenant,
    useAdminTenant, useAdminTenantAiCreditAdjust, useAdminTenantAuditLogs, useAdminTenantDailyOrderStats,
    useAdminTenantLoginHistory, useAdminTenantOrderStatusHistory,
    type AdminChannelAccount, type AdminAdAccount, type AdminMember, type AdminTenantDetail, type AdminFullAuditEntry,
} from '@admin/lib/admin';
```
to:
```tsx
import { errorMessage } from '@/lib/api';
import { useReasonConfirm } from '@admin/components/ReasonConfirmModal';
import {
    useAdminChangePlan, useAdminDeleteChannel, useAdminPlans, useAdminReactivateTenant, useAdminSuspendTenant,
    useAdminTenant, useAdminTenantAiCreditAdjust, useAdminTenantAuditLogs, useAdminTenantDailyOrderStats,
    useAdminTenantLoginHistory, useAdminTenantOrderStatusHistory,
    type AdminChannelAccount, type AdminAdAccount, type AdminMember, type AdminTenantDetail, type AdminFullAuditEntry,
} from '@admin/lib/admin';
```

- [ ] **Step 2: Rewrite `OverviewTab`**

Change the whole `OverviewTab` function (current lines 72-220) from:
```tsx
function OverviewTab({ t }: { t: AdminTenantDetail }) {
    const { message, modal } = App.useApp();
    const suspend = useAdminSuspendTenant();
    const reactivate = useAdminReactivateTenant();
    const change = useAdminChangePlan();
    const allPlans = useAdminPlans().data as Array<{ code: string; name: string; is_active: boolean }> | undefined;
    const planOptions = (allPlans ?? []).filter((p) => p.is_active).map((p) => ({ value: p.code, label: p.name }));

    const [planOpen, setPlanOpen] = useState(false);
    const [planCode, setPlanCode] = useState<string>(t.subscription?.plan_code ?? 'starter');
    const [cycle, setCycle] = useState<'monthly' | 'yearly' | 'trial'>('monthly');
    const [reason, setReason] = useState('');

    const onSuspend = () => {
        let suspendReason = '';
        modal.confirm({
            title: 'Tạm khoá gian hàng',
            content: (
                <Form layout="vertical" style={{ marginTop: 12 }}>
                    <Form.Item label="Lý do (≥10 ký tự)">
                        <Input.TextArea rows={3} onChange={(e) => { suspendReason = e.target.value; }} />
                    </Form.Item>
                </Form>
            ),
            okText: 'Tạm khoá', okType: 'danger', cancelText: 'Huỷ',
            onOk: async () => {
                if (suspendReason.trim().length < 10) {
                    message.error('Lý do phải có tối thiểu 10 ký tự.');
                    throw new Error('reason too short');
                }
                try {
                    await suspend.mutateAsync({ tenantId: t.id, reason: suspendReason.trim() });
                    message.success('Đã tạm khoá tenant.');
                } catch (e) { message.error(errorMessage(e)); throw e; }
            },
        });
    };

    const onReactivate = () => {
        modal.confirm({
            title: 'Mở lại tenant?',
            content: 'Tenant sẽ trở lại trạng thái hoạt động bình thường.',
            okText: 'Mở lại', cancelText: 'Huỷ',
            onOk: async () => {
                try {
                    await reactivate.mutateAsync({ tenantId: t.id });
                    message.success('Đã mở lại tenant.');
                } catch (e) { message.error(errorMessage(e)); throw e; }
            },
        });
    };

    const submitPlan = async () => {
        if (reason.trim().length < 10) {
            message.error('Lý do phải có tối thiểu 10 ký tự.');
            return;
        }
        try {
            await change.mutateAsync({ tenantId: t.id, plan_code: planCode, cycle, reason: reason.trim() });
            message.success('Đã đổi gói cho tenant.');
            setPlanOpen(false); setReason('');
        } catch (e) { message.error(errorMessage(e)); }
    };

    const sub = t.subscription;

    return (
        <>
            <Descriptions size="small" column={2} bordered style={{ marginBottom: 16 }}>
                <Descriptions.Item label="Slug">{t.slug}</Descriptions.Item>
                <Descriptions.Item label="Tạo lúc">{formatDate(t.created_at)}</Descriptions.Item>
                <Descriptions.Item label="Chủ sở hữu" span={2}>
                    {t.owner ? `${t.owner.name} <${t.owner.email}>` : '—'}
                </Descriptions.Item>
                <Descriptions.Item label="Hạn mức kênh" span={2}>
                    <Space>
                        <Typography.Text strong style={{ color: t.usage.channel_accounts.over ? '#cf1322' : undefined }}>
                            {t.usage.channel_accounts.used} / {t.usage.channel_accounts.limit < 0 ? '∞' : t.usage.channel_accounts.limit}
                        </Typography.Text>
                        {t.usage.channel_accounts.over && (
                            <Tag color={sub?.over_quota_locked ? 'red' : 'orange'}
                                icon={sub?.over_quota_locked ? <LockOutlined /> : <WarningOutlined />}>
                                {sub?.over_quota_locked ? 'Đã khoá (quá 48h)' : 'Đang đếm 48h ân hạn'}
                            </Tag>
                        )}
                    </Space>
                </Descriptions.Item>
            </Descriptions>

            <div style={{ marginBottom: 24 }}>
                {t.status === 'suspended'
                    ? <Button type="primary" icon={<UnlockOutlined />} onClick={onReactivate} loading={reactivate.isPending}>Mở lại</Button>
                    : <Button danger icon={<LockOutlined />} onClick={onSuspend} loading={suspend.isPending}>Tạm khoá</Button>}
            </div>

            <Typography.Title level={5}>Gói thuê bao</Typography.Title>
            {sub ? (
                <Descriptions size="small" column={2} bordered>
                    <Descriptions.Item label="Gói">{(sub.plan_code ?? '—').toUpperCase()}</Descriptions.Item>
                    <Descriptions.Item label="Trạng thái">{sub.status}</Descriptions.Item>
                    <Descriptions.Item label="Chu kỳ">{sub.billing_cycle}</Descriptions.Item>
                    <Descriptions.Item label="Hết hạn">{formatDate(sub.current_period_end, false)}</Descriptions.Item>
                    {sub.over_quota_warned_at && (
                        <Descriptions.Item label="Cảnh báo vượt mức" span={2}>
                            <Space>
                                <Typography.Text>{formatDate(sub.over_quota_warned_at)}</Typography.Text>
                                <Tag color={sub.over_quota_locked ? 'red' : 'orange'}>
                                    {sub.over_quota_locked ? 'Đã quá 48h — đang khoá' : 'Còn trong 48h ân hạn'}
                                </Tag>
                            </Space>
                        </Descriptions.Item>
                    )}
                </Descriptions>
            ) : <Empty description="Chưa có subscription" />}

            <div style={{ marginTop: 16 }}>
                <Button type="primary" icon={<SwapOutlined />} onClick={() => setPlanOpen(true)}>Đổi gói</Button>
            </div>

            <Modal
                open={planOpen} onCancel={() => setPlanOpen(false)} title="Đổi gói cho tenant"
                okText="Xác nhận đổi" cancelText="Huỷ" onOk={submitPlan} confirmLoading={change.isPending}
            >
                <Form layout="vertical">
                    <Form.Item label="Gói">
                        <Radio.Group value={planCode} onChange={(e) => setPlanCode(e.target.value)} optionType="button" buttonStyle="solid"
                            options={planOptions.length ? planOptions : PLAN_OPTIONS} />
                    </Form.Item>
                    <Form.Item label="Chu kỳ">
                        <Radio.Group value={cycle} onChange={(e) => setCycle(e.target.value)} optionType="button" buttonStyle="solid"
                            options={[
                                { value: 'monthly', label: 'Tháng' },
                                { value: 'yearly', label: 'Năm' },
                                { value: 'trial', label: 'Trial' },
                            ]} />
                    </Form.Item>
                    <Form.Item label="Lý do (≥10 ký tự)" required>
                        <Input.TextArea rows={3} value={reason} onChange={(e) => setReason(e.target.value)}
                            placeholder="vd: Khách yêu cầu hạ gói về Starter. Ticket #1234." />
                    </Form.Item>
                    <Typography.Paragraph type="warning" style={{ fontSize: 12 }}>
                        Đổi gói tay không tạo hoá đơn. Subscription cũ ⇒ cancelled, subscription mới ⇒ active từ
                        thời điểm này. Nếu gói thấp hơn ⇒ tenant có thể bị vào trạng thái "vượt mức" (banner đếm 48h).
                    </Typography.Paragraph>
                </Form>
            </Modal>
        </>
    );
}
```
to:
```tsx
function OverviewTab({ t }: { t: AdminTenantDetail }) {
    const { message, modal } = App.useApp();
    const confirmWithReason = useReasonConfirm();
    const suspend = useAdminSuspendTenant();
    const reactivate = useAdminReactivateTenant();
    const change = useAdminChangePlan();
    const allPlans = useAdminPlans().data as Array<{ code: string; name: string; is_active: boolean }> | undefined;
    const planOptions = (allPlans ?? []).filter((p) => p.is_active).map((p) => ({ value: p.code, label: p.name }));

    const [planOpen, setPlanOpen] = useState(false);
    const [planCode, setPlanCode] = useState<string>(t.subscription?.plan_code ?? 'starter');
    const [cycle, setCycle] = useState<'monthly' | 'yearly' | 'trial'>('monthly');
    const [planForm] = Form.useForm<{ reason: string }>();

    const onSuspend = () => {
        confirmWithReason({
            title: 'Tạm khoá gian hàng',
            danger: true,
            okText: 'Tạm khoá',
            onConfirm: async (reason) => {
                await suspend.mutateAsync({ tenantId: t.id, reason });
                message.success('Đã tạm khoá tenant.');
            },
        });
    };

    const onReactivate = () => {
        // Mở lại KHÔNG cần lý do — AdminTenantService::reactivate() không nhận reason (khác
        // suspend/changePlan), khớp tier "standard" cho hành động khôi phục quyền truy cập.
        modal.confirm({
            title: 'Mở lại tenant?',
            content: 'Tenant sẽ trở lại trạng thái hoạt động bình thường.',
            okText: 'Mở lại', cancelText: 'Huỷ',
            onOk: async () => {
                try {
                    await reactivate.mutateAsync({ tenantId: t.id });
                    message.success('Đã mở lại tenant.');
                } catch (e) { message.error(errorMessage(e)); throw e; }
            },
        });
    };

    const openPlanModal = () => {
        planForm.resetFields();
        setPlanOpen(true);
    };

    const submitPlan = async () => {
        let values: { reason: string };
        try {
            values = await planForm.validateFields();
        } catch {
            return; // Lỗi hiển thị ngay dưới field "Lý do" — không cần toast riêng.
        }
        try {
            await change.mutateAsync({ tenantId: t.id, plan_code: planCode, cycle, reason: values.reason.trim() });
            message.success('Đã đổi gói cho tenant.');
            setPlanOpen(false);
            planForm.resetFields();
        } catch (e) { message.error(errorMessage(e)); }
    };

    const sub = t.subscription;

    return (
        <>
            <Descriptions size="small" column={2} bordered style={{ marginBottom: 16 }}>
                <Descriptions.Item label="Slug">{t.slug}</Descriptions.Item>
                <Descriptions.Item label="Tạo lúc">{formatDate(t.created_at)}</Descriptions.Item>
                <Descriptions.Item label="Chủ sở hữu" span={2}>
                    {t.owner ? `${t.owner.name} <${t.owner.email}>` : '—'}
                </Descriptions.Item>
                <Descriptions.Item label="Hạn mức kênh" span={2}>
                    <Space>
                        <Typography.Text strong style={{ color: t.usage.channel_accounts.over ? '#cf1322' : undefined }}>
                            {t.usage.channel_accounts.used} / {t.usage.channel_accounts.limit < 0 ? '∞' : t.usage.channel_accounts.limit}
                        </Typography.Text>
                        {t.usage.channel_accounts.over && (
                            <Tag color={sub?.over_quota_locked ? 'red' : 'orange'}
                                icon={sub?.over_quota_locked ? <LockOutlined /> : <WarningOutlined />}>
                                {sub?.over_quota_locked ? 'Đã khoá (quá 48h)' : 'Đang đếm 48h ân hạn'}
                            </Tag>
                        )}
                    </Space>
                </Descriptions.Item>
            </Descriptions>

            <div style={{ marginBottom: 24 }}>
                {t.status === 'suspended'
                    ? <Button type="primary" icon={<UnlockOutlined />} onClick={onReactivate} loading={reactivate.isPending}>Mở lại</Button>
                    : <Button danger icon={<LockOutlined />} onClick={onSuspend} loading={suspend.isPending}>Tạm khoá</Button>}
            </div>

            <Typography.Title level={5}>Gói thuê bao</Typography.Title>
            {sub ? (
                <Descriptions size="small" column={2} bordered>
                    <Descriptions.Item label="Gói">{(sub.plan_code ?? '—').toUpperCase()}</Descriptions.Item>
                    <Descriptions.Item label="Trạng thái">{sub.status}</Descriptions.Item>
                    <Descriptions.Item label="Chu kỳ">{sub.billing_cycle}</Descriptions.Item>
                    <Descriptions.Item label="Hết hạn">{formatDate(sub.current_period_end, false)}</Descriptions.Item>
                    {sub.over_quota_warned_at && (
                        <Descriptions.Item label="Cảnh báo vượt mức" span={2}>
                            <Space>
                                <Typography.Text>{formatDate(sub.over_quota_warned_at)}</Typography.Text>
                                <Tag color={sub.over_quota_locked ? 'red' : 'orange'}>
                                    {sub.over_quota_locked ? 'Đã quá 48h — đang khoá' : 'Còn trong 48h ân hạn'}
                                </Tag>
                            </Space>
                        </Descriptions.Item>
                    )}
                </Descriptions>
            ) : <Empty description="Chưa có subscription" />}

            <div style={{ marginTop: 16 }}>
                <Button type="primary" icon={<SwapOutlined />} onClick={openPlanModal}>Đổi gói</Button>
            </div>

            <Modal
                open={planOpen} onCancel={() => { setPlanOpen(false); planForm.resetFields(); }} title="Đổi gói cho tenant"
                okText="Xác nhận đổi" cancelText="Huỷ" onOk={submitPlan} confirmLoading={change.isPending}
            >
                <Form form={planForm} layout="vertical">
                    <Form.Item label="Gói">
                        <Radio.Group value={planCode} onChange={(e) => setPlanCode(e.target.value)} optionType="button" buttonStyle="solid"
                            options={planOptions.length ? planOptions : PLAN_OPTIONS} />
                    </Form.Item>
                    <Form.Item label="Chu kỳ">
                        <Radio.Group value={cycle} onChange={(e) => setCycle(e.target.value)} optionType="button" buttonStyle="solid"
                            options={[
                                { value: 'monthly', label: 'Tháng' },
                                { value: 'yearly', label: 'Năm' },
                                { value: 'trial', label: 'Trial' },
                            ]} />
                    </Form.Item>
                    <Form.Item
                        name="reason"
                        label="Lý do (≥10 ký tự)"
                        rules={[{ required: true, min: 10, message: 'Lý do phải có tối thiểu 10 ký tự.' }]}
                    >
                        <Input.TextArea rows={3} placeholder="vd: Khách yêu cầu hạ gói về Starter. Ticket #1234." />
                    </Form.Item>
                    <Typography.Paragraph type="warning" style={{ fontSize: 12 }}>
                        Đổi gói tay không tạo hoá đơn. Subscription cũ ⇒ cancelled, subscription mới ⇒ active từ
                        thời điểm này. Nếu gói thấp hơn ⇒ tenant có thể bị vào trạng thái "vượt mức" (banner đếm 48h).
                    </Typography.Paragraph>
                </Form>
            </Modal>
        </>
    );
}
```

- [ ] **Step 3: Rewrite `ChannelsTab`'s `onDelete`**

Change:
```tsx
function ChannelsTab({ tenantId, accounts }: { tenantId: number; accounts: AdminChannelAccount[] }) {
    const del = useAdminDeleteChannel();
    const { message, modal } = App.useApp();

    const onDelete = (acc: AdminChannelAccount) => {
        let reason = '';
        modal.confirm({
            title: <Space><WarningOutlined style={{ color: '#cf1322' }} /> Xoá kết nối «{acc.name}»?</Space>,
            content: (
                <div>
                    <Typography.Paragraph type="warning" style={{ marginBottom: 8 }}>
                        Hành động này KHÔNG hoàn tác: xoá kết nối + xoá đơn của gian hàng + huỷ liên kết SKU.
                        Tồn đã giữ chỗ sẽ được nhả.
                    </Typography.Paragraph>
                    <Form layout="vertical" style={{ marginTop: 12 }}>
                        <Form.Item label="Lý do (≥10 ký tự — sẽ ghi audit log)">
                            <Input.TextArea rows={3} onChange={(e) => { reason = e.target.value; }}
                                placeholder="vd: Khách yêu cầu gỡ kênh sau khi hạ gói về Starter." />
                        </Form.Item>
                    </Form>
                </div>
            ),
            okText: 'Xoá kết nối', okType: 'danger', cancelText: 'Huỷ',
            onOk: async () => {
                if (reason.trim().length < 10) {
                    message.error('Lý do phải có tối thiểu 10 ký tự.');
                    throw new Error('reason too short');
                }
                try {
                    const r = await del.mutateAsync({ tenantId, channelAccountId: acc.id, reason: reason.trim() });
                    message.success(`Đã xoá kết nối: ${r.deleted_orders} đơn + ${r.unlinked_skus} liên kết SKU.`);
                } catch (e) { message.error(errorMessage(e)); throw e; }
            },
        });
    };
```
to:
```tsx
function ChannelsTab({ tenantId, accounts }: { tenantId: number; accounts: AdminChannelAccount[] }) {
    const del = useAdminDeleteChannel();
    const { message } = App.useApp();
    const confirmWithReason = useReasonConfirm();

    const onDelete = (acc: AdminChannelAccount) => {
        confirmWithReason({
            title: <Space><WarningOutlined style={{ color: '#cf1322' }} /> Xoá kết nối «{acc.name}»?</Space>,
            danger: true,
            okText: 'Xoá kết nối',
            warningText: 'Hành động này KHÔNG hoàn tác: xoá kết nối + xoá đơn của gian hàng + huỷ liên kết SKU. Tồn đã giữ chỗ sẽ được nhả.',
            reasonPlaceholder: 'vd: Khách yêu cầu gỡ kênh sau khi hạ gói về Starter.',
            onConfirm: async (reason) => {
                const r = await del.mutateAsync({ tenantId, channelAccountId: acc.id, reason });
                message.success(`Đã xoá kết nối: ${r.deleted_orders} đơn + ${r.unlinked_skus} liên kết SKU.`);
            },
        });
    };
```
(The rest of `ChannelsTab` — the `if (accounts.length === 0) return <Empty .../>` line and the
`<Table>` — is unchanged, leave it exactly as-is.)

- [ ] **Step 4: Rewrite `AiCreditTab`'s `onApply`**

Change:
```tsx
function AiCreditTab({ tenantId, t }: { tenantId: number; t: AdminTenantDetail }) {
    const adjust = useAdminTenantAiCreditAdjust();
    const { message, modal } = App.useApp();
    const [amount, setAmount] = useState<number | null>(null);
    const [reason, setReason] = useState('');

    const c = t.ai_credit;

    const onApply = () => {
        if (!amount) {
            message.error('Số lượng phải khác 0.');
            return;
        }
        if (reason.trim().length < 10) {
            message.error('Lý do phải có tối thiểu 10 ký tự.');
            return;
        }
        const amt = amount;
        const trimmedReason = reason.trim();
        modal.confirm({
            title: amt > 0 ? `Cộng ${amt} lượt AI cho tenant?` : `Trừ ${Math.abs(amt)} lượt AI của tenant?`,
            content: `Lý do: ${trimmedReason}`,
            okText: 'Xác nhận', cancelText: 'Huỷ',
            onOk: async () => {
                try {
                    await adjust.mutateAsync({ tenantId, amount: amt, reason: trimmedReason });
                    message.success('Đã cập nhật hạn mức AI.');
                    setAmount(null); setReason('');
                } catch (e) { message.error(errorMessage(e)); throw e; }
            },
        });
    };

    return (
        <>
            <Descriptions size="small" column={2} bordered style={{ marginBottom: 16 }}>
                <Descriptions.Item label="Bật AI">{c.enabled ? <Tag color="green">Bật</Tag> : <Tag color="red">Tắt</Tag>}</Descriptions.Item>
                <Descriptions.Item label="Không giới hạn">{c.unlimited ? <Tag color="purple">Có</Tag> : <Tag>Không</Tag>}</Descriptions.Item>
                <Descriptions.Item label="Hạn mức tháng">{c.monthly_allowance}</Descriptions.Item>
                <Descriptions.Item label="Đã dùng trong kỳ">{c.period_used}</Descriptions.Item>
                <Descriptions.Item label="Số dư mua thêm">{c.purchased_balance}</Descriptions.Item>
                <Descriptions.Item label="Còn lại">{c.available == null ? '∞' : c.available}</Descriptions.Item>
            </Descriptions>

            <Typography.Title level={5}>Cộng / trừ hạn mức tay</Typography.Title>
            <Space align="start" style={{ marginBottom: 24 }} wrap>
                <InputNumber value={amount} onChange={(v) => setAmount(v)} placeholder="vd: 100 hoặc -50" style={{ width: 160 }} />
                <Input.TextArea rows={2} value={reason} onChange={(e) => setReason(e.target.value)}
                    placeholder="Lý do (≥10 ký tự)" style={{ width: 360 }} />
                <Button type="primary" onClick={onApply} loading={adjust.isPending}>Áp dụng</Button>
            </Space>
```
to:
```tsx
function AiCreditTab({ tenantId, t }: { tenantId: number; t: AdminTenantDetail }) {
    const adjust = useAdminTenantAiCreditAdjust();
    const { message } = App.useApp();
    const confirmWithReason = useReasonConfirm();
    const [amount, setAmount] = useState<number | null>(null);

    const c = t.ai_credit;

    const onApply = () => {
        if (!amount) {
            message.error('Số lượng phải khác 0.');
            return;
        }
        const amt = amount;
        confirmWithReason({
            title: amt > 0 ? `Cộng ${amt} lượt AI cho tenant?` : `Trừ ${Math.abs(amt)} lượt AI của tenant?`,
            okText: 'Xác nhận',
            onConfirm: async (reason) => {
                await adjust.mutateAsync({ tenantId, amount: amt, reason });
                message.success('Đã cập nhật hạn mức AI.');
                setAmount(null);
            },
        });
    };

    return (
        <>
            <Descriptions size="small" column={2} bordered style={{ marginBottom: 16 }}>
                <Descriptions.Item label="Bật AI">{c.enabled ? <Tag color="green">Bật</Tag> : <Tag color="red">Tắt</Tag>}</Descriptions.Item>
                <Descriptions.Item label="Không giới hạn">{c.unlimited ? <Tag color="purple">Có</Tag> : <Tag>Không</Tag>}</Descriptions.Item>
                <Descriptions.Item label="Hạn mức tháng">{c.monthly_allowance}</Descriptions.Item>
                <Descriptions.Item label="Đã dùng trong kỳ">{c.period_used}</Descriptions.Item>
                <Descriptions.Item label="Số dư mua thêm">{c.purchased_balance}</Descriptions.Item>
                <Descriptions.Item label="Còn lại">{c.available == null ? '∞' : c.available}</Descriptions.Item>
            </Descriptions>

            <Typography.Title level={5}>Cộng / trừ hạn mức tay</Typography.Title>
            <Space align="start" style={{ marginBottom: 24 }} wrap>
                <InputNumber value={amount} onChange={(v) => setAmount(v)} placeholder="vd: 100 hoặc -50" style={{ width: 160 }} />
                <Button type="primary" onClick={onApply} loading={adjust.isPending}>Áp dụng</Button>
            </Space>
```
(The rest of `AiCreditTab` — the two `Table`s for usage-by-month / usage-by-feature — is unchanged.)

- [ ] **Step 5: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: succeeds. Watch for an "unused variable `modal`" lint error in `ChannelsTab`/`AiCreditTab`
if the `App.useApp()` destructure wasn't trimmed to `{ message }` in both — the diffs above already
do this, double-check it landed.

- [ ] **Step 6: Manual browser verification**

Requires Task 1 (backend `reason` on user endpoints is unrelated to this page, so Task 1 isn't a
hard prerequisite here — tenant-level endpoints already supported `reason`). Start the dev stack,
log into `/admin/tenants`, open any tenant's detail page, then:
1. **Tổng quan tab, Tạm khoá**: click "Tạm khoá". Confirm a modal titled "Tạm khoá gian hàng" opens
   with a red "Tạm khoá" button and a "Lý do" textarea. Click "Tạm khoá" with the field empty —
   confirm an inline error appears under the field ("Lý do phải có tối thiểu 10 ký tự.") and the
   modal stays open. Type ≥10 characters, click "Tạm khoá" again — confirm the modal closes, a
   success toast appears, and the tenant's status tag flips to "Tạm khoá".
2. **Tổng quan tab, Mở lại**: on the now-suspended tenant, click "Mở lại". Confirm the OLD-style
   plain yes/no modal appears (title "Mở lại tenant?", no reason field) — this one intentionally
   did NOT change. Confirm it works and the status flips back to "Hoạt động".
3. **Tổng quan tab, Đổi gói**: click "Đổi gói cho tenant". Confirm the modal still shows Gói +
   Chu kỳ radio groups plus a Lý do field. Click "Xác nhận đổi" with an empty reason — confirm an
   inline error appears under "Lý do" (not a toast) and the modal stays open. Fill in a valid
   reason and submit — confirm success and the "Gói thuê bao" section updates.
4. **Kênh kết nối tab**: if the tenant has at least one channel account, click "Xoá" on one. Confirm
   the modal shows the warning paragraph ("Hành động này KHÔNG hoàn tác...") above the reason field
   (not below), and the same ≥10-char inline validation as step 1.
5. **Hạn mức AI tab**: type a nonzero amount (e.g. `50`) in the number field, leave the (now single)
   reason field — confirm there is no separate reason textarea on the page anymore. Click "Áp dụng"
   — confirm a `useReasonConfirm` modal opens titled "Cộng 50 lượt AI cho tenant?" with its own Lý
   do field. Submit with a valid reason — confirm success and "Đã dùng trong kỳ"/"Còn lại" update.
6. Open browser devtools console — confirm zero errors on this page across all 5 checks above.

- [ ] **Step 7: Commit**

```bash
git add app/resources/js/admin/pages/tenants/AdminTenantDetailPage.tsx
git commit -m "feat(admin): tenant detail dùng useReasonConfirm cho hành động rủi ro cao"
```

---

### Task 4: `AdminUserFormDrawer.tsx` — `useReasonConfirm` + Vietnamese labels

**Files:**
- Modify: `app/resources/js/admin/pages/users/AdminUserFormDrawer.tsx`

**Interfaces:**
- Consumes: `useReasonConfirm` (Phase 0), `useSuspendAdminUser`/`useReactivateAdminUser` (Task 2's
  new `{ id, reason }` signature).
- Depends on: Task 1 (backend must accept `reason`) and Task 2 (hook signature) — this task's
  `npm run typecheck` will not pass until Task 2 has landed.

- [ ] **Step 1: Replace the whole file**

Write the complete new `app/resources/js/admin/pages/users/AdminUserFormDrawer.tsx`:
```tsx
// Spec 2026-05-17 (redesign 2026-07-21) — drawer thêm/sửa super-admin.
//
// `target === 'new'`: form tạo mới (username + name + email? + password).
// `target` là AdminRow: form sửa metadata (name + email) + action buttons
// (đặt lại mật khẩu, tạm khoá, mở lại). Không cho phép tự tạm khoá / đặt lại mật khẩu
// chính mình — BE chặn (409 CANNOT_SELF_MUTATE). UI show error message.
// Tạm khoá/Mở lại dùng useReasonConfirm — tier "high-impact" (khoá tài khoản khỏi hệ
// thống, cả 2 chiều) theo docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md §5.1.
// Đặt lại mật khẩu vẫn dùng Popconfirm (spec không xếp tier high-impact cho hành động này).

import { useEffect, useState } from 'react';
import { Drawer, Form, Input, Space, Button, App, Popconfirm, Typography } from 'antd';
import { errorMessage } from '@/lib/api';
import { useReasonConfirm } from '@admin/components/ReasonConfirmModal';
import {
    useCreateAdminUser,
    useUpdateAdminUser,
    useSuspendAdminUser,
    useReactivateAdminUser,
    useResetAdminPassword,
    type AdminRow,
} from '../../lib/adminUsers';

type Target = AdminRow | 'new' | null;

export function AdminUserFormDrawer({
    open,
    target,
    onClose,
}: {
    open: boolean;
    target: Target;
    onClose: () => void;
}) {
    const [form] = Form.useForm();
    const [newPassword, setNewPassword] = useState('');
    const create = useCreateAdminUser();
    const update = useUpdateAdminUser();
    const suspend = useSuspendAdminUser();
    const reactivate = useReactivateAdminUser();
    const reset = useResetAdminPassword();
    const { message } = App.useApp();
    const confirmWithReason = useReasonConfirm();

    useEffect(() => {
        if (!open) return;
        setNewPassword('');
        if (target === 'new') {
            form.resetFields();
        } else if (target) {
            form.setFieldsValue({
                username: target.username,
                name: target.name,
                email: target.email ?? '',
            });
        }
    }, [open, target, form]);

    const isNew = target === 'new';
    const editing = target && typeof target !== 'string' ? target : null;

    const onSuspend = () => {
        if (!editing) return;
        confirmWithReason({
            title: `Tạm khoá admin «${editing.username}»?`,
            danger: true,
            okText: 'Tạm khoá',
            onConfirm: async (reason) => {
                await suspend.mutateAsync({ id: editing.id, reason });
                message.success('Đã tạm khoá.');
                onClose();
            },
        });
    };

    const onReactivate = () => {
        if (!editing) return;
        confirmWithReason({
            title: `Mở lại admin «${editing.username}»?`,
            okText: 'Mở lại',
            onConfirm: async (reason) => {
                await reactivate.mutateAsync({ id: editing.id, reason });
                message.success('Đã mở lại.');
                onClose();
            },
        });
    };

    return (
        <Drawer
            open={open}
            title={isNew ? 'Thêm super-admin' : `Sửa: ${editing?.username ?? ''}`}
            width={420}
            onClose={onClose}
            destroyOnHidden
        >
            <Form
                layout="vertical"
                form={form}
                onFinish={(v: { username?: string; name: string; email?: string; password?: string }) => {
                    if (isNew) {
                        create.mutate(
                            {
                                username: v.username!,
                                name: v.name,
                                email: v.email || undefined,
                                password: v.password!,
                            },
                            {
                                onSuccess: () => {
                                    message.success('Đã tạo admin.');
                                    onClose();
                                },
                                onError: (e) => message.error(errorMessage(e, 'Tạo thất bại.')),
                            },
                        );
                    } else if (editing) {
                        update.mutate(
                            { id: editing.id, name: v.name, email: v.email || null },
                            {
                                onSuccess: () => {
                                    message.success('Đã lưu.');
                                    onClose();
                                },
                                onError: (e) => message.error(errorMessage(e, 'Lưu thất bại.')),
                            },
                        );
                    }
                }}
            >
                <Form.Item name="username" label="Username" rules={[{ required: true }]}>
                    <Input disabled={!isNew} placeholder="ops_lead" />
                </Form.Item>
                <Form.Item name="name" label="Tên" rules={[{ required: true }]}>
                    <Input />
                </Form.Item>
                <Form.Item name="email" label="Email (không bắt buộc)">
                    <Input placeholder="ops@cmbcore.vn" />
                </Form.Item>
                {isNew && (
                    <Form.Item name="password" label="Mật khẩu" rules={[{ required: true, min: 8 }]}>
                        <Input.Password autoComplete="new-password" />
                    </Form.Item>
                )}

                <Space wrap>
                    <Button type="primary" htmlType="submit" loading={create.isPending || update.isPending}>
                        Lưu
                    </Button>

                    {editing && (
                        <>
                            <Popconfirm
                                title="Đặt mật khẩu mới"
                                description={
                                    <div style={{ width: 220 }}>
                                        <Input.Password
                                            placeholder="Mật khẩu mới (≥ 8)"
                                            value={newPassword}
                                            onChange={(e) => setNewPassword(e.target.value)}
                                        />
                                    </div>
                                }
                                okText="Đổi"
                                cancelText="Huỷ"
                                onConfirm={() => {
                                    if (newPassword.length < 8) {
                                        message.error('Mật khẩu phải ≥ 8 ký tự.');
                                        return;
                                    }
                                    reset.mutate(
                                        { id: editing.id, password: newPassword },
                                        {
                                            onSuccess: () => {
                                                message.success('Đã đổi mật khẩu.');
                                                setNewPassword('');
                                            },
                                            onError: (e) => message.error(errorMessage(e)),
                                        },
                                    );
                                }}
                            >
                                <Button>Đặt lại mật khẩu</Button>
                            </Popconfirm>

                            {editing.is_active ? (
                                <Button danger onClick={onSuspend} loading={suspend.isPending}>
                                    Tạm khoá
                                </Button>
                            ) : (
                                <Button onClick={onReactivate} loading={reactivate.isPending}>
                                    Mở lại
                                </Button>
                            )}
                        </>
                    )}
                </Space>

                {editing && (
                    <Typography.Paragraph type="secondary" style={{ marginTop: 12, fontSize: 12 }}>
                        Lưu ý: không thể tự tạm khoá / đặt lại mật khẩu chính mình.
                    </Typography.Paragraph>
                )}
            </Form>
        </Drawer>
    );
}
```

- [ ] **Step 2: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: succeeds (assuming Task 2 already landed — if not, `suspend.mutateAsync({ id, reason })`
won't typecheck against the old `(id: number)` signature).

- [ ] **Step 3: Manual browser verification**

Requires Task 1 (backend) deployed. Log into `/admin/users`, switch to the "Super-admin" tab (or
however admin users are listed on `AdminUsersPage`), open an existing admin (not yourself) in the
drawer, then:
1. Confirm the two action buttons read "Tạm khoá" (if active) or "Mở lại" (if inactive) — no
   English text anywhere on the drawer.
2. Confirm the reset-password button reads "Đặt lại mật khẩu" (still a `Popconfirm`, not the new
   modal — click it and confirm the old inline-password Popconfirm UI is unchanged).
3. Click "Tạm khoá". Confirm the `useReasonConfirm` modal opens (title `Tạm khoá admin «username»?`,
   red confirm button, reason textarea). Submit with <10 chars — confirm inline field error, modal
   stays open. Submit with a valid reason — confirm success toast, drawer closes, and the admin's
   row in the list shows as inactive.
4. Re-open that same (now inactive) admin. Click "Mở lại". Confirm the modal opens (non-danger,
   primary blue confirm button) and also requires a reason (this is the one direction that's new —
   double check it isn't silently skippable). Submit — confirm success and the row flips back to
   active.
5. Try to open your OWN admin account's row (if visible in the list) and click "Tạm khoá" — confirm
   the existing `CANNOT_SELF_MUTATE` 409 error surfaces via toast (unchanged behavior, just
   confirming Task 1's `refuseSelf()` check still runs before the reason validation).
6. Open browser devtools console — confirm zero errors.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/admin/pages/users/AdminUserFormDrawer.tsx
git commit -m "feat(admin): admin user drawer dùng useReasonConfirm + nhãn tiếng Việt"
```

---

### Task 5: `TenantUserDrawer.tsx` — `useReasonConfirm` + Vietnamese labels + loading `Spin`

**Files:**
- Modify: `app/resources/js/admin/pages/users/TenantUserDrawer.tsx`

**Interfaces:**
- Consumes: `useReasonConfirm` (Phase 0), `useSuspendTenantUser`/`useReactivateTenantUser` (Task
  2's new `{ id, reason }` signature), `useTenantUserDetail`'s `isLoading` (already returned by the
  hook at `app/resources/js/admin/lib/tenantUsers.tsx:34-40` — a `useQuery` wrapper — just never
  destructured on the caller side until now).
- Depends on: Task 1 and Task 2, same as Task 4.

- [ ] **Step 1: Replace the whole file**

Write the complete new `app/resources/js/admin/pages/users/TenantUserDrawer.tsx`:
```tsx
// Spec 2026-05-17 (redesign 2026-07-21) — drawer chi tiết tenant user. Sửa name/email, đặt lại
// mật khẩu, tạm khoá/mở lại. Hiển thị danh sách tenant đang là thành viên.
// Tạm khoá/Mở lại dùng useReasonConfirm — tier "high-impact" theo
// docs/superpowers/specs/2026-07-21-admin-panel-ux-redesign-design.md §5.1.
// Loading: Spin trong lúc useTenantUserDetail() chưa trả dữ liệu — trước đây thiếu (spec §5.5),
// drawer mở ra thấy form trống trong vài trăm ms rồi mới nạp dữ liệu.

import { useEffect, useState } from 'react';
import { Drawer, Form, Input, Button, Space, App, Popconfirm, Spin, Tag, Typography, Descriptions } from 'antd';
import { errorMessage } from '@/lib/api';
import { useReasonConfirm } from '@admin/components/ReasonConfirmModal';
import {
    useTenantUserDetail,
    useUpdateTenantUser,
    useResetTenantUserPassword,
    useSuspendTenantUser,
    useReactivateTenantUser,
    useTenantUserAiUsage,
} from '../../lib/tenantUsers';

export function TenantUserDrawer({
    userId,
    onClose,
}: {
    userId: number | null;
    onClose: () => void;
}) {
    const [form] = Form.useForm();
    const [newPassword, setNewPassword] = useState('');
    const { data, isLoading } = useTenantUserDetail(userId);
    const aiUsage = useTenantUserAiUsage(userId);
    const update = useUpdateTenantUser();
    const reset = useResetTenantUserPassword();
    const suspend = useSuspendTenantUser();
    const reactivate = useReactivateTenantUser();
    const { message } = App.useApp();
    const confirmWithReason = useReasonConfirm();

    useEffect(() => {
        if (data) {
            form.setFieldsValue({ name: data.name, email: data.email });
        }
    }, [data, form]);

    if (userId === null) return null;

    if (isLoading || !data) {
        return (
            <Drawer open width={460} title="Người dùng" onClose={onClose} destroyOnHidden>
                <div style={{ textAlign: 'center', padding: 48 }}><Spin /></div>
            </Drawer>
        );
    }

    const suspended = !!data.suspended_at;

    const onSuspend = () => {
        confirmWithReason({
            title: 'Tạm khoá người dùng này?',
            danger: true,
            okText: 'Tạm khoá',
            warningText: 'Người dùng sẽ không thể đăng nhập vào bất kỳ tenant nào cho tới khi được mở lại.',
            onConfirm: async (reason) => {
                await suspend.mutateAsync({ id: userId, reason });
                message.success('Đã khoá.');
                onClose();
            },
        });
    };

    const onReactivate = () => {
        confirmWithReason({
            title: 'Mở lại người dùng này?',
            okText: 'Mở lại',
            onConfirm: async (reason) => {
                await reactivate.mutateAsync({ id: userId, reason });
                message.success('Đã kích hoạt lại.');
                onClose();
            },
        });
    };

    return (
        <Drawer
            open
            width={460}
            title={`Người dùng: ${data.name}`}
            onClose={onClose}
            destroyOnHidden
        >
            <Form
                layout="vertical"
                form={form}
                onFinish={(v) =>
                    update.mutate(
                        { id: userId, ...v },
                        {
                            onSuccess: () => {
                                message.success('Đã lưu.');
                                onClose();
                            },
                            onError: (e) => message.error(errorMessage(e)),
                        },
                    )
                }
            >
                <Form.Item name="name" label="Tên" rules={[{ required: true }]}>
                    <Input />
                </Form.Item>
                <Form.Item name="email" label="Email">
                    <Input />
                </Form.Item>

                <Typography.Paragraph type="secondary" style={{ marginBottom: 12 }}>
                    Tenant:{' '}
                    {data.tenants.length
                        ? data.tenants.map((t) => (
                              <Tag key={t.id}>
                                  {t.name} · {t.role}
                              </Tag>
                          ))
                        : <Typography.Text type="secondary">—</Typography.Text>}
                </Typography.Paragraph>

                {suspended && (
                    <Typography.Paragraph>
                        <Tag color="red">Tạm khoá</Tag> Người dùng này không thể vào tenant nào cho tới khi
                        được kích hoạt lại.
                    </Typography.Paragraph>
                )}

                <Descriptions title="Lượt gọi AI" column={1} size="small" style={{ marginTop: 16, marginBottom: 16 }}>
                    <Descriptions.Item label="Tổng">{aiUsage.data?.all_time ?? 0}</Descriptions.Item>
                    {(aiUsage.data?.by_feature ?? []).map((f) => (
                        <Descriptions.Item key={f.feature} label={f.feature}>{f.count}</Descriptions.Item>
                    ))}
                </Descriptions>

                <Space wrap>
                    <Button type="primary" htmlType="submit" loading={update.isPending}>
                        Lưu
                    </Button>

                    <Popconfirm
                        title="Đặt mật khẩu mới"
                        description={
                            <div style={{ width: 220 }}>
                                <Input.Password
                                    placeholder="Mật khẩu mới (≥ 8)"
                                    value={newPassword}
                                    onChange={(e) => setNewPassword(e.target.value)}
                                />
                            </div>
                        }
                        okText="Đổi"
                        cancelText="Huỷ"
                        onConfirm={() => {
                            if (newPassword.length < 8) {
                                message.error('Mật khẩu phải ≥ 8 ký tự.');
                                return;
                            }
                            reset.mutate(
                                { id: userId, password: newPassword },
                                {
                                    onSuccess: () => {
                                        message.success('Đã đổi mật khẩu.');
                                        setNewPassword('');
                                    },
                                    onError: (e) => message.error(errorMessage(e)),
                                },
                            );
                        }}
                    >
                        <Button>Đặt lại mật khẩu</Button>
                    </Popconfirm>

                    {suspended ? (
                        <Button onClick={onReactivate} loading={reactivate.isPending}>
                            Mở lại
                        </Button>
                    ) : (
                        <Button danger onClick={onSuspend} loading={suspend.isPending}>
                            Tạm khoá
                        </Button>
                    )}
                </Space>
            </Form>
        </Drawer>
    );
}
```

- [ ] **Step 2: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: succeeds. This is the last of the Task 2/4/5 dependency chain — if this passes, Task 2's
Step 4 commit can now happen too (see Task 2's note).

- [ ] **Step 3: Manual browser verification**

Requires Task 1 deployed. Log into `/admin/users`, on the "Người dùng" (tenant users) tab, click any
row to open `TenantUserDrawer`, then:
1. On slow network (devtools → Network → Slow 3G, or just watch closely on fast network) confirm
   the drawer opens showing a centered `Spin` spinner first, THEN the actual form fields — not a
   blank/empty form that populates a moment later (the bug this step fixes).
2. Confirm the two action buttons read "Tạm khoá"/"Mở lại" and reset-password reads "Đặt lại mật
   khẩu" — no English.
3. Click "Tạm khoá". Confirm the modal shows the warning text ("Người dùng sẽ không thể đăng nhập
   vào bất kỳ tenant nào...") above the reason field, red confirm button. Submit with a valid
   reason — confirm success, drawer closes, row shows "Tạm khoá" tag in the list.
4. Re-open that user, click "Mở lại". Confirm reason is required here too, submit, confirm the
   user is reactivated.
5. Open browser devtools console — confirm zero errors, including no React "Rendered fewer hooks
   than expected" warning (would indicate a hooks-order bug from the new early return — the plan's
   Step 1 code keeps every hook call before both early returns, so this should not occur, but verify
   it in practice).

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/admin/pages/users/TenantUserDrawer.tsx
git commit -m "feat(admin): tenant user drawer dùng useReasonConfirm + Spin loading + nhãn tiếng Việt"
```

(If Task 2's commit was held per its own note, stage and commit it together with — or immediately
after — this one now that the full chain typechecks.)

---

### Task 6: `AdminInvoicesPage.tsx` — `Segmented` → `Radio.Group` + Vietnamese `Empty` state

**Files:**
- Modify: `app/resources/js/admin/pages/tenants/AdminInvoicesPage.tsx`

**Interfaces:**
- Consumes: nothing new — `antd` (`Radio`, `Empty` added to the existing import list).
- Produces: nothing consumed elsewhere.

Two independent fixes per spec §5.5: the status filter uses `Segmented` (spec: standardize on
`Radio.Group`), and the main invoices `Table` has no `locale.emptyText` override (falls back to
antd's English default). The nested `payments` `Table` inside the detail `Drawer` already has
`locale={{ emptyText: 'Chưa có lần thanh toán nào' }}` — a custom Vietnamese string, not the antd
default — so it already satisfies the intent of §5.5 and is **left unchanged** here.

- [ ] **Step 1: Swap `Segmented` for `Radio.Group`, add `Empty` to the main table**

In `app/resources/js/admin/pages/tenants/AdminInvoicesPage.tsx`, change:
```tsx
import { Card, DatePicker, Drawer, Input, Segmented, Space, Table, Tag, Typography } from 'antd';
```
to:
```tsx
import { Card, DatePicker, Drawer, Empty, Input, Radio, Space, Table, Tag, Typography } from 'antd';
```

Change:
```tsx
                <Space style={{ marginBottom: 12 }} wrap>
                    <Segmented options={STATUS_OPTIONS} value={status} onChange={(v) => { setStatus(v as string); setPage(1); }} />
                    <TenantPicker value={tenantId} onChange={(v) => { setTenantId(v); setPage(1); }} placeholder="Tenant (mã/tên/email)" style={{ width: 220 }} />
```
to:
```tsx
                <Space style={{ marginBottom: 12 }} wrap>
                    <Radio.Group
                        options={STATUS_OPTIONS}
                        optionType="button"
                        buttonStyle="solid"
                        value={status}
                        onChange={(e) => { setStatus(e.target.value as string); setPage(1); }}
                    />
                    <TenantPicker value={tenantId} onChange={(v) => { setTenantId(v); setPage(1); }} placeholder="Tenant (mã/tên/email)" style={{ width: 220 }} />
```

Change:
```tsx
                <Table
                    rowKey="id" size="small"
                    loading={isFetching}
                    columns={columns}
                    dataSource={data?.data ?? []}
                    pagination={{ current: page, pageSize: 20, total: data?.meta.pagination.total ?? 0, showSizeChanger: false, onChange: setPage }}
                />
```
to:
```tsx
                <Table
                    rowKey="id" size="small"
                    loading={isFetching}
                    columns={columns}
                    dataSource={data?.data ?? []}
                    pagination={{ current: page, pageSize: 20, total: data?.meta.pagination.total ?? 0, showSizeChanger: false, onChange: setPage }}
                    locale={{ emptyText: <Empty description="Không có hoá đơn nào khớp bộ lọc." /> }}
                />
```

- [ ] **Step 2: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: succeeds.

- [ ] **Step 3: Manual browser verification**

Log into `/admin/invoices`, then:
1. Confirm the status filter now renders as button-style radios ("Tất cả" / "Chờ" /
   "Đã thanh toán" / "Hủy" / "Hoàn tiền"), not the pill-style `Segmented` control.
2. Click through 2-3 different status options — confirm the table filters correctly (same behavior
   as before, only the control's visual style changed).
3. Filter to a status/tenant combination with zero results (e.g. search a tenant with no invoices,
   or a narrow date range) — confirm the table shows "Không có hoá đơn nào khớp bộ lọc." instead of
   antd's default "No data".
4. Open any invoice's detail Drawer — confirm the nested "Các lần thanh toán" table (unchanged)
   still shows "Chưa có lần thanh toán nào" for invoices with no payments.
5. Open browser devtools console — confirm zero errors.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/admin/pages/tenants/AdminInvoicesPage.tsx
git commit -m "fix(admin): lịch sử thanh toán dùng Radio.Group + Empty tiếng Việt"
```

---

### Task 7: `AdminPlansPage.tsx` — plan edit `Modal` → `Drawer`

**Files:**
- Modify: `app/resources/js/admin/pages/tenants/AdminPlansPage.tsx`

**Interfaces:**
- Consumes: nothing new — `antd` (`Drawer` replaces `Modal` in the import list).
- Produces: nothing consumed elsewhere.

Per spec §5.2, `Drawer` is the default "for anything with more than ~3 fields or that also displays
existing record detail." The current `PlanModal` edits an existing plan record and has 12 fields
(name, description, 4 numeric price/limit/trial/sort fields, 5 more numeric limit fields, an
active-toggle, and a multi-tag feature-flags block) — a clear Modal-vs-Drawer violation, unlike
`AdminVouchersPage`'s create-voucher Modal (Task-adjacent note below) which is a small, single-
purpose *create* action, not a detail+edit surface. `ProTrialConfigCard`'s inline `Card` form is
**not** touched here — it's a standalone settings block (not a per-record CRUD action), and it isn't
one of the three pages spec §5.2 explicitly names for inline-Card removal (those are Phase 2c).

- [ ] **Step 1: Rewrite `PlanModal` as `PlanDrawer`**

In `app/resources/js/admin/pages/tenants/AdminPlansPage.tsx`, change the import line:
```tsx
import { App, Button, Card, DatePicker, Form, Input, InputNumber, Modal, Space, Switch, Table, Tag, Typography } from 'antd';
```
to:
```tsx
import { App, Button, Card, DatePicker, Drawer, Form, Input, InputNumber, Space, Switch, Table, Tag, Typography } from 'antd';
```

Change the call site in `AdminPlansPage()`:
```tsx
            <PlanModal
                open={editing != null}
                plan={editing}
                onClose={() => setEditing(null)}
            />
```
to:
```tsx
            <PlanDrawer
                open={editing != null}
                plan={editing}
                onClose={() => setEditing(null)}
            />
```

Change the whole `PlanModal` function (current lines 181-269) from:
```tsx
function PlanModal({ open, plan, onClose }: { open: boolean; plan: AdminPlan | null; onClose: () => void }) {
    const { message } = App.useApp();
    const update = useAdminUpdatePlan();
    const [form] = Form.useForm();

    // `form` (từ useForm) tồn tại xuyên suốt giữa các lần mở modal, nên `initialValues`
    // chỉ áp 1 lần → không cập nhật theo plan mới chọn. Chủ động nạp lại mỗi khi mở.
    useEffect(() => {
        if (open && plan) {
            form.setFieldsValue(valuesFromPlan(plan));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, plan?.id, form]);

    if (!plan) return null;

    const initialValues = valuesFromPlan(plan);

    const submit = (v: Record<string, unknown>) => {
        update.mutate({
            id: plan.id,
            name: v.name as string, description: v.description as string, is_active: v.is_active as boolean,
            sort_order: v.sort_order as number,
            price_monthly: v.price_monthly as number, price_yearly: v.price_yearly as number, trial_days: v.trial_days as number,
            limits: {
                max_channel_accounts: v.max_channel_accounts as number,
                max_channel_accounts_per_platform: v.max_channel_accounts_per_platform as number,
                ai_credits_monthly: v.ai_credits_monthly as number,
                messaging_ai_replies_monthly: v.messaging_ai_replies_monthly as number,
                messaging_media_mb_daily: v.messaging_media_mb_daily as number,
            },
            features: v.features as Record<string, boolean>,
        }, {
            onSuccess: () => { message.success('Đã cập nhật gói.'); onClose(); },
            onError: (e: unknown) => message.error(errorMessage(e, 'Không cập nhật được.')),
        });
    };

    return (
        <Modal
            title={`Sửa gói: ${plan.code}`}
            open={open}
            onCancel={onClose}
            onOk={() => form.submit()}
            okText="Lưu"
            cancelText="Huỷ"
            confirmLoading={update.isPending}
            destroyOnClose
            width={620}
        >
            {open && (
                <Form form={form} layout="vertical" initialValues={initialValues} onFinish={submit}>
                    <Form.Item name="name" label="Tên hiển thị" rules={[{ required: true }]}>
                        <Input />
                    </Form.Item>
                    <Form.Item name="description" label="Mô tả">
                        <Input.TextArea rows={2} />
                    </Form.Item>
                    <Space wrap>
                        <Form.Item name="price_monthly" label="Giá tháng (VND)"><InputNumber style={{ width: 150 }} min={0} /></Form.Item>
                        <Form.Item name="price_yearly" label="Giá năm (VND)"><InputNumber style={{ width: 150 }} min={0} /></Form.Item>
                        <Form.Item name="trial_days" label="Trial (ngày)"><InputNumber style={{ width: 100 }} min={0} max={365} /></Form.Item>
                        <Form.Item name="sort_order" label="Thứ tự"><InputNumber style={{ width: 90 }} min={0} /></Form.Item>
                    </Space>
                    <Typography.Text type="secondary">Hạn mức — đặt <b>-1</b> để không giới hạn. "Gian hàng / nền tảng" áp cho <b>từng</b> nền tảng: TikTok, Shopee, Lazada và Facebook Page (vd 2 = tối đa 2 gian hàng mỗi nền tảng).</Typography.Text>
                    <Space wrap style={{ marginTop: 8 }}>
                        <Form.Item name="max_channel_accounts" label="Số gian hàng (tổng)"><InputNumber style={{ width: 140 }} min={-1} /></Form.Item>
                        <Form.Item name="max_channel_accounts_per_platform" label="Gian hàng / nền tảng"><InputNumber style={{ width: 150 }} min={-1} /></Form.Item>
                        <Form.Item name="ai_credits_monthly" label="Lượt AI tặng / kỳ"><InputNumber style={{ width: 140 }} min={-1} /></Form.Item>
                        <Form.Item name="messaging_ai_replies_monthly" label="AI reply / tháng"><InputNumber style={{ width: 140 }} min={-1} /></Form.Item>
                        <Form.Item name="messaging_media_mb_daily" label="Media MB / ngày"><InputNumber style={{ width: 140 }} min={-1} /></Form.Item>
                    </Space>
                    <Form.Item name="is_active" label="Đang bán?" valuePropName="checked">
                        <Switch />
                    </Form.Item>
                    <Form.Item label="Tính năng nâng cao">
                        <Space wrap>
                            {KNOWN_FEATURES.map((k) => (
                                <Form.Item key={k} name={['features', k]} valuePropName="checked" noStyle>
                                    <FeatureToggle name={k} />
                                </Form.Item>
                            ))}
                        </Space>
                    </Form.Item>
                </Form>
            )}
        </Modal>
    );
}
```
to:
```tsx
function PlanDrawer({ open, plan, onClose }: { open: boolean; plan: AdminPlan | null; onClose: () => void }) {
    const { message } = App.useApp();
    const update = useAdminUpdatePlan();
    const [form] = Form.useForm();

    // `form` (từ useForm) tồn tại xuyên suốt giữa các lần mở drawer, nên `initialValues`
    // chỉ áp 1 lần → không cập nhật theo plan mới chọn. Chủ động nạp lại mỗi khi mở.
    useEffect(() => {
        if (open && plan) {
            form.setFieldsValue(valuesFromPlan(plan));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, plan?.id, form]);

    if (!plan) return null;

    const initialValues = valuesFromPlan(plan);

    const submit = (v: Record<string, unknown>) => {
        update.mutate({
            id: plan.id,
            name: v.name as string, description: v.description as string, is_active: v.is_active as boolean,
            sort_order: v.sort_order as number,
            price_monthly: v.price_monthly as number, price_yearly: v.price_yearly as number, trial_days: v.trial_days as number,
            limits: {
                max_channel_accounts: v.max_channel_accounts as number,
                max_channel_accounts_per_platform: v.max_channel_accounts_per_platform as number,
                ai_credits_monthly: v.ai_credits_monthly as number,
                messaging_ai_replies_monthly: v.messaging_ai_replies_monthly as number,
                messaging_media_mb_daily: v.messaging_media_mb_daily as number,
            },
            features: v.features as Record<string, boolean>,
        }, {
            onSuccess: () => { message.success('Đã cập nhật gói.'); onClose(); },
            onError: (e: unknown) => message.error(errorMessage(e, 'Không cập nhật được.')),
        });
    };

    return (
        <Drawer
            title={`Sửa gói: ${plan.code}`}
            open={open}
            onClose={onClose}
            width={620}
            destroyOnHidden
        >
            <Form form={form} layout="vertical" initialValues={initialValues} onFinish={submit}>
                <Form.Item name="name" label="Tên hiển thị" rules={[{ required: true }]}>
                    <Input />
                </Form.Item>
                <Form.Item name="description" label="Mô tả">
                    <Input.TextArea rows={2} />
                </Form.Item>
                <Space wrap>
                    <Form.Item name="price_monthly" label="Giá tháng (VND)"><InputNumber style={{ width: 150 }} min={0} /></Form.Item>
                    <Form.Item name="price_yearly" label="Giá năm (VND)"><InputNumber style={{ width: 150 }} min={0} /></Form.Item>
                    <Form.Item name="trial_days" label="Trial (ngày)"><InputNumber style={{ width: 100 }} min={0} max={365} /></Form.Item>
                    <Form.Item name="sort_order" label="Thứ tự"><InputNumber style={{ width: 90 }} min={0} /></Form.Item>
                </Space>
                <Typography.Text type="secondary">Hạn mức — đặt <b>-1</b> để không giới hạn. "Gian hàng / nền tảng" áp cho <b>từng</b> nền tảng: TikTok, Shopee, Lazada và Facebook Page (vd 2 = tối đa 2 gian hàng mỗi nền tảng).</Typography.Text>
                <Space wrap style={{ marginTop: 8 }}>
                    <Form.Item name="max_channel_accounts" label="Số gian hàng (tổng)"><InputNumber style={{ width: 140 }} min={-1} /></Form.Item>
                    <Form.Item name="max_channel_accounts_per_platform" label="Gian hàng / nền tảng"><InputNumber style={{ width: 150 }} min={-1} /></Form.Item>
                    <Form.Item name="ai_credits_monthly" label="Lượt AI tặng / kỳ"><InputNumber style={{ width: 140 }} min={-1} /></Form.Item>
                    <Form.Item name="messaging_ai_replies_monthly" label="AI reply / tháng"><InputNumber style={{ width: 140 }} min={-1} /></Form.Item>
                    <Form.Item name="messaging_media_mb_daily" label="Media MB / ngày"><InputNumber style={{ width: 140 }} min={-1} /></Form.Item>
                </Space>
                <Form.Item name="is_active" label="Đang bán?" valuePropName="checked">
                    <Switch />
                </Form.Item>
                <Form.Item label="Tính năng nâng cao">
                    <Space wrap>
                        {KNOWN_FEATURES.map((k) => (
                            <Form.Item key={k} name={['features', k]} valuePropName="checked" noStyle>
                                <FeatureToggle name={k} />
                            </Form.Item>
                        ))}
                    </Space>
                </Form.Item>
                <Form.Item>
                    <Button type="primary" htmlType="submit" loading={update.isPending}>Lưu</Button>
                </Form.Item>
            </Form>
        </Drawer>
    );
}
```
(Dropping the `{open && (...)}` guard around the `<Form>` is intentional and safe: `Drawer` with
`destroyOnHidden` already unmounts its children when closed, so the extra guard was redundant; the
`if (!plan) return null;` line above still prevents rendering with no plan selected.)

- [ ] **Step 2: Typecheck, lint, build**

```bash
npm run typecheck && npm run lint && npm run build
```
Expected: succeeds.

- [ ] **Step 3: Manual browser verification**

Log into `/admin/plans`, then:
1. Click "Sửa" on any plan row. Confirm a right-side `Drawer` slides in (not a centered `Modal`)
   titled "Sửa gói: `<code>`", width ~620px.
2. Confirm all fields are present and pre-filled with the plan's current values (name, description,
   4 price/limit fields, the 5 numeric limit fields, "Đang bán?" switch, feature tags).
3. Toggle a feature tag, change a price, click "Lưu" (now a plain `Button` inside the form, not a
   Drawer footer button). Confirm success toast, drawer closes, and the table row reflects the
   change.
4. Open a second plan without reloading the page — confirm the drawer's fields update to the new
   plan's values (not stale values from the previous one — this exercises the `useEffect`'s
   `[open, plan?.id, form]` re-sync, unchanged from before).
5. Confirm the "Chế độ trải nghiệm Pro" inline card above the table is visually unchanged (this task
   does not touch it).
6. Open browser devtools console — confirm zero errors.

- [ ] **Step 4: Commit**

```bash
git add app/resources/js/admin/pages/tenants/AdminPlansPage.tsx
git commit -m "fix(admin): sửa gói dùng Drawer thay Modal (nhiều trường + xem chi tiết)"
```

---

## Note: `AdminVouchersPage.tsx` requires no changes in this phase

Read in full before writing this plan (`app/resources/js/admin/pages/tenants/AdminVouchersPage.tsx`).
Two things were explicitly re-audited against spec §5:

- **Disable-voucher `modal.confirm`** (inside the `columns` definition, `onClick` on the "Vô hiệu
  hoá" button): no reason field, plain yes/no. This is correct as-is — spec §5.1 lists "disable
  voucher" by name as a **standard**-tier example (no reason required), not high-impact. Leaving it
  a plain `modal.confirm` (or `Popconfirm`) is the spec-compliant choice, not an oversight.
- **`CreateVoucherModal`** (a `Modal`, ~8 fields) vs. **`VoucherDetailDrawer`**'s grant-to-tenant
  sub-`Modal` (`reason` field already uses declarative `rules={[{ required: true, min: 10 }]}` —
  already the correct pattern, nothing to fix): per spec §5.2, `Modal` is for "genuinely small,
  single-purpose actions." `CreateVoucherModal` creates one new record in one atomic step — it does
  not also display/edit an *existing* record's full detail (that's `VoucherDetailDrawer`'s job,
  already a `Drawer`). Its field count is on the high side for a "quick create," but it's a single-
  purpose creation flow, not a detail+edit surface, so it does not trigger the Modal-vs-Drawer rule
  the way `AdminPlansPage`'s edit-existing-record Modal did (Task 7). Converting it to a Drawer would
  be a cosmetic-only change with no interaction-pattern benefit — skipped per this plan's YAGNI
  instruction.

No code changes were made to this file.

---

## Phase 2a self-review checklist

- Every high-impact confirm the spec's §5.1 names for this page cluster is covered: tenant suspend
  (Task 3), channel delete (Task 3), AI credit adjust (Task 3), tenant change-plan (Task 3, kept
  bespoke per spec's own carve-out), admin-user suspend/reactivate (Task 4), tenant-user
  suspend/reactivate (Task 5). Tenant *reactivate* and voucher *disable* were deliberately left at
  standard tier — cross-checked against spec wording and existing backend `reason` support, not
  assumed.
- No frontend call site was promoted to `useReasonConfirm` without first confirming (or, for user
  accounts, adding in Task 1) that the backend endpoint it calls actually accepts and persists a
  `reason` — Task 1 exists specifically because two endpoint pairs didn't.
- `useReasonConfirm`'s `ReasonConfirmOptions` interface was used exactly as Phase 0 defined it —
  no field was added or renamed to fit a call site; where the interface didn't fit (change-plan's
  extra fields), the call site stayed a bespoke `Modal` instead of forcing a mismatch.
- Every page in scope (`AdminTenantDetailPage.tsx`, `AdminUserFormDrawer.tsx`,
  `TenantUserDrawer.tsx`, `AdminVouchersPage.tsx`, `AdminPlansPage.tsx`, `AdminInvoicesPage.tsx`)
  was read in full before any change was planned for it — `AdminVouchersPage.tsx` was read and
  found to need no changes, documented above rather than silently skipped or given busywork edits.
- `SecretInput` does not appear anywhere in this plan — confirmed none of the six pages in this
  batch has a credential/API-key field (per the task brief's explicit correction from an earlier,
  wrong audit pass).
- Every frontend task's manual verification script has concrete numbered click-through steps (no
  "test the change" placeholders); the one backend task (Task 1) has real PHPUnit assertions, not
  TODO stubs.
- Every `useReasonConfirm`/mutation-hook code sample in this plan is the complete function, not a
  "similar to the pattern above" reference — each was derived from an actual `Read` of the current
  file content (captured while writing this plan) with the exact diff applied on top.
- Task ordering documents the real dependency chain (Task 1 → Task 2 → Tasks 4/5 must land together
  for the frontend build to stay green) rather than presenting 7 independent tasks that would break
  the build if executed out of order.
