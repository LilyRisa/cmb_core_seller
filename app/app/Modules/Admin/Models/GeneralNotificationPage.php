<?php

namespace CMBcoreSeller\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Plan C (2026-07-23) — trang thông báo chung admin soạn + gửi theo tenant/tất cả. KHÔNG
 * tenant-scoped (giống {@see Announcement}). Xem cũng ghi {@see GeneralNotificationPageView}.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $body_html
 * @property string|null $cover_image_url
 * @property string|null $cta_label
 * @property string|null $cta_url
 * @property string $audience_type
 * @property array<int,int>|null $audience_tenant_ids
 * @property string $status
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $sent_at
 * @property int $created_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class GeneralNotificationPage extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_SENT = 'sent';

    public const AUDIENCE_ALL = 'all';

    public const AUDIENCE_TENANT_IDS = 'tenant_ids';

    protected $fillable = [
        'title', 'slug', 'body_html', 'cover_image_url', 'cta_label', 'cta_url',
        'audience_type', 'audience_tenant_ids', 'status', 'scheduled_at', 'expires_at', 'sent_at', 'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'audience_tenant_ids' => 'array',
            'scheduled_at' => 'datetime',
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /** Hết hạn hiển thị? Tính LIVE tại thời điểm gọi — không lưu trạng thái riêng. */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function views(): HasMany
    {
        return $this->hasMany(GeneralNotificationPageView::class, 'page_id');
    }
}
