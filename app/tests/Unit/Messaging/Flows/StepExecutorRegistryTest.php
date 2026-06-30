<?php

namespace Tests\Unit\Messaging\Flows;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowContext;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Steps\FlowStep;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Steps\StepExecutor;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Steps\StepExecutorRegistry;
use CMBcoreSeller\Modules\Messaging\Services\Flows\Steps\StepResult;
use Tests\TestCase;

/** Step bù nhìn dùng trong test, định nghĩa ngay ở đây. */
class NoopStep implements StepExecutor
{
    public function type(): string
    {
        return 'noop';
    }

    public function execute(FlowStep $step, FlowContext $ctx): StepResult
    {
        return StepResult::done();
    }
}

/**
 * @covers \CMBcoreSeller\Modules\Messaging\Services\Flows\Steps\StepExecutorRegistry
 * @covers \CMBcoreSeller\Modules\Messaging\Services\Flows\Steps\StepResult
 * @covers \CMBcoreSeller\Modules\Messaging\Services\Flows\Steps\FlowStep
 */
class StepExecutorRegistryTest extends TestCase
{
    public function test_registry_resolves_and_unknown_throws(): void
    {
        $r = new StepExecutorRegistry($this->app);
        $r->register('noop', NoopStep::class);
        $this->assertTrue($r->has('noop'));
        $this->assertInstanceOf(StepExecutor::class, $r->for('noop'));
        $this->assertFalse($r->has('missing'));
        $this->expectException(\InvalidArgumentException::class);
        $r->for('missing');
    }

    public function test_step_result_wait_carries_handle(): void
    {
        $result = StepResult::wait('h');
        $this->assertSame('h', $result->handle());
        $this->assertTrue($result->isWait());
        $this->assertFalse($result->isFail());
    }

    public function test_step_result_done_is_not_wait(): void
    {
        $result = StepResult::done();
        $this->assertFalse($result->isWait());
        $this->assertFalse($result->isFail());
    }

    public function test_step_result_fail_carries_error(): void
    {
        $result = StepResult::fail('something_wrong');
        $this->assertTrue($result->isFail());
        $this->assertSame('something_wrong', $result->error());
    }

    public function test_flow_step_from_array(): void
    {
        $step = FlowStep::fromArray(['id' => 's1', 'type' => 'send_text', 'text' => 'hello']);
        $this->assertSame('s1', $step->id);
        $this->assertSame('send_text', $step->type);
        $this->assertSame(['text' => 'hello'], $step->config);
    }
}
