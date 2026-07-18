# Admin Invoice/Payment History Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a global, cross-tenant, filterable "Lịch sử thanh toán" (payment history) screen to the admin panel — a paginated list of every `Invoice` across all tenants (including ones still `pending` = opened but not completed), with a detail drawer showing the payment attempts tied to each invoice.

**Architecture:** One new read-only backend endpoint (`GET /api/v1/admin/invoices`) added to the existing `AdminTenantController` (which already owns the invoice/payment admin logic — `createInvoice`/`markInvoicePaid`/`refundPayment`/`invoiceResource()`), following the exact filter/pagination/response-envelope pattern already used by `AdminAuditLogController::index`. One new FE page (`AdminInvoicesPage.tsx`) that is a structural copy of the existing `AdminAuditLogsPage.tsx` (filter bar → paginated Table → Drawer detail), wired into the admin router and sidebar.

**Tech Stack:** Laravel 11 (PHP), PHPUnit, React 18 + Ant Design + TanStack Query (admin SPA, `resources/js/admin/`).

## Global Constraints

- Design: `docs/superpowers/specs/2026-07-18-admin-invoice-payment-history-design.md` — every behavior below traces back to it.
- **View-only.** No mark-paid/refund actions in this feature (those endpoints already exist elsewhere, unused by this page).
- No new tables/migrations — reuses `invoices`/`payments` as-is.
- No new RBAC/permission check — the existing sibling admin billing endpoints (`createInvoice`/`markInvoicePaid`/`refundPayment`) only require the `auth:admin_web` middleware group (any authenticated admin), no finer-grained `billing.view` gate exists anywhere in this codebase today. Match that — do not invent a new permission check.
- Response envelope: `{ data: [...], meta: { pagination: { page, per_page, total, total_pages } } }` — identical shape to every other admin list endpoint (`AdminAuditLogController`, `AdminVoucherController`).
- Query cross-tenant tables with `withoutGlobalScope(TenantScope::class)` (this endpoint is intentionally cross-tenant, mirrors `AdminAuditLogController`/`AdminTenantController::show`).
- All PHP commands run from `app/` (`cd app` first). PSR-4 `CMBcoreSeller\` maps to `app/app/`.
- Quality gate before calling any task "done": `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `php artisan test --filter=<relevant>`; frontend: `npm run typecheck && npm run lint && npm run build`.
- User-facing strings Vietnamese; code/identifiers English.
- Money fields display as `new Intl.NumberFormat('vi-VN').format(v) + ' đ'` (existing convention, see `AdminVouchersPage.tsx`).

---

### Task 1: Backend — `GET /api/v1/admin/invoices`

**Files:**
- Modify: `app/app/Modules/Admin/Http/Controllers/AdminTenantController.php` (add one public method, reuse existing `private function invoiceResource()` at line 451)
- Modify: `app/app/Modules/Admin/Http/routes.php` (add one route)
- Test: `app/tests/Feature/Admin/AdminInvoiceHistoryTest.php`

**Interfaces:**
- Produces: `GET /api/v1/admin/invoices?status=&tenant_id=&q=&date_from=&date_to=&page=&per_page=` → `{ data: Array<AdminInvoiceRow>, meta: { pagination } }` where `AdminInvoiceRow` = the existing `invoiceResource()` shape (`id, code, status, subtotal, total, currency, due_at, paid_at, period_start, period_end, meta, created_at`) plus `tenant_id: int`, `tenant: {id,name,slug}|null`, `payments: Array<{id, invoice_id, gateway, amount, status, occurred_at, refunded_at}>`.
- Consumed by: Task 2/3 (frontend hook + page).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Admin;

use CMBcoreSeller\Models\AdminUser;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Payment;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lịch sử thanh toán hóa đơn (admin, xuyên tenant) — 2026-07-18.
 */
class AdminInvoiceHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private Subscription $subA;

    private Subscription $subB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $plan = Plan::query()->where('code', 'starter')->first();

        $ownerA = User::factory()->create();
        $this->tenantA = Tenant::create(['name' => 'Shop A']);
        $this->tenantA->users()->attach($ownerA->getKey(), ['role' => Role::Owner->value]);
        $this->subA = Subscription::query()->create([
            'tenant_id' => $this->tenantA->getKey(), 'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => 'monthly',
            'current_period_start' => now(), 'current_period_end' => now()->addDays(30),
        ]);

        $ownerB = User::factory()->create();
        $this->tenantB = Tenant::create(['name' => 'Shop B']);
        $this->tenantB->users()->attach($ownerB->getKey(), ['role' => Role::Owner->value]);
        $this->subB = Subscription::query()->create([
            'tenant_id' => $this->tenantB->getKey(), 'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => 'monthly',
            'current_period_start' => now(), 'current_period_end' => now()->addDays(30),
        ]);
    }

    private function makeInvoice(Tenant $tenant, Subscription $sub, string $code, string $status, ?\Illuminate\Support\Carbon $createdAt = null): Invoice
    {
        $invoice = Invoice::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->getKey(), 'subscription_id' => $sub->getKey(),
            'code' => $code, 'status' => $status,
            'period_start' => now()->toDateString(), 'period_end' => now()->addDays(30)->toDateString(),
            'subtotal' => 190_000, 'tax' => 0, 'total' => 190_000, 'currency' => 'VND',
            'due_at' => now()->addDays(3),
        ]);
        if ($createdAt) {
            $invoice->forceFill(['created_at' => $createdAt])->save();
        }

        return $invoice;
    }

    public function test_admin_lists_invoices_across_all_tenants(): void
    {
        $admin = AdminUser::factory()->create();
        $this->makeInvoice($this->tenantA, $this->subA, 'INV-A-1', Invoice::STATUS_PENDING);
        $this->makeInvoice($this->tenantB, $this->subB, 'INV-B-1', Invoice::STATUS_PAID);

        $res = $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/invoices')->assertOk();

        $codes = collect($res->json('data'))->pluck('code')->all();
        $this->assertContains('INV-A-1', $codes);
        $this->assertContains('INV-B-1', $codes);
        $this->assertSame(2, $res->json('meta.pagination.total'));
    }

    public function test_regular_user_cannot_access(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web')->getJson('/api/v1/admin/invoices')->assertStatus(401);
    }

    public function test_filters_by_status(): void
    {
        $admin = AdminUser::factory()->create();
        $this->makeInvoice($this->tenantA, $this->subA, 'INV-PENDING', Invoice::STATUS_PENDING);
        $this->makeInvoice($this->tenantA, $this->subA, 'INV-PAID', Invoice::STATUS_PAID);

        $res = $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/invoices?status=pending')->assertOk();

        $codes = collect($res->json('data'))->pluck('code')->all();
        $this->assertSame(['INV-PENDING'], $codes);
    }

    public function test_filters_by_tenant_id(): void
    {
        $admin = AdminUser::factory()->create();
        $this->makeInvoice($this->tenantA, $this->subA, 'INV-A-ONLY', Invoice::STATUS_PENDING);
        $this->makeInvoice($this->tenantB, $this->subB, 'INV-B-ONLY', Invoice::STATUS_PENDING);

        $res = $this->actingAs($admin, 'admin_web')
            ->getJson('/api/v1/admin/invoices?tenant_id='.$this->tenantA->getKey())->assertOk();

        $codes = collect($res->json('data'))->pluck('code')->all();
        $this->assertSame(['INV-A-ONLY'], $codes);
        $this->assertSame($this->tenantA->getKey(), $res->json('data.0.tenant.id'));
        $this->assertSame('Shop A', $res->json('data.0.tenant.name'));
    }

    public function test_filters_by_code_search(): void
    {
        $admin = AdminUser::factory()->create();
        $this->makeInvoice($this->tenantA, $this->subA, 'INV-202607-0001', Invoice::STATUS_PENDING);
        $this->makeInvoice($this->tenantA, $this->subA, 'INV-202606-0001', Invoice::STATUS_PENDING);

        $res = $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/invoices?q=202607')->assertOk();

        $codes = collect($res->json('data'))->pluck('code')->all();
        $this->assertSame(['INV-202607-0001'], $codes);
    }

    public function test_filters_by_created_date_range(): void
    {
        $admin = AdminUser::factory()->create();
        $this->makeInvoice($this->tenantA, $this->subA, 'INV-OLD', Invoice::STATUS_PENDING, now()->subDays(30));
        $this->makeInvoice($this->tenantA, $this->subA, 'INV-NEW', Invoice::STATUS_PENDING, now());

        $res = $this->actingAs($admin, 'admin_web')
            ->getJson('/api/v1/admin/invoices?date_from='.now()->subDays(2)->toIso8601String())->assertOk();

        $codes = collect($res->json('data'))->pluck('code')->all();
        $this->assertSame(['INV-NEW'], $codes);
    }

    public function test_payments_are_scoped_to_their_own_invoice_not_leaked_across_tenants(): void
    {
        $admin = AdminUser::factory()->create();
        $invoiceA = $this->makeInvoice($this->tenantA, $this->subA, 'INV-A-PAY', Invoice::STATUS_PAID);
        $invoiceB = $this->makeInvoice($this->tenantB, $this->subB, 'INV-B-PAY', Invoice::STATUS_PAID);

        Payment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenantA->getKey(), 'invoice_id' => $invoiceA->getKey(),
            'gateway' => Payment::GATEWAY_SEPAY, 'external_ref' => 'REF-A', 'amount' => 190_000,
            'status' => Payment::STATUS_SUCCEEDED, 'occurred_at' => now(),
        ]);
        Payment::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $this->tenantB->getKey(), 'invoice_id' => $invoiceB->getKey(),
            'gateway' => Payment::GATEWAY_VNPAY, 'external_ref' => 'REF-B', 'amount' => 190_000,
            'status' => Payment::STATUS_SUCCEEDED, 'occurred_at' => now(),
        ]);

        $res = $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/invoices')->assertOk();

        $rows = collect($res->json('data'))->keyBy('code');
        $this->assertCount(1, $rows['INV-A-PAY']['payments']);
        $this->assertSame('REF-A', $rows['INV-A-PAY']['payments'][0]['external_ref'] ?? null, 'field not exposed is fine, but if present must not mix — see next assertion');
        $this->assertSame(Payment::GATEWAY_SEPAY, $rows['INV-A-PAY']['payments'][0]['gateway']);
        $this->assertCount(1, $rows['INV-B-PAY']['payments']);
        $this->assertSame(Payment::GATEWAY_VNPAY, $rows['INV-B-PAY']['payments'][0]['gateway']);
    }

    public function test_pagination_meta_shape(): void
    {
        $admin = AdminUser::factory()->create();
        for ($i = 1; $i <= 3; $i++) {
            $this->makeInvoice($this->tenantA, $this->subA, 'INV-PAGE-'.$i, Invoice::STATUS_PENDING);
        }

        $res = $this->actingAs($admin, 'admin_web')->getJson('/api/v1/admin/invoices?per_page=2')->assertOk();

        $this->assertCount(2, $res->json('data'));
        $this->assertSame(1, $res->json('meta.pagination.page'));
        $this->assertSame(2, $res->json('meta.pagination.per_page'));
        $this->assertSame(3, $res->json('meta.pagination.total'));
        $this->assertSame(2, $res->json('meta.pagination.total_pages'));
    }
}
```

**Note on `test_payments_are_scoped_to_their_own_invoice_not_leaked_across_tenants`:** the assertion referencing `external_ref` is deliberately checking a field the controller's response MAY OR MAY NOT expose — the real assertions that matter are the `gateway` checks right after it (which prove no cross-tenant/cross-invoice mixing). If `external_ref` isn't in the response shape, delete that one assertion line rather than adding the field just to satisfy it — it's not part of the required response shape below.

- [ ] **Step 2: Run test to verify it fails**

Run: `cd app && php artisan test --filter=AdminInvoiceHistoryTest`
Expected: FAIL — route `admin.invoices.index` / method `AdminTenantController::invoices` doesn't exist yet (404 or method-not-found).

- [ ] **Step 3: Write the implementation**

In `app/app/Modules/Admin/Http/Controllers/AdminTenantController.php`, add this public method. Place it right before the existing `private function invoiceResource(Invoice $invoice): array` method (around line 451), so it can call that helper directly:

```php
    /**
     * GET /api/v1/admin/invoices — lịch sử thanh toán hóa đơn xuyên tenant, có lọc (2026-07-18).
     * `status=pending` = hóa đơn đã mở yêu cầu thanh toán nhưng chưa hoàn thành.
     */
    public function invoices(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));

        $query = Invoice::query()->withoutGlobalScope(TenantScope::class)->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($tenantId = $request->query('tenant_id')) {
            $query->where('tenant_id', (int) $tenantId);
        }
        if ($q = $request->query('q')) {
            $query->where('code', 'like', '%'.$q.'%');
        }
        if ($from = $request->query('date_from')) {
            $query->where('created_at', '>=', $from);
        }
        if ($to = $request->query('date_to')) {
            $query->where('created_at', '<=', $to);
        }

        $page = $query->with('payments')->paginate($perPage);

        $tenantIds = collect($page->items())->pluck('tenant_id')->unique()->values();
        $tenants = Tenant::query()->whereIn('id', $tenantIds)->get(['id', 'name', 'slug'])->keyBy('id');

        $rows = collect($page->items())->map(function (Invoice $invoice) use ($tenants) {
            $t = $tenants->get($invoice->tenant_id);

            return array_merge($this->invoiceResource($invoice), [
                'tenant_id' => $invoice->tenant_id,
                'tenant' => $t ? ['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug] : null,
                'payments' => $invoice->payments->map(fn (Payment $p) => [
                    'id' => $p->id, 'invoice_id' => $p->invoice_id,
                    'gateway' => $p->gateway, 'amount' => $p->amount, 'status' => $p->status,
                    'occurred_at' => $p->occurred_at?->toIso8601String(),
                    'refunded_at' => $p->refunded_at?->toIso8601String(),
                ])->all(),
            ]);
        })->all();

        return response()->json([
            'data' => $rows,
            'meta' => ['pagination' => [
                'page' => $page->currentPage(), 'per_page' => $page->perPage(),
                'total' => $page->total(), 'total_pages' => $page->lastPage(),
            ]],
        ]);
    }

