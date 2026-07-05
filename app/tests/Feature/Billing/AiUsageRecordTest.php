<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter;
use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_increments_usage_counter_even_without_active_plan(): void
    {
        // aiEnabled=false (no plan) → wallet debit skipped, but the CALL still counts.
        app(AiCreditMeter::class)->record(77, 1, 'marketing', 999);

        $row = AiUsageCounter::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', 77)->where('user_id', 999)->first();

        $this->assertNotNull($row);
        $this->assertSame('marketing', $row->feature);
        $this->assertSame(1, $row->count);
        $this->assertSame((int) now()->format('Ym'), $row->period_ym);
    }

    public function test_record_defaults_to_system_user_and_other_feature(): void
    {
        app(AiCreditMeter::class)->record(77);

        $this->assertDatabaseHas('ai_usage_counters', [
            'tenant_id' => 77, 'user_id' => 0, 'feature' => 'other', 'count' => 1,
        ]);
    }
}
