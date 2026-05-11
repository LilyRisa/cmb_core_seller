<?php

namespace CMBcoreSeller\Modules\Orders\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
