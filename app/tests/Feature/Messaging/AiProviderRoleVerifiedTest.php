<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Ai\Contracts\AiProviderCredentials;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AiProviderRoleVerifiedTest extends TestCase
{
    use RefreshDatabase;

    public function test_columns_and_defaults(): void
    {
        foreach (['role','vision_verified','vision_verified_at','vision_verify_error','transcription_verified','transcription_verified_at','transcription_verify_error'] as $c) {
            $this->assertTrue(Schema::hasColumn('ai_providers', $c), "missing {$c}");
        }
        $p = AiProvider::query()->create(['code'=>'x','adapter'=>'openai_compatible','is_active'=>true,'base_url'=>'https://h','default_model'=>'m']);
        $this->assertSame('chat', $p->fresh()->role);
        $this->assertNull($p->fresh()->vision_verified);
    }

    public function test_runtime_config_carries_vision_verified(): void
    {
        AiProvider::query()->create(['code'=>'v','adapter'=>'openai_compatible','is_active'=>true,'role'=>'vision','base_url'=>'https://h','default_model'=>'m','vision_verified'=>true]);
        $cfg = app(AiProviderCredentials::class)->resolve('v');
        $this->assertTrue($cfg->visionVerified);
    }
}
