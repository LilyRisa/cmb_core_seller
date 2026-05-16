<?php

namespace CMBcoreSeller\Modules\Admin\Services;

use Carbon\Carbon;
use CMBcoreSeller\Modules\Billing\Events\InvoicePaid;
use CMBcoreSeller\Modules\Billing\Models\Invoice;
use CMBcoreSeller\Modules\Billing\Models\InvoiceLine;
use CMBcoreSeller\Modules\Billing\Models\Payment;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Services\OverQuotaCheckService;
use CMBcoreSeller\Modules\Billing\Services\SubscriptionService;
use CMBcoreSeller\Modules\Billing\Services\UsageService;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Services\ChannelConnectionService;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service hợp lý hoá các hành động super-admin can thiệp vào tenant. SPEC 0020.
 *
 * Mọi method ghi audit_logs với `tenant_id` = target tenant + `user_id` = admin.
 * Bypass TenantScope tường minh (admin không có tenant context).
 */
class AdminTenantService
{
    public function __construct(
        protected ChannelConnectionService $channels,
        protected SubscriptionService $subscriptions,
        protected UsageService $usage,
        protected OverQuotaCheckService $overQuota,
    ) {}

    /**
     * Xoá kết nối kênh hộ khách hàng (force, không cần xác nhận tên — reason bắt buộc).
     * Reuse `ChannelConnectionService::deleteWithOrders` (xoá kết nối + đơn + sku_mappings + nhả tồn).
     *
     * @return array{deleted_orders:int, unlinked_skus:int}
     */
    public function forceDeleteChannelAccount(Tenant $tenant, int $channelAccountId, string $reason, int $adminUserId): array
    {
        $this->requireReason($reason);
        $account = ChannelAccount::query()->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->getKey())
            ->findOrFail($channelAccountId);

        $result = $this->channels->deleteWithOrders($account, $adminUserId);

        AuditLog::query()->create([
            'tenant_id' => $tenant->getKey(),
            'user_id' => $adminUserId,
            'action' => 'admin.channel_account.delete',
            'auditable_type' => $account->getMorphClass(),
            'auditable_id' => $account->getKey(),
            'changes' => [
                'reason' => $reason,
                'provider' => $account->provider,
                'shop_name' => $account->effectiveName(),
                'deleted_orders' => $result['deleted_orders'],
                'unlinked_skus' => $result['unlinked_skus'],
            ],
            'ip' => request()->ip(),
        ]);

        // Trigger ngay over-quota recompute (không đợi scheduler) — UX banner cập nhật sớm.
        $sub = $this->subscriptions->currentFor((int) $tenant->getKey());
        if ($sub !== null) {
            $this->overQuota->apply($sub);
        }

