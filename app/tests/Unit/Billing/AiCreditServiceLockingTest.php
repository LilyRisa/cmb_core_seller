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

    /** lockedWallet() (dùng bởi consume/record/grantPurchase) PHẢI gọi lockForUpdate() — không thể verify
     * qua SQL log vì SQLite (driver test suite dùng, xem phpunit.xml) drop hẳn mệnh đề FOR UPDATE
     * (SQLiteGrammar::compileLock() luôn trả rỗng), nên kiểm tra tĩnh trực tiếp trên source thay vì hành vi
     * runtime — vẫn bắt được nếu ai đó lỡ xoá dòng lockForUpdate() sau này.
     *
     * Cô lập thân method bằng đếm ngoặc nhọn cân bằng (không dùng regex "tới dấu } đầu tiên" — đã thử
     * và bị "tràn" sang method kế tiếp khi lockForUpdate() bị xoá, vì method sau (countUsage()) cũng
     * gọi lockForUpdate() nên regex vẫn khớp nhầm và test giả vờ PASS dù bug đã bị tái tạo).
     */
    public function test_locked_wallet_method_calls_lock_for_update(): void
    {
        $source = file_get_contents(app_path('Modules/Billing/Services/AiCreditService.php'));
        $this->assertNotFalse($source);

        $signaturePos = strpos($source, 'function lockedWallet(');
        $this->assertNotFalse($signaturePos, 'Không tìm thấy method lockedWallet() trong AiCreditService.');

        $braceOpen = strpos($source, '{', $signaturePos);
        $this->assertNotFalse($braceOpen, 'Không tìm thấy dấu { mở đầu thân method lockedWallet().');

        // Đếm ngoặc nhọn để tìm đúng dấu đóng của CHÍNH method này, tránh dính sang method kế tiếp.
        $depth = 0;
        $braceClose = null;
        for ($i = $braceOpen, $len = strlen($source); $i < $len; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            } elseif ($source[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    $braceClose = $i;
                    break;
                }
            }
        }
        $this->assertNotNull($braceClose, 'Không tìm thấy dấu } đóng thân method lockedWallet().');

        $methodBody = substr($source, $braceOpen, $braceClose - $braceOpen + 1);
        $this->assertStringContainsString(
            'lockForUpdate()',
            $methodBody,
            'lockedWallet() phải gọi lockForUpdate() để tránh race condition mất lượt đếm AI (xem AiCreditServiceLockingTest).',
        );
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
