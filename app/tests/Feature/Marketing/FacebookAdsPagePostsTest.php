<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsPagePostsTest extends TestCase
{
    private function connector(): FacebookAdsConnector
    {
        return new FacebookAdsConnector(['graph_version' => 'v19.0']);
    }

    public function test_list_pages_maps_id_name_token(): void
    {
        Http::fake(['graph.facebook.com/*/me/accounts*' => Http::response([
            'data' => [['id' => '123', 'name' => 'Shop', 'access_token' => 'PAGETOK']],
        ], 200)]);

        $pages = $this->connector()->listPages('USERTOK');

        $this->assertCount(1, $pages);
        $this->assertSame('123', $pages[0]->id);
        $this->assertSame('Shop', $pages[0]->name);
        $this->assertSame('PAGETOK', $pages[0]->accessToken);
    }

    public function test_list_page_posts_maps_media_and_engagement(): void
    {
        Http::fake(['graph.facebook.com/*/123/published_posts*' => Http::response([
            'data' => [[
                'id' => '123_456',
                'message' => 'Sale Tết',
                'created_time' => '2026-06-01T00:00:00+0000',
                'full_picture' => 'https://img/p.jpg',
                'attachments' => ['data' => [['media_type' => 'photo']]],
                'likes' => ['summary' => ['total_count' => 1200]],
                'comments' => ['summary' => ['total_count' => 89]],
                'shares' => ['count' => 45],
            ]],
        ], 200)]);

        $posts = $this->connector()->listPagePosts('PAGETOK', '123', 25);

        $this->assertCount(1, $posts);
        $p = $posts[0];
        $this->assertSame('123_456', $p->id);
        $this->assertSame('Sale Tết', $p->message);
        $this->assertSame('photo', $p->mediaType);
        $this->assertSame('https://img/p.jpg', $p->imageUrl);
        $this->assertSame(1200, $p->likes);
        $this->assertSame(89, $p->comments);
        $this->assertSame(45, $p->shares);
    }

    public function test_list_page_posts_extracts_call_to_action_link_and_type(): void
    {
        Http::fake(['graph.facebook.com/*/123/published_posts*' => Http::response([
            'data' => [[
                'id' => '123_456',
                'created_time' => '2026-06-01T00:00:00+0000',
                'call_to_action' => ['type' => 'SHOP_NOW', 'value' => ['link' => 'https://shop.example/sale']],
                'attachments' => ['data' => [['media_type' => 'share', 'target' => ['url' => 'https://ignored']]]],
            ]],
        ], 200)]);

        $p = $this->connector()->listPagePosts('PAGETOK', '123')[0];

        $this->assertSame('https://shop.example/sale', $p->linkUrl);
        $this->assertSame('SHOP_NOW', $p->ctaType);
    }

    public function test_list_page_posts_falls_back_to_attachment_link(): void
    {
        Http::fake(['graph.facebook.com/*/123/published_posts*' => Http::response([
            'data' => [[
                'id' => '123_456',
                'created_time' => '2026-06-01T00:00:00+0000',
                'attachments' => ['data' => [['media_type' => 'share', 'target' => ['url' => 'https://blog.example/post']]]],
            ]],
        ], 200)]);

        $p = $this->connector()->listPagePosts('PAGETOK', '123')[0];

        $this->assertSame('https://blog.example/post', $p->linkUrl);
        $this->assertNull($p->ctaType);
    }

    public function test_list_page_posts_photo_only_has_no_link_or_cta(): void
    {
        Http::fake(['graph.facebook.com/*/123/published_posts*' => Http::response([
            'data' => [[
                'id' => '123_456',
                'created_time' => '2026-06-01T00:00:00+0000',
                'full_picture' => 'https://img/p.jpg',
                'attachments' => ['data' => [['media_type' => 'photo']]],
            ]],
        ], 200)]);

        $p = $this->connector()->listPagePosts('PAGETOK', '123')[0];

        $this->assertNull($p->linkUrl);
        $this->assertNull($p->ctaType);
    }

    public function test_list_page_posts_handles_missing_engagement_gracefully(): void
    {
        Http::fake(['graph.facebook.com/*/123/published_posts*' => Http::response([
            'data' => [['id' => '123_789', 'created_time' => '2026-06-02T00:00:00+0000']],
        ], 200)]);

        $posts = $this->connector()->listPagePosts('PAGETOK', '123');

        $this->assertSame(0, $posts[0]->likes);
        $this->assertSame(0, $posts[0]->comments);
        $this->assertSame(0, $posts[0]->shares);
        $this->assertNull($posts[0]->message);
        $this->assertSame('status', $posts[0]->mediaType);
    }
}
