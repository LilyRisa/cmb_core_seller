<?php

namespace CMBcoreSeller\Modules\Orders\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Append-only; one row per status change. See docs/03-domain/order-status-state-machine.md §3.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $order_id
 * @property string|null $from_status
 * @property string $to_status
 * @property string|null $raw_status
 * @property string $source
 * @property Carbon|null $changed_at
 * @property array|null $payload
 * @property Carbon|null $created_at
 */
class OrderStatusHistory extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null;

    public const SOURCE_CHANNEL = 'channel';

    public const SOURCE_POLLING = 'polling';

    public const SOURCE_WEBHOOK = 'webhook';

    public const SOURCE_USER = 'user';

    public const SOURCE_SYSTEM = 'system';

    public const SOURCE_CARRIER = 'carrier';

    protected $table = 'order_status_history';

    protected $fillable = [
        'tenant_id', 'order_id', 'from_status', 'to_status', 'raw_status', 'source', 'changed_at', 'payload', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
            'created_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
