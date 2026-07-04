<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderRoleFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_providers_filters_by_role(): void
    {
        AiProvider::query()->create(['code' => 'chat1', 'adapter' => 'openai_compatible', 'is_active' => true, 'role' => 'chat', 'base_url' => 'https://h', 'default_model' => 'm']);
        AiProvider::query()->create(['code' => 'vis1', 'adapter' => 'openai_compatible', 'is_active' => true, 'role' => 'vision', 'base_url' => 'https://h', 'default_model' => 'm']);
        AiProvider::query()->create(['code' => 'stt1', 'adapter' => 'openai_compatible', 'is_active' => true, 'role' => 'transcription', 'base_url' => 'https://h', 'default_model' => 'm']);

        $reg = app(AiAssistantRegistry::class);
        $this->assertSame(['chat1'], $reg->activeProviders());          // default chat
        $this->assertSame(['vis1'], $reg->activeProviders('vision'));
        $this->assertSame(['stt1'], $reg->activeProviders('transcription'));
    }

    public function test_chat_default_ignores_non_chat_providers(): void
    {
        // vision provider sort_order 0 KHÔNG được thành chat mặc định.
        AiProvider::query()->create(['code' => 'visA', 'adapter' => 'openai_compatible', 'is_active' => true, 'role' => 'vision', 'sort_order' => 0, 'base_url' => 'https://h', 'default_model' => 'm']);
        AiProvider::query()->create(['code' => 'chatB', 'adapter' => 'openai_compatible', 'is_active' => true, 'role' => 'chat', 'sort_order' => 1, 'base_url' => 'https://h', 'default_model' => 'm']);
        $this->assertSame(['chatB'], app(AiAssistantRegistry::class)->activeProviders());
    }
}
