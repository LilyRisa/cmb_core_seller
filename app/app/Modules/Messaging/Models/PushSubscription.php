<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Web Push subscription (1 trình duyệt/thiết bị của 1 user trong 1 tenant).
 *
 * KHÔNG dùng BelongsToTenant: digest command chạy trong scheduler (không có tenant
 * context) cần quét cross-tenant. tenant_id set tường minh ở controller (auth).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property string $endpoint
 * @property string $p256dh
 * @property string $auth
 * @property ?Carbon $last_seen_at
 * @property ?Carbon $last_notified_at
 */
class PushSubscription extends Model
{
    protected $table = 'messaging_push_subscriptions';

    protected $fillable = [
        'tenant_id', 'user_id', 'endpoint', 'p256dh', 'auth',
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
