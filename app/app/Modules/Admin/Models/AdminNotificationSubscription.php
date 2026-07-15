<?php

namespace CMBcoreSeller\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 loại thông báo mà 1 recipient đã bật (SPEC 2026-07-15). Chỉ có `created_at`
 * (không `updated_at`) — hàng này không bao giờ update, chỉ tạo/xoá.
 *
 * @property int $id
 * @property int $admin_notification_recipient_id
 * @property string $notification_type
 */
class AdminNotificationSubscription extends Model
{
    public $timestamps = false;

    protected $fillable = ['admin_notification_recipient_id', 'notification_type', 'created_at'];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(AdminNotificationRecipient::class, 'admin_notification_recipient_id');
    }
}
