<?php

namespace CMBcoreSeller\Modules\Billing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Ví lượt gọi AI của tenant (SPEC 0032).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $purchased_balance credit mua thêm (vĩnh viễn, ≤ 5000)
 * @property int $period_used lượt đã dùng trong kỳ (trừ hạn mức tặng kèm gói)
 * @property Carbon|null $period_anchor
 */
class AiCreditWallet extends Model
{
    use BelongsToTenant;

    /** Mua thêm: tối thiểu 500, bước 100, cộng dồn tối đa 5000. */
    public const PURCHASE_MIN = 500;

    public const PURCHASE_STEP = 100;

    public const PURCHASE_MAX_BALANCE = 5000;

    protected $fillable = ['tenant_id', 'purchased_balance', 'period_used', 'period_anchor'];

    protected $casts = [
        'purchased_balance' => 'integer',
        'period_used' => 'integer',
        'period_anchor' => 'date',
    ];
}
