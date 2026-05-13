<?php

namespace CMBcoreSeller\Modules\Procurement\Models;

use CMBcoreSeller\Modules\Inventory\Models\Sku;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bảng giá nhập (NCC × SKU) theo thời kỳ. `is_default=true` ⇒ giá mặc định khi tạo PO. Phase 6.1.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $supplier_id
 * @property int $sku_id
 * @property int $unit_cost
 * @property int $moq
 * @property string $currency
 * @property string|null $valid_from
 * @property string|null $valid_to
 * @property bool $is_default
 * @property string|null $note
 * @property-read Supplier|null $supplier
 * @property-read Sku|null $sku
 */
class SupplierPrice extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'supplier_id', 'sku_id', 'unit_cost', 'moq', 'currency', 'valid_from', 'valid_to', 'is_default', 'note'];

    protected function casts(): array
    {
        return ['unit_cost' => 'integer', 'moq' => 'integer', 'is_default' => 'boolean', 'valid_from' => 'date', 'valid_to' => 'date'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }
}
