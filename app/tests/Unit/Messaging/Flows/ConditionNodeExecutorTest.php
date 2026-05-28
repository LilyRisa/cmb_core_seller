<?php

namespace Tests\Unit\Messaging\Flows;

use CMBcoreSeller\Modules\Messaging\Models\Conversation;
use CMBcoreSeller\Modules\Messaging\Models\FlowRun;
use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowNode;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\ConditionNodeExecutor;
use PHPUnit\Framework\TestCase;

class ConditionNodeExecutorTest extends TestCase
{
    private function ctx(string $inbound): FlowContext
    {
        return new FlowContext(new Conversation, new FlowRun, $inbound);
    }

    public function test_any_match_returns_match_handle(): void
    {
        $node = new FlowNode('n', 'condition', ['keywords' => ['giá', 'price'], 'match' => 'any']);
        $this->assertSame('match', (new ConditionNodeExecutor)->execute($node, $this->ctx('cho hỏi GIÁ bao nhiêu'))->handle);
    }

    public function test_no_match_returns_no_match_handle(): void
    {
        $node = new FlowNode('n', 'condition', ['keywords' => ['giá'], 'match' => 'any']);
        $this->assertSame('no_match', (new ConditionNodeExecutor)->execute($node, $this->ctx('xin chào'))->handle);
    }

    public function test_all_mode_requires_every_keyword(): void
    {
        $node = new FlowNode('n', 'condition', ['keywords' => ['ship', 'phí'], 'match' => 'all']);
        $this->assertSame('no_match', (new ConditionNodeExecutor)->execute($node, $this->ctx('ship không'))->handle);
        $this->assertSame('match', (new ConditionNodeExecutor)->execute($node, $this->ctx('ship phí bao nhiêu'))->handle);
    }
}
