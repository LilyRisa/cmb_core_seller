<?php

namespace CMBcoreSeller\Modules\Support\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Một đoạn hội thoại CSKH của tenant (SPEC-0028). Mở/đóng theo `status`; khi đóng,
 * user nhắn tin mới sẽ tạo hội thoại MỚI. `user_unread_count` = số tin CSKH user
 * chưa đọc (nguồn badge widget).
 *
 * @property int $id
 * @property int $tenant_id
 * @property ?int $user_id
 * @property string $status open|closed
 * @property Carbon|null $last_message_at
 * @property ?string $last_sender user|cskh
 * @property int $user_unread_count
 * @property Carbon|null $closed_at
 * @property ?int $closed_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int,SupportMessage> $messages
 * @property-read ?SupportMessage $latestMessage
 */
class SupportConversation extends Model
{
    use BelongsToTenant;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const SENDER_USER = 'user';

    public const SENDER_CSKH = 'cskh';

    protected $fillable = [
        'tenant_id', 'user_id', 'status', 'last_message_at', 'last_sender',
        'user_unread_count', 'closed_at', 'closed_by',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
        'user_unread_count' => 'integer',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class);
    }

    /** Tin mới nhất — preview ở danh sách admin. */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(SupportMessage::class)->latestOfMany();
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }
}
