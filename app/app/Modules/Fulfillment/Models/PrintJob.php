<?php

namespace CMBcoreSeller\Modules\Fulfillment\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A generated PDF: `label` (merged shipping labels), `picking` (pick list grouped by
 * SKU) or `packing` (one packing slip per order). Minimal v1 — 90-day retention is a
 * later spec (docs/03-domain/fulfillment-and-printing.md §8). See SPEC 0006 §5.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $type
 * @property array $scope
 * @property string|null $file_url
 * @property string|null $file_path
 * @property int|null $file_size
 * @property string $status
 * @property string|null $error
 * @property array|null $meta
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PrintJob extends Model
{
    use BelongsToTenant;

    public const TYPE_LABEL = 'label';

    public const TYPE_PICKING = 'picking';

    public const TYPE_PACKING = 'packing';

    /** Sales invoice / order slip — one printable page per order (mã đơn, người mua/nhận, hàng + tiền). */
    public const TYPE_INVOICE = 'invoice';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_DONE = 'done';

    public const STATUS_ERROR = 'error';

    protected $fillable = ['tenant_id', 'type', 'scope', 'file_url', 'file_path', 'file_size', 'status', 'error', 'meta', 'created_by'];

    protected function casts(): array
    {
        return ['scope' => 'array', 'meta' => 'array', 'file_size' => 'integer'];
    }

    /** @return list<int> */
    public function orderIds(): array
    {
        return array_values(array_map('intval', (array) ($this->scope['order_ids'] ?? [])));
    }

    /** @return list<int> */
    public function shipmentIds(): array
    {
        return array_values(array_map('intval', (array) ($this->scope['shipment_ids'] ?? [])));
    }
}
