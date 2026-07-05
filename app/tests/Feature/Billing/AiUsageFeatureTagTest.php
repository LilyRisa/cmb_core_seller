<?php

namespace Tests\Feature\Billing;

use CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter;
use CMBcoreSeller\Modules\Billing\Models\AiUsageCounter;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageFeatureTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_are_grouped_by_feature_label(): void
    {
        app(AiCreditMeter::class)->record(5, 1, 'intent', 0);
        app(AiCreditMeter::class)->record(5, 1, 'messaging', 0);

        $features = AiUsageCounter::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', 5)->pluck('feature')->sort()->values()->all();

        $this->assertSame(['intent', 'messaging'], $features);
    }
}
