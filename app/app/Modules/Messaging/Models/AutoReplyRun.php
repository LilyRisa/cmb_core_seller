<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Audit + idempotency lookup cho auto-reply. UNIQUE
 * `(rule_id, conversation_id, window_key)` ⇒ rule chạy 2 lần cùng window = 1 fire.
 */
class AutoReplyRun extends Model
{
    use BelongsToTenant;

    public const STATUS_FIRED = 'fired';
    public const STATUS_SKIPPED_COOLDOWN = 'skipped_cooldown';
    public const STATUS_SKIPPED_FILTER = 'skipped_filter';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id', 'rule_id', 'conversation_id',
        'window_key', 'fired_at', 'message_id', 'status', 'error',
    ];

    protected function casts(): array
    {
        return [
            'fired_at' => 'datetime',
        ];
    }
}
