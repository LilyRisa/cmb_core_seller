<?php

namespace CMBcoreSeller\Modules\Products\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A batch of product push jobs tracking overall publish progress.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $type
 * @property int $total
 * @property int $succeeded
 * @property int $failed
 * @property string $status
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ProductPushBatch extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    public function jobs(): HasMany
    {
        return $this->hasMany(ProductPushJob::class);
    }

    public function recountAndFinish(): void
    {
        $this->succeeded = $this->jobs()->where('status', 'success')->count();
        $this->failed = $this->jobs()->where('status', 'failed')->count();
        if ($this->succeeded + $this->failed >= $this->total) {
            $this->status = 'done';
        }
        $this->save();
    }
}
