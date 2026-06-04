<?php

namespace Tests\Feature\Marketing;

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
}
