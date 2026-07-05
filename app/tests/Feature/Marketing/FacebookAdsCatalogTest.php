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

    public function test_sanitize_drops_invalid_messenger_home(): void
    {
        // Meta khai tử Messenger Inbox ⇒ messenger_home không còn hợp lệ v25 (gửi lên reject cả ad set).
        $out = FacebookAdsCatalog::sanitizePlacements('messenger', ['messenger_home', 'story'], ['mobile']);
        $this->assertNotContains('messenger_home', $out);
        $this->assertContains('story', $out);
    }

    public function test_sanitize_drops_messenger_story_when_desktop_only(): void
    {
        // Messenger Stories mobile-only.
        $out = FacebookAdsCatalog::sanitizePlacements('messenger', ['story'], ['desktop']);
        $this->assertSame([], $out);
    }

    public function test_sanitize_drops_facebook_story_when_desktop_only(): void
    {
        // FB Stories mobile-only ⇒ gỡ khi chỉ nhắm desktop.
        $out = FacebookAdsCatalog::sanitizePlacements('facebook', ['feed', 'story'], ['desktop']);
        $this->assertNotContains('story', $out);
        $this->assertContains('feed', $out);
    }

    public function test_sanitize_adds_feed_when_marketplace_selected_without_it(): void
    {
        // marketplace/search bắt buộc kèm feed — tự thêm để FB không reject.
        $out = FacebookAdsCatalog::sanitizePlacements('facebook', ['marketplace'], []);
        $this->assertContains('feed', $out);
        $this->assertContains('marketplace', $out);
    }

    public function test_sanitize_drops_unknown_position(): void
    {
        // Giá trị lạ (không thuộc tập hợp lệ) bị loại thay vì gửi lên gây reject.
        $out = FacebookAdsCatalog::sanitizePlacements('facebook', ['feed', 'totally_made_up'], []);
        $this->assertSame(['feed'], $out);
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
