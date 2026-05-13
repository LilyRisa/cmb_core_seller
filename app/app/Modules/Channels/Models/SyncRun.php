<?php

namespace CMBcoreSeller\Modules\Channels\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One order-sync run (poll / backfill / webhook). See docs/03-domain/order-sync-pipeline.md §3.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $channel_account_id
 * @property string $type
 * @property string $status
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property string|null $cursor
 * @property array<string,int>|null $stats
 * @property string|null $error
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ChannelAccount|null $channelAccount
 */
class SyncRun extends Model
{
    use BelongsToTenant;

    public const TYPE_POLL = 'poll';

    public const TYPE_BACKFILL = 'backfill';

    public const TYPE_WEBHOOK = 'webhook';

    /** Status-based sync — kéo mọi đơn ở trạng thái "chưa bàn giao ĐVVC" bất kể thời gian. SPEC §3.3. */
    public const TYPE_UNPROCESSED = 'unprocessed';

    public const STATUS_RUNNING = 'running';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id', 'channel_account_id', 'type', 'status',
        'started_at', 'finished_at', 'cursor', 'stats', 'error',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'stats' => 'array',
        ];
    }

    public function channelAccount(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class);
    }

    /** @param array<string,int> $delta */
    public function bump(array $delta): void
    {
        $stats = $this->stats ?? ['fetched' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
        foreach ($delta as $k => $v) {
            $stats[$k] = ($stats[$k] ?? 0) + $v;
        }
        $this->stats = $stats;
    }

    public function finish(string $status = self::STATUS_DONE, ?string $error = null): void
    {
        $this->forceFill(['status' => $status, 'finished_at' => now(), 'error' => $error])->save();
    }
}
