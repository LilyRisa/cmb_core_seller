<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 1 tin nhắn, hoặc inbound (buyer gửi) hoặc outbound (shop gửi).
 *
 * `delivery_status` flow:
 *   - inbound: NULL (chỉ outbound mới care delivery)
 *   - outbound: pending → sent → delivered → read
 *               pending → failed (+ failure_code)
 *
 * Idempotency: webhook + polling cùng về 1 tin ⇒ `(conversation_id, external_message_id)`
 * UNIQUE chống dedupe. Outbound chưa nhận echo-back ⇒ external_message_id NULL
 * cho tới khi SendMessage job update.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $conversation_id
 * @property ?string $external_message_id
 * @property string $direction
 * @property string $kind
 * @property ?string $body
 * @property int $attachments_count
 * @property ?int $sent_by_user_id
 * @property bool $sent_by_ai
 * @property string $delivery_status
 * @property ?string $failure_code
 * @property ?int $reply_to_message_id
 * @property ?Carbon $sent_at
 * @property ?Carbon $delivered_at
 * @property ?Carbon $read_at
 * @property ?array $raw_payload
 * @property ?array $meta
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class Message extends Model
{
    use BelongsToTenant;

    public const DIRECTION_INBOUND = 'inbound';

    public const DIRECTION_OUTBOUND = 'outbound';

    public const KIND_TEXT = 'text';

    public const KIND_IMAGE = 'image';

    public const KIND_VIDEO = 'video';

    public const KIND_AUDIO = 'audio';

    public const KIND_FILE = 'file';

    public const KIND_TEMPLATE = 'template';

    public const KIND_INTERACTIVE = 'interactive';

    /** Tin nhắn tiện ích gửi qua utility template đã Meta duyệt (SPEC-0032). */
    public const KIND_UTILITY_TEMPLATE = 'utility_template';

    public const KIND_SYSTEM = 'system';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_READ = 'read';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id', 'conversation_id', 'external_message_id',
        'direction', 'kind', 'body', 'attachments_count',
        'sent_by_user_id', 'sent_by_ai', 'delivery_status', 'failure_code',
        'reply_to_message_id', 'sent_at', 'delivered_at', 'read_at',
        'raw_payload', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'attachments_count' => 'integer',
            'sent_by_ai' => 'boolean',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'raw_payload' => 'array',
            'meta' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function isInbound(): bool
    {
        return $this->direction === self::DIRECTION_INBOUND;
    }

    public function isOutbound(): bool
    {
        return $this->direction === self::DIRECTION_OUTBOUND;
    }
}
