<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\AiAssistantRun;
use CMBcoreSeller\Modules\Messaging\Models\AiProvider;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\AiSuggestionService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Test AI auto-mode + guardrail intent (SPEC-0024 S7 §4.6). Provider Manual:
 * classifyIntent theo keyword (deterministic) ⇒ test escalate vs auto-send.
 */
class MessagingAutoModeTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private Conversation $conv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        Queue::fake();

        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'AutoModeShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $plan = Plan::query()->where('code', Plan::CODE_BUSINESS)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        AiProvider::query()->create(['code' => 'manual', 'adapter' => 'manual', 'is_active' => true]);

        $account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'manual',
            'external_shop_id' => 's', 'shop_name' => 's', 'status' => 'active', 'messaging_enabled' => true,
        ]);
        $this->conv = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'channel_account_id' => $account->id, 'provider' => 'manual',
            'external_conversation_id' => 'c', 'buyer_external_id' => 'b', 'buyer_name' => 'Khách',
            'status' => Conversation::STATUS_OPEN, 'last_inbound_at' => now(),
        ]);
    }

    private function service(): AiSuggestionService
    {
        return app(AiSuggestionService::class);
    }

    private function outboundCount(): int
    {
        return Message::withoutGlobalScopes()
            ->where('conversation_id', $this->conv->id)
            ->where('direction', Message::DIRECTION_OUTBOUND)->count();
    }

    public function test_auto_responds_to_safe_intent(): void
    {
        $result = $this->service()->autoRespond($this->conv, 'Đơn của em bao giờ giao ạ?');

        $this->assertSame('sent', $result['action']);
        $this->assertSame('other', $result['intent']);
        $this->assertSame(1, $this->outboundCount());

        $msg = Message::withoutGlobalScopes()->where('conversation_id', $this->conv->id)->where('direction', 'outbound')->first();
        $this->assertTrue((bool) $msg->sent_by_ai);

        $run = AiAssistantRun::withoutGlobalScopes()->latest('id')->first();
        $this->assertSame(AiAssistantRun::MODE_AUTO, $run->mode);
        $this->assertSame(AiAssistantRun::STATUS_SUCCESS, $run->status);
    }

    public function test_escalates_sensitive_intent_without_sending(): void
    {
        $result = $this->service()->autoRespond($this->conv, 'Tôi muốn hoàn tiền ngay lập tức!');

        $this->assertSame('escalated', $result['action']);
        $this->assertSame('refund', $result['intent']);
        $this->assertSame(0, $this->outboundCount());

        $meta = Conversation::withoutGlobalScopes()->find($this->conv->id)->meta;
        $this->assertTrue($meta['requires_human'] ?? false);
    }

    public function test_auto_responds_via_custom_http_provider(): void
    {
        // Chỉ custom_http active ⇒ resolveProviderCode chọn nó (chuỗi đầy đủ: classify → reply → queue).
        AiProvider::query()->where('code', 'manual')->update(['is_active' => false]);
        AiProvider::query()->create([
            'code' => 'my-llm', 'adapter' => 'custom_http', 'is_active' => true,
            'api_key' => 'secret-key', 'base_url' => 'https://llm.example.com/chat', 'default_model' => 'my-model',
            'adapter_config' => [
                'headers' => ['Authorization' => 'Bearer {{api_key}}'],
                'request_template' => '{"model":"{{model}}","system":"{{system}}","messages":{{messages_json}}}',
                'response_path' => 'data.reply.text',
            ],
        ]);

        // 2 lần gọi cùng endpoint: classify trước (trả nhãn an toàn 'other'), rồi reply.
        Http::fake(['llm.example.com/*' => Http::sequence()
            ->push(['data' => ['reply' => ['text' => 'other']]], 200)
            ->push(['data' => ['reply' => ['text' => 'Dạ shop hỗ trợ anh/chị ngay ạ.']]], 200)]);

        $result = $this->service()->autoRespond($this->conv, 'Đơn của em bao giờ giao ạ?');

        $this->assertSame('sent', $result['action']);
        $this->assertSame(1, $this->outboundCount());

        $run = AiAssistantRun::withoutGlobalScopes()->latest('id')->first();
        $this->assertSame(AiAssistantRun::MODE_AUTO, $run->mode);
        $this->assertSame(AiAssistantRun::STATUS_SUCCESS, $run->status);

        // Chuỗi thực sự đi qua CustomHttpConnector (header api_key được nội suy thô).
        Http::assertSent(fn ($req) => str_contains($req->url(), 'llm.example.com/chat')
            && $req->hasHeader('Authorization', 'Bearer secret-key'));
    }

    public function test_settings_accepts_auto_mode(): void
    {
        $this->actingAs($this->owner)->withHeaders(['X-Tenant-Id' => (string) $this->tenant->getKey()])
            ->patchJson('/api/v1/messaging/settings', [
                'ai_provider_code' => 'manual', 'ai_enabled' => true, 'auto_mode' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.auto_mode', true)
            ->assertJsonPath('data.ai_provider_code', 'manual');
    }
}
