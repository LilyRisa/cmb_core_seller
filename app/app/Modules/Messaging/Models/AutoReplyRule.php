<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Quy tắc trả lời tự động — 4 trigger:
 *   - schedule: theo lịch (vd 22:00-08:00 hằng ngày)
 *   - order_status: khi order chuyển trạng thái (vd delivered → cảm ơn)
 *   - away_no_response: NV chưa trả lời sau N phút
 *   - first_message: lần đầu buyer nhắn tới (chào)
 *
 * `trigger_config` shape phụ thuộc trigger (xem SPEC-0024 §5.6).
 * Engine + 4 trigger handler ở S5 (sau Phase 6.5 done).
 */
class AutoReplyRule extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const TRIGGER_SCHEDULE = 'schedule';

    public const TRIGGER_ORDER_STATUS = 'order_status';

    public const TRIGGER_AWAY_NO_RESPONSE = 'away_no_response';

    public const TRIGGER_FIRST_MESSAGE = 'first_message';

    public const TRIGGER_KEYWORD = 'keyword';

    public const ACTION_TEMPLATE = 'template';

    public const ACTION_RAW = 'raw';

    public const ACTION_AI_REPLY = 'ai_reply';

    protected $fillable = [
        'tenant_id', 'name', 'trigger', 'trigger_config',
        'filter', 'action', 'cooldown_seconds', 'enabled', 'priority', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'trigger_config' => 'array',
            'filter' => 'array',
            'action' => 'array',
            'cooldown_seconds' => 'integer',
            'priority' => 'integer',
            'enabled' => 'boolean',
        ];
    }
}
