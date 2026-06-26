<?php

namespace Tests\Unit\Channels;

use CMBcoreSeller\Integrations\Channels\Lazada\LazadaMappers;
use Tests\TestCase;

/**
 * Lazada `/orders/get` (list) trả `updated_at` KHÔNG kèm offset (giờ shop GMT+7); `/order/get` (detail)
 * trả kèm `+0700`. Parse naive theo app tz=UTC ⇒ lệch +7h ⇒ source_updated_at "tương lai" ⇒ stale-guard
 * (OrderUpsertService) bỏ qua mọi update sau ⇒ đơn Lazada đóng băng. time() phải parse naive theo tz shop.
 */
class LazadaMapperTimeTest extends TestCase
{
    public function test_naive_lazada_timestamp_is_interpreted_in_shop_timezone(): void
    {
        // "10:37:21" không offset = 10:37 GMT+7 = 03:37 UTC (KHÔNG phải 10:37 UTC).
        $t = LazadaMappers::time('2026-06-26 10:37:21');
        $this->assertNotNull($t);
        $this->assertSame('2026-06-26T03:37:21+00:00', $t->utc()->toIso8601String());
    }

    public function test_offset_aware_lazada_timestamp_keeps_its_offset(): void
    {
        // Có offset +0700 ⇒ offset trong chuỗi thắng ⇒ 14:39:41+07:00 = 07:39:41 UTC.
        $t = LazadaMappers::time('2026-06-26 14:39:41 +0700');
        $this->assertNotNull($t);
        $this->assertSame('2026-06-26T07:39:41+00:00', $t->utc()->toIso8601String());
    }

    public function test_iso_with_offset_is_correct(): void
    {
        $t = LazadaMappers::time('2026-06-26T14:39:41+07:00');
        $this->assertSame('2026-06-26T07:39:41+00:00', $t?->utc()->toIso8601String());
    }

    public function test_null_and_empty_return_null(): void
    {
        $this->assertNull(LazadaMappers::time(null));
        $this->assertNull(LazadaMappers::time(''));
    }
}
