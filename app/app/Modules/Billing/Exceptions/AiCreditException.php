<?php

namespace CMBcoreSeller\Modules\Billing\Exceptions;

use RuntimeException;

/**
 * Không thể gọi AI (SPEC 0032): gói không có AI / đã hạ gói, hoặc hết lượt.
 * `code` map sang lỗi API (402).
 */
class AiCreditException extends RuntimeException
{
    public function __construct(public string $errorCode, string $message)
    {
        parent::__construct($message);
    }

    /** Gói hiện tại không có AI (chưa nâng cấp / đã hạ gói). Credit đã mua được giữ nhưng không dùng được. */
    public static function unavailable(): self
    {
        return new self(
            'AI_UNAVAILABLE',
            'Tính năng AI cần gói trả phí có AI đang hoạt động. Hãy nâng cấp/gia hạn để sử dụng (lượt đã mua vẫn được giữ).',
        );
    }

    /** Hết lượt gọi AI (đã dùng hết hạn mức tặng + credit mua). */
    public static function exhausted(): self
    {
        return new self('AI_CREDITS_EXHAUSTED', 'Đã hết lượt gọi AI. Hãy mua thêm gói lượt AI để tiếp tục.');
    }
}
