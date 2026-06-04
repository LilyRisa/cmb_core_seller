<?php

namespace Tests\Unit\Ads;

use CMBcoreSeller\Integrations\Ads\DTO\AdEntityDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdInsightDTO;
use PHPUnit\Framework\TestCase;

class AdsDtoTest extends TestCase
{
    public function test_entity_dto_holds_fields(): void
    {
        $e = new AdEntityDTO(
            level: 'campaign', externalId: 'C1', parentExternalId: null, name: 'Camp',
            status: 'ACTIVE', effectiveStatus: 'ACTIVE', dailyBudget: 100000, lifetimeBudget: null, raw: ['id' => 'C1'],
        );
        $this->assertSame('campaign', $e->level);
        $this->assertSame(100000, $e->dailyBudget);
    }

    public function test_insight_dto_holds_metrics(): void
    {
        $i = new AdInsightDTO(
            level: 'campaign', externalId: 'C1', dateStart: '2026-06-01', dateStop: '2026-06-04',
            spend: 50000, impressions: 1000, clicks: 30, reach: 800, ctr: 3.0, cpc: 1666, cpm: 50000,
            frequency: 1.25, purchaseRoas: 2.5, raw: [],
        );
        $this->assertSame(50000, $i->spend);
        $this->assertSame(2.5, $i->purchaseRoas);
    }
}
