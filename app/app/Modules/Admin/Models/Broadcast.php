<?php

namespace CMBcoreSeller\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Log của mỗi broadcast email admin gửi. SPEC 0023. KHÔNG tenant-scoped.
 *
 * @property int $id
 * @property string $subject
 * @property string $body_markdown
 * @property array $audience {kind: 'all_owners'|'all_admins_and_owners'|'tenant_ids', tenant_ids?: int[]}
 * @property int $recipient_count
 * @property int $sent_count
 * @property int $skipped_count
 * @property Carbon|null $sent_at
 * @property int $created_by_user_id
 * @property array|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Broadcast extends Model
{
    public const AUDIENCE_ALL_OWNERS = 'all_owners';

    public const AUDIENCE_ALL_ADMINS_AND_OWNERS = 'all_admins_and_owners';

    public const AUDIENCE_TENANT_IDS = 'tenant_ids';

    public const MAX_RECIPIENTS = 5000;

    protected $fillable = [
        'subject', 'body_markdown', 'audience',
        'recipient_count', 'sent_count', 'skipped_count',
        'sent_at', 'created_by_user_id', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'audience' => 'array',
            'recipient_count' => 'integer',
            'sent_count' => 'integer',
            'skipped_count' => 'integer',
            'sent_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
