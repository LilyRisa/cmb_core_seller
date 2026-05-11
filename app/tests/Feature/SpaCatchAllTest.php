<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaCatchAllTest extends TestCase
{
    use RefreshDatabase; // the oauth-callback test touches oauth_states

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

    public function test_oauth_callback_with_a_bad_state_redirects_into_the_spa(): void
    {
        // No valid oauth_states row -> friendly redirect to /channels?error=oauth_state (not a 5xx, not the SPA shell).
        $this->get('/oauth/tiktok/callback?code=abc&state=does-not-exist')
            ->assertRedirect('/channels?error=oauth_state');
    }

    public function test_webhook_rejects_an_unsigned_request(): void
    {
        // No valid Authorization signature -> 401, nothing stored. (Happy-path webhook ingest is covered in TikTokWebhookTest.)
        $this->postJson('/webhook/tiktok', ['type' => 1, 'data' => []])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'INVALID_SIGNATURE');
    }
}
