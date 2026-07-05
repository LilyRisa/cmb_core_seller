<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 1-1 với `channel_accounts`. Cột `settings` encrypted vì có thể chứa
 * config nhạy cảm (vd Facebook webhook secret per page).
 *
 * @property int $channel_account_id (PK)
 * @property int $tenant_id
 * @property bool $messaging_enabled
 * @property ?Carbon $last_inbound_at
 * @property ?Carbon $last_outbound_at
 * @property ?array $outbound_window_meta
 * @property bool $ai_enabled
 * @property bool $ai_auto_mode
 * @property ?array $settings
 * @property ?array $business_info
 * @property ?string $page_avatar_path
 * @property ?string $page_avatar_url
 * @property ?Carbon $page_avatar_synced_at
 * @property ?string $sync_status
 * @property ?int $sync_total_conversations
 * @property int $sync_done_conversations
 * @property int $sync_message_count
 * @property ?string $sync_cursor
 * @property ?Carbon $sync_started_at
 * @property ?Carbon $sync_finished_at
 * @property ?string $sync_error
 * @property ?Carbon $last_synced_at
 * @property string $comment_sync_status
 * @property ?Carbon $comment_synced_at
 * @property ?string $comment_sync_error
 */
class MessagingAccountMeta extends Model
{
    use BelongsToTenant;

    protected $table = 'messaging_account_meta';

    protected $primaryKey = 'channel_account_id';

    public $incrementing = false;

    protected $keyType = 'int';

    public const SYNC_IDLE = 'idle';

    public const SYNC_QUEUED = 'queued';

    public const SYNC_RUNNING = 'running';

    public const SYNC_DONE = 'done';

    public const SYNC_FAILED = 'failed';

    protected $fillable = [
        'channel_account_id', 'tenant_id',
        'messaging_enabled', 'last_inbound_at', 'last_outbound_at',
        'outbound_window_meta', 'ai_enabled', 'ai_auto_mode', 'settings', 'business_info',
        // SPEC 2026-05-21: sync-state + page avatar (+ page_avatar_url fallback CDN)
        'page_avatar_path', 'page_avatar_url', 'page_avatar_synced_at', 'sync_status',
        'sync_total_conversations', 'sync_done_conversations', 'sync_message_count',
        'sync_cursor', 'sync_started_at', 'sync_finished_at', 'sync_error', 'last_synced_at',
        // SPEC 2026-05-21: comment-sync columns (tách khỏi message sync_*)
        'comment_sync_status', 'comment_synced_at', 'comment_sync_error',
    ];

    protected function casts(): array
    {
        return [
            'messaging_enabled' => 'boolean',
            'ai_enabled' => 'boolean',
            'ai_auto_mode' => 'boolean',
            'last_inbound_at' => 'datetime',
            'last_outbound_at' => 'datetime',
            'outbound_window_meta' => 'array',
            'settings' => 'encrypted:array',
            'business_info' => 'array',
            // SPEC 2026-05-21
            'page_avatar_synced_at' => 'datetime',
            'sync_started_at' => 'datetime',
            'sync_finished_at' => 'datetime',
            'last_synced_at' => 'datetime',
            // SPEC 2026-05-21: comment-sync
            'comment_synced_at' => 'datetime',
        ];
    }

    public function channelAccount(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class);
    }
}
