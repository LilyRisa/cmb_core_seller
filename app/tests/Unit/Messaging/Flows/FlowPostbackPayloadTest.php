<?php

namespace Tests\Unit\Messaging\Flows;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowPostbackPayload;
use PHPUnit\Framework\TestCase;

class FlowPostbackPayloadTest extends TestCase
{
    public function test_encode_then_decode_round_trips(): void
    {
        $encoded = FlowPostbackPayload::encode('42', 'ask', 'b_buy');
        $this->assertSame(['flow_id' => '42', 'node_id' => 'ask', 'handle' => 'b_buy'], FlowPostbackPayload::decode($encoded));
    }

    public function test_decode_rejects_non_flow_payload(): void
    {
        // Postback của get_started / persistent menu — không phải payload flow.
        $this->assertNull(FlowPostbackPayload::decode('GET_STARTED'));
        $this->assertNull(FlowPostbackPayload::decode('{"t":"menu","n":"x","h":"y"}'));
    }

    public function test_decode_rejects_malformed_or_incomplete(): void
    {
        $this->assertNull(FlowPostbackPayload::decode(''));
        $this->assertNull(FlowPostbackPayload::decode('not-json'));
        $this->assertNull(FlowPostbackPayload::decode('{"t":"flow","n":"ask"}'));   // thiếu handle
        $this->assertNull(FlowPostbackPayload::decode('{"t":"flow","h":"b_buy"}')); // thiếu node
    }

    public function test_decode_tolerates_payload_encoded_before_flow_id_was_added(): void
    {
        // Payload cũ (đã gửi cho khách trước khi thêm field 'f') — vẫn phải decode được,
        // chỉ thiếu flow_id (null), KHÔNG được coi là hỏng.
        $this->assertSame(
            ['flow_id' => null, 'node_id' => 'ask', 'handle' => 'b_buy'],
            FlowPostbackPayload::decode('{"t":"flow","n":"ask","h":"b_buy"}'),
        );
    }
}
