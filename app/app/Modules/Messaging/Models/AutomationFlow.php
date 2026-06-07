<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Kịch bản tự động (Flow Builder). `graph` = { nodes:[], edges:[] }.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $provider
 * @property string $status
 * @property string $trigger_type
 * @property ?array $trigger_config
 * @property ?array $graph
 * @property int $version
 * @property bool $enabled
 * @property bool $applies_all_pages
 * @property ?int $created_by
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class AutomationFlow extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_ARCHIVED = 'archived';

    public const TRIGGER_COMMENT_ON_POST = 'comment_on_post';

    public const TRIGGER_COMMENT_ANY = 'comment_any';

    public const TRIGGER_INBOX_FIRST_MESSAGE = 'inbox_first_message';

    public const TRIGGER_INBOX_KEYWORD = 'inbox_keyword';

    public const TRIGGER_INBOX_ANY = 'inbox_any';

    protected $fillable = [
        'tenant_id', 'name', 'provider', 'status', 'trigger_type',
        'trigger_config', 'graph', 'version', 'enabled', 'applies_all_pages', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'trigger_config' => 'array',
            'graph' => 'array',
            'version' => 'integer',
            'enabled' => 'boolean',
            'applies_all_pages' => 'boolean',
        ];
    }

    /** Các page (channel_account) flow này áp dụng — bỏ qua khi `applies_all_pages=true`. SPEC 0035. */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(ChannelAccount::class, 'automation_flow_page');
    }
}
