<?php

namespace CMBcoreSeller\Modules\Notifications\Models;

use CMBcoreSeller\Modules\Notifications\Services\NotificationDispatcher;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Thông báo in-app (SPEC 0036). Tenant-scoped, 1 dòng / 1 user nhận; trạng thái đọc
 * lưu per-user qua `read_at`. Tạo bởi {@see NotificationDispatcher}.
 *
 * Bảng `app_notifications` (tránh đụng database notifications mặc định của Laravel).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property string $type
 * @property string $level
 * @property string $title
 * @property string|null $body
 * @property string|null $action_url
 * @property array<string,mixed>|null $data
 * @property string|null $dedup_key
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Notification extends Model
{
    use BelongsToTenant;

    public const LEVEL_INFO = 'info';

    public const LEVEL_WARNING = 'warning';

    public const LEVEL_CRITICAL = 'critical';

    protected $table = 'app_notifications';

    protected $fillable = [
        'tenant_id', 'user_id', 'type', 'level', 'title', 'body', 'action_url', 'data', 'dedup_key', 'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];
}
