<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageCounterModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_counter_row_persists_and_increments(): void
    {
        $row = AiUsageCounter::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => 1, 'user_id' => 0, 'period_ym' => 202607, 'feature' => 'messaging', 'count' => 0,
        ]);
        $row->increment('count', 2);

        $this->assertDatabaseHas('ai_usage_counters', [
            'tenant_id' => 1, 'user_id' => 0, 'period_ym' => 202607, 'feature' => 'messaging', 'count' => 2,
        ]);
    }
}
