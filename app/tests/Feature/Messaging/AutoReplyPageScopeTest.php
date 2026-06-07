<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Messaging\DTO\MessageDirection;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageDTO;
use CMBcoreSeller\Integrations\Messaging\DTO\MessageKind;
use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Billing\Database\Seeders\BillingPlanSeeder;
use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Messaging\Models\AutoReplyRule;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * SPEC 0035 — auto-reply theo từng page.
 * Rule gán page cụ thể chỉ fire cho page đó; rule applies_all_pages fire mọi page;
 * page-specific thắng all-pages (first-wins).
 */
class AutoReplyPageScopeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ChannelAccount $pageA;

    private ChannelAccount $pageB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        Queue::fake();

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'MultiPageShop']);
        $this->tenant->users()->attach($owner->getKey(), ['role' => Role::Owner->value]);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE, 'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);

        $this->pageA = $this->account('page_a');
        $this->pageB = $this->account('page_b');
    }

    private function account(string $ext): ChannelAccount
    {
        return ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(), 'provider' => 'facebook_page',
            'external_shop_id' => $ext, 'shop_name' => $ext, 'status' => 'active', 'messaging_enabled' => true,
        ]);
    }

    private function firstMessageRule(string $reply, bool $allPages, ?ChannelAccount $page = null, int $priority = 100): AutoReplyRule
    {
        $rule = AutoReplyRule::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'name' => $reply,
            'trigger' => AutoReplyRule::TRIGGER_FIRST_MESSAGE,
            'trigger_config' => [],
            'enabled' => true,
            'applies_all_pages' => $allPages,
            'cooldown_seconds' => 0,
            'priority' => $priority,
            'action' => ['kind' => 'raw', 'raw_text' => $reply],
        ]);
        if ($page !== null) {
            $rule->pages()->attach($page->getKey(), ['tenant_id' => $this->tenant->getKey()]);
        }

        return $rule;
    }

    private function ingestFirstInbound(ChannelAccount $page, string $ext): Conversation
    {
        $ingest = app(MessageIngestionService::class);
        $res = $ingest->ingest($page, new MessageDTO(
            externalConversationId: $ext, externalMessageId: $ext.'_m1', buyerExternalId: 'buyer',
            direction: MessageDirection::Inbound, kind: MessageKind::Text, body: 'xin chào',
        ));
        $ingest->fireEventsForNewMessage($res['conversation'], $res['message'], $res['created']);

        return $res['conversation'];
    }

    private function lastOutbound(int $convId): ?Message
    {
        return Message::withoutGlobalScopes()
            ->where('conversation_id', $convId)->where('direction', Message::DIRECTION_OUTBOUND)
            ->latest('id')->first();
    }

    private function outboundCount(int $convId): int
    {
        return Message::withoutGlobalScopes()
            ->where('conversation_id', $convId)->where('direction', Message::DIRECTION_OUTBOUND)->count();
    }

    public function test_page_specific_rule_fires_only_for_that_page(): void
    {
        $this->firstMessageRule('CHỈ PAGE A', allPages: false, page: $this->pageA);

        $convA = $this->ingestFirstInbound($this->pageA, 'conv_a');
        $convB = $this->ingestFirstInbound($this->pageB, 'conv_b');

        $this->assertSame(1, $this->outboundCount($convA->id), 'fire cho page A');
        $this->assertSame('CHỈ PAGE A', $this->lastOutbound($convA->id)?->body);
        $this->assertSame(0, $this->outboundCount($convB->id), 'KHÔNG fire cho page B');
    }

    public function test_all_pages_rule_fires_for_every_page(): void
    {
        $this->firstMessageRule('MỌI TRANG', allPages: true);

        $convA = $this->ingestFirstInbound($this->pageA, 'conv_a2');
        $convB = $this->ingestFirstInbound($this->pageB, 'conv_b2');

        $this->assertSame(1, $this->outboundCount($convA->id));
        $this->assertSame(1, $this->outboundCount($convB->id));
    }

    public function test_page_specific_beats_all_pages_on_same_page(): void
    {
        // all-pages priority cao hơn (10) nhưng page-specific phải thắng (SPEC 0035).
        $this->firstMessageRule('MỌI TRANG', allPages: true, priority: 10);
        $this->firstMessageRule('PAGE A RIÊNG', allPages: false, page: $this->pageA, priority: 100);

        $convA = $this->ingestFirstInbound($this->pageA, 'conv_a3');

        $this->assertSame(1, $this->outboundCount($convA->id), 'chỉ 1 auto-reply (first-wins)');
        $this->assertSame('PAGE A RIÊNG', $this->lastOutbound($convA->id)?->body, 'page-specific thắng all-pages');
    }
}
