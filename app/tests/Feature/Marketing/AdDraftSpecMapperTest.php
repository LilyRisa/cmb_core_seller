<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use CMBcoreSeller\Modules\Marketing\Services\AdDraftSpecMapper;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdDraftSpecMapperTest extends TestCase
{
    use RefreshDatabase;

    private function draft(array $payload): AdDraft
    {
        app(CurrentTenant::class)->set(Tenant::create(['name' => 'T']));

        return AdDraft::create([
            'ad_account_id' => 11, 'name' => 'Tết', 'objective' => 'messages',
            'campaign_external_id' => 'C1', 'adset_external_id' => 'AS1',
            'payload' => $payload,
        ]);
    }

    public function test_maps_campaign_with_none_special_category(): void
    {
        $spec = app(AdDraftSpecMapper::class)->campaign($this->draft([]));

        $this->assertSame('messages', $spec->objective);
        $this->assertSame('Tết', $spec->name);
        $this->assertSame(['NONE'], $spec->specialAdCategories);
    }

    public function test_maps_adset_budget_targeting_page(): void
    {
        $draft = $this->draft([
            'budget' => ['daily_major' => 150000],
            'targeting' => ['geo_locations' => ['countries' => ['VN']]],
            'creative' => ['page_id' => '123'],
        ]);

        $spec = app(AdDraftSpecMapper::class)->adSet($draft, 'VND');

        $this->assertSame('C1', $spec->campaignExternalId);
        $this->assertSame(150000, $spec->dailyBudgetMajor);
        $this->assertSame('VND', $spec->currency);
        $this->assertSame(['geo_locations' => ['countries' => ['VN']]], $spec->targeting);
        $this->assertSame('123', $spec->pageId);
    }

    public function test_maps_ad_from_existing_page_post(): void
    {
        $draft = $this->draft(['creative' => ['page_id' => '123', 'page_post_id' => '123_456', 'cta' => 'MESSAGE_PAGE']]);

        $spec = app(AdDraftSpecMapper::class)->ad($draft);

        $this->assertSame('AS1', $spec->adSetExternalId);
        $this->assertSame('123', $spec->pageId);
        $this->assertSame('123_456', $spec->pagePostId);
        $this->assertSame('MESSAGE_PAGE', $spec->cta);
    }
}
