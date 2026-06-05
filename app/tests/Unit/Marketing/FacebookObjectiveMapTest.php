<?php

namespace Tests\Unit\Marketing;

use CMBcoreSeller\Integrations\Ads\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Ads\Facebook\FacebookObjectiveMap;
use PHPUnit\Framework\TestCase;

class FacebookObjectiveMapTest extends TestCase
{
    public function test_messages_objective_maps_to_messenger_conversation_spec(): void
    {
        $spec = FacebookObjectiveMap::spec('messages');

        $this->assertSame('OUTCOME_ENGAGEMENT', $spec['objective']);
        $this->assertSame('CONVERSATIONS', $spec['optimization_goal']);
        $this->assertSame('IMPRESSIONS', $spec['billing_event']);
        $this->assertSame('MESSENGER', $spec['destination_type']);
        $this->assertTrue($spec['needs_promoted_object']);
        $this->assertContains('MESSAGE_PAGE', $spec['cta_options']);
    }

    public function test_traffic_objective_does_not_need_promoted_object(): void
    {
        $spec = FacebookObjectiveMap::spec('traffic');

        $this->assertSame('OUTCOME_TRAFFIC', $spec['objective']);
        $this->assertSame('LINK_CLICKS', $spec['optimization_goal']);
        $this->assertFalse($spec['needs_promoted_object']);
    }

    public function test_engagement_objective_maps_to_post_engagement_spec(): void
    {
        $spec = FacebookObjectiveMap::spec('engagement');

        $this->assertSame('OUTCOME_ENGAGEMENT', $spec['objective']);
        $this->assertSame('POST_ENGAGEMENT', $spec['optimization_goal']);
        $this->assertFalse($spec['needs_promoted_object']);
        $this->assertNull($spec['destination_type']);
    }

    public function test_unknown_objective_throws(): void
    {
        $this->expectException(UnsupportedOperation::class);
        FacebookObjectiveMap::spec('sales');
    }

    public function test_conversions_objective_uses_pixel_promoted_object(): void
    {
        $spec = FacebookObjectiveMap::spec('conversions');

        $this->assertSame('OUTCOME_SALES', $spec['objective']);
        $this->assertSame('OFFSITE_CONVERSIONS', $spec['optimization_goal']);
        $this->assertTrue($spec['needs_promoted_object']);
        $this->assertSame('pixel', $spec['promoted_object']);
        $this->assertContains('SHOP_NOW', $spec['cta_options']);
    }

    public function test_supported_lists_objectives(): void
    {
        $this->assertSame(['messages', 'engagement', 'traffic', 'conversions'], FacebookObjectiveMap::supported());
    }
}
