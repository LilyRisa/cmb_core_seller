<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillWebhookDedupeKeyTest extends TestCase
{
    use RefreshDatabase;

    private function makeRow(array $attrs): WebhookEvent
    {
        return WebhookEvent::query()->create(array_merge([
            'provider' => 'tiktok', 'event_type' => 'order', 'external_id' => 'ORD_1',
            'external_shop_id' => 'SHOP_1', 'signature_ok' => true, 'status' => WebhookEvent::STATUS_PENDING,
            'received_at' => now(), 'payload' => [],
        ], $attrs));
    }

    public function test_backfills_null_dedupe_status_key(): void
    {
        $row = $this->makeRow(['order_raw_status' => 'PICKED', 'external_id' => 'ORD_BACKFILL']);
        $this->assertNull($row->dedupe_status_key);

        $this->artisan('webhooks:backfill-dedupe-key')->assertExitCode(0);

        $this->assertSame('PICKED', $row->fresh()->dedupe_status_key);
    }

    public function test_backfills_empty_string_when_status_null(): void
    {
        $row = $this->makeRow(['order_raw_status' => null, 'external_id' => 'ORD_BACKFILL_NULL']);

        $this->artisan('webhooks:backfill-dedupe-key')->assertExitCode(0);

        $this->assertSame('', $row->fresh()->dedupe_status_key);
    }

    public function test_removes_true_duplicate_rows_keeping_earliest_id(): void
    {
        // Race cũ: 2 row giống hệt nhau theo khoá dedupe thật (kể cả order_raw_status) — phải xoá row sau,
        // giữ id nhỏ nhất.
        $older = $this->makeRow(['external_id' => 'ORD_DUP', 'order_raw_status' => 'PICKED']);
        $newer = $this->makeRow(['external_id' => 'ORD_DUP', 'order_raw_status' => 'PICKED']);

        $this->artisan('webhooks:backfill-dedupe-key')->assertExitCode(0);

        $this->assertNotNull(WebhookEvent::query()->find($older->id));
        $this->assertNull(WebhookEvent::query()->find($newer->id));
    }

    public function test_keeps_rows_with_different_status_as_valid_transitions(): void
    {
        // KHÔNG phải trùng — 2 trạng thái khác nhau của cùng đơn là 2 transition hợp lệ, không xoá.
        $a = $this->makeRow(['external_id' => 'ORD_TRANS', 'order_raw_status' => 'AWAITING_SHIPMENT']);
        $b = $this->makeRow(['external_id' => 'ORD_TRANS', 'order_raw_status' => 'PICKED']);

        $this->artisan('webhooks:backfill-dedupe-key')->assertExitCode(0);

        $this->assertNotNull(WebhookEvent::query()->find($a->id));
        $this->assertNotNull(WebhookEvent::query()->find($b->id));
    }

    public function test_does_not_collide_rows_whose_fields_contain_delimiter_char(): void
    {
        // Hai bộ 5 khoá KHÁC NHAU thật sự, nhưng nếu ghép bằng implode('|', [...]) không escape thì
        // chuỗi nối ra lại TRÙNG NHAU (vì external_id/external_shop_id chứa ký tự '|'):
        //   external_id='A|B', external_shop_id='C'  → "tiktok|order|A|B|C|PICKED"
        //   external_id='A',   external_shop_id='B|C' → "tiktok|order|A|B|C|PICKED"
        // Đây là 2 webhook thật khác nhau — không được xoá cái nào.
        $a = $this->makeRow(['external_id' => 'A|B', 'external_shop_id' => 'C', 'order_raw_status' => 'PICKED']);
        $b = $this->makeRow(['external_id' => 'A', 'external_shop_id' => 'B|C', 'order_raw_status' => 'PICKED']);

        $this->artisan('webhooks:backfill-dedupe-key')->assertExitCode(0);

        $this->assertNotNull(WebhookEvent::query()->find($a->id));
        $this->assertNotNull(WebhookEvent::query()->find($b->id));
    }

    public function test_idempotent_second_run_removes_nothing(): void
    {
        $this->makeRow(['external_id' => 'ORD_DUP2', 'order_raw_status' => 'PICKED']);
        $this->makeRow(['external_id' => 'ORD_DUP2', 'order_raw_status' => 'PICKED']);

        $this->artisan('webhooks:backfill-dedupe-key')->assertExitCode(0);
        $this->assertSame(1, WebhookEvent::query()->where('external_id', 'ORD_DUP2')->count());

        // Chạy lại lần 2 — không còn gì để xoá.
        $this->artisan('webhooks:backfill-dedupe-key')->assertExitCode(0);
        $this->assertSame(1, WebhookEvent::query()->where('external_id', 'ORD_DUP2')->count());
    }
}
