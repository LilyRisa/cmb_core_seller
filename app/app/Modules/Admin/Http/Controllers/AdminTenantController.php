<?php

namespace CMBcoreSeller\Modules\Admin\Http\Controllers;

use CMBcoreSeller\Modules\Admin\Services\AdminTenantService;
use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\Payment;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Models\VoucherRedemption;
use CMBcoreSeller\Modules\Billing\Services\OverQuotaCheckService;
use CMBcoreSeller\Modules\Billing\Services\SubscriptionService;
use CMBcoreSeller\Modules\Billing\Services\UsageService;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Models\TenantUser;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

/**
 * /api/v1/admin/tenants — super-admin xem & can thiệp tenant. SPEC 0020.
 * KHÔNG có middleware `tenant` (admin global). Mọi query phải bỏ TenantScope.
 */
class AdminTenantController extends Controller
{
    public function __construct(
        protected AdminTenantService $service,
        protected SubscriptionService $subscriptions,
        protected UsageService $usage,
        protected OverQuotaCheckService $overQuota,
    ) {}

    /** GET /api/v1/admin/tenants */
    public function index(Request $request): JsonResponse
    {
        $q = (string) $request->query('q', '');
        $overQuota = $request->boolean('over_quota');
        $suspended = $request->boolean('suspended');
        $perPage = max(1, min(100, (int) $request->query('per_page', 30)));

        $query = Tenant::query()->orderByDesc('created_at');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            });
        }
        if ($suspended) {
            $query->where('status', 'suspended');
        }

        $page = $query->paginate($perPage);

        $rows = collect($page->items())->map(fn (Tenant $t) => $this->summary($t))->all();

        if ($overQuota) {
            $rows = array_values(array_filter($rows, fn ($r) => ($r['subscription']['over_quota_warned_at'] ?? null) !== null));
        }

        return response()->json([
            'data' => $rows,
            'meta' => ['pagination' => [
                'page' => $page->currentPage(), 'per_page' => $page->perPage(),
                'total' => $page->total(), 'total_pages' => $page->lastPage(),
            ]],
        ]);
    }

    /** GET /api/v1/admin/tenants/{id} */
    public function show(int $id): JsonResponse
    {
        $tenant = Tenant::query()->findOrFail($id);

        $channels = ChannelAccount::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())->orderBy('id')->get();
        $members = TenantUser::query()
            ->where('tenant_id', $tenant->getKey())
            ->with('user:id,name,email,is_super_admin')->get();
        $audits = AuditLog::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())
            ->where('action', 'like', 'admin.%')
            ->orderByDesc('id')->limit(20)->get();
        $invoices = Invoice::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())
            ->orderByDesc('id')->limit(10)->get();
        $payments = Payment::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())
            ->orderByDesc('id')->limit(10)->get();
        $vouchersRedeemed = VoucherRedemption::query()
            ->where('tenant_id', $tenant->getKey())
            ->with('voucher:id,code,name,kind')
            ->orderByDesc('id')->limit(10)->get();

        return response()->json(['data' => array_merge($this->summary($tenant), [
            'channel_accounts' => $channels->map(fn (ChannelAccount $a) => [
                'id' => $a->id, 'provider' => $a->provider, 'name' => $a->effectiveName(),
                'shop_name' => $a->shop_name, 'display_name' => $a->display_name,
                'external_shop_id' => $a->external_shop_id,
                'status' => $a->status,
                'last_synced_at' => optional($a->last_synced_at)->toIso8601String(),
                'created_at' => $a->created_at?->toIso8601String(),
            ])->all(),
            'members' => $members->map(fn (TenantUser $m) => [
                'user_id' => $m->user_id, 'role' => $m->role->value ?? $m->role,
                'name' => $m->user->name ?? null, 'email' => $m->user->email ?? null,
                'is_super_admin' => (bool) ($m->user->is_super_admin ?? false),
            ])->all(),
            'recent_admin_actions' => $audits->map(fn (AuditLog $a) => [
                'id' => $a->id, 'action' => $a->action, 'user_id' => $a->user_id,
                'changes' => $a->changes, 'ip' => $a->ip,
                'created_at' => optional($a->created_at)->toIso8601String(),
            ])->all(),
            'invoices' => $invoices->map(fn (Invoice $i) => $this->invoiceResource($i))->all(),
            'payments' => $payments->map(fn (Payment $p) => [
                'id' => $p->id, 'invoice_id' => $p->invoice_id,
                'gateway' => $p->gateway, 'amount' => $p->amount,
                'status' => $p->status,
                'occurred_at' => $p->occurred_at?->toIso8601String(),
                'refunded_at' => $p->refunded_at?->toIso8601String(),
            ])->all(),
            'vouchers_redeemed' => $vouchersRedeemed->map(fn (VoucherRedemption $r) => [
                'id' => $r->id,
                'voucher_code' => $r->voucher?->code, 'voucher_name' => $r->voucher?->name,
                'voucher_kind' => $r->voucher?->kind,
                'discount_amount' => $r->discount_amount,
                'granted_days' => $r->granted_days,
                'invoice_id' => $r->invoice_id,
                'created_at' => $r->created_at?->toIso8601String(),
            ])->all(),
        ])]);
    }

    /** DELETE /api/v1/admin/tenants/{tid}/channel-accounts/{caid} */
    public function deleteChannelAccount(Request $request, int $tid, int $caid): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']]);
        $tenant = Tenant::query()->findOrFail($tid);
        $result = $this->service->forceDeleteChannelAccount($tenant, $caid, $data['reason'], (int) $request->user()->getKey());

        return response()->json(['data' => $result]);
    }

    /** POST /api/v1/admin/tenants/{tid}/subscription */
    public function changePlan(Request $request, int $tid): JsonResponse
    {
        $data = $request->validate([
            'plan_code' => ['required', 'string', 'max:32'],
            'cycle' => ['required', 'string', 'in:monthly,yearly,trial'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);
        $tenant = Tenant::query()->findOrFail($tid);
        $sub = $this->service->changePlan($tenant, $data['plan_code'], $data['cycle'], $data['reason'], (int) $request->user()->getKey());

        return response()->json(['data' => $this->subscriptionResource($sub)]);
    }

    /** POST /api/v1/admin/tenants/{tid}/suspend */
    public function suspend(Request $request, int $tid): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']]);
        $tenant = Tenant::query()->findOrFail($tid);
        $this->service->suspend($tenant, $data['reason'], (int) $request->user()->getKey());

        return response()->json(['data' => $this->summary($tenant->refresh())]);
    }

    /** POST /api/v1/admin/tenants/{tid}/reactivate */
    public function reactivate(Request $request, int $tid): JsonResponse
    {
        $tenant = Tenant::query()->findOrFail($tid);
        $this->service->reactivate($tenant, (int) $request->user()->getKey());

        return response()->json(['data' => $this->summary($tenant->refresh())]);
    }

    /** POST /api/v1/admin/tenants/{tid}/extend-trial — SPEC 0023 §3.3 */
    public function extendTrial(Request $request, int $tid): JsonResponse
    {
        $data = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:365'],
            'plan_code' => ['nullable', 'string', Rule::in(Plan::CODES)],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);
        $tenant = Tenant::query()->findOrFail($tid);
        $sub = $this->service->extendTrial(
            $tenant, (int) $data['days'], $data['plan_code'] ?? null,
            $data['reason'], (int) $request->user()->getKey(),
        );

        return response()->json(['data' => $this->subscriptionResource($sub)]);
    }

    /** POST /api/v1/admin/tenants/{tid}/feature-overrides — SPEC 0023 §3.5 */
    public function featureOverrides(Request $request, int $tid): JsonResponse
    {
        $data = $request->validate([
            'features' => ['required', 'array'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);
        $tenant = Tenant::query()->findOrFail($tid);
        $sub = $this->service->setFeatureOverrides(
            $tenant, $data['features'], $data['reason'], (int) $request->user()->getKey(),
        );

        return response()->json(['data' => $this->subscriptionResource($sub)]);
    }

    /** POST /api/v1/admin/tenants/{tid}/invoices — SPEC 0023 §3.6 (manual invoice) */
    public function createInvoice(Request $request, int $tid): JsonResponse
    {
        $data = $request->validate([
            'plan_code' => ['required', 'string', Rule::in(Plan::CODES)],
            'cycle' => ['required', 'string', 'in:monthly,yearly,trial'],
            'amount' => ['nullable', 'integer', 'min:0'],
            'period_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $tenant = Tenant::query()->findOrFail($tid);
        $invoice = $this->service->createManualInvoice($tenant, $data, (int) $request->user()->getKey());

        return response()->json(['data' => $this->invoiceResource($invoice)], 201);
    }

    /** POST /api/v1/admin/invoices/{id}/mark-paid — SPEC 0023 §3.6 */
    public function markInvoicePaid(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'payment_method' => ['nullable', 'string', 'max:32'],
            'reference' => ['nullable', 'string', 'max:128'],
            'paid_at' => ['nullable', 'date'],
        ]);
        $invoice = Invoice::query()->withoutGlobalScope(TenantScope::class)->findOrFail($id);
        $invoice = $this->service->markInvoicePaid($invoice, $data, (int) $request->user()->getKey());

        return response()->json(['data' => $this->invoiceResource($invoice->fresh())]);
    }

    /** POST /api/v1/admin/payments/{id}/refund — SPEC 0023 §3.7 */
    public function refundPayment(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'rollback_subscription' => ['nullable', 'boolean'],
        ]);
        $payment = Payment::query()->withoutGlobalScope(TenantScope::class)->findOrFail($id);
        $payment = $this->service->refundPayment(
            $payment, $data['reason'], (bool) ($data['rollback_subscription'] ?? false),
            (int) $request->user()->getKey(),
        );

        return response()->json(['data' => [
            'id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'refunded_at' => $payment->refunded_at?->toIso8601String(),
        ]]);
    }

    /** @return array<string, mixed> */
    private function summary(Tenant $tenant): array
    {
        $sub = $this->subscriptions->currentFor((int) $tenant->getKey());
        $owner = TenantUser::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('role', 'owner')->with('user:id,name,email')->first();

        $used = $this->usage->channelAccounts((int) $tenant->getKey());
        $limit = $sub?->plan?->maxChannelAccounts();

        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'status' => $tenant->status,
            'created_at' => $tenant->created_at?->toIso8601String(),
            'owner' => $owner && $owner->user ? [
                'id' => $owner->user->id, 'name' => $owner->user->name, 'email' => $owner->user->email,
            ] : null,
            'subscription' => $sub ? $this->subscriptionResource($sub) : null,
            'usage' => [
                'channel_accounts' => [
                    'used' => $used,
                    'limit' => $limit ?? -1,
                    'over' => $limit !== null && $limit >= 0 && $used > $limit,
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function subscriptionResource(Subscription $sub): array
    {
        return [
            'id' => $sub->id,
            'plan_code' => $sub->plan?->code,
            'plan_name' => $sub->plan?->name,
            'status' => $sub->status,
            'billing_cycle' => $sub->billing_cycle,
            'current_period_start' => optional($sub->current_period_start)->toIso8601String(),
            'current_period_end' => optional($sub->current_period_end)->toIso8601String(),
            'over_quota_warned_at' => $sub->over_quota_warned_at?->toIso8601String(),
            'over_quota_locked' => $this->overQuota->isPastGrace($sub),
            'feature_overrides' => (array) ($sub->meta['feature_overrides'] ?? []),
        ];
    }

    /** @return array<string, mixed> */
    private function invoiceResource(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'code' => $invoice->code,
            'status' => $invoice->status,
            'subtotal' => $invoice->subtotal,
            'total' => $invoice->total,
            'currency' => $invoice->currency,
            'due_at' => $invoice->due_at->toIso8601String(),
            'paid_at' => $invoice->paid_at?->toIso8601String(),
            'period_start' => (string) $invoice->period_start,
            'period_end' => (string) $invoice->period_end,
            'meta' => $invoice->meta,
            'created_at' => $invoice->created_at?->toIso8601String(),
        ];
    }
}
