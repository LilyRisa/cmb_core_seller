<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Tài liệu training cho AI (RAG). 3 source: upload (file vào MinIO), url
 * (fetch về index), inline (text gõ trực tiếp). `IndexKnowledgeDoc` job (S6)
 * chunk + embed → ghi `ai_knowledge_chunks`.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $title
 * @property string $source
 * @property ?string $storage_path
 * @property ?string $url
 * @property ?string $inline_text
 * @property int $chunk_count
 * @property ?string $embedding_provider_code
 * @property ?string $embedding_model
 * @property int $embedding_version
 * @property ?Carbon $indexed_at
 * @property string $status
 * @property bool $applies_all_pages
 * @property string $provider
 * @property ?string $error
 * @property ?int $created_by
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class AiKnowledgeDocument extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const SOURCE_UPLOAD = 'upload';

    public const SOURCE_URL = 'url';

    public const SOURCE_INLINE = 'inline';

    public const STATUS_PENDING = 'pending';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id', 'title', 'source', 'storage_path', 'url', 'inline_text',
        'chunk_count', 'embedding_provider_code', 'embedding_model', 'embedding_version',
        'indexed_at', 'status', 'applies_all_pages', 'provider', 'error', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'chunk_count' => 'integer',
            'embedding_version' => 'integer',
            'indexed_at' => 'datetime',
            'applies_all_pages' => 'boolean',
        ];
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(AiKnowledgeChunk::class, 'document_id');
    }

    /** Các page (channel_account) tài liệu áp dụng — bỏ qua khi `applies_all_pages=true`. SPEC 0035. */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(ChannelAccount::class, 'ai_knowledge_document_page', 'ai_knowledge_document_id', 'channel_account_id');
    }
}
