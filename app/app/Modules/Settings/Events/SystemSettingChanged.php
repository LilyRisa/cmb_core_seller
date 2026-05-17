<?php

namespace CMBcoreSeller\Modules\Settings\Events;

/**
 * Spec 2026-05-17 — phát ra mỗi khi `SystemSettingService::set/forget` thay
 * đổi một cấu hình. Listeners có thể subscribe để: ghi audit log
 * (`LogSystemSettingChanged`), invalidate module-specific cache (vd OAuth
 * client cache TikTok khi `marketplace.tiktok.*` đổi), broadcast tới các
 * worker khác qua queue, v.v.
 */
class SystemSettingChanged
{
    public function __construct(public readonly string $key) {}
}
