<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Map (page, PSID) → bài viết nguồn, để hội thoại DM bắt nguồn từ bình luận biết
 * mình đến từ bài viết nào (SPEC 2026-06-09). Ghi khi gửi tin riêng cho comment
 * (CommentDmLinker::record); đọc khi có DM inbound (stampInbound). Truy vấn trong
 * webhook/job KHÔNG có tenant context ⇒ luôn scope theo channel_account_id +
 * withoutGlobalScope(TenantScope).
 *
 * @property int $tenant_id
 * @property int $channel_account_id
 * @property string $psid
 * @property string $fb_post_id
 * @property ?string $fb_comment_id
 */
class CommentDmLink extends Model
{
    use BelongsToTenant;

    protected $table = 'messaging_comment_dm_links';

    protected $fillable = [
        'tenant_id', 'channel_account_id', 'psid', 'fb_post_id', 'fb_comment_id', 'linked_at',
    ];

    protected $casts = [
        'linked_at' => 'datetime',
    ];
}
