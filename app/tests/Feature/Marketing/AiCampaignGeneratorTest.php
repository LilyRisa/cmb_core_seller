<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Contracts\MarketingAnalysisClient;
use CMBcoreSeller\Modules\Marketing\Models\AdAccount;
use CMBcoreSeller\Modules\Marketing\Services\AiCampaignGenerator;
use CMBcoreSeller\Modules\Marketing\Services\AiCampaignRequest;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiCampaignGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private function account(): AdAccount
    {
        app(CurrentTenant::class)->set(Tenant::create(['name' => 'T']));

        return AdAccount::create(['provider' => 'facebook', 'external_account_id' => 'act_1', 'currency' => 'VND', 'status' => 'active', 'access_token' => 'TOK']);
    }

    private function bindAi(array $payload): void
    {
        $this->app->instance(MarketingAnalysisClient::class, new class($payload) implements MarketingAnalysisClient
        {
            public function __construct(private array $payload) {}

            public function analyze(array $data, string $instruction, ?string $schema = null, ?\Closure $fallback = null, ?int $tenantId = null): array
            {
                return ['payload' => $this->payload, 'provider_code' => 'stub-test', 'model' => 'm'];
            }
        });
    }

    private function request(AdAccount $acc, array $over = []): AiCampaignRequest
    {
        return new AiCampaignRequest(...array_merge([
            'adAccountId' => $acc->id, 'tenantId' => $acc->tenant_id, 'userId' => null,
            'objective' => 'messages', 'mode' => 'test', 'optimizationMode' => 'advantage_plus',
            'pageId' => '655064411022030', 'pagePostId' => '655064411022030_122',
            'caption' => 'Sale lớn', 'likes' => 10, 'comments' => 2, 'shares' => 1,
            'linkUrl' => null, 'ctaType' => 'MESSAGE_PAGE', 'landingText' => null,
            'pixelId' => null, 'conversionEvent' => null, 'startTime' => null,
            'currency' => 'VND', 'timezone' => 'Asia/Ho_Chi_Minh', 'prompt' => 'Chạy test bài này',
        ], $over));
    }

    public function test_generates_valid_draft_with_clamped_budget_and_post_creative(): void
    {
        $this->bindAi([
            'campaign' => ['budget_mode' => 'adset'],
            'adsets' => [[
                'name' => 'AI Nhóm', 'budget' => ['daily_major' => 10000], // dưới min → clamp
                'targeting' => ['geo_locations' => ['countries' => ['VN']], 'age_min' => 22, 'age_max' => 45],
                'placement_config' => ['automatic' => true],
                'ads' => [['name' => 'AI QC', 'creative' => ['cta' => 'MESSAGE_PAGE']]],
            ]],
            'recommendations' => ['Theo dõi CPM 3 ngày rồi scale'],
        ]);
        $acc = $this->account();

        $res = app(AiCampaignGenerator::class)->generate($this->request($acc));

        $draft = $res['draft'];
        $this->assertSame('draft', $draft->status);
        $this->assertSame('messages', $draft->objective);
        $node = $draft->payload['adsets'][0];
        $this->assertGreaterThanOrEqual(50000, $node['budget']['daily_major']); // clamp guardrail
        $this->assertSame('655064411022030_122', $node['ads'][0]['creative']['page_post_id']); // gắn từ request
        $this->assertSame('655064411022030', $node['ads'][0]['creative']['page_id']);
        $this->assertNotEmpty($node['schedule']['start_time']); // lịch được set
        $this->assertContains('Theo dõi CPM 3 ngày rồi scale', $res['recommendations']);
    }

    public function test_conversions_objective_sets_pixel_and_link(): void
    {
        $this->bindAi([
            'campaign' => ['budget_mode' => 'adset'],
            'adsets' => [['name' => 'N', 'budget' => ['daily_major' => 200000], 'targeting' => ['geo_locations' => ['countries' => ['VN']]], 'ads' => [['name' => 'A', 'creative' => []]]]],
            'recommendations' => [],
        ]);
        $acc = $this->account();

        $res = app(AiCampaignGenerator::class)->generate($this->request($acc, [
            'objective' => 'conversions', 'pixelId' => '995567938729307',
            'conversionEvent' => 'COMPLETE_REGISTRATION', 'linkUrl' => 'https://shop.vn/dang-ky',
        ]));

        $node = $res['draft']->payload['adsets'][0];
        $this->assertSame('995567938729307', $node['conversion']['pixel_id']);
        $this->assertSame('COMPLETE_REGISTRATION', $node['conversion']['custom_event_type']);
        $this->assertSame('https://shop.vn/dang-ky', $node['ads'][0]['creative']['link_url']);
    }
}
