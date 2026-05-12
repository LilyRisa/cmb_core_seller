<?php

namespace CMBcoreSeller\Modules\Customers\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Append-only note on a customer — written by staff (`kind=manual`) or by the
 * reputation engine (`kind=auto.*`) or on merge (`kind=system.merge`). No soft
 * delete; staff may hard-delete only their own manual notes. See SPEC 0002 §4.5, §5.1.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $customer_id
 * @property int|null $author_user_id
 * @property string $kind
 * @property string $severity
 * @property string $note
 * @property int|null $order_id
 * @property string|null $dedupe_key
 * @property Carbon|null $created_at
 */
class CustomerNote extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null;

    public const KIND_MANUAL = 'manual';

    public const KIND_MERGE = 'system.merge';

    public const SEV_INFO = 'info';

    public const SEV_WARNING = 'warning';

    public const SEV_DANGER = 'danger';

    protected $fillable = [
        'tenant_id', 'customer_id', 'author_user_id', 'kind', 'severity', 'note', 'order_id', 'dedupe_key', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isAuto(): bool
    {
        return str_starts_with($this->kind, 'auto.') || $this->kind === self::KIND_MERGE;
    }
}
