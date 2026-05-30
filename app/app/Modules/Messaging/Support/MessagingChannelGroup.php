<?php

namespace CMBcoreSeller\Modules\Messaging\Support;

/**
 * Phân nhóm provider của Messaging thành 2 nhóm để tách cấu hình AI tự động
 * (ADR-0022): Facebook (social) vs Marketplace (sàn TMĐT).
 *
 * Đây là 1 CHỖ DUY NHẤT biết tên `facebook_page` cho mục đích phân nhóm — không
 * rải hardcode khắp listener/controller. Cùng tiền lệ với
 * `ChannelAccount::messagingConnectorCode()`.
 */
final class MessagingChannelGroup
{
    public const FACEBOOK = 'facebook';

    public const MARKETPLACE = 'marketplace';

    /** Facebook Page = nhóm facebook; mọi sàn TMĐT khác (tiktok/shopee/lazada/manual) = marketplace. */
    public static function forProvider(?string $provider): string
    {
        return $provider === 'facebook_page' ? self::FACEBOOK : self::MARKETPLACE;
    }

    public static function isFacebook(?string $provider): bool
    {
        return self::forProvider($provider) === self::FACEBOOK;
    }
}
