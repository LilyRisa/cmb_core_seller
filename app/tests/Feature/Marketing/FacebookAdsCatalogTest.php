<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsCatalog;
use Tests\TestCase;

class FacebookAdsCatalogTest extends TestCase
{
    public function test_sanitize_strips_deprecated_video_feeds(): void
    {
        $out = FacebookAdsCatalog::sanitizePlacements('facebook', ['feed', 'video_feeds', 'story'], []);
        $this->assertNotContains('video_feeds', $out);
        $this->assertContains('feed', $out);
        $this->assertContains('story', $out);
    }

    public function test_sanitize_strips_desktop_only_when_mobile_only(): void
    {
        $out = FacebookAdsCatalog::sanitizePlacements('facebook', ['feed', 'right_hand_column'], ['mobile']);
        $this->assertNotContains('right_hand_column', $out);
        $this->assertContains('feed', $out);
    }

    public function test_sanitize_keeps_desktop_only_when_desktop_selected(): void
    {
        $out = FacebookAdsCatalog::sanitizePlacements('facebook', ['feed', 'right_hand_column'], ['mobile', 'desktop']);
        $this->assertContains('right_hand_column', $out);
    }

    public function test_non_facebook_platform_untouched(): void
    {
        $out = FacebookAdsCatalog::sanitizePlacements('instagram', ['stream', 'story'], ['mobile']);
        $this->assertSame(['stream', 'story'], $out);
    }

    public function test_objectives_lists_supported(): void
    {
        $objs = FacebookAdsCatalog::objectives();
        $this->assertContains('messages', $objs);
        $this->assertContains('conversions', $objs);
        $this->assertContains('traffic', $objs);
    }

    public function test_json_schema_describes_campaign_tree_and_excludes_deprecated(): void
    {
        $schema = FacebookAdsCatalog::jsonSchema();
        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('campaign', $schema['properties']);
        $this->assertArrayHasKey('adsets', $schema['properties']);
        // objective enum khớp danh sách hỗ trợ
        $objEnum = $schema['properties']['campaign']['properties']['objective']['enum'] ?? [];
        $this->assertContains('conversions', $objEnum);
        // schema không liệt kê vị trí khai tử cho người/AI chọn
        $json = json_encode($schema);
        $this->assertStringNotContainsString('video_feeds', (string) $json);
    }
}
