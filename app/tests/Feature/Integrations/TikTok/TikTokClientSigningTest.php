<?php

namespace Tests\Feature\Integrations\TikTok;

use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokClient;
use CMBcoreSeller\Integrations\Channels\TikTok\TikTokSigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pins the fix for the production bug "[106001] sign query parameter is invalid" on
 * /return_refund/202309/returns/{id}/reject: an empty decision body ({} / []) is EXCLUDED
 * from the signature, so it must NOT be transmitted either — otherwise TikTok recomputes a
 * different `sign` over the `[]` body it receives and rejects the request.
 */
class TikTokClientSigningTest extends TestCase
{
    use RefreshDatabase;

    private function authContext(): AuthContext
    {
        return new AuthContext(
            channelAccountId: 1,
            provider: 'tiktok',
            externalShopId: 'shop_1',
            accessToken: 'TOKEN',
            extra: [],
        );
    }

    private function configureClient(): void
    {
        config([
            'integrations.tiktok.app_key' => 'AK',
            'integrations.tiktok.app_secret' => 'SECRET',
            'integrations.tiktok.base_url' => 'https://open-api.example.test',
            'integrations.throttle.tiktok' => 0, // disable rate limiter wait in tests
        ]);
    }

    public function test_empty_body_is_not_transmitted_and_sign_matches_no_body(): void
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(1_700_000_000));
        $this->configureClient();
        Http::fake(['*' => Http::response(['code' => 0, 'message' => 'ok', 'data' => []], 200)]);

        $path = '/return_refund/202309/returns/4041087791969109659/reject';
        (new TikTokClient)->requestRaw('POST', $path, $this->authContext(), [], [], shopScoped: true);

        Http::assertSent(function ($request) use ($path) {
            $this->assertSame('', $request->body(), 'empty decision body must be sent as no body');
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);
            $expected = TikTokSigner::sign('SECRET', $path, ['app_key' => 'AK', 'timestamp' => '1700000000'], '');
            $this->assertSame($expected, $q['sign'] ?? null, 'sign must be computed over an empty body');

            return true;
        });
    }

    public function test_populated_body_is_transmitted_and_signed_with_body(): void
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(1_700_000_000));
        $this->configureClient();
        Http::fake(['*' => Http::response(['code' => 0, 'message' => 'ok', 'data' => []], 200)]);

        $path = '/return_refund/202309/returns/123/reject';
        (new TikTokClient)->requestRaw('POST', $path, $this->authContext(), [], ['comment' => 'no'], shopScoped: true);

        Http::assertSent(function ($request) use ($path) {
            $this->assertSame('{"comment":"no"}', $request->body());
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);
            $expected = TikTokSigner::sign('SECRET', $path, ['app_key' => 'AK', 'timestamp' => '1700000000'], '{"comment":"no"}');
            $this->assertSame($expected, $q['sign'] ?? null);

            return true;
        });
    }
}
