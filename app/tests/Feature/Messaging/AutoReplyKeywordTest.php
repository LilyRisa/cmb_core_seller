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
use CMBcoreSeller\Modules\Messaging\Models\AutoReplyRun;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\Message;
use CMBcoreSeller\Modules\Messaging\Services\AutoReplyEngine;
use CMBcoreSeller\Modules\Messaging\Services\MessageIngestionService;
use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Feature G+H — auto-reply theo từ khoá (keyword trigger).
 *
 * - Rule khớp khi body tin nhắn inbound chứa ít nhất 1 keyword (case-insensitive substring).
 * - Khi nhiều keyword-rule khớp, chỉ rule có NHIỀU matched-keyword nhất được fire (G+H).
 * - Tôn trọng enabled=false, cooldown, và idempotency đúng như các trigger khác.
 */
class AutoReplyKeywordTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Tenant $tenant;

    private ChannelAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingPlanSeeder::class);
        Queue::fake(); // không chạy SendMessage job — chỉ kiểm message được tạo

        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->tenant = Tenant::create(['name' => 'KeywordShop']);
        $this->tenant->users()->attach($this->owner->getKey(), ['role' => Role::Owner->value]);
        $plan = Plan::query()->where('code', Plan::CODE_PRO)->firstOrFail();
        Subscription::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->account = ChannelAccount::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'provider' => 'manual',
            'external_shop_id' => 'shop_kw_1',
            'shop_name' => 'Keyword Shop',
            'status' => 'active',
            'messaging_enabled' => true,
        ]);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function rule(array $attrs): AutoReplyRule
    {
        return AutoReplyRule::query()->create(array_merge([
            'tenant_id' => $this->tenant->getKey(),
            'name' => 'kw-rule',
            'trigger' => AutoReplyRule::TRIGGER_KEYWORD,
            'enabled' => true,
            'cooldown_seconds' => 0,
            'priority' => 100,
            'action' => ['kind' => 'raw', 'raw_text' => 'auto reply'],
        ], $attrs));
    }

    private function ingestInbound(string $convExt, string $msgExt, string $body): Conversation
    {
        $ingest = app(MessageIngestionService::class);
        $dto = new MessageDTO(
            externalConversationId: $convExt,
            externalMessageId: $msgExt,
            buyerExternalId: 'buyer_kw',
            direction: MessageDirection::Inbound,
            kind: MessageKind::Text,
            body: $body,
        );
        $res = $ingest->ingest($this->account, $dto);
        $ingest->fireEventsForNewMessage($res['conversation'], $res['message'], $res['created']);

        return $res['conversation'];
    }

    private function outboundMessages(int $convId): Collection
    {
        return Message::withoutGlobalScopes()
            ->where('conversation_id', $convId)
            ->where('direction', Message::DIRECTION_OUTBOUND)
            ->get();
    }

    private function outboundCount(int $convId): int
    {
        return $this->outboundMessages($convId)->count();
    }

    private function engine(): AutoReplyEngine
    {
        return app(AutoReplyEngine::class);
    }

    // -----------------------------------------------------------------------
    // Feature G — basic keyword matching
    // -----------------------------------------------------------------------

    /**
     * Keyword rule với keywords ["giá","ship"] PHẢI fire khi body chứa 1 trong 2.
     */
    public function test_keyword_rule_fires_when_body_contains_keyword(): void
    {
        $rule = $this->rule([
            'trigger_config' => ['keywords' => ['giá', 'ship']],
            'action' => ['kind' => 'raw', 'raw_text' => 'Giá và phí ship như sau ạ!'],
        ]);

        $conv = $this->ingestInbound('kw_c1', 'kw_m1', 'cho hỏi giá ship?');

        $msgs = $this->outboundMessages($conv->id);
        $this->assertCount(1, $msgs);
        $this->assertSame('Giá và phí ship như sau ạ!', $msgs->first()->body);
        $this->assertSame($rule->id, $msgs->first()->meta['auto_rule_id'] ?? null);

        $this->assertDatabaseHas('auto_reply_runs', [
            'rule_id' => $rule->id,
            'conversation_id' => $conv->id,
            'status' => AutoReplyRun::STATUS_FIRED,
        ]);
    }

    /**
     * Cùng rule KHÔNG fire khi body KHÔNG chứa bất kỳ keyword nào.
     */
    public function test_keyword_rule_does_not_fire_when_no_keyword_in_body(): void
    {
        $this->rule([
            'trigger_config' => ['keywords' => ['giá', 'ship']],
            'action' => ['kind' => 'raw', 'raw_text' => 'Giá...'],
        ]);

        $conv = $this->ingestInbound('kw_c2', 'kw_m2', 'xin chào');

        $this->assertSame(0, $this->outboundCount($conv->id));
    }

    /**
     * Keyword matching không phân biệt hoa/thường.
     */
    public function test_keyword_matching_is_case_insensitive(): void
    {
        $this->rule([
            'trigger_config' => ['keywords' => ['GIÁ']],
            'action' => ['kind' => 'raw', 'raw_text' => 'OK'],
        ]);

        $conv = $this->ingestInbound('kw_c3', 'kw_m3', 'cho hỏi giá hàng');

        $this->assertSame(1, $this->outboundCount($conv->id));
    }

    // -----------------------------------------------------------------------
    // Feature H — overlap: most-specific (highest matched count) wins
    // -----------------------------------------------------------------------

    /**
     * Rule A: keywords ["giá"] (1 keyword).
     * Rule B: keywords ["giá","ship"] (2 keywords).
     * Inbound: "giá ship bao nhiêu" ⇒ B có matched=2 > A matched=1 ⇒ chỉ B fire.
     */
    public function test_most_specific_keyword_rule_wins_on_overlap(): void
    {
        $ruleA = $this->rule([
            'name' => 'rule-A',
            'trigger_config' => ['keywords' => ['giá']],
            'action' => ['kind' => 'raw', 'raw_text' => 'Reply từ rule A'],
            'priority' => 10,
        ]);

        $ruleB = $this->rule([
            'name' => 'rule-B',
            'trigger_config' => ['keywords' => ['giá', 'ship']],
            'action' => ['kind' => 'raw', 'raw_text' => 'Reply từ rule B'],
            'priority' => 20, // priority cao hơn (kém ưu tiên hơn A) nhưng matched nhiều hơn
        ]);

        $conv = $this->ingestInbound('kw_c4', 'kw_m4', 'giá ship bao nhiêu');

        $msgs = $this->outboundMessages($conv->id);
        $this->assertCount(1, $msgs, 'Chỉ 1 auto-reply được gửi khi nhiều rule khớp');
        $this->assertSame('Reply từ rule B', $msgs->first()->body, 'Rule B (2 matched) phải thắng rule A (1 matched)');
        $this->assertSame($ruleB->id, $msgs->first()->meta['auto_rule_id'] ?? null);

        // Rule A không được fire
        $this->assertDatabaseMissing('auto_reply_runs', [
            'rule_id' => $ruleA->id,
            'conversation_id' => $conv->id,
            'status' => AutoReplyRun::STATUS_FIRED,
        ]);
    }

    /**
     * Tie-break: cùng matched count ⇒ rule priority thấp hơn (ưu tiên cao hơn) thắng.
     */
    public function test_priority_tiebreak_when_same_matched_count(): void
    {
        $ruleHigh = $this->rule([
            'name' => 'high-priority',
            'trigger_config' => ['keywords' => ['giá']],
            'action' => ['kind' => 'raw', 'raw_text' => 'Ưu tiên cao'],
            'priority' => 10,
        ]);

        $ruleLow = $this->rule([
            'name' => 'low-priority',
            'trigger_config' => ['keywords' => ['giá']],
            'action' => ['kind' => 'raw', 'raw_text' => 'Ưu tiên thấp'],
            'priority' => 50,
        ]);

        $conv = $this->ingestInbound('kw_c5', 'kw_m5', 'hỏi giá đi');

        $msgs = $this->outboundMessages($conv->id);
        $this->assertCount(1, $msgs);
        $this->assertSame('Ưu tiên cao', $msgs->first()->body);
        $this->assertSame($ruleHigh->id, $msgs->first()->meta['auto_rule_id'] ?? null);
    }

    // -----------------------------------------------------------------------
    // enabled=false
    // -----------------------------------------------------------------------

    public function test_disabled_keyword_rule_does_not_fire(): void
    {
        $this->rule([
            'trigger_config' => ['keywords' => ['giá']],
            'action' => ['kind' => 'raw', 'raw_text' => 'không nên fire'],
            'enabled' => false,
        ]);

        $conv = $this->ingestInbound('kw_c6', 'kw_m6', 'cho hỏi giá');

        $this->assertSame(0, $this->outboundCount($conv->id));
    }

    // -----------------------------------------------------------------------
    // Cooldown / Idempotency
    // -----------------------------------------------------------------------

    /**
     * Cooldown: sau khi rule đã fire, tin nhắn thứ 2 cùng keyword trong thời gian cooldown
     * KHÔNG được fire lại.
     */
    public function test_cooldown_prevents_double_fire(): void
    {
        $this->rule([
            'trigger_config' => ['keywords' => ['giá']],
            'action' => ['kind' => 'raw', 'raw_text' => 'OK'],
            'cooldown_seconds' => 3600, // 1 giờ
        ]);

        // Tin 1 — fire
        $conv = $this->ingestInbound('kw_cool', 'kw_m_c1', 'hỏi giá');
        $this->assertSame(1, $this->outboundCount($conv->id));

        // Tin 2 trong cùng conversation — còn trong cooldown ⇒ không fire
        $this->ingestInbound('kw_cool', 'kw_m_c2', 'hỏi giá lại');
        $this->assertSame(1, $this->outboundCount($conv->id));
    }

    /**
     * Idempotency: replay cùng inbound_body+rule → window_key trùng → skip.
     */
    public function test_idempotency_on_replay(): void
    {
        $rule = $this->rule([
            'trigger_config' => ['keywords' => ['giá']],
            'action' => ['kind' => 'raw', 'raw_text' => 'OK idempotent'],
            'cooldown_seconds' => 0,
        ]);

        $conv = Conversation::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'channel_account_id' => $this->account->id,
            'provider' => 'manual',
            'external_conversation_id' => 'kw_idemp',
            'buyer_external_id' => 'b',
            'status' => Conversation::STATUS_OPEN,
        ]);

        $context = ['inbound_body' => 'hỏi giá đi ạ'];
        $engine = $this->engine();

        $run1 = $engine->fire($conv->fresh(), AutoReplyRule::TRIGGER_KEYWORD, $context);
        $run2 = $engine->fire($conv->fresh(), AutoReplyRule::TRIGGER_KEYWORD, $context);

        $this->assertNotNull($run1);
        $this->assertNull($run2, 'Replay cùng window_key phải bị idempotency block');

        $this->assertSame(1, $this->outboundCount($conv->id));
    }

    // -----------------------------------------------------------------------
    // Keyword trigger không fire khi first_message đã fire (listener chain)
    // -----------------------------------------------------------------------

    /**
     * Listener fires first_message trước; nếu first_message fires thì keyword KHÔNG
     * được chạy nữa (tránh double auto-reply trên cùng 1 tin nhắn đầu tiên).
     */
    public function test_keyword_not_fired_when_first_message_already_fired(): void
    {
        // first_message rule
        AutoReplyRule::query()->create([
            'tenant_id' => $this->tenant->getKey(),
            'name' => 'welcome',
            'trigger' => AutoReplyRule::TRIGGER_FIRST_MESSAGE,
            'trigger_config' => [],
            'enabled' => true,
            'cooldown_seconds' => 0,
            'priority' => 1,
            'action' => ['kind' => 'raw', 'raw_text' => 'Chào mừng!'],
        ]);

        // keyword rule
        $this->rule([
            'trigger_config' => ['keywords' => ['giá']],
            'action' => ['kind' => 'raw', 'raw_text' => 'Hỏi giá ạ'],
        ]);

        // Tin đầu tiên có chứa keyword — first_message rule sẽ thắng ở listener
        $conv = $this->ingestInbound('kw_fm', 'kw_fm_m1', 'hỏi giá');

        // Chỉ 1 auto-reply (first_message), không có thêm keyword reply
        $this->assertSame(1, $this->outboundCount($conv->id));
        $msgs = $this->outboundMessages($conv->id);
        $this->assertSame('Chào mừng!', $msgs->first()->body);
    }
}
