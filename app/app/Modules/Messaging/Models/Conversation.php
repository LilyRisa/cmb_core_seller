<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 1 cuộc hội thoại buyer↔shop trên 1 nền tảng. Header cho `messages`.
 *
 * `status`:
 *  - open: mặc định, tin mới đến đẩy lên top
 *  - snoozed: NV tạm ẩn (snoozed_until); tin mới ⇒ tự về open
 *  - resolved: NV bấm xong; tin mới ⇒ tự về open
 *  - spam: NV bấm; auto-reply bypass; tin vẫn lưu nhưng ẩn khỏi inbox mặc định
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $channel_account_id
 * @property string $provider
 * @property string $external_conversation_id
 * @property string $buyer_external_id
 * @property ?string $buyer_name
 * @property ?string $buyer_avatar_url
 * @property ?int $customer_id
 * @property ?int $order_id
 * @property string $status
 * @property ?Carbon $snoozed_until
 * @property int $unread_count
 * @property int $message_count
 * @property ?Carbon $last_message_at
 * @property ?string $last_message_preview
 * @property ?Carbon $last_inbound_at
 * @property ?Carbon $last_outbound_at
 * @property ?int $assigned_user_id
 * @property ?array $tags
 * @property ?array $meta
 * @property ?Carbon $blocked_at
 * @property ?int $blocked_by_user_id
 * @property bool $manually_unread
 * @property ?string $buyer_avatar_path
 * @property bool $has_phone
 * @property ?string $detected_phone
 */
class Conversation extends Model
{
    use BelongsToTenant;

    public const STATUS_OPEN = 'open';

    public const STATUS_SNOOZED = 'snoozed';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_SPAM = 'spam';

    protected $fillable = [
        'tenant_id', 'channel_account_id', 'provider',
        'external_conversation_id', 'buyer_external_id', 'buyer_name', 'buyer_avatar_url',
        'customer_id', 'order_id', 'status', 'snoozed_until',
        'unread_count', 'message_count', 'last_message_at', 'last_message_preview',
        'last_inbound_at', 'last_outbound_at', 'assigned_user_id', 'tags', 'meta',
        'blocked_at', 'blocked_by_user_id', 'manually_unread', 'buyer_avatar_path',
        'has_phone', 'detected_phone',
    ];

    protected function casts(): array
    {
        return [
            'snoozed_until' => 'datetime',
            'last_message_at' => 'datetime',
            'last_inbound_at' => 'datetime',
            'last_outbound_at' => 'datetime',
            'tags' => 'array',
            'meta' => 'array',
            'blocked_at' => 'datetime',
            'manually_unread' => 'boolean',
            'has_phone' => 'boolean',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function channelAccount(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class);
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_OPEN);
    }

    public function scopeUnread(Builder $q): Builder
    {
        return $q->where('unread_count', '>', 0);
    }

    public function scopeNotBlocked(Builder $q): Builder
    {
        return $q->whereNull('blocked_at');
    }
}
