<?php

namespace Tests\Unit\Billing;

use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Billing\Services\AiCreditService;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AiCreditServiceLockingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        $this->tenant = Tenant::create(['name' => 'Shop']);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        $now = now();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => $now, 'current_period_end' => $now->copy()->addMonth(),
        ]);
    }

    /**
     * record() phải bọc trong transaction có lockForUpdate trên ai_credit_wallets.
     *
     * Bỏ qua trên SQLite: SQLiteGrammar::compileLock() luôn trả '' (SQLite không có cú pháp
     * FOR UPDATE — cả CI lẫn `php artisan test` mặc định chạy DB_CONNECTION=sqlite), nên
     * lockForUpdate() không bao giờ để lại dấu vết "for update" trong SQL dù code đúng hay sai.
     * Trên Postgres (PostgresGrammar::compileLock()) thì có, nên test này cho tín hiệu thật khi
     * chạy với DB_CONNECTION=pgsql.
     */
    public function test_record_locks_wallet_row_for_update(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite không hỗ trợ cú pháp FOR UPDATE thật (compileLock() luôn trả rỗng) — chạy với DB_CONNECTION=pgsql để xác nhận khoá row.');
        }

        app(AiCreditService::class)->record($this->tenant->getKey(), 1, 'suggest');

        // Xác nhận query log có câu SELECT ... FOR UPDATE trên ai_credit_wallets khi record() chạy lần 2
        // (lần 1 firstOrCreate tạo mới, chưa chắc có lock — lần 2 chắc chắn phải load qua đường có lock).
        DB::enableQueryLog();
        app(AiCreditService::class)->record($this->tenant->getKey(), 1, 'suggest');
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        $hasLock = collect($log)->contains(fn ($q) => str_contains(strtolower($q['query']), 'ai_credit_wallets')
            && str_contains(strtolower($q['query']), 'for update'));
        $this->assertTrue($hasLock, 'record() phải khoá row ai_credit_wallets bằng lockForUpdate() trong transaction.');
    }

    /** countUsage() không được để mất lượt đếm khi firstOrCreate đụng unique index (race). */
    public function test_count_usage_survives_concurrent_first_create_collision(): void
    {
        $tenantId = $this->tenant->getKey();
        $ym = (int) now()->format('Ym');

        // Giả lập race: row đã tồn tại NGAY TRƯỚC khi record() chạy (mô phỏng process khác đã tạo trước).
        AiUsageCounter::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenantId, 'user_id' => 0, 'period_ym' => $ym, 'feature' => 'suggest', 'count' => 5,
        ]);

        app(AiCreditService::class)->record($tenantId, 1, 'suggest', 0);

        $row = AiUsageCounter::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('user_id', 0)->where('period_ym', $ym)->where('feature', 'suggest')->first();
        $this->assertSame(6, $row->count, 'Lượt đếm phải cộng dồn đúng, không bị mất do race trên firstOrCreate.');
    }
}
