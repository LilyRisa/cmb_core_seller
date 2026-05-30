<?php

namespace CMBcoreSeller\Modules\Support\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Câu hỏi người dùng gửi CSKH từ widget trợ giúp (tab "Hỏi CSKH"). Theo tenant.
 *
 * @property int $id
 * @property int $tenant_id
 * @property ?int $user_id
 * @property string $question
 * @property string $status pending|answered|closed
 * @property ?string $answer
 * @property ?int $answered_by
 * @property Carbon|null $answered_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SupportRequest extends Model
{
    use BelongsToTenant;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ANSWERED = 'answered';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'tenant_id', 'user_id', 'question', 'status',
        'answer', 'answered_by', 'answered_at',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
    ];
}
