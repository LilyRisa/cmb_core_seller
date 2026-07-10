<?php

use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $old = Plan::query()->where('code', 'test_unlimited')->first();
        $starter = Plan::query()->where('code', Plan::CODE_STARTER)->first();
        if ($old === null || $starter === null) {
            return; // môi trường chưa có gói — no-op an toàn.
        }

        Subscription::query()->withoutGlobalScope(TenantScope::class)
            ->where('plan_id', $old->getKey())
            ->whereIn('status', Subscription::ALIVE_STATUSES)
            ->orderBy('id')
            ->each(function (Subscription $sub) use ($starter) {
                // Idempotent: nếu tenant đã có sub starter alive thì chỉ cancel cái test.
                $hasStarterAlive = Subscription::query()->withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $sub->tenant_id)
                    ->where('plan_id', $starter->getKey())
                    ->whereIn('status', Subscription::ALIVE_STATUSES)->exists();

                DB::transaction(function () use ($sub, $starter, $hasStarterAlive) {
                    $sub->forceFill([
                        'status' => Subscription::STATUS_CANCELLED,
                        'cancelled_at' => now(), 'cancel_at' => now(), 'ended_at' => now(),
                    ])->save();

                    if (! $hasStarterAlive) {
                        Subscription::query()->create([
                            'tenant_id' => $sub->tenant_id,
                            'plan_id' => $starter->getKey(),
                            'status' => Subscription::STATUS_ACTIVE,
                            'billing_cycle' => Subscription::CYCLE_MONTHLY,
                            'current_period_start' => now(),
                            'current_period_end' => now()->addMonth(),
                            'meta' => ['migrated_from' => 'test_unlimited'],
                        ]);
                    }
                });
            });

        $old->forceFill(['is_active' => false])->save();
    }

    public function down(): void
    {
        // Không khôi phục — one-way migration.
    }
};
