<?php

namespace CMBcoreSeller\Modules\VisualSearch\Models;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Một "item" AI training do seller nhập tay (sản phẩm/logo/bao bì/linh kiện…).
 * Index ảnh của item vào Qdrant để nhận diện khi khách gửi ảnh.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property ?string $description
 * @property ?array<string,mixed> $attributes
 * @property ?string $ref_code
 * @property string $status
 * @property bool $applies_all_pages
 * @property ?int $primary_image_id
 * @property ?int $created_by
 * @property ?string $content_text
 * @property string $source
 * @property ?string $url
 * @property ?string $storage_path
 * @property string $provider
 * @property string $kb_status
 * @property int $chunk_count
 * @property ?string $embedding_provider_code
 * @property ?string $embedding_model
 * @property int $embedding_version
 * @property ?Carbon $kb_indexed_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class VisualTrainingItem extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INDEXING = 'indexing';

    public const STATUS_FAILED = 'failed';

    public const KB_PENDING = 'pending';

    public const KB_READY = 'ready';

    public const KB_FAILED = 'failed';

    public const SOURCE_INLINE = 'inline';

    public const SOURCE_URL = 'url';

    public const SOURCE_UPLOAD = 'upload';

    protected $fillable = [
        'tenant_id', 'name', 'description', 'attributes', 'ref_code',
        'status', 'applies_all_pages', 'primary_image_id', 'created_by',
        'content_text', 'source', 'url', 'storage_path', 'provider',
        'kb_status', 'chunk_count', 'embedding_provider_code', 'embedding_model',
        'embedding_version', 'kb_indexed_at',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'applies_all_pages' => 'boolean',
            'primary_image_id' => 'integer',
            'kb_indexed_at' => 'datetime',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(VisualTrainingImage::class, 'item_id');
    }

    public function primaryImage(): BelongsTo
    {
        return $this->belongsTo(VisualTrainingImage::class, 'primary_image_id');
    }

    /** Page (channel_account) item áp dụng — bỏ qua khi applies_all_pages=true. SPEC 0035. */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(ChannelAccount::class, 'visual_training_item_page', 'item_id', 'channel_account_id');
    }
}
