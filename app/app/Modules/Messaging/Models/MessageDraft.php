<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * AI suggestion chờ NV duyệt. Tự expire sau 1h qua scheduler (`PruneAiSuggestionDrafts`).
 * NV bấm "Gửi" ⇒ `accept` → tạo message thật + link `accepted_message_id`.
 */
class MessageDraft extends Model
{
    use BelongsToTenant;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'tenant_id', 'conversation_id', 'ai_run_id',
        'draft_text', 'suggested_attachments', 'status',
        'accepted_at', 'accepted_by', 'accepted_message_id', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'suggested_attachments' => 'array',
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