```

Check the top of the file already imports `Payment` and `Tenant` (it does — both are used by existing methods `show()`/`refundPayment()`); no new `use` statements should be needed. If either import is missing, add `use CMBcoreSeller\Modules\Billing\Models\Payment;` / `use CMBcoreSeller\Modules\Tenancy\Models\Tenant;` alphabetically among the existing `use` block.

In `app/app/Modules/Admin/Http/routes.php`, add the route in the "Invoice & payment global (SPEC 0023)" section (around line 84-88), right before the `mark-paid` line:

```php
        // --- Invoice & payment global (SPEC 0023 + lịch sử thanh toán 2026-07-18) ---
        Route::get('invoices', [AdminTenantController::class, 'invoices'])->name('admin.invoices.index');
        Route::post('invoices/{id}/mark-paid', [AdminTenantController::class, 'markInvoicePaid'])
            ->whereNumber('id')->name('admin.invoices.mark-paid');
```

(Only change the comment header and add the one new `Route::get` line — the two existing lines below it are shown for exact placement context, don't duplicate them.)

- [ ] **Step 4: Run test to verify it passes**

Run: `cd app && php artisan test --filter=AdminInvoiceHistoryTest`
Expected: PASS (7 tests).

- [ ] **Step 5: Commit**

```bash
git add app/app/Modules/Admin/Http/Controllers/AdminTenantController.php app/app/Modules/Admin/Http/routes.php app/tests/Feature/Admin/AdminInvoiceHistoryTest.php
git commit -m "feat(billing): thêm GET /admin/invoices — lịch sử thanh toán hóa đơn xuyên tenant"
```

---

### Task 2: Frontend — types + `useAdminInvoices` hook

**Files:**
- Modify: `app/resources/js/admin/lib/admin.tsx`

**Interfaces:**
- Consumes: `GET /admin/invoices` (Task 1).
- Produces (used by Task 3): `AdminInvoiceFilters` type, `AdminInvoiceHistoryRow` type, `useAdminInvoices(filters): UseQueryResult<Paginated<AdminInvoiceHistoryRow>>`.

- [ ] **Step 1: Add the types and hook**

In `app/resources/js/admin/lib/admin.tsx`, right after the existing `AdminPayment` interface (around line 90-94), add:

```tsx
export interface AdminInvoiceHistoryRow extends AdminInvoice {
    tenant_id: number;
    tenant: { id: number; name: string; slug: string } | null;
    payments: AdminPayment[];
}