        return $result;
    }

    /**
     * Đổi gói tenant ngay lập tức (bypass DOWNGRADE_NOT_ALLOWED của BillingService).
     * Subscription cũ ⇒ cancelled `cancelled_at=now`; subscription mới ⇒ active, period_end = now + cycle.
     * KHÔNG tạo invoice (admin tự xử lý thanh toán bên ngoài).
     */
    public function changePlan(Tenant $tenant, string $planCode, string $cycle, string $reason, int $adminUserId): Subscription
    {
        $this->requireReason($reason);
        if (! in_array($cycle, [Subscription::CYCLE_MONTHLY, Subscription::CYCLE_YEARLY, Subscription::CYCLE_TRIAL], true)) {
            throw ValidationException::withMessages(['cycle' => 'Chu kỳ chỉ chấp nhận monthly/yearly/trial.']);
        }
        $plan = Plan::query()->where('code', $planCode)->where('is_active', true)->first();
        if ($plan === null) {
            throw ValidationException::withMessages(['plan_code' => "Plan {$planCode} không tồn tại hoặc đã ngừng."]);
        }

        $tenantId = (int) $tenant->getKey();
        $days = match ($cycle) {
            Subscription::CYCLE_YEARLY => 365,
            Subscription::CYCLE_MONTHLY => 30,
            default => max($plan->trial_days, 14),
        };

        return DB::transaction(function () use ($tenantId, $plan, $cycle, $days, $reason, $adminUserId) {
            $current = $this->subscriptions->currentFor($tenantId);
            $fromCode = $current?->plan?->code;

            if ($current !== null) {
                $current->forceFill([
                    'status' => Subscription::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'cancel_at' => now(),
                    'ended_at' => now(),
                ])->save();
            }

            $now = now();
            $new = Subscription::query()->create([
                'tenant_id' => $tenantId,
                'plan_id' => $plan->getKey(),
                'status' => Subscription::STATUS_ACTIVE,
                'billing_cycle' => $cycle,
                'current_period_start' => $now,
                'current_period_end' => $now->copy()->addDays($days),
                'meta' => ['admin_set_by' => $adminUserId, 'reason' => $reason],
            ]);

            AuditLog::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $adminUserId,
                'action' => 'admin.subscription.change',
                'auditable_type' => $new->getMorphClass(),
                'auditable_id' => $new->getKey(),
                'changes' => [
                    'from_plan' => $fromCode,
                    'to_plan' => $plan->code,
                    'cycle' => $cycle,
                    'reason' => $reason,
                ],
                'ip' => request()->ip(),
            ]);

            // Recompute over-quota cho subscription mới (gói thấp hơn có thể trigger banner).
            $new->load('plan');
            $this->overQuota->apply($new);

            return $new->fresh(['plan']) ?? $new;
        });
    }

    public function suspend(Tenant $tenant, string $reason, int $adminUserId): Tenant
    {
        $this->requireReason($reason);
        $tenant->forceFill(['status' => 'suspended'])->save();
        AuditLog::query()->create([
            'tenant_id' => (int) $tenant->getKey(),
            'user_id' => $adminUserId,
            'action' => 'admin.tenant.suspend',
            'auditable_type' => $tenant->getMorphClass(),
            'auditable_id' => $tenant->getKey(),
            'changes' => ['reason' => $reason],
            'ip' => request()->ip(),
        ]);

        return $tenant;
    }

    public function reactivate(Tenant $tenant, int $adminUserId): Tenant
    {
        $tenant->forceFill(['status' => 'active'])->save();
        AuditLog::query()->create([
            'tenant_id' => (int) $tenant->getKey(),
            'user_id' => $adminUserId,
            'action' => 'admin.tenant.reactivate',
            'auditable_type' => $tenant->getMorphClass(),
            'auditable_id' => $tenant->getKey(),
            'ip' => request()->ip(),
        ]);

        return $tenant;
    }

    /**
     * SPEC 0023 §3.3 — admin tặng trial N ngày tuỳ ý. Khác `changePlan` ở chỗ status
     * luôn `trialing` (FE hiện banner trial) và KHÔNG ràng buộc min 14 ngày.
     */
    public function extendTrial(Tenant $tenant, int $days, ?string $planCode, string $reason, int $adminUserId): Subscription
    {
        $this->requireReason($reason);
        if ($days < 1 || $days > 365) {
            throw ValidationException::withMessages(['days' => 'Số ngày trial phải từ 1 đến 365.']);
        }
        $planCode ??= Plan::CODE_TRIAL;
        $plan = Plan::query()->where('code', $planCode)->where('is_active', true)->first();
        if ($plan === null) {
            throw ValidationException::withMessages(['plan_code' => "Plan {$planCode} không tồn tại hoặc đã ngừng."]);
        }

        $tenantId = (int) $tenant->getKey();

        return DB::transaction(function () use ($tenantId, $plan, $days, $reason, $adminUserId) {
            $current = $this->subscriptions->currentFor($tenantId);
            $fromCode = $current?->plan?->code;
            if ($current !== null) {
                $current->forceFill([
                    'status' => Subscription::STATUS_CANCELLED,
                    'cancelled_at' => now(), 'cancel_at' => now(), 'ended_at' => now(),
                ])->save();
            }

            $now = now();
            $new = Subscription::query()->create([
                'tenant_id' => $tenantId,
                'plan_id' => $plan->getKey(),
                'status' => Subscription::STATUS_TRIALING,
                'billing_cycle' => Subscription::CYCLE_TRIAL,
                'current_period_start' => $now,
                'current_period_end' => $now->copy()->addDays($days),
                'trial_ends_at' => $now->copy()->addDays($days),
                'meta' => ['admin_extend_trial_by' => $adminUserId, 'reason' => $reason, 'days' => $days],
            ]);

            AuditLog::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $adminUserId,
                'action' => 'admin.trial.extend',
                'auditable_type' => $new->getMorphClass(),
                'auditable_id' => $new->getKey(),
                'changes' => ['from_plan' => $fromCode, 'to_plan' => $plan->code, 'days' => $days, 'reason' => $reason],
                'ip' => request()->ip(),
            ]);

            return $new->fresh(['plan']) ?? $new;
        });
    }

    /**
     * SPEC 0023 §3.5 — set/clear feature override per tenant.
     * `$features = ['mass_listing' => true, 'fifo_cogs' => false, 'procurement' => null]`
     * (null = bỏ override, rớt xuống plan).
     *
     * @param  array<string, bool|null>  $features
     */
    public function setFeatureOverrides(Tenant $tenant, array $features, string $reason, int $adminUserId): Subscription
    {
        $this->requireReason($reason);
        $tenantId = (int) $tenant->getKey();
        $sub = $this->subscriptions->currentFor($tenantId) ?? $this->subscriptions->ensureTrialFallback($tenantId);
        if ($sub === null) {
            throw ValidationException::withMessages(['tenant' => 'Tenant chưa có subscription để đặt override.']);
        }

        $current = (array) ($sub->meta['feature_overrides'] ?? []);
        $before = $current;
        foreach ($features as $key => $val) {
            if ($val === null) {
                unset($current[$key]);
            } else {
                $current[$key] = (bool) $val;
            }
        }

        $sub->forceFill(['meta' => array_merge((array) $sub->meta, ['feature_overrides' => $current])])->save();

        AuditLog::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $adminUserId,
            'action' => 'admin.feature_override.set',
            'auditable_type' => $sub->getMorphClass(),
            'auditable_id' => $sub->getKey(),
            'changes' => ['before' => $before, 'after' => $current, 'reason' => $reason],
            'ip' => request()->ip(),
        ]);

        return $sub->fresh(['plan']) ?? $sub;
    }

    /**
     * SPEC 0023 §3.6 — admin tạo invoice manual (khách chuyển khoản offline).
     * Tạo invoice `status=pending` + invoice_line plan với amount tuỳ ý + meta.created_by_admin.
     */
    public function createManualInvoice(Tenant $tenant, array $data, int $adminUserId): Invoice
    {
        $tenantId = (int) $tenant->getKey();
        $plan = Plan::query()->where('code', $data['plan_code'])->where('is_active', true)->first();
        if ($plan === null) {
            throw ValidationException::withMessages(['plan_code' => 'Plan không tồn tại.']);
        }
        if (! in_array($data['cycle'], [Subscription::CYCLE_MONTHLY, Subscription::CYCLE_YEARLY, Subscription::CYCLE_TRIAL], true)) {
            throw ValidationException::withMessages(['cycle' => 'Cycle không hợp lệ.']);
        }

        return DB::transaction(function () use ($tenantId, $plan, $data, $adminUserId) {
            $now = now();
            $days = match ($data['cycle']) {
                Subscription::CYCLE_YEARLY => 365,
                Subscription::CYCLE_TRIAL => max(1, (int) ($data['period_days'] ?? 14)),
                default => 30,
            };
            $amount = (int) ($data['amount'] ?? match ($data['cycle']) {
                Subscription::CYCLE_YEARLY => $plan->price_yearly,
                Subscription::CYCLE_TRIAL => 0,
                default => $plan->price_monthly,
            });
            $current = $this->subscriptions->currentFor($tenantId);

            $invoice = Invoice::query()->create([
                'tenant_id' => $tenantId,
                'subscription_id' => $current?->getKey() ?? 0,
                'code' => Invoice::nextCode($tenantId),
                'status' => Invoice::STATUS_PENDING,
                'period_start' => $now->format('Y-m-d'),
                'period_end' => $now->copy()->addDays($days)->format('Y-m-d'),
                'subtotal' => $amount, 'tax' => 0, 'total' => $amount, 'currency' => 'VND',
                'due_at' => $now->copy()->addDays(7),
                'meta' => [
                    'plan_code' => $plan->code,
                    'cycle' => $data['cycle'],
                    'created_by_admin' => $adminUserId,
                    'note' => $data['note'] ?? null,
                ],
            ]);

            InvoiceLine::query()->create([
                'invoice_id' => $invoice->getKey(),
                'kind' => InvoiceLine::KIND_PLAN,
                'description' => "Gói {$plan->name} ({$data['cycle']}) — tạo bởi admin",
                'quantity' => 1, 'unit_price' => $amount, 'amount' => $amount,
            ]);

            AuditLog::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => $adminUserId,
                'action' => 'admin.invoice.create_manual',
                'auditable_type' => $invoice->getMorphClass(),
                'auditable_id' => $invoice->getKey(),
                'changes' => ['plan' => $plan->code, 'cycle' => $data['cycle'], 'amount' => $amount, 'note' => $data['note'] ?? null],
                'ip' => request()->ip(),
            ]);

            return $invoice->fresh(['lines']);
        });
    }

    /**
     * SPEC 0023 §3.6 — đánh dấu invoice đã thanh toán manual (khách chuyển khoản offline).
     * Tạo `payments` row gateway=manual + fire `InvoicePaid` ⇒ ActivateSubscription tự swap plan.
     */
    public function markInvoicePaid(Invoice $invoice, array $data, int $adminUserId): Invoice
    {
        if ($invoice->status === Invoice::STATUS_PAID) {
            // idempotent — no-op
            AuditLog::query()->create([
                'tenant_id' => (int) $invoice->tenant_id,
                'user_id' => $adminUserId,
                'action' => 'admin.invoice.mark_paid.noop',
                'auditable_type' => $invoice->getMorphClass(),
                'auditable_id' => $invoice->getKey(),
                'ip' => request()->ip(),
            ]);

            return $invoice;
        }

        return DB::transaction(function () use ($invoice, $data, $adminUserId) {
            $now = isset($data['paid_at']) ? Carbon::parse($data['paid_at']) : now();
            $payment = Payment::query()->create([
                'tenant_id' => $invoice->tenant_id,
                'invoice_id' => $invoice->getKey(),
                'gateway' => Payment::GATEWAY_MANUAL,
                'external_ref' => $data['reference'] ?? 'MANUAL-'.$invoice->code,
                'amount' => (int) $invoice->total,
                'status' => Payment::STATUS_SUCCEEDED,
                'occurred_at' => $now,
                'meta' => [
                    'method' => $data['payment_method'] ?? 'bank_transfer',
                    'reference' => $data['reference'] ?? null,
                    'marked_by_admin' => $adminUserId,
                ],
                'raw_payload' => null,
            ]);

            $invoice->forceFill([
                'status' => Invoice::STATUS_PAID,
                'paid_at' => $now,
            ])->save();

            AuditLog::query()->create([
                'tenant_id' => (int) $invoice->tenant_id,
                'user_id' => $adminUserId,
                'action' => 'admin.invoice.mark_paid',
                'auditable_type' => $invoice->getMorphClass(),
                'auditable_id' => $invoice->getKey(),
                'changes' => ['payment_id' => $payment->getKey(), 'amount' => $invoice->total],
                'ip' => request()->ip(),
            ]);

            // Fire event ⇒ existing ActivateSubscription listener tự swap plan.
            event(new InvoicePaid($invoice->fresh(), $payment));

            return $invoice->fresh();
        });
    }

    /**
     * SPEC 0023 §3.7 — refund payment. Đánh dấu nội bộ (KHÔNG gọi gateway API).
     * Tuỳ chọn rollback subscription về trial fallback.
     */
    public function refundPayment(Payment $payment, string $reason, bool $rollbackSubscription, int $adminUserId): Payment
    {
        $this->requireReason($reason);
        if ($payment->status === Payment::STATUS_REFUNDED) {
            throw new HttpResponseException(response()->json([
                'error' => ['code' => 'ALREADY_REFUNDED', 'message' => 'Thanh toán đã được hoàn trước đó.'],
            ], 422));
        }

        return DB::transaction(function () use ($payment, $reason, $rollbackSubscription, $adminUserId) {
            $payment->forceFill([
                'status' => Payment::STATUS_REFUNDED,
                'refunded_at' => now(),
                'meta' => array_merge((array) $payment->meta, [
                    'refunded_by_admin' => $adminUserId,
                    'refund_reason' => $reason,
                ]),
            ])->save();

            // Đánh dấu invoice refunded — admin context không có current tenant ⇒ bypass scope.
            $invoice = Invoice::query()->withoutGlobalScope(TenantScope::class)->find($payment->invoice_id);
            if ($invoice !== null && $invoice->status === Invoice::STATUS_PAID) {
                $invoice->forceFill(['status' => Invoice::STATUS_REFUNDED])->save();
            }

            // Rollback subscription nếu cần — đóng sub hiện tại + tạo trial fallback.
            $rolledBack = false;
            if ($rollbackSubscription) {
                $current = $this->subscriptions->currentFor((int) $payment->tenant_id);
                if ($current !== null) {
                    $current->forceFill([
                        'status' => Subscription::STATUS_CANCELLED,
                        'cancelled_at' => now(), 'cancel_at' => now(), 'ended_at' => now(),
                    ])->save();
                    $this->subscriptions->ensureTrialFallback((int) $payment->tenant_id);
                    $rolledBack = true;
                }
            }

            AuditLog::query()->create([
                'tenant_id' => (int) $payment->tenant_id,
                'user_id' => $adminUserId,
                'action' => 'admin.payment.refund',
                'auditable_type' => $payment->getMorphClass(),
                'auditable_id' => $payment->getKey(),
                'changes' => [
                    'reason' => $reason,
                    'amount' => $payment->amount,
                    'rollback_subscription' => $rolledBack,
                ],
                'ip' => request()->ip(),
            ]);

            return $payment->fresh();
        });
    }

    private function requireReason(string $reason): void
    {
        if (mb_strlen(trim($reason)) < 10) {
            throw ValidationException::withMessages(['reason' => 'Lý do phải có tối thiểu 10 ký tự.']);
        }
    }
}
