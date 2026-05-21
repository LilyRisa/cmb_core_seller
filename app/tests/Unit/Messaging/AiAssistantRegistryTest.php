<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\Exceptions\ProviderNotConfigured;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiAssistantRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_two_instances_of_same_adapter_with_own_code(): void
    {
        $reg = app(AiAssistantRegistry::class); // đã register adapter ở IntegrationsServiceProvider
        AiProvider::query()->create(['code' => 'deepseek-prod', 'adapter' => 'openai_compatible', 'is_active' => true, 'default_model' => 'deepseek-chat', 'base_url' => 'https://api.deepseek.com']);
        AiProvider::query()->create(['code' => 'qwen-cheap', 'adapter' => 'openai_compatible', 'is_active' => true, 'default_model' => 'qwen-plus', 'base_url' => 'https://dashscope-intl.aliyuncs.com/compatible-mode']);

        $a = $reg->for('deepseek-prod');
        $b = $reg->for('qwen-cheap');

        $this->assertSame('deepseek-prod', $a->code());
        $this->assertSame('qwen-cheap', $b->code());
    }

    public function test_inactive_provider_throws(): void
    {
        $reg = app(AiAssistantRegistry::class);
        AiProvider::query()->create(['code' => 'gemini-flash', 'adapter' => 'openai_compatible', 'is_active' => false]);
        $this->expectException(ProviderNotConfigured::class);
        $reg->for('gemini-flash');
    }

    public function test_adapters_lists_registered_keys(): void
    {
        $reg = app(AiAssistantRegistry::class);
        $this->assertEqualsCanonicalizing(['anthropic', 'openai_compatible', 'manual'], $reg->adapters());
    }
}
