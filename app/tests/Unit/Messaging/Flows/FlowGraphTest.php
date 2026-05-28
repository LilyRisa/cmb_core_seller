<?php

namespace Tests\Unit\Messaging\Flows;

use CMBcoreSeller\Modules\Messaging\Services\Flows\Graph\FlowGraph;
use PHPUnit\Framework\TestCase;

class FlowGraphTest extends TestCase
{
    private function graph(): FlowGraph
    {
        return new FlowGraph([
            'nodes' => [
                ['id' => 'n1', 'type' => 'trigger', 'data' => []],
                ['id' => 'n2', 'type' => 'send_message', 'data' => ['text' => 'hi']],
                ['id' => 'n3', 'type' => 'condition', 'data' => ['keywords' => ['x']]],
                ['id' => 'n4', 'type' => 'end', 'data' => []],
            ],
            'edges' => [
                ['source' => 'n1', 'target' => 'n2', 'sourceHandle' => null],
                ['source' => 'n3', 'target' => 'n4', 'sourceHandle' => 'match'],
                ['source' => 'n3', 'target' => 'n2', 'sourceHandle' => 'no_match'],
            ],
        ]);
    }

    public function test_finds_trigger_node(): void
    {
        $this->assertSame('n1', $this->graph()->triggerNode()?->id);
    }

    public function test_next_node_follows_default_edge(): void
    {
        $this->assertSame('n2', $this->graph()->nextNodeId('n1', null));
    }

    public function test_next_node_follows_named_handle(): void
    {
        $this->assertSame('n4', $this->graph()->nextNodeId('n3', 'match'));
        $this->assertSame('n2', $this->graph()->nextNodeId('n3', 'no_match'));
    }

    public function test_next_node_null_when_no_edge(): void
    {
        $this->assertNull($this->graph()->nextNodeId('n4', null));
    }

    public function test_node_lookup_by_id_returns_type_and_data(): void
    {
        $node = $this->graph()->node('n2');
        $this->assertSame('send_message', $node?->type);
        $this->assertSame('hi', $node?->data['text']);
    }
}
