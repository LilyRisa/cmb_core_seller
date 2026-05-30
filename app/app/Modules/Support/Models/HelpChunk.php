<?php

namespace CMBcoreSeller\Modules\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Một chunk tài liệu trợ giúp (GLOBAL — không theo tenant). Vector tương ứng lưu ở
 * Qdrant với point id = chunk id. Dùng cho fallback keyword + hiển thị nguồn tham khảo.
 *
 * @property int $id
 * @property string $title
 * @property ?string $module
 * @property ?string $screen
 * @property ?string $question
 * @property string $answer
 * @property array<int,string> $keywords
 * @property string $chunk_text
 * @property ?string $embedding_model
 * @property ?int $token_count
 * @property Carbon|null $indexed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class HelpChunk extends Model
{
    protected $fillable = [
        'source', 'ref_key', 'title', 'module', 'screen',
        'question', 'answer', 'keywords', 'chunk_text',
        'embedding_model', 'token_count', 'indexed_at',
    ];

    protected $casts = [
        'keywords' => 'array',
        'token_count' => 'integer',
        'indexed_at' => 'datetime',
    ];
}
