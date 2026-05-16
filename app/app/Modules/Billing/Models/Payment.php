<?php

namespace CMBcoreSeller\Modules\Billing\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Thanh toán cho invoice. Idempotency: unique (gateway, external_ref).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $invoice_id
 * @property string $gateway
 * @property string $external_ref
 * @property int $amount
 * @property string $status
 * @property array|null $raw_payload
 * @property Carbon|null $occurred_at
 * @property Carbon|null $refunded_at SPEC 0023 — admin refund timestamp.
 * @property array|null $meta SPEC 0023 — manual/refund metadata.
 */
class Payment extends Model
{
    use BelongsToTenant;

    public const GATEWAY_SEPAY = 'sepay';

    public const GATEWAY_VNPAY = 'vnpay';

    public const GATEWAY_MOMO = 'momo';

    public const GATEWAY_MANUAL = 'manual';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'tenant_id', 'invoice_id', 'gateway', 'external_ref',
        'amount', 'status', 'raw_payload', 'occurred_at',
        'refunded_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'raw_payload' => 'array',
            'occurred_at' => 'datetime',
            'refunded_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
