<?php

namespace CMBcoreSeller\Modules\VisualSearch\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Ảnh của 1 item — CHỈ metadata lưu trữ (tách khỏi embedding để đổi model không
 * phá schema). Embedding nằm ở {@see VisualTrainingEmbedding}.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $item_id
 * @property string $storage_disk
 * @property string $storage_path
 * @property string $image_hash
 * @property string $mime_type
 * @property int $width
 * @property int $height
 * @property int $size_bytes
 * @property int $sort_order
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class VisualTrainingImage extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'item_id', 'storage_disk', 'storage_path', 'image_hash',
        'mime_type', 'width', 'height', 'size_bytes', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'size_bytes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(VisualTrainingItem::class, 'item_id');
    }

    public function embeddings(): HasMany
    {
        return $this->hasMany(VisualTrainingEmbedding::class, 'image_id');
    }
}
