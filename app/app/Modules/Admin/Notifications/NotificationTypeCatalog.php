<?php

namespace CMBcoreSeller\Modules\Admin\Notifications;

/**
 * Danh sách loại thông báo admin nhận qua email (SPEC 2026-07-15). Hằng số PHP thuần —
 * KHÔNG theo Connector/Registry pattern của tầng Integrations (không có "provider" ở đây,
 * chỉ là danh sách code nội bộ). Thêm loại mới: thêm 1 const + 1 dòng nhãn ở `all()`.
 */
final class NotificationTypeCatalog
{
    public const SUPPORT_NEW_CONVERSATION = 'support.new_conversation';

    public const AUTH_USER_VERIFIED = 'auth.user_verified';

    /** @return array<string,string> code => nhãn tiếng Việt hiển thị FE */
    public static function all(): array
    {
        return [
            self::SUPPORT_NEW_CONVERSATION => 'Khách nhắn CSKH (mở cuộc hội thoại mới)',
            self::AUTH_USER_VERIFIED => 'Người dùng đăng ký & xác minh email thành công',
        ];
    }

    public static function isValid(string $type): bool
    {
        return array_key_exists($type, self::all());
    }
}
