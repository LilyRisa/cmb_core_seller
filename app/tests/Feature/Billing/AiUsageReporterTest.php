<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Contracts\AiUsageReporter;
use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageReporterTest extends TestCase
{
    use RefreshDatabase;

    private function seedCounter(int $userId, int $ym, string $feature, int $count): void
    {
        AiUsageCounter::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'user_id' => $userId, 'period_ym' => $ym, 'feature' => $feature, 'count' => $count,
        ]);
    }

    public function test_usage_for_users_splits_month_and_all_time(): void
    {
        $ym = (int) now()->format('Ym');
        $this->seedCounter(10, $ym, 'messaging', 3);
        $this->seedCounter(10, 202601, 'messaging', 5); // old month
        $this->seedCounter(20, $ym, 'intent', 2);

        $out = app(AiUsageReporter::class)->usageForUsers([10, 20, 30]);

        $this->assertSame(['this_month' => 3, 'all_time' => 8], $out[10]);
        $this->assertSame(['this_month' => 2, 'all_time' => 2], $out[20]);
        $this->assertSame(['this_month' => 0, 'all_time' => 0], $out[30]); // no rows
    }

    public function test_breakdown_for_user_groups_by_month_and_feature(): void
    {
        $ym = (int) now()->format('Ym');
        $this->seedCounter(10, $ym, 'messaging', 3);
        $this->seedCounter(10, $ym, 'intent', 1);

        $b = app(AiUsageReporter::class)->breakdownForUser(10);

        $this->assertSame(4, $b['all_time']);
        $this->assertSame([['feature' => 'messaging', 'count' => 3], ['feature' => 'intent', 'count' => 1]], $b['by_feature']);
        $this->assertSame([['period_ym' => $ym, 'count' => 4]], $b['by_month']);
    }
}
