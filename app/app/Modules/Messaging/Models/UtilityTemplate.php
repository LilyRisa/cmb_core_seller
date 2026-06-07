<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Mẫu tin nhắn tiện ích (Messenger Utility Message) — SPEC-0032. Per-tenant,
 * per-Page (`channel_account_id`). Vòng đời `status`: draft → pending (đã submit
 * lên Meta) → approved | rejected. Chỉ template `approved` mới gửi được; ngoài cửa
 * sổ 24h đây là cách duy nhất gửi tin tự động (message tag đã bị Meta khai tử).
 *
 * Body dùng `{{1}},{{2}}…`; `variables` map thứ tự placeholder → nguồn dữ liệu
 * (vd `['tracking_url','order_number']`) để notifier/composer điền đúng.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $channel_account_id
 * @property string $code
 * @property string $name
 * @property string $language
 * @property string $body
 * @property array<int, array<string, mixed>>|null $buttons
 * @property array<int, string>|null $variables
 * @property string|null $external_template_id
 * @property string $status
 * @property string|null $reject_reason
 * @property bool $enabled
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class UtilityTemplate extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'tenant_id', 'channel_account_id', 'code', 'name', 'language',
        'body', 'buttons', 'variables', 'external_template_id',
        'status', 'reject_reason', 'enabled', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'buttons' => 'array',
            'variables' => 'array',
            'enabled' => 'boolean',
        ];
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
