<?php

namespace Tests\Unit\Messaging\Flows;

use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\EndNodeExecutor;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\NodeExecutorRegistry;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes\NodeResult;
use RuntimeException;
use Tests\TestCase;

class NodeExecutorRegistryTest extends TestCase
{
    public function test_resolves_registered_executor_by_type(): void
    {
        $registry = new NodeExecutorRegistry;
        $registry->register('end', EndNodeExecutor::class);

        $this->assertTrue($registry->has('end'));
        $this->assertSame('end', $registry->for('end')->type());
    }

    public function test_unknown_type_throws(): void
    {
        $this->expectException(RuntimeException::class);
        (new NodeExecutorRegistry)->for('does_not_exist');
    }

    public function test_result_factories(): void
    {
        $this->assertTrue(NodeResult::wait()->isWait());
        $this->assertTrue(NodeResult::end()->isEnd());
        $this->assertSame('match', NodeResult::advance('match')->handle);
        $this->assertTrue(NodeResult::advance()->isAdvance());
        $this->assertSame('boom', NodeResult::fail('boom')->error);
    }
}
