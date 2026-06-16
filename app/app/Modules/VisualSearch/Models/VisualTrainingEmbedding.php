<?php

namespace CMBcoreSeller\Modules\VisualSearch\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Lớp embedding tách riêng: 1 ảnh có thể có nhiều embedding (model/version khác)
 * → migrate model song song không phá schema. `vector_id` = point id trong Qdrant.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $image_id
 * @property string $model
 * @property int $version
 * @property string $collection
 * @property string $vector_id
 * @property int $dim
 * @property string $status
 * @property ?string $error
 * @property ?Carbon $indexed_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class VisualTrainingEmbedding extends Model
{
    use BelongsToTenant;

    public const STATUS_PENDING = 'pending';

    public const STATUS_INDEXED = 'indexed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id', 'image_id', 'model', 'version', 'collection',
        'vector_id', 'dim', 'status', 'error', 'indexed_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'dim' => 'integer',
            'indexed_at' => 'datetime',
        ];
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(VisualTrainingImage::class, 'image_id');
    }
}
