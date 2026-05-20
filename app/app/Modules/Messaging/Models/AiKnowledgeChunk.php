<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 chunk text + embedding của 1 document. `embedding` JSON cho S1; S6 sẽ
 * migrate sang pgvector trên Postgres + HNSW index (filter `tenant_id` trước).
 */
class AiKnowledgeChunk extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'document_id', 'chunk_index', 'chunk_text', 'embedding', 'token_count',
    ];

    protected function casts(): array
    {
        return [
            'chunk_index' => 'integer',
            'embedding' => 'array',
            'token_count' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(AiKnowledgeDocument::class, 'document_id');
    }
}
