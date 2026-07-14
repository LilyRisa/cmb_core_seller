<?php

namespace Tests\Feature\Channels;

use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Fixtures\Channels\tiktok\TikTokFixtures as F;
use Tests\TestCase;

/**
 * Giai đoạn 2 (design 2026-07-14 §2) — unique constraint thật trên webhook_events +
 * WebhookIngestService::ingest() bắt được vi phạm unique (race hiếm giữa exists() fast-path
 * và create()) và trả duplicate 200 thay vì để lộ QueryException 500.
 *
 * Test thứ hai đi qua endpoint HTTP thật (/webhook/tiktok, ký HMAC) như WebhookDedupeStatusKeyTest —
 * request giả không chữ ký trong task-6-brief.md sẽ bị 401 trước khi chạm code cần test, và payload
 * mẫu trong brief dùng `type` dạng chuỗi trong khi verifier thật đọc `type` là int map qua
 * config('integrations.tiktok.webhook_event_types'). Mục tiêu quan sát được (theo ghi chú trong
 * brief) là response cuối cùng 200 + note=duplicate, không bắt buộc ép đúng nhánh exists()/catch.
 */
class WebhookDedupeUniqueConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_unique_constraint_rejects_true_duplicate_at_db_level(): void
    {
        WebhookEvent::query()->create([
            'provider' => 'tiktok', 'event_type' => 'order', 'external_id' => 'ORD_1',
            'external_shop_id' => 'SHOP_1', 'order_raw_status' => 'PICKED', 'dedupe_status_key' => 'PICKED',
            'signature_ok' => true, 'payload' => [], 'status' => WebhookEvent::STATUS_PENDING, 'received_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        DB::table('webhook_events')->insert([
            'provider' => 'tiktok', 'event_type' => 'order', 'external_id' => 'ORD_1',
            'external_shop_id' => 'SHOP_1', 'order_raw_status' => 'PICKED', 'dedupe_status_key' => 'PICKED',
            'signature_ok' => true, 'payload' => json_encode([]), 'status' => WebhookEvent::STATUS_PENDING, 'received_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_ingest_catches_unique_violation_and_returns_duplicate_note(): void
    {
        Queue::fake();
        F::configure();

        // Row đã tồn tại đúng theo khoá dedupe (provider, event_type, external_id, external_shop_id,
        // order_raw_status) — mô phỏng đích đến của race: dù exists() fast-path bắt được ở đây (đường
        // đi thường gặp) hay create() vi phạm unique constraint (đường race hiếm), response quan sát
        // được phải như nhau: 200 + note=duplicate.
        WebhookEvent::query()->create([
            'provider' => 'tiktok', 'event_type' => 'order_status_update', 'external_id' => F::ORDER_ID,
            'external_shop_id' => F::SHOP_ID, 'order_raw_status' => 'AWAITING_SHIPMENT',
            'dedupe_status_key' => 'AWAITING_SHIPMENT', 'signature_ok' => true, 'payload' => [],
            'status' => WebhookEvent::STATUS_PENDING, 'received_at' => now(),
        ]);

        $body = [
            'type' => 1, 'tts_notification_id' => 'ntf-'.uniqid(), 'shop_id' => F::SHOP_ID, 'timestamp' => now()->timestamp,
            'data' => ['order_id' => F::ORDER_ID, 'order_status' => 'AWAITING_SHIPMENT', 'update_time' => now()->timestamp],
        ];
        $raw = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $sig = hash_hmac('sha256', F::APP_KEY.$raw, F::APP_SECRET);

        $response = $this->call('POST', '/webhook/tiktok', server: ['HTTP_AUTHORIZATION' => $sig, 'CONTENT_TYPE' => 'application/json'], content: $raw);

        $response->assertOk();
        $this->assertSame('duplicate', $response->json('note'));
    }
}
