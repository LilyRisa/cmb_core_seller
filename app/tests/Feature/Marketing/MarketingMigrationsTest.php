<?php

namespace Tests\Feature\Marketing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MarketingMigrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_tables_exist_with_tenant_id(): void
    {
        foreach (['ad_accounts', 'ad_entities', 'ad_insight_snapshots'] as $t) {
            $this->assertTrue(Schema::hasTable($t), "missing $t");
            $this->assertTrue(Schema::hasColumn($t, 'tenant_id'), "$t missing tenant_id");
        }
        $this->assertTrue(Schema::hasColumn('ad_accounts', 'access_token'));
        $this->assertTrue(Schema::hasColumn('ad_entities', 'parent_id'));
        $this->assertTrue(Schema::hasColumn('ad_insight_snapshots', 'fetched_at'));
    }
}
