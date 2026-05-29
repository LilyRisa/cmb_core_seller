<?php

namespace CMBcoreSeller\Modules\Messaging\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Phát khi buyer bấm nút (Facebook postback) — KHÔNG ingest thành tin nhắn.
 *
 * Tách khỏi MessageReceived: postback chỉ để tiến luồng (Flow Builder), không
 * phải nội dung hội thoại. `payload` do builder sinh (opaque); listener
 * AdvanceFlowOnPostback giải mã. Internal — không broadcast.
 */
class PostbackReceived
{
    use Dispatchable;

    public function __construct(
        public int $conversationId,
        public string $payload,
        public ?string $externalMessageId = null,
    ) {}
}