export interface AdminInvoiceFilters {
    status?: string; tenant_id?: number; q?: string;
    date_from?: string; date_to?: string;
    page?: number; per_page?: number;
}

export function useAdminInvoices(filters: AdminInvoiceFilters = {}) {
    return useQuery({
        queryKey: ['admin', 'invoices', filters],
        queryFn: async () => {
            const params: Record<string, string | number> = {};
            (Object.keys(filters) as Array<keyof AdminInvoiceFilters>).forEach((k) => {
                const v = filters[k];
                if (v != null && v !== '') params[k] = v as string | number;
            });
            const { data } = await api.get<Paginated<AdminInvoiceHistoryRow>>('/admin/invoices', { params });
            return data;
        },
        placeholderData: (p) => p, staleTime: 15_000,
    });
}
```

This mirrors `AdminAuditFilters`/`useAdminAuditLogs` exactly (same file, a few hundred lines below) — same `params`-building loop, same `placeholderData`/`staleTime` options, same `Paginated<T>` wrapper type already defined earlier in this file.

- [ ] **Step 2: Typecheck**

Run: `cd app && npm run typecheck`
Expected: 0 new errors (the file already compiles cleanly today per the established baseline — confirm no errors reference `admin.tsx`).

- [ ] **Step 3: Commit**

```bash
git add app/resources/js/admin/lib/admin.tsx
git commit -m "feat(billing): thêm useAdminInvoices hook + types cho lịch sử thanh toán"
```

---

### Task 3: Frontend — `AdminInvoicesPage.tsx` + routing/sidebar

**Files:**
- Create: `app/resources/js/admin/pages/tenants/AdminInvoicesPage.tsx`
- Modify: `app/resources/js/admin/AdminApp.tsx` (add route)
- Modify: `app/resources/js/admin/AdminLayout.tsx` (add sidebar item + icon import)

**Interfaces:**
- Consumes: `useAdminInvoices`, `AdminInvoiceHistoryRow`, `AdminInvoiceFilters` (Task 2).

- [ ] **Step 1: Create the page**

```tsx
import { useMemo, useState } from 'react';
import { Card, DatePicker, Drawer, Input, Segmented, Space, Table, Tag, Typography } from 'antd';
import type { ColumnsType } from 'antd/es/table';
import dayjs from 'dayjs';
import { PageHeader } from '@/components/PageHeader';
import { formatDateTimeSeconds } from '@/lib/format';
import { useAdminInvoices, type AdminInvoiceHistoryRow, type AdminPayment } from '@admin/lib/admin';

