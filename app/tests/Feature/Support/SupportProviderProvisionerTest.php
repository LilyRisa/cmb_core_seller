<?php

namespace Tests\Feature\Support;

use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Support\Services\SupportProviderProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Tự provision AI provider RIÊNG cho support từ env. */
class SupportProviderProvisionerTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_dedicated_support_provider_when_api_key_set(): void
    {
        config([
            'support.assistant.provider_code' => 'support',
            'support.assistant.api_key' => 'sk-support-xxx',
            'support.assistant.base_url' => 'https://api.openai.com',
            'support.assistant.chat_model' => 'gpt-4o-mini',
        ]);

        $res = app(SupportProviderProvisioner::class)->ensure();

        $this->assertTrue($res['provisioned']);
        $this->assertSame('support', $res['code']);

        $row = AiProvider::query()->find('support');
        $this->assertNotNull($row);
        $this->assertSame('openai_compatible', $row->adapter);
        $this->assertTrue((bool) $row->is_active);
        $this->assertSame('sk-support-xxx', $row->api_key); // decrypt qua cast
        $this->assertSame('gpt-4o-mini', $row->default_model);
        // Key encrypted at rest.
        $this->assertNotSame('sk-support-xxx', $row->getRawOriginal('api_key'));
    }

    public function test_skips_when_no_api_key(): void
    {
        config([
            'support.assistant.provider_code' => 'support',
            'support.assistant.api_key' => '',
        ]);

        $res = app(SupportProviderProvisioner::class)->ensure();

        $this->assertFalse($res['provisioned']);
        $this->assertSame('no_api_key', $res['reason']);
        $this->assertNull(AiProvider::query()->find('support'));
    }

    public function test_is_idempotent_updates_existing(): void
    {
        config(['support.assistant.provider_code' => 'support', 'support.assistant.api_key' => 'sk-1']);
        app(SupportProviderProvisioner::class)->ensure();

        config(['support.assistant.api_key' => 'sk-2', 'support.assistant.chat_model' => 'gpt-4o']);
        app(SupportProviderProvisioner::class)->ensure();

        $this->assertSame(1, AiProvider::query()->where('code', 'support')->count());
        $row = AiProvider::query()->find('support');
        $this->assertSame('sk-2', $row->api_key);
        $this->assertSame('gpt-4o', $row->default_model);
    }
}
