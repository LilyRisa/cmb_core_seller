<?php

namespace Tests\Unit\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use PHPUnit\Framework\TestCase;

class AdsAuthoringContractTest extends TestCase
{
    public function test_connector_has_authoring_query_methods(): void
    {
        $c = new FacebookAdsConnector(['graph_version' => 'v19.0']);

        $this->assertTrue(method_exists($c, 'listPages'));
        $this->assertTrue(method_exists($c, 'listPagePosts'));
        $this->assertTrue(method_exists($c, 'searchTargeting'));
        $this->assertTrue(method_exists($c, 'estimateAudience'));
        $this->assertTrue(method_exists($c, 'generatePreviews'));
        $this->assertTrue($c->supports('page.posts.read'));
        $this->assertTrue($c->supports('targeting.search'));
        $this->assertTrue($c->supports('preview.generate'));
    }
}