const { RangePicker } = DatePicker;

const STATUS_OPTIONS = [
    { value: '', label: 'Tất cả' },
    { value: 'pending', label: 'Chờ' },
    { value: 'paid', label: 'Đã thanh toán' },
    { value: 'void', label: 'Hủy' },
    { value: 'refunded', label: 'Hoàn tiền' },
];

const STATUS_COLOR: Record<string, string> = {
    pending: 'orange', paid: 'green', void: 'default', refunded: 'red',
};

function formatMoney(v: number): string {
    return new Intl.NumberFormat('vi-VN').format(v) + ' đ';
}

export function AdminInvoicesPage() {
    const [status, setStatus] = useState('');
    const [tenantId, setTenantId] = useState('');
    const [q, setQ] = useState('');
    const [range, setRange] = useState<[dayjs.Dayjs, dayjs.Dayjs] | null>(null);
    const [page, setPage] = useState(1);
    const [openRow, setOpenRow] = useState<AdminInvoiceHistoryRow | null>(null);

    const filters = useMemo(() => ({
        status: status || undefined,
        tenant_id: tenantId ? Number(tenantId) : undefined,
        q: q || undefined,
        date_from: range?.[0]?.toISOString(),
        date_to: range?.[1]?.toISOString(),
        page, per_page: 20,
    }), [status, tenantId, q, range, page]);

    const { data, isFetching } = useAdminInvoices(filters);

    const columns: ColumnsType<AdminInvoiceHistoryRow> = [
        { title: 'Mã HD', dataIndex: 'code', render: (v: string) => <span style={{ fontFamily: 'ui-monospace, monospace' }}>{v}</span> },
        {
            title: 'Shop', dataIndex: 'tenant',
            render: (t: AdminInvoiceHistoryRow['tenant']) => t ? `#${t.id} · ${t.name}` : '—',
        },
        { title: 'Số tiền', dataIndex: 'total', render: (v: number) => formatMoney(v) },
        {
            title: 'Trạng thái', dataIndex: 'status',
            render: (v: string) => <Tag color={STATUS_COLOR[v] ?? 'default'}>{STATUS_OPTIONS.find((o) => o.value === v)?.label ?? v}</Tag>,
        },
        { title: 'Tạo lúc', dataIndex: 'created_at', render: (v: string | null) => formatDateTimeSeconds(v) },
        { title: 'Hạn', dataIndex: 'due_at', render: (v: string | null) => formatDateTimeSeconds(v) },
        { title: 'Thanh toán lúc', dataIndex: 'paid_at', render: (v: string | null) => (v ? formatDateTimeSeconds(v) : '—') },
        { title: '', key: 'open', width: 80, render: (_, r) => <a onClick={() => setOpenRow(r)}>Xem</a> },
    ];

    const paymentColumns: ColumnsType<AdminPayment> = [
        { title: 'Cổng', dataIndex: 'gateway' },
        { title: 'Số tiền', dataIndex: 'amount', render: (v: number) => formatMoney(v) },
        {
            title: 'Trạng thái', dataIndex: 'status',
            render: (v: string) => <Tag color={v === 'succeeded' ? 'green' : v === 'failed' ? 'red' : v === 'refunded' ? 'orange' : 'default'}>{v}</Tag>,
        },
        { title: 'Lúc', dataIndex: 'occurred_at', render: (v: string | null) => (v ? formatDateTimeSeconds(v) : '—') },
    ];

    return (
        <>
            <PageHeader title="Lịch sử thanh toán" subtitle="Hóa đơn xuyên mọi shop, gồm cả yêu cầu đã mở nhưng chưa hoàn thành (Chờ)." />
            <Card>
                <Space style={{ marginBottom: 12 }} wrap>
                    <Segmented options={STATUS_OPTIONS} value={status} onChange={(v) => { setStatus(v as string); setPage(1); }} />
                    <Input placeholder="tenant_id" value={tenantId} onChange={(e) => { setTenantId(e.target.value); setPage(1); }} style={{ width: 120 }} />
                    <Input.Search placeholder="Tìm mã hóa đơn" value={q} onChange={(e) => setQ(e.target.value)} onSearch={() => setPage(1)} style={{ width: 220 }} allowClear />
                    <RangePicker showTime onChange={(v) => { setRange(v as [dayjs.Dayjs, dayjs.Dayjs] | null); setPage(1); }} />
                </Space>

                <Table
                    rowKey="id" size="small"
                    loading={isFetching}
                    columns={columns}
                    dataSource={data?.data ?? []}
                    pagination={{ current: page, pageSize: 20, total: data?.meta.pagination.total ?? 0, showSizeChanger: false, onChange: setPage }}
                />
            </Card>

            <Drawer
                open={openRow != null}
                onClose={() => setOpenRow(null)}
                width={640}
                title={openRow ? openRow.code : 'Chi tiết hóa đơn'}
            >
                {openRow && (
                    <>
                        <Typography.Paragraph><strong>Shop:</strong> {openRow.tenant ? `#${openRow.tenant.id} · ${openRow.tenant.name}` : '—'}</Typography.Paragraph>
                        <Typography.Paragraph><strong>Trạng thái:</strong> <Tag color={STATUS_COLOR[openRow.status] ?? 'default'}>{STATUS_OPTIONS.find((o) => o.value === openRow.status)?.label ?? openRow.status}</Tag></Typography.Paragraph>
                        <Typography.Paragraph><strong>Số tiền:</strong> {formatMoney(openRow.total)}</Typography.Paragraph>
                        <Typography.Paragraph><strong>Kỳ:</strong> {openRow.period_start} → {openRow.period_end}</Typography.Paragraph>
                        <Typography.Paragraph><strong>Tạo lúc:</strong> {formatDateTimeSeconds(openRow.created_at)}</Typography.Paragraph>
                        <Typography.Paragraph><strong>Hạn thanh toán:</strong> {formatDateTimeSeconds(openRow.due_at)}</Typography.Paragraph>
                        <Typography.Paragraph><strong>Thanh toán lúc:</strong> {openRow.paid_at ? formatDateTimeSeconds(openRow.paid_at) : '—'}</Typography.Paragraph>

                        <Typography.Title level={5}>Các lần thanh toán</Typography.Title>
                        <Table
                            rowKey="id" size="small" pagination={false}
                            columns={paymentColumns}
                            dataSource={openRow.payments}
                            locale={{ emptyText: 'Chưa có lần thanh toán nào' }}
                        />
                    </>
                )}
            </Drawer>
        </>
    );
}
```

- [ ] **Step 2: Wire the route**

In `app/resources/js/admin/AdminApp.tsx`, add the import (alphabetically near the other page imports, right after the `AdminAuditLogsPage` import at line 14):

```tsx
import { AdminAuditLogsPage } from './pages/tenants/AdminAuditLogsPage';
import { AdminInvoicesPage } from './pages/tenants/AdminInvoicesPage';
```

Add the route right after the `audit-logs` route (around line 49):

```tsx
                    <Route path="audit-logs" element={<AdminAuditLogsPage />} />
                    <Route path="invoices" element={<AdminInvoicesPage />} />
