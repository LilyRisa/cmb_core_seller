<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Mẫu tin trả lời. Body có thể chứa vars `{{customer.name}}`, `{{order.code}}`
 * — `TemplateResolver` (S3) sẽ resolve trước khi gửi. `scope` (jsonb) cho phép
 * giới hạn template chỉ áp cho 1 provider (vd template MESSAGE_TAG chỉ Facebook).
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property string $name
 * @property string $body
 * @property array<int,string>|null $vars
 * @property array<int,mixed>|null $attachments
 * @property array<string,mixed>|null $scope
 * @property string|null $shortcut_key
 * @property bool $enabled
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
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
