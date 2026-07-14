<?php

namespace CMBcoreSeller\Integrations\Messaging\Exceptions;

use RuntimeException;

/**
 * Ném khi Graph API từ chối vì thiếu quyền `page_events` (Advanced Access, cần Meta App
 * Review) — dùng để caller phân biệt với lỗi tạm thời (retry vô nghĩa khi thiếu quyền).
 */
class MissingScopeException extends RuntimeException
{
    public static function forPageEvents(string $reason = ''): self
    {
        $suffix = $reason !== '' ? " ({$reason})" : '';

        return new self('Thiếu quyền page_events — cần "Cấp quyền lại" (kết nối lại) để bật báo cáo chuyển đổi.'.$suffix);
    }
}