```

- [ ] **Step 3: Add the sidebar entry**

In `app/resources/js/admin/AdminLayout.tsx`, add `TransactionOutlined` to the icon import block (around line 6-24, alongside the other `*Outlined` imports — insert alphabetically is not required here since the existing list isn't strictly alphabetical, just add it near `SettingOutlined`/`AuditOutlined` for readability):

```tsx
import {
    DashboardOutlined,
    ShopOutlined,
    UserOutlined,
    SettingOutlined,
    AuditOutlined,
    TransactionOutlined,
    LogoutOutlined,
    SafetyCertificateOutlined,
    GiftOutlined,
    ProfileOutlined,
    NotificationOutlined,
    SoundOutlined,
    ApiOutlined,
    CustomerServiceOutlined,
    SolutionOutlined,
    PictureOutlined,
    AudioOutlined,
    MailOutlined,
} from '@ant-design/icons';
```

Add the sidebar item to `SIDEBAR_ITEMS` (around line 28-46), right after the `audit-logs` entry:

```tsx
    { key: '/admin/audit-logs', icon: <AuditOutlined />, label: 'Nhật ký' },
    { key: '/admin/invoices', icon: <TransactionOutlined />, label: 'Lịch sử thanh toán' },
];
```

- [ ] **Step 4: Typecheck, lint, build**

Run: `cd app && npm run typecheck && npm run lint && npm run build`
Expected: all green (0 new errors/warnings in the 3 touched files — pre-existing warnings elsewhere in the repo are not yours to fix).

- [ ] **Step 5: Manual smoke check**

Run: `cd app && composer dev`, log into `/admin/login`, navigate to **Lịch sử thanh toán** in the sidebar. Confirm: page loads without error, filters (status Segmented, tenant_id input, code search, date RangePicker) update the table, clicking a row opens the Drawer with invoice detail + nested payments table (empty state "Chưa có lần thanh toán nào" for a pending invoice with no payment attempts yet).

- [ ] **Step 6: Commit**

```bash
git add app/resources/js/admin/pages/tenants/AdminInvoicesPage.tsx app/resources/js/admin/AdminApp.tsx app/resources/js/admin/AdminLayout.tsx
git commit -m "feat(billing): trang Lịch sử thanh toán trong admin"
```

---

### Task 4: Docs + final verification

**Files:**
- Modify: `docs/05-api/endpoints.md`
- Modify: `docs/superpowers/specs/2026-07-18-admin-invoice-payment-history-design.md`

**Interfaces:** None — documentation only.

- [ ] **Step 1: Update `05-api/endpoints.md`**

Find the "Admin Tier 1+2 (SPEC 0023)" section (search for `admin.invoices.mark-paid` or `admin/payments/{id}/refund`) and add a row right before the mark-paid line:

```
| GET | `/api/v1/admin/invoices` | `auth:admin_web` | `?status=&tenant_id=&q=&date_from=&date_to=&page=&per_page=` | `{ data:[{...invoice, tenant, payments[]}], meta:{pagination} }` — lịch sử thanh toán xuyên tenant, view-only. `status=pending` = đã mở yêu cầu nhưng chưa hoàn thành. |
```

- [ ] **Step 2: Mark design doc implemented**

In `docs/superpowers/specs/2026-07-18-admin-invoice-payment-history-design.md`, add a line right after the title/date/author header:

```
- **Trạng thái:** Implemented
```

- [ ] **Step 3: Full quality gate**

Run from `app/`:
```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test --filter=AdminInvoiceHistoryTest
npm run typecheck && npm run lint && npm run build
```
Expected: all green. Fix any `pint`/`phpstan` issues in the new/touched PHP file before proceeding (run `vendor/bin/pint` without `--test` to auto-fix, then re-check `phpstan`).

- [ ] **Step 4: Commit**

```bash
git add docs/05-api/endpoints.md docs/superpowers/specs/2026-07-18-admin-invoice-payment-history-design.md
git commit -m "docs(billing): cập nhật endpoints.md + đánh dấu thiết kế lịch sử thanh toán đã triển khai"
```
