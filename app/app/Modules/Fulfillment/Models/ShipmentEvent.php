<?php

namespace CMBcoreSeller\Modules\Fulfillment\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One entry in a shipment's tracking timeline — a carrier scan or a system action
 * (`created`, `packed_scanned`, `cancelled`, …). Deduped on (shipment_id, code, occurred_at).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $shipment_id
 * @property string $code
 * @property string|null $description
 * @property string|null $status
 * @property Carbon $occurred_at
 * @property string $source
 * @property array|null $raw
 * @property Carbon|null $created_at
 * @property-read Shipment|null $shipment
 */
class ShipmentEvent extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null;

    public const SOURCE_CARRIER = 'carrier';

    public const SOURCE_SYSTEM = 'system';

    public const SOURCE_USER = 'user';

    protected $fillable = ['tenant_id', 'shipment_id', 'code', 'description', 'status', 'occurred_at', 'source', 'raw', 'created_at'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime', 'raw' => 'array'];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
