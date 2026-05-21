<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Thẻ hội thoại do tenant tạo: tên + màu (hex). Gán vào conversations.tags (mảng id).
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $color
 */
class MessagingTag extends Model
{
    use BelongsToTenant;

    protected $table = 'messaging_tags';

    protected $fillable = ['tenant_id', 'name', 'color'];
}
