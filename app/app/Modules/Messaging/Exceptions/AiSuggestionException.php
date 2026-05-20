<?php

namespace CMBcoreSeller\Modules\Messaging\Exceptions;

use RuntimeException;

/**
 * Lỗi pipeline AI suggestion — mang sẵn HTTP status + error code để controller
 * map thẳng ra response (SPEC-0024 §7).
 */
class AiSuggestionException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $httpStatus,
        string $message,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public static function providerNotAvailable(): self
    {
        return new self(
            'AI_PROVIDER_NOT_AVAILABLE',
            422,
            'Chưa có AI provider khả dụng. Vào Cài đặt > Tin nhắn để chọn, hoặc liên hệ quản trị viên.',
        );
    }

    public static function limitReached(int $used, int $limit): self
    {
        return new self(
            'PLAN_LIMIT_REACHED',
            402,
            "Đã dùng hết hạn mức AI reply tháng này ({$used}/{$limit}). Nâng cấp gói để tiếp tục.",
            ['used' => $used, 'limit' => $limit, 'period' => 'month'],
        );
    }

    public static function generationFailed(string $reason): self
    {
        return new self(
            'AI_UNAVAILABLE',
            503,
            'AI không phản hồi, vui lòng thử lại.',
            ['reason' => $reason],
        );
    }
}
