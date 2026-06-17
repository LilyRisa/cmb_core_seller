<?php

namespace CMBcoreSeller\Modules\Customers\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Cache một bản báo cáo "bom hàng" Pancake POS cho một số điện thoại (đối chiếu
 * qua `phone_hash`). Một dòng / (tenant, phone_hash). Xem SPEC 0038.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $phone_hash
 * @property int $order_fail
 * @property int $order_success
 * @property int $warning_count
 * @property array<int,array{reason:string,reported_at:?string}>|null $warnings
 * @property bool $has_data
 * @property Carbon $synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CustomerBadReport extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'phone_hash', 'order_fail', 'order_success',
        'warning_count', 'warnings', 'has_data', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'order_fail' => 'integer',
            'order_success' => 'integer',
            'warning_count' => 'integer',
            'warnings' => 'array',
            'has_data' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    /** Còn trong cửa sổ cache (chưa cần gọi lại Pancake)? */
    public function isFresh(int $ttlMinutes): bool
    {
        return $this->synced_at->gt(now()->subMinutes($ttlMinutes));
    }
}
