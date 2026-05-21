<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cấu hình 1 LLM provider (super-admin quản, KHÔNG tenant-scoped — catalog chung).
 *
 * `code` = free-form instance slug (VD: claude, deepseek-r1, my-openrouter).
 * `adapter` = loại API connector: anthropic | openai_compatible | manual.
 * Nhiều instance khác nhau có thể dùng cùng adapter (deepseek/qwen/openrouter
 * đều là openai_compatible, chỉ khác base_url/api_key/default_model).
 *
 * `api_key` encrypted-at-rest; không bao giờ serialize ra response (xem `$hidden`).
 * `capabilities` KHÔNG ở đây — luôn đọc từ connector class.
 */
class AiProvider extends Model
{
    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code', 'adapter', 'display_name', 'api_key', 'base_url',
        'default_model', 'pricing', 'is_active', 'sort_order', 'notes', 'created_by_admin_id',
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
