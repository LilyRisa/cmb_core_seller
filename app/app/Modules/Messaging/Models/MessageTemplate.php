<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Mẫu tin trả lời. Body có thể chứa vars `{{customer.name}}`, `{{order.code}}`
 * — `TemplateResolver` (S3) sẽ resolve trước khi gửi. `scope` (jsonb) cho phép
 * giới hạn template chỉ áp cho 1 provider (vd template MESSAGE_TAG chỉ Facebook).
 */
class MessageTemplate extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'code', 'name', 'body',
        'vars', 'attachments', 'scope', 'shortcut_key',
        'enabled', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'vars' => 'array',
            'attachments' => 'array',
            'scope' => 'array',
            'enabled' => 'boolean',
        ];
    }
}
