<?php

namespace Tests\Feature\VisualSearch;

use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Settings\Services\SystemSettingService;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualImageInput;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualItemCandidate;
use CMBcoreSeller\Modules\VisualSearch\Services\VisionReRanker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VisionRerankProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('ai.vision.enabled', true);
        config()->set('ai.vision.models', ['gpt-5', 'gemini']);
        // Credit luôn cho phép — cô lập khỏi Billing.
        $this->app->instance(AiCreditMeter::class, new class implements AiCreditMeter
        {
            public function aiEnabled(int $t): bool
            {
                return true;
            }

            public function canUse(int $t, int $n = 1): bool
            {
                return true;
            }

            public function consume(int $t, int $n = 1): void {}

            public function record(int $t, int $n = 1): void {}

            public function grantPurchase(int $t, int $a): int
            {
                return $a;
            }

            public function summary(int $t): array
            {
                return ['enabled' => true, 'unlimited' => true, 'monthly_allowance' => 0, 'period_used' => 0, 'purchased_balance' => 0, 'available' => null];
            }
        });
    }

    private function makeProvider(string $code, string $model, string $host, string $role = 'chat'): void
    {
        AiProvider::query()->create([
            'code' => $code, 'adapter' => 'openai_compatible', 'is_active' => true, 'role' => $role,
            'api_key' => 'sk-test', 'base_url' => "https://{$host}", 'default_model' => $model,
        ]);
    }

    /** @return list<array{candidate:VisualItemCandidate, image:?string}> */
    private function candidates(): array
    {
        $img = 'data:image/jpeg;base64,'.base64_encode('fake-bytes');

        return [[
            'candidate' => new VisualItemCandidate(itemId: 77, name: 'Áo thun', description: null, attributes: [], confidence: 0.5),
            'image' => $img,
        ]];
    }

    public function test_uses_dedicated_rerank_provider_when_configured(): void
    {
        $this->makeProvider('chat_min', 'mn/Minimax-M3', 'chat.example.com');   // non-vision
        $this->makeProvider('rr_vis', 'ts/gemini-3.5-flash', 'rerank.example.com', 'vision'); // vision — override phải match role này
        app(SystemSettingService::class)->set('visual_search.rerank.provider_code', 'rr_vis');

        Http::fake([
            'rerank.example.com/*' => Http::response(['choices' => [['message' => ['content' => '{"match":1}']]]], 200),
            'chat.example.com/*' => Http::response([], 500),
        ]);

        $ctx = new AiContext(tenantId: 1, providerCode: 'chat_min', model: 'mn/Minimax-M3');
        $picked = app(VisionReRanker::class)->pick(1, 'chat_min', $ctx, VisualImageInput::fromBinary('cust', 'image/jpeg'), $this->candidates());

        $this->assertSame(77, $picked);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'rerank.example.com'));
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'chat.example.com'));
    }

    public function test_falls_back_to_chat_provider_when_unset(): void
    {
        $this->makeProvider('chat_vis', 'ts/gpt-5.4-mini', 'chat.example.com'); // vision chat, no rerank setting

        Http::fake([
            'chat.example.com/*' => Http::response(['choices' => [['message' => ['content' => '{"match":1}']]]], 200),
        ]);

        $ctx = new AiContext(tenantId: 1, providerCode: 'chat_vis', model: 'ts/gpt-5.4-mini');
        $picked = app(VisionReRanker::class)->pick(1, 'chat_vis', $ctx, VisualImageInput::fromBinary('cust', 'image/jpeg'), $this->candidates());

        $this->assertSame(77, $picked);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'chat.example.com'));
    }
}
