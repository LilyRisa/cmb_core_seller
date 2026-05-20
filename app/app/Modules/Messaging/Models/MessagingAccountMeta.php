<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

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
 * @property ?array $settings
 */
class MessagingAccountMeta extends Model
{
    use BelongsToTenant;

    protected $table = 'messaging_account_meta';

    protected $primaryKey = 'channel_account_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'channel_account_id', 'tenant_id',
        'messaging_enabled', 'last_inbound_at', 'last_outbound_at',
        'outbound_window_meta', 'ai_enabled', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'messaging_enabled' => 'boolean',
            'ai_enabled' => 'boolean',
            'last_inbound_at' => 'datetime',
            'last_outbound_at' => 'datetime',
            'outbound_window_meta' => 'array',
            'settings' => 'encrypted:array',
        ];
    }

    public function channelAccount(): BelongsTo
    {
        return $this->belongsTo(\CMBcoreSeller\Modules\Channels\Models\ChannelAccount::class);
    }
}
