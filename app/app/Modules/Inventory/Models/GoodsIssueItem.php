<?php

namespace CMBcoreSeller\Modules\Inventory\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $goods_issue_id
 * @property int $sku_id
 * @property int $qty
 * @property-read Sku|null $sku
 * @property-read GoodsIssue|null $goodsIssue
 */
class GoodsIssueItem extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = ['tenant_id', 'goods_issue_id', 'sku_id', 'qty'];

    protected function casts(): array
    {
        return ['qty' => 'integer'];
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function goodsIssue(): BelongsTo
    {
        return $this->belongsTo(GoodsIssue::class);
    }
}
