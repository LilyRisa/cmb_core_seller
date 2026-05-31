<?php

namespace CMBcoreSeller\Modules\Support\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Một tin nhắn trong hội thoại CSKH. `sender` user|cskh; `type` text|system
 * (system = thông báo tự sinh, vd "Hỗ trợ viên đã đóng đoạn hội thoại").
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $support_conversation_id
 * @property string $sender user|cskh
 * @property string $type text|system
 * @property ?int $user_id
 * @property ?int $admin_id
 * @property ?string $body
 * @property int $attachments_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ?SupportConversation $conversation
 * @property-read Collection<int,SupportMessageAttachment> $attachments
 */
class SupportMessage extends Model
{
    use BelongsToTenant;

    public const SENDER_USER = 'user';

    public const SENDER_CSKH = 'cskh';

    public const TYPE_TEXT = 'text';

    public const TYPE_SYSTEM = 'system';

    protected $fillable = [
        'tenant_id', 'support_conversation_id', 'sender', 'type',
        'user_id', 'admin_id', 'body', 'attachments_count',
    ];

    protected $casts = [
        'attachments_count' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class, 'support_conversation_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportMessageAttachment::class);
    }
}
