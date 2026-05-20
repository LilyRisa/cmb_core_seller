<?php

namespace CMBcoreSeller\Integrations\Messaging\Exceptions;

use RuntimeException;

/**
 * Ném bởi `OutboundWindowGuard` khi cố gửi tin sau khi `freeWindowHours` đã
 * trôi qua mà không kèm `message_tag` hợp lệ (vd Facebook 24h rule).
 * Controller bắt → 422 OUTBOUND_WINDOW_CLOSED + gợi ý dùng template tag.
 */
class OutboundWindowClosed extends RuntimeException
{
    public static function for(string $provider, int $hoursSinceLastInbound): self
    {
        return new self(
            "Provider [{$provider}] outbound window đã đóng ({$hoursSinceLastInbound}h kể từ inbound cuối). ".
            'Vui lòng dùng template với message_tag hợp lệ.'
        );
    }
}
