<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Integrations\Messaging\DTO\MessagingAuthContext;
use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookPageConnector;
use CMBcoreSeller\Integrations\Messaging\Facebook\FacebookSignatureVerifier;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookBackfillConnectorTest extends TestCase
{
    private function connector(): FacebookPageConnector
    {
        return new FacebookPageConnector(
            ['app_secret' => 'x', 'graph_version' => 'v19.0', 'app_id' => 'app123'],
            new FacebookSignatureVerifier,
        );
    }

    private function auth(): MessagingAuthContext
    {
        return new MessagingAuthContext(
            channelAccountId: 1, provider: 'facebook_page',
            externalShopId: 'PAGE_123', accessToken: 'PAGE_TOKEN',
        );
    }

    public function test_supports_backfill_capability(): void
    {
        $this->assertTrue($this->connector()->supports('inbound.backfill'));
    }

    public function test_fetch_page_profile_returns_name_and_avatar(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'name' => 'My Shop Page',
                'picture' => ['data' => ['url' => 'https://cdn.fb/pageavatar.jpg']],
                'id' => 'PAGE_123',
            ], 200),
        ]);

        $profile = $this->connector()->fetchPageProfile($this->auth());

        $this->assertSame('My Shop Page', $profile['name']);
        $this->assertSame('https://cdn.fb/pageavatar.jpg', $profile['avatar_url']);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/PAGE_123')
            && (str_contains($r->url(), 'fields=name%2Cpicture') || str_contains($r->url(), 'fields=name,picture')));
    }

    public function test_fetch_user_profile_returns_name_and_avatar(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'name' => 'Nguyen Van A',
                'profile_pic' => 'https://cdn.fb/psidavatar.jpg',
                'id' => 'PSID_999',
            ], 200),
        ]);

        $profile = $this->connector()->fetchUserProfile($this->auth(), 'PSID_999');

        $this->assertSame('Nguyen Van A', $profile['name']);
        $this->assertSame('https://cdn.fb/psidavatar.jpg', $profile['avatar_url']);
    }

    public function test_fetch_profile_failure_returns_nulls(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'no']], 400)]);
        $profile = $this->connector()->fetchUserProfile($this->auth(), 'PSID_X');
        $this->assertNull($profile['name']);
        $this->assertNull($profile['avatar_url']);
    }
}
