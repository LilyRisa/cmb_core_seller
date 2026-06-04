<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Contracts\MarketingAnalysisClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MarketingAiSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_ai_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('marketing_ai_providers'));
        $this->assertTrue(Schema::hasColumn('marketing_ai_providers', 'adapter'));
        $this->assertTrue(Schema::hasColumn('marketing_ai_providers', 'api_key'));
        $this->assertTrue(Schema::hasColumn('marketing_ai_providers', 'is_active'));

        $this->assertTrue(Schema::hasTable('ad_forecasts'));
        $this->assertTrue(Schema::hasColumn('ad_forecasts', 'tenant_id'));
        $this->assertTrue(Schema::hasColumn('ad_forecasts', 'ad_account_id'));
        $this->assertTrue(Schema::hasColumn('ad_forecasts', 'payload'));
        $this->assertTrue(Schema::hasColumn('ad_forecasts', 'generated_at'));
    }

    public function test_stub_includes_creative_review_per_creative(): void
    {
        $client = app(MarketingAnalysisClient::class);

        $result = $client->analyze([
            'rows' => [],
            'creatives' => [['ad_id' => 'AD1', 'name' => 'QC Tết'], ['post_id' => 'P2', 'name' => 'Bài 2']],
        ], 'instr');

        $review = $result['payload']['creative_review'] ?? null;
        $this->assertIsArray($review);
        $this->assertCount(2, $review);
        $this->assertSame('AD1', $review[0]['ref']);
        $this->assertArrayHasKey('verdict', $review[0]);
        $this->assertArrayHasKey('suggestions', $review[0]);
    }
}
