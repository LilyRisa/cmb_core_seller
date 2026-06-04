<?php

namespace Tests\Unit\Marketing;

use CMBcoreSeller\Integrations\Ads\DTO\AdSetSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AdSpecDTO;
use CMBcoreSeller\Integrations\Ads\DTO\CampaignSpecDTO;
use PHPUnit\Framework\TestCase;

class AdSpecDtoTest extends TestCase
{
    public function test_dtos_construct_and_expose_fields(): void
    {
        $c = new CampaignSpecDTO(objective: 'messages', name: 'Camp');
        $this->assertSame('messages', $c->objective);
        $this->assertSame('PAUSED', $c->status);
        $this->assertSame([], $c->specialAdCategories);

        $s = new AdSetSpecDTO(
            name: 'Set', campaignExternalId: 'C1', objective: 'messages',
            dailyBudgetMajor: 150000, currency: 'VND',
            targeting: ['geo_locations' => ['countries' => ['VN']]],
            pageId: '123', startTime: null,
        );
        $this->assertSame(150000, $s->dailyBudgetMajor);
        $this->assertSame('123', $s->pageId);
        $this->assertNull($s->startTime);

        $a = new AdSpecDTO(
            name: 'Ad', adSetExternalId: 'AS1', pageId: '123',
            pagePostId: '123_456', cta: 'MESSAGE_PAGE',
        );
        $this->assertSame('123_456', $a->pagePostId);
        $this->assertNull($a->imageHash);
        $this->assertSame('MESSAGE_PAGE', $a->cta);
    }
}
