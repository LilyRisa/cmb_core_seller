<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * 1 chunk text + embedding của 1 mục "Kiến thức" (visual item). `embedding` JSON; Qdrant giữ vector
 * cho search (filter `tenant_id`). Hệ tài liệu text thuần cũ (document_id) ĐÃ GỠ.
 *
 * @property int $id
 * @property int $tenant_id
 * @property ?int $visual_item_id
 * @property int $chunk_index
 * @property string $chunk_text
 * @property ?array $embedding
 * @property int $token_count
 */
class AiKnowledgeChunk extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'visual_item_id', 'chunk_index', 'chunk_text', 'embedding', 'token_count',
    ];

    protected function casts(): array
    {
        return [
            'chunk_index' => 'integer',
            'embedding' => 'array',
            'token_count' => 'integer',
        ];
    }
}
