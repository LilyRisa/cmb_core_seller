<?php

namespace CMBcoreSeller\Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Mỗi lần voucher được sử dụng. SPEC 0023.
 *
 * KHÔNG dùng BelongsToTenant vì admin grant từ context không có current tenant;
 * tenant_id cột thường + index. Truy vấn /admin/* tự where('tenant_id', ...).
 *
 * @property int $id
 * @property int $voucher_id
 * @property int $tenant_id
 * @property int|null $user_id
 * @property int|null $invoice_id
 * @property int|null $subscription_id
 * @property int $discount_amount
 * @property int $granted_days
 * @property array|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Voucher|null $voucher
 */
class VoucherRedemption extends Model
{
    protected $fillable = [
        'voucher_id', 'tenant_id', 'user_id',
        'invoice_id', 'subscription_id',
        'discount_amount', 'granted_days', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'integer',
            'granted_days' => 'integer',
            'meta' => 'array',
        ];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
