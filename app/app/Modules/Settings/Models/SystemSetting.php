<?php

namespace CMBcoreSeller\Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Một dòng trong `system_settings`. Đừng đọc trực tiếp từ controller —
 * luôn đi qua `SystemSettingService` để tận dụng cache `rememberForever`
 * và auto-decrypt cho `is_secret`.
 */
class SystemSetting extends Model
{
    protected $fillable = [
        'key', 'value', 'type', 'group',
        'is_secret', 'description', 'updated_by_admin_id',
    ];

    protected $casts = [
        'is_secret' => 'boolean',
    ];
}
