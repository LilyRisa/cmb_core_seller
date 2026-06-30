<?php

namespace Tests\Unit\Messaging\Flows;

use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\PostRouterNodeExecutor;
use PHPUnit\Framework\TestCase;

class PostRouterNodeExecutorTest extends TestCase
{
    private function ctx(?string $postId): FlowContext
    {
        $conv = new Conversation;
        $conv->meta = $postId !== null ? ['fb_post_id' => $postId] : [];

        return new FlowContext($conv, new FlowRun, null);
    }

    private function node(): FlowNode
    {
        return new FlowNode('n', 'post_router', ['posts' => [
            ['id' => 'PAGE_1_111', 'label' => 'Sale A'],
            ['id' => 'PAGE_1_222', 'label' => 'Sale B'],
        ]]);
    }

    public function test_routes_to_matching_post_handle(): void
    {
        $this->assertSame('PAGE_1_222', (new PostRouterNodeExecutor)->execute($this->node(), $this->ctx('PAGE_1_222'))->handle);
    }

    public function test_unmatched_post_goes_default(): void
    {
        $this->assertSame('default', (new PostRouterNodeExecutor)->execute($this->node(), $this->ctx('PAGE_1_999'))->handle);
    }

    public function test_no_post_on_conversation_goes_default(): void
    {
        $this->assertSame('default', (new PostRouterNodeExecutor)->execute($this->node(), $this->ctx(null))->handle);
    }
}
