<?php

namespace CMBcoreSeller\Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Expo Push token cho 1 thiết bị mobile của 1 user trong 1 tenant — SPEC 0029.
 *
 * KHÔNG dùng BelongsToTenant: `messaging:push-digest` chạy trong scheduler
 * (không có tenant context) cần quét cross-tenant. tenant_id set tường minh ở
 * controller (auth). Mirror đúng pattern của {@see PushSubscription}.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property string $expo_push_token
 * @property string $platform
 * @property ?Carbon $last_seen_at
 * @property ?Carbon $last_notified_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class MobileDevice extends Model
{
    protected $table = 'mobile_devices';

    protected $fillable = [
        'tenant_id', 'user_id', 'expo_push_token', 'platform',
        'last_seen_at', 'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'last_notified_at' => 'datetime',
        ];
    }
}
