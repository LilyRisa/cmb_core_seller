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

    private function makeDraft(array $payload, array $overrides = []): AdDraft
    {
        app(CurrentTenant::class)->set(Tenant::create(['name' => 'T']));
        AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK']);

        return AdDraft::create(array_merge([
            'ad_account_id' => AdAccount::query()->value('id'),
            'name' => 'Tết', 'objective' => 'messages', 'payload' => $payload,
        ], $overrides));
    }

    /** @return array<string,mixed> two ad sets × two ads */
    private function treePayload(): array
    {
        $ad = fn (string $k, string $post) => ['key' => $k, 'name' => $k, 'creative' => ['page_id' => '1', 'page_post_id' => $post, 'cta' => 'MESSAGE_PAGE']];

        return ['adsets' => [
            ['key' => 'a1', 'name' => 'N1', 'budget' => ['daily_major' => 100000], 'targeting' => [], 'external_id' => null, 'ads' => [$ad('d1', '1_1'), $ad('d2', '1_2')]],
            ['key' => 'a2', 'name' => 'N2', 'budget' => ['daily_major' => 200000], 'targeting' => [], 'external_id' => null, 'ads' => [$ad('d3', '1_3'), $ad('d4', '1_4')]],
        ]];
    }

    private function fakeGraph(?int $failAdsetIndex = null): void
    {
        $as = 0;
        $ad = 0;
        Http::fake(function ($request) use (&$as, &$ad, $failAdsetIndex) {
            $u = $request->url();
            if (str_contains($u, '/campaigns')) {
                return Http::response(['id' => 'C9'], 200);
            }
            if (str_contains($u, '/adsets')) {
                $as++;
                if ($failAdsetIndex !== null && $as === $failAdsetIndex) {
                    return Http::response(['error' => ['message' => 'bad']], 400);
                }

                return Http::response(['id' => "AS{$as}"], 200);
            }
            if (str_contains($u, '/ads')) {
                $ad++;

                return Http::response(['id' => "AD{$ad}"], 200);
            }

            return Http::response([], 200);
        });
    }

    private function dispatch(AdDraft $draft): void
    {
        (new PublishAdDraft($draft->id))->handle(app(AdsRegistry::class), app(AdDraftSpecMapper::class));
    }

    public function test_publishes_full_tree(): void
    {
        $this->fakeGraph();
        $draft = $this->makeDraft($this->treePayload());

        $this->dispatch($draft);
        $draft->refresh();

        $this->assertSame('published', $draft->status);
        $this->assertSame('C9', $draft->campaign_external_id);
        $sets = $draft->payload['adsets'];
        $this->assertSame('AS1', $sets[0]['external_id']);
        $this->assertSame('AS2', $sets[1]['external_id']);
        $this->assertSame('AD1', $sets[0]['ads'][0]['external_id']);
        $this->assertSame('AD2', $sets[0]['ads'][1]['external_id']);
        $this->assertSame('AD3', $sets[1]['ads'][0]['external_id']);
        $this->assertSame('AD4', $sets[1]['ads'][1]['external_id']);
    }

    public function test_resume_skips_nodes_with_ids(): void
    {
        $this->fakeGraph();
        $payload = $this->treePayload();
        $payload['adsets'][0]['external_id'] = 'ASX';
        $payload['adsets'][0]['ads'][0]['external_id'] = 'ADX1';
        $payload['adsets'][0]['ads'][1]['external_id'] = 'ADX2';
        $draft = $this->makeDraft($payload, ['campaign_external_id' => 'CX']);

        $this->dispatch($draft);
        $draft->refresh();

        $this->assertSame('published', $draft->status);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/campaigns'));
        $sets = $draft->payload['adsets'];
        $this->assertSame('ASX', $sets[0]['external_id']);   // unchanged
        $this->assertSame('AS1', $sets[1]['external_id']);   // only adset 2 created
        $this->assertSame('AD1', $sets[1]['ads'][0]['external_id']);
    }

    public function test_failure_keeps_partial_tree(): void
    {
        $this->fakeGraph(failAdsetIndex: 2);   // 2nd ad set create fails
        $draft = $this->makeDraft($this->treePayload());

        $this->dispatch($draft);
        $draft->refresh();

        $this->assertSame('failed', $draft->status);
        $this->assertNotNull($draft->last_error);
        $sets = $draft->payload['adsets'];
        $this->assertSame('AS1', $sets[0]['external_id']);          // 1st kept
        $this->assertSame('AD1', $sets[0]['ads'][0]['external_id']);
        $this->assertNull($sets[1]['external_id'] ?? null);          // 2nd not created
    }

    public function test_legacy_flat_payload_publishes_as_one_adset_one_ad(): void
    {
        $this->fakeGraph();
        $draft = $this->makeDraft([
            'budget' => ['daily_major' => 150000], 'targeting' => [],
            'creative' => ['page_id' => '1', 'page_post_id' => '1_1', 'cta' => 'MESSAGE_PAGE'],
        ]);

        $this->dispatch($draft);
        $draft->refresh();

        $this->assertSame('published', $draft->status);
        $this->assertCount(1, $draft->payload['adsets']);
        $this->assertSame('AS1', $draft->payload['adsets'][0]['external_id']);
        $this->assertSame('AD1', $draft->payload['adsets'][0]['ads'][0]['external_id']);
    }
}
