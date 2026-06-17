<?php

namespace CMBcoreSeller\Modules\Customers\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Báo cáo "bom hàng" nội bộ gắn với một đơn thủ công bị hoàn (SPEC 0038 v2).
 * Một dòng / đơn (`order_id` unique). Đối chiếu khách qua `phone_hash`.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $phone_hash
 * @property int $order_id
 * @property string|null $order_number
 * @property string $reason
 * @property int|null $reported_by_user_id
 * @property Carbon $reported_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CustomerReport extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'phone_hash', 'order_id', 'order_number',
        'reason', 'reported_by_user_id', 'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'reported_by_user_id' => 'integer',
            'reported_at' => 'datetime',
        ];
    }
}
