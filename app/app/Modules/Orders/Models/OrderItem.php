<?php

namespace CMBcoreSeller\Modules\Orders\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $order_id
 * @property string $external_item_id
 * @property string|null $external_product_id
 * @property string|null $external_sku_id
 * @property string|null $seller_sku
 * @property int|null $sku_id
 * @property string $name
 * @property string|null $variation
 * @property int $quantity
 * @property int $unit_price
 * @property int $discount
 * @property int $subtotal
 * @property string|null $image
 * @property array|null $raw
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class OrderItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'order_id', 'external_item_id', 'external_product_id', 'external_sku_id',
        'seller_sku', 'sku_id', 'name', 'variation', 'quantity', 'unit_price', 'discount', 'subtotal', 'image', 'raw',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer', 'discount' => 'integer', 'subtotal' => 'integer',
            'raw' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
