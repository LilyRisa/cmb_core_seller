<?php

namespace Tests\Unit\Messaging\Flows;

use CMBcoreSeller\Modules\Messaging\Services\Flows\FlowPostbackPayload;
use PHPUnit\Framework\TestCase;

class FlowPostbackPayloadTest extends TestCase
{
    public function test_encode_then_decode_round_trips(): void
    {
        $encoded = FlowPostbackPayload::encode('ask', 'b_buy');
        $this->assertSame(['node_id' => 'ask', 'handle' => 'b_buy'], FlowPostbackPayload::decode($encoded));
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
}
