<?php

namespace CMBcoreSeller\Modules\Billing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Hoá đơn — BelongsToTenant. Code `INV-YYYYMM-NNNN` unique trong tenant. SPEC 0018.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $subscription_id
 * @property string $code
 * @property string $status
 * @property string $period_start
 * @property string $period_end
 * @property int $subtotal
 * @property int $tax
 * @property int $total
 * @property string $currency
 * @property Carbon $due_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $voided_at
 * @property array|null $customer_snapshot
 * @property array|null $meta
 * @property-read Subscription|null $subscription
 * @property-read Collection<int, InvoiceLine> $lines
 * @property-read Collection<int, Payment> $payments
 */
class Invoice extends Model
{
    use BelongsToTenant;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_VOID = 'void';

    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'tenant_id', 'subscription_id', 'code', 'status',
        'period_start', 'period_end', 'subtotal', 'tax', 'total', 'currency',
        'due_at', 'paid_at', 'voided_at', 'customer_snapshot', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'subtotal' => 'integer',
            'tax' => 'integer',
            'total' => 'integer',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
            'customer_snapshot' => 'array',
            'meta' => 'array',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Sinh mã invoice kế tiếp: INV-YYYYMM-NNNN trong phạm vi tenant. */
    public static function nextCode(int $tenantId): string
    {
        $prefix = 'INV-'.now()->format('Ym').'-';
        $n = self::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('code', 'like', $prefix.'%')->count() + 1;

        return sprintf('%s%04d', $prefix, $n);
    }
}
