<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tài liệu training cho AI (RAG). 3 source: upload (file vào MinIO), url
 * (fetch về index), inline (text gõ trực tiếp). `IndexKnowledgeDoc` job (S6)
 * chunk + embed → ghi `ai_knowledge_chunks`.
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
        'indexed_at', 'status', 'error', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'chunk_count' => 'integer',
            'embedding_version' => 'integer',
            'indexed_at' => 'datetime',
        ];
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(AiKnowledgeChunk::class, 'document_id');
    }
}
