<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookCampaignBlueprint;
use Tests\TestCase;

class FacebookCampaignBlueprintTest extends TestCase
{
    /** @return array<string,mixed> */
    private function messagesPayload(): array
    {
        return [
            'campaign' => ['budget_mode' => 'adset'],
            'adsets' => [[
                'name' => 'Nhóm 1',
                'budget' => ['daily_major' => 100000],
                'targeting' => ['geo_locations' => ['countries' => ['VN']], 'age_min' => 22, 'age_max' => 45],
                'placement_config' => ['automatic' => false, 'device_platforms' => ['mobile'], 'positions' => ['facebook' => ['feed', 'video_feeds', 'right_hand_column']]],
                'ads' => [[
                    'name' => 'QC 1',
                    'creative' => ['mode' => 'page_post', 'page_id' => '655064411022030', 'page_post_id' => '655064411022030_122'],
                ]],
            ]],
        ];
    }

    public function test_valid_messages_blueprint_has_no_errors(): void
    {
        $bp = FacebookCampaignBlueprint::fromArray($this->messagesPayload(), 'messages');
        $this->assertSame([], $bp->validate());
    }

    public function test_missing_page_id_for_messages_is_error(): void
    {
        $p = $this->messagesPayload();
        unset($p['adsets'][0]['ads'][0]['creative']['page_id']);
        $errors = FacebookCampaignBlueprint::fromArray($p, 'messages')->validate();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('page', strtolower(implode(' ', $errors)));
    }

    public function test_no_adsets_is_error(): void
    {
        $errors = FacebookCampaignBlueprint::fromArray(['campaign' => ['budget_mode' => 'adset'], 'adsets' => []], 'messages')->validate();
        $this->assertNotEmpty($errors);
    }

    public function test_abo_zero_budget_is_error(): void
    {
        $p = $this->messagesPayload();
        $p['adsets'][0]['budget']['daily_major'] = 0;
        $errors = FacebookCampaignBlueprint::fromArray($p, 'messages')->validate();
        $this->assertNotEmpty($errors);
    }

    public function test_conversions_requires_pixel_and_event(): void
    {
        $p = $this->messagesPayload();
        $p['adsets'][0]['ads'][0]['creative']['link_url'] = 'https://shop.vn/dk';
        // thiếu conversion.pixel_id/custom_event_type
        $errors = FacebookCampaignBlueprint::fromArray($p, 'conversions')->validate();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('pixel', strtolower(implode(' ', $errors)));
    }

    public function test_sanitize_strips_deprecated_and_desktop_only(): void
    {
        $bp = FacebookCampaignBlueprint::fromArray($this->messagesPayload(), 'messages')->sanitize();
        $fb = $bp->toPayload()['adsets'][0]['placement_config']['positions']['facebook'];
        $this->assertNotContains('video_feeds', $fb);     // khai tử
        $this->assertNotContains('right_hand_column', $fb); // desktop-only + chỉ mobile
        $this->assertContains('feed', $fb);
    }

    public function test_to_payload_preserves_adsets(): void
    {
        $payload = FacebookCampaignBlueprint::fromArray($this->messagesPayload(), 'messages')->toPayload();
        $this->assertCount(1, $payload['adsets']);
        $this->assertSame('Nhóm 1', $payload['adsets'][0]['name']);
    }
}
