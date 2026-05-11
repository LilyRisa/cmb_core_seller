<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpaCatchAllTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // CI's backend job doesn't build the Vite assets (the frontend job does);
        // stub @vite so the SPA shell still renders.
        $this->withoutVite();
    }

    public function test_root_serves_the_spa_shell(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<div id="app">', false);
    }

    public function test_unknown_web_path_serves_the_spa_shell(): void
    {
        // React Router handles client-side routing — any non-API path returns the shell.
        $this->get('/orders/123')
            ->assertOk()
            ->assertSee('<div id="app">', false);
    }

    public function test_unknown_api_path_returns_json_404_not_html(): void
    {
        $this->getJson('/api/v1/this-does-not-exist')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_unimplemented_oauth_callback_is_not_swallowed_by_the_spa(): void
    {
        $this->get('/oauth/tiktok/callback?code=abc&state=xyz')->assertStatus(501);
    }

    public function test_webhook_stub_acknowledges_receipt(): void
    {
        $this->postJson('/webhook/tiktok', ['ping' => true])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }
}
