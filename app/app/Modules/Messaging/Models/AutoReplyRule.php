<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Quy tắc trả lời tự động — 4 trigger:
 *   - schedule: theo lịch (vd 22:00-08:00 hằng ngày)
 *   - order_status: khi order chuyển trạng thái (vd delivered → cảm ơn)
 *   - away_no_response: NV chưa trả lời sau N phút
 *   - first_message: lần đầu buyer nhắn tới (chào)
 *   - keyword: buyer nhắn có từ khoá khớp
 *
 * `trigger_config` shape phụ thuộc trigger (xem SPEC-0024 §5.6).
 * Engine + trigger handler ở AutoReplyEngine.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $trigger
 * @property ?array $trigger_config
 * @property ?array $filter
 * @property array $action
 * @property int $cooldown_seconds
 * @property bool $enabled
 * @property bool $applies_all_pages
 * @property int $priority
 * @property ?int $created_by
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property ?Carbon $deleted_at
 */
class AutoReplyRule extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const TRIGGER_SCHEDULE = 'schedule';

    public const TRIGGER_ORDER_STATUS = 'order_status';

    public const TRIGGER_AWAY_NO_RESPONSE = 'away_no_response';

    public const TRIGGER_FIRST_MESSAGE = 'first_message';

    public const TRIGGER_KEYWORD = 'keyword';

    /** Mọi bình luận mới (chỉ áp cho thread_type=comment). */
    public const TRIGGER_COMMENT_ANY = 'comment_any';

    public const ACTION_TEMPLATE = 'template';

    public const ACTION_RAW = 'raw';

    public const ACTION_AI_REPLY = 'ai_reply';

    protected $fillable = [
        'tenant_id', 'name', 'trigger', 'trigger_config',
        'filter', 'action', 'cooldown_seconds', 'enabled', 'applies_all_pages', 'priority', 'created_by',
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
            'applies_all_pages' => 'boolean',
        ];
    }

    /** Các page (channel_account) rule này áp dụng — bỏ qua khi `applies_all_pages=true`. SPEC 0035. */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(ChannelAccount::class, 'auto_reply_rule_page');
    }
}
