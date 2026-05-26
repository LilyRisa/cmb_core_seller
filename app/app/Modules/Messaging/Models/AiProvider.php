<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Cấu hình 1 LLM provider (super-admin quản, KHÔNG tenant-scoped — catalog chung).
 *
 * `code` = free-form instance slug (VD: claude, deepseek-r1, my-openrouter).
 * `adapter` = loại API connector: anthropic | openai_compatible | custom_http | manual.
 * Nhiều instance khác nhau có thể dùng cùng adapter (deepseek/qwen/openrouter
 * đều là openai_compatible, chỉ khác base_url/api_key/default_model).
 *
 * `api_key` encrypted-at-rest; không bao giờ serialize ra response (xem `$hidden`).
 * `capabilities` KHÔNG ở đây — luôn đọc từ connector class.
 * `adapter_config` (JSON) chỉ dùng cho adapter `custom_http` (SPEC-0026).
 *
 * @property string $code
 * @property string|null $adapter
 * @property string|null $display_name
 * @property string|null $api_key
 * @property string|null $base_url
 * @property string|null $default_model
 * @property array<int, array<string, mixed>>|null $pricing
 * @property array<string, mixed>|null $adapter_config
 * @property bool $is_active
 * @property int $sort_order
 * @property string|null $notes
 * @property int|null $created_by_admin_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AiProvider extends Model
{
    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code', 'adapter', 'display_name', 'api_key', 'base_url',
        'default_model', 'pricing', 'adapter_config', 'is_active', 'sort_order', 'notes', 'created_by_admin_id',
    ];

    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'pricing' => 'array',
            'adapter_config' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
