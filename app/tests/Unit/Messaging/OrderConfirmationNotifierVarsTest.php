<?php

namespace Tests\Unit\Messaging;

use CMBcoreSeller\Modules\Messaging\Services\OrderConfirmationNotifier;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * #1 SPEC-0032 — mẫu tin tiện ích phải hỗ trợ nguồn biến `buyer.name`/`buyer.first_name`
 * (ngoài order_number/tracking_url) để điền vào tham số vị trí {{1}},{{2}}… của Meta.
 *
 * Test hàm thuần `resolveDataMap` (không cần DB/boot) qua reflection.
 */
class OrderConfirmationNotifierVarsTest extends TestCase
{
    private function dataMap(string $buyerName, string $orderNumber, string $url): array
    {
        $notifier = (new ReflectionClass(OrderConfirmationNotifier::class))->newInstanceWithoutConstructor();
        $m = new ReflectionMethod(OrderConfirmationNotifier::class, 'resolveDataMap');
        $m->setAccessible(true);

        return $m->invoke($notifier, $buyerName, $orderNumber, $url);
    }

    public function test_buyer_name_and_first_name_resolved(): void
    {
        $data = $this->dataMap('Nguyễn Văn A', 'DH-100', 'https://x/tracking?code=DH-100');

        $this->assertSame('Nguyễn Văn A', $data['buyer.name']);
        // "Tên gọi" tiếng Việt = chữ cuối.
        $this->assertSame('A', $data['buyer.first_name']);
        $this->assertSame('DH-100', $data['order_number']);
        $this->assertSame('https://x/tracking?code=DH-100', $data['tracking_url']);
    }

    public function test_empty_buyer_name_yields_empty_strings(): void
    {
        $data = $this->dataMap('', 'DH-200', 'https://x');

        $this->assertSame('', $data['buyer.name']);
        $this->assertSame('', $data['buyer.first_name']);
        $this->assertSame('DH-200', $data['order_number']);
    }
}
