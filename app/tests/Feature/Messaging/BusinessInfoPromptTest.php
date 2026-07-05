<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\MessagingAccountMeta;
use CMBcoreSeller\Modules\Messaging\Services\AiSuggestionService;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessInfoPromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_info_block_is_rendered_for_page(): void
    {
        MessagingAccountMeta::withoutGlobalScope(TenantScope::class)->create([
            'channel_account_id' => 55, 'tenant_id' => 1,
            'business_info' => ['shop_name' => 'Shop A', 'phone' => '0909', 'address' => 'Hà Nội'],
        ]);
        $conv = new Conversation(['tenant_id' => 1, 'channel_account_id' => 55]);

        $svc = app(AiSuggestionService::class);
        $method = (new \ReflectionMethod($svc, 'withBusinessInfo'));
        $method->setAccessible(true);
        $out = $method->invoke($svc, 'BASE', $conv);

        $this->assertStringContainsString('BASE', $out);
        $this->assertStringContainsString('Thông tin cửa hàng', $out);
        $this->assertStringContainsString('Shop A', $out);
        $this->assertStringContainsString('0909', $out);
    }

    public function test_no_business_info_returns_extra_unchanged(): void
    {
        $conv = new Conversation(['tenant_id' => 1, 'channel_account_id' => 999]);
        $svc = app(AiSuggestionService::class);
        $method = (new \ReflectionMethod($svc, 'withBusinessInfo'));
        $method->setAccessible(true);

        $this->assertSame('BASE', $method->invoke($svc, 'BASE', $conv));
    }
}
