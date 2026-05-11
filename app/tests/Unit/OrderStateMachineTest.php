<?php

namespace Tests\Unit;

use CMBcoreSeller\Modules\Orders\Services\OrderStateMachine;
use CMBcoreSeller\Support\Enums\StandardOrderStatus as S;
use PHPUnit\Framework\TestCase;

/** See docs/03-domain/order-status-state-machine.md §2-§3. */
class OrderStateMachineTest extends TestCase
{
    private OrderStateMachine $sm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sm = new OrderStateMachine;
    }

    public function test_happy_path_transitions_are_allowed(): void
    {
        $this->assertTrue($this->sm->canTransition(S::Unpaid, S::Pending));
        $this->assertTrue($this->sm->canTransition(S::Pending, S::Processing));
        $this->assertTrue($this->sm->canTransition(S::Processing, S::ReadyToShip));
        $this->assertTrue($this->sm->canTransition(S::ReadyToShip, S::Shipped));
        $this->assertTrue($this->sm->canTransition(S::Shipped, S::Delivered));
        $this->assertTrue($this->sm->canTransition(S::Delivered, S::Completed));
    }

    public function test_cancel_and_return_branches(): void
    {
        $this->assertTrue($this->sm->canTransition(S::Pending, S::Cancelled));
        $this->assertTrue($this->sm->canTransition(S::Shipped, S::DeliveryFailed));
        $this->assertTrue($this->sm->canTransition(S::DeliveryFailed, S::Shipped));
        $this->assertTrue($this->sm->canTransition(S::Delivered, S::Returning));
        $this->assertTrue($this->sm->canTransition(S::Returning, S::ReturnedRefunded));
    }

    public function test_illegal_transitions_are_rejected(): void
    {
        $this->assertFalse($this->sm->canTransition(S::Completed, S::Processing));
        $this->assertFalse($this->sm->canTransition(S::Cancelled, S::Pending));
        $this->assertFalse($this->sm->canTransition(S::Shipped, S::Unpaid));
        $this->assertFalse($this->sm->canTransition(S::Unpaid, S::Shipped));
    }

    public function test_same_status_is_an_idempotent_no_op(): void
    {
        $this->assertTrue($this->sm->canTransition(S::Shipped, S::Shipped));
    }

    public function test_backward_jump_detection(): void
    {
        // forward / branch jumps are not regressions
        $this->assertFalse($this->sm->isBackwardJump(S::Pending, S::Shipped));
        $this->assertFalse($this->sm->isBackwardJump(S::Shipped, S::Cancelled));
        $this->assertFalse($this->sm->isBackwardJump(S::Shipped, S::Returning));

        // a small regression (channel correction) — backward, but not abnormal
        $this->assertTrue($this->sm->isBackwardJump(S::ReadyToShip, S::Processing));
        $this->assertFalse($this->sm->isAbnormalBackwardJump(S::ReadyToShip, S::Processing));

        // a big regression / regression out of a terminal status — abnormal -> has_issue
        $this->assertTrue($this->sm->isAbnormalBackwardJump(S::Completed, S::Processing));
        $this->assertTrue($this->sm->isAbnormalBackwardJump(S::Shipped, S::Pending));
    }
}
