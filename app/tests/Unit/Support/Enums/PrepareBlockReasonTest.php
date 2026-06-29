<?php

namespace Tests\Unit\Support\Enums;

use CMBcoreSeller\Support\Enums\PrepareBlockReason;
use PHPUnit\Framework\TestCase;

class PrepareBlockReasonTest extends TestCase
{
    public function test_each_case_has_vietnamese_label(): void
    {
        foreach (PrepareBlockReason::cases() as $case) {
            $this->assertNotSame('', trim($case->label()));
        }
    }

    public function test_known_labels(): void
    {
        $this->assertSame('Chờ người mua thanh toán', PrepareBlockReason::AwaitingPayment->label());
        $this->assertSame('Đang xử lý yêu cầu huỷ — chưa thể chuẩn bị', PrepareBlockReason::CancelInProgress->label());
    }
}
