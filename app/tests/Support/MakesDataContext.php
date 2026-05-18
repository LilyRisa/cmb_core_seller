<?php

namespace Tests\Support;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;

trait MakesDataContext
{
    protected function makeContext(array $overrides = []): DataContext
    {
        $d = array_merge([
            'order_number' => 'M-001',
            'tracking_no' => 'TRK123',
            'carrier' => 'ghn',
            'sender_name' => 'Shop A',
            'sender_phone' => '0901',
            'sender_address' => '12 Lê Lợi, Q1, TP.HCM',
            'recipient_name' => 'Nguyễn Văn B',
            'recipient_phone' => '0911',
            'recipient_address' => '34 Trần Hưng Đạo, Hai Bà Trưng, Hà Nội',
            'recipient_address_detail' => '34 Trần Hưng Đạo',
            'recipient_address_admin' => 'Hai Bà Trưng, Hà Nội',
            'cod' => 250000,
            'weight_g' => 500,
            'total_qty' => 2,
            'print_note' => 'Cảm ơn quý khách',
            'created_at_fmt' => '18/05/2026 10:30',
            'items' => [['name' => 'Áo thun', 'sku' => 'AT01', 'qty' => 2]],
        ], $overrides);

        return new DataContext(...$d);
    }
}
