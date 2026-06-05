<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsPostLinksTest extends TestCase
{
    private function connector(): FacebookAdsConnector
    {
        return new FacebookAdsConnector(['graph_version' => 'v19.0']);
    }

    public function test_empty_ids_makes_no_http_call(): void
    {
        Http::fake();

        $this->assertSame([], $this->connector()->fetchPostLinks('TOK', []));

        Http::assertNothingSent();
    }

    public function test_resolves_post_cta_link_via_page_token_skips_messenger(): void
    {
        Http::fake([
            // Page tokens for the seller's pages.
            'graph.facebook.com/*/me/accounts*' => Http::response(['data' => [
                ['id' => '727', 'name' => 'Shop A', 'access_token' => 'PAGE_A'],
            ]], 200),
            // Batched post CTAs read with the page token: one website link, one Messenger.
            'graph.facebook.com/*' => Http::response([
                '727_111' => ['id' => '727_111', 'call_to_action' => ['type' => 'ORDER_NOW', 'value' => ['link' => 'https://shop.example/d800']]],
                '727_222' => ['id' => '727_222', 'call_to_action' => ['type' => 'MESSAGE_PAGE', 'value' => (object) []]],
            ], 200),
        ]);

        // 999_333 belongs to a page we have no token for ⇒ silently skipped.
        $out = $this->connector()->fetchPostLinks('TOK', ['727_111', '727_222', '999_333']);

        $this->assertSame(['727_111' => 'https://shop.example/d800'], $out);

        // The CTA read must use the PAGE token, not the user token.
        Http::assertSent(fn ($req) => str_contains($req->url(), 'me/accounts') === false
            ? str_contains($req->url(), 'access_token=PAGE_A')
            : true);
    }
}
