<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\AdsRegistry;
use CMBcoreSeller\Modules\Marketing\Jobs\PublishAdDraft;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Models\AdDraft;
use CMBcoreSeller\Modules\Marketing\Services\AdDraftSpecMapper;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublishAdDraftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['integrations.ads' => ['facebook']]);
        $this->app->forgetInstance(AdsRegistry::class);
    }

    private function makeDraft(array $overrides = []): AdDraft
    {
        app(CurrentTenant::class)->set(Tenant::create(['name' => 'T']));
        AdAccount::create([
            'provider' => 'facebook', 'external_account_id' => 'act_1',
            'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK',
        ]);

        return AdDraft::create(array_merge([
            'ad_account_id' => AdAccount::query()->value('id'),
            'name' => 'Tết', 'objective' => 'messages',
            'payload' => [
                'budget' => ['daily_major' => 150000],
                'targeting' => ['geo_locations' => ['countries' => ['VN']]],
                'creative' => ['page_id' => '123', 'page_post_id' => '123_456', 'cta' => 'MESSAGE_PAGE'],
            ],
        ], $overrides));
    }

    private function dispatch(AdDraft $draft): void
    {
        (new PublishAdDraft($draft->id))->handle(app(AdsRegistry::class), app(AdDraftSpecMapper::class));
    }

    public function test_publishes_three_levels_and_marks_published(): void
    {
        Http::fake([
            'graph.facebook.com/*/campaigns' => Http::response(['id' => 'C9'], 200),
            'graph.facebook.com/*/adsets' => Http::response(['id' => 'AS9'], 200),
            'graph.facebook.com/*/ads' => Http::response(['id' => 'AD9'], 200),
        ]);

        $draft = $this->makeDraft();
        $this->dispatch($draft);

        $draft->refresh();
        $this->assertSame('published', $draft->status);
        $this->assertSame('C9', $draft->campaign_external_id);
        $this->assertSame('AS9', $draft->adset_external_id);
        $this->assertSame('AD9', $draft->ad_external_id);
    }

    public function test_resume_skips_already_created_levels(): void
    {
        Http::fake([
            'graph.facebook.com/*/adsets' => Http::response(['id' => 'AS9'], 200),
            'graph.facebook.com/*/ads' => Http::response(['id' => 'AD9'], 200),
        ]);

        $draft = $this->makeDraft(['campaign_external_id' => 'C_EXISTING']);
        $this->dispatch($draft);

        $draft->refresh();
        $this->assertSame('published', $draft->status);
        $this->assertSame('C_EXISTING', $draft->campaign_external_id);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/campaigns'));
    }

    public function test_failure_marks_failed_and_records_error(): void
    {
        Http::fake([
            'graph.facebook.com/*/campaigns' => Http::response(['id' => 'C9'], 200),
            'graph.facebook.com/*/adsets' => Http::response(['error' => ['message' => 'bad targeting']], 400),
        ]);

        $draft = $this->makeDraft();
        $this->dispatch($draft);

        $draft->refresh();
        $this->assertSame('failed', $draft->status);
        $this->assertSame('C9', $draft->campaign_external_id);
        $this->assertNull($draft->adset_external_id);
        $this->assertNotNull($draft->last_error);
    }
}
