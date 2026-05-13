<?php

namespace CMBcoreSeller\Modules\Finance\Models;

use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 1 dòng phí/doanh thu của statement — bất biến. SPEC 0016.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $settlement_id
 * @property int|null $order_id
 * @property string|null $external_order_id
 * @property string|null $external_line_id
 * @property string $fee_type
 * @property int $amount
 * @property Carbon|null $occurred_at
 * @property string|null $description
 * @property array|null $raw
 * @property Carbon $created_at
 * @property-read Settlement|null $settlement
 * @property-read Order|null $order
 */
class SettlementLine extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'settlement_id', 'order_id', 'external_order_id', 'external_line_id', 'fee_type', 'amount', 'occurred_at', 'description', 'raw', 'created_at'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'occurred_at' => 'datetime', 'created_at' => 'datetime', 'raw' => 'array'];
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
