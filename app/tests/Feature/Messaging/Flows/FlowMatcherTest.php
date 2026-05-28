<?php

namespace Tests\Feature\Messaging\Flows;

use CMBcoreSeller\Modules\Messaging\Models\AutomationFlow;
use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlowMatcherTest extends TestCase
{
    use RefreshDatabase;

    private function flow(string $trigger, array $cfg = [], array $over = []): AutomationFlow
    {
        return AutomationFlow::create(array_merge([
            'tenant_id' => 1, 'name' => 'F', 'provider' => 'facebook_page',
            'status' => AutomationFlow::STATUS_ACTIVE, 'trigger_type' => $trigger,
            'trigger_config' => $cfg, 'enabled' => true,
            'graph' => ['nodes' => [['id' => 't', 'type' => 'trigger', 'data' => []]], 'edges' => []],
        ], $over));
    }

    private function commentConv(?string $postId): Conversation
    {
        static $seq = 0;
        $seq++;

        return Conversation::create([
            'tenant_id' => 1, 'channel_account_id' => 1, 'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_COMMENT, 'external_conversation_id' => 'cmt'.$seq,
            'buyer_external_id' => 'b1', 'status' => 'open', 'message_count' => 1,
            'meta' => $postId !== null ? ['fb_post_id' => $postId] : [],
        ]);
    }

    private function matcher(): FlowMatcher
    {
        return app(FlowMatcher::class);
    }

    public function test_comment_on_post_matches_only_configured_post(): void
    {
        $this->flow(AutomationFlow::TRIGGER_COMMENT_ON_POST, ['post_ids' => ['POST_123']]);

        $hit = $this->matcher()->matching($this->commentConv('POST_123'), [AutomationFlow::TRIGGER_COMMENT_ON_POST]);
        $miss = $this->matcher()->matching($this->commentConv('POST_999'), [AutomationFlow::TRIGGER_COMMENT_ON_POST]);

        $this->assertCount(1, $hit);
        $this->assertCount(0, $miss);
    }

    public function test_inbox_keyword_matches_only_on_keyword(): void
    {
        $this->flow(AutomationFlow::TRIGGER_INBOX_KEYWORD, ['keywords' => ['giá']]);
        $conv = Conversation::create([
            'tenant_id' => 1, 'channel_account_id' => 1, 'provider' => 'facebook_page',
            'thread_type' => Conversation::THREAD_MESSAGE, 'external_conversation_id' => 'c1',
            'buyer_external_id' => 'b1', 'status' => 'open', 'message_count' => 1,
        ]);

        $this->assertCount(1, $this->matcher()->matching($conv, [AutomationFlow::TRIGGER_INBOX_KEYWORD], 'cho hỏi GIÁ'));
        $this->assertCount(0, $this->matcher()->matching($conv, [AutomationFlow::TRIGGER_INBOX_KEYWORD], 'xin chào'));
    }

    public function test_disabled_and_non_active_flows_excluded(): void
    {
        $this->flow(AutomationFlow::TRIGGER_COMMENT_ANY, [], ['enabled' => false]);
        $this->flow(AutomationFlow::TRIGGER_COMMENT_ANY, [], ['status' => AutomationFlow::STATUS_PAUSED]);

        $this->assertCount(0, $this->matcher()->matching($this->commentConv('P'), [AutomationFlow::TRIGGER_COMMENT_ANY]));
    }
}
