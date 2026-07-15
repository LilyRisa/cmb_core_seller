<?php

namespace CMBcoreSeller\Modules\Support\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Phát khi 1 tenant MỞ CUỘC HỘI THOẠI CSKH MỚI (không phải mọi tin nhắn) — dùng để báo
 * admin qua email (SPEC 2026-07-15). Tách khỏi `SupportMessageCreated` (broadcast realtime
 * FE, bắn cho MỌI tin kể cả CSKH trả lời) để không trộn 2 mục đích khác nhau — payload
 * broadcast (khách FE nhận) không cần mang thêm dữ liệu chỉ admin cần.
 */
class SupportNewConversationOpened
{
    use Dispatchable;

    public function __construct(
        public int $conversationId,
        public int $tenantId,
        public ?int $userId,
        public string $snippet,
    ) {}
}
