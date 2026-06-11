<?php

namespace CMBcoreSeller\Modules\Products\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An individual push job within a ProductPushBatch.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $product_push_batch_id
 * @property int $listing_draft_id
 * @property string $status
 * @property string|null $step_label
 * @property int $progress
 * @property array|null $error
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ProductPushJob extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'error' => 'array',
            'progress' => 'integer',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductPushBatch::class, 'product_push_batch_id');
    }

    public function mark(string $status, ?string $step = null, int $progress = 0, ?array $error = null): void
    {
        $this->fill(array_filter(
            ['status' => $status, 'step_label' => $step, 'progress' => $progress, 'error' => $error],
            fn ($v) => $v !== null && $v !== ''
        ))->save();
    }
}
