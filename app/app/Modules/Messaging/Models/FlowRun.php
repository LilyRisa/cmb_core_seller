<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * State máy chạy 1 flow trên 1 conversation. `context` giữ biến thu thập +
 * `_sent` (id node đã gửi, chống gửi lại khi advance lặp).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $flow_id
 * @property int $conversation_id
 * @property ?string $current_node_id
 * @property string $status
 * @property ?array $context
 * @property ?string $error
 */
class FlowRun extends Model
{
    use BelongsToTenant;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ENDED = 'ended';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id', 'flow_id', 'conversation_id', 'current_node_id',
        'status', 'context', 'error', 'entered_at', 'last_advanced_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'entered_at' => 'datetime',
            'last_advanced_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
