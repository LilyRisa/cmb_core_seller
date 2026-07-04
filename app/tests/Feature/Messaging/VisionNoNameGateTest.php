<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\OpenAi\OpenAiConnector;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VisionNoNameGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_analyze_images_attempts_regardless_of_model_name(): void
    {
        // Model tên KHÔNG "vision" vẫn phải được gửi ảnh (không gate tên).
        AiProvider::query()->create(['code'=>'p','adapter'=>'openai_compatible','is_active'=>true,'role'=>'vision','api_key'=>'sk-x','base_url'=>'https://api.x.com','default_model'=>'mn/Minimax-M3','vision_verified'=>true]);
        Http::fake(['api.x.com/*' => Http::response(['choices'=>[['message'=>['content'=>'{"match":1}']]]], 200)]);

        $out = app()->makeWith(OpenAiConnector::class, ['code'=>'p'])->analyzeImages(
            new AiContext(tenantId: 1, providerCode: 'p'), ['data:image/png;base64,AAAA'], 'pick');

        $this->assertStringContainsString('match', $out);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/chat/completions'));
    }
}
