<?php

namespace CMBcoreSeller\Modules\Notifications\Support;

use CMBcoreSeller\Modules\Tenancy\Models\TenantUser;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Authorize private channel `tenant.{tenantId}.notifications.{userId}` (SPEC 0036).
 *
 * Tách khỏi routes/channels.php để test được (giống MessagingChannelAuthorizer). User
 * chỉ được nghe channel CỦA CHÍNH MÌNH trong tenant mình là thành viên: `userId` PHẢI
 * trùng auth id + phải là thành viên tenant. Sai ⇒ false ⇒ Echo từ chối join.
 */
class NotificationChannelAuthorizer
{
    public function canListen(Authenticatable $user, int $tenantId, int $userId): bool
    {
        if ((int) $user->getAuthIdentifier() !== $userId) {
            return false; // chỉ nghe channel của chính mình
        }

        return TenantUser::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->exists();
    }
}
