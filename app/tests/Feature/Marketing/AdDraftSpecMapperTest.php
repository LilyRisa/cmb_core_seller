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
            'campaign_external_id' => 'C1', 'payload' => $payload,
        ]);
    }

    public function test_maps_campaign_with_none_special_category(): void
    {
        $spec = app(AdDraftSpecMapper::class)->campaign($this->draft([]));

        $this->assertSame('messages', $spec->objective);
        $this->assertSame('Tết', $spec->name);
        $this->assertSame(['NONE'], $spec->specialAdCategories);
    }

    public function test_adset_nodes_wrap_legacy_flat_payload(): void
    {
        $draft = $this->draft(['budget' => ['daily_major' => 1000], 'creative' => ['page_id' => '1']]);
        $nodes = app(AdDraftSpecMapper::class)->adsetNodes($draft);
        $this->assertCount(1, $nodes);
        $this->assertCount(1, $nodes[0]['ads']);
    }

    public function test_maps_adset_from_node(): void
    {
        $draft = $this->draft([]);
        $node = [
            'name' => 'Nhóm A',
            'budget' => ['daily_major' => 150000],
            'targeting' => ['geo_locations' => ['countries' => ['VN']]],
            'schedule' => ['start_time' => null],
            'ads' => [['creative' => ['page_id' => '123']]],
        ];

        $spec = app(AdDraftSpecMapper::class)->adSet($draft, $node, 'CAMP9', 'VND');

        $this->assertSame('Nhóm A', $spec->name);
        $this->assertSame('CAMP9', $spec->campaignExternalId);
        $this->assertSame('messages', $spec->objective);
        $this->assertSame(150000, $spec->dailyBudgetMajor);
        $this->assertSame('VND', $spec->currency);
        $this->assertSame(['geo_locations' => ['countries' => ['VN']]], $spec->targeting);
        $this->assertSame('123', $spec->pageId);  // from first ad's creative
    }

    public function test_maps_ad_from_node(): void
    {
        $draft = $this->draft([]);
        $node = ['name' => 'QC 1', 'creative' => ['page_id' => '123', 'page_post_id' => '123_456', 'cta' => 'MESSAGE_PAGE']];

        $spec = app(AdDraftSpecMapper::class)->ad($draft, $node, 'AS9');

        $this->assertSame('QC 1', $spec->name);
        $this->assertSame('AS9', $spec->adSetExternalId);
        $this->assertSame('123', $spec->pageId);
        $this->assertSame('123_456', $spec->pagePostId);
        $this->assertSame('MESSAGE_PAGE', $spec->cta);
    }
}
