<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cấu hình 1 LLM provider (super-admin quản, KHÔNG tenant-scoped — catalog chung).
 *
 * `code` = mã connector đã register ở `AiAssistantRegistry`
 * (claude/openai/gemini/local_llm/manual). `api_key` encrypted-at-rest; không
 * bao giờ serialize ra response (xem `$hidden`).
 *
 * `capabilities` KHÔNG ở đây — luôn đọc từ connector class.
 */
class AiProvider extends Model
{
    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code', 'display_name', 'api_key', 'base_url',
        'default_model', 'pricing', 'is_active', 'created_by_admin_id',
    ];

    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'pricing' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
