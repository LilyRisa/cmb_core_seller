<?php

namespace CMBcoreSeller\Integrations\Messaging\Zalo;

class ZaloApiException extends \RuntimeException
{
    public function __construct(public int $zaloError, string $message)
    {
        parent::__construct($message, 0);
    }

    public static function from(int $error, string $message): self
    {
        return new self($error, "Zalo API error {$error}: {$message}");
    }

    /**
     * Lỗi OA chưa đủ gói để gửi tin (error -224) hoặc thiếu quyền gói.
     * FE hiển thị cảnh báo; job đánh fail 'provider_permission' và không retry.
     */
    public function isTierOrPermissionBlocked(): bool
    {
        return $this->zaloError === -224
            || str_contains($this->getMessage(), 'OA Tier')
            || str_contains($this->getMessage(), 'pricing')
            || str_contains($this->getMessage(), 'permission');
    }
}
