<?php

namespace CMBcoreSeller\Modules\Settings\Listeners;

use CMBcoreSeller\Modules\Settings\Events\SystemSettingChanged;
use CMBcoreSeller\Modules\Tenancy\Models\AuditLog;

/**
 * Spec 2026-05-17 — ghi audit `admin.setting.update` mỗi khi setting đổi.
 *
 * KHÔNG log giá trị mới (kể cả non-secret) — tránh leak qua DB backup / dev
 * seed nếu lỡ. Audit chỉ ghi `{key}` + `admin_user_id` (đọc qua AuditLog::record
 * → guard admin_web).
 *
 * Run sync (không queue) — vì nó là một row INSERT đơn giản và phải bảo đảm
 * audit luôn ghi trước khi response trả về.
 */
class LogSystemSettingChanged
{
    public function handle(SystemSettingChanged $event): void
    {
        AuditLog::record('admin.setting.update', null, ['key' => $event->key]);
    }
}
