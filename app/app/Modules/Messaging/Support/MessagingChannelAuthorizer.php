<?php

namespace CMBcoreSeller\Modules\Messaging\Support;

use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Models\TenantUser;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Authorize private broadcast channel `tenant.{id}.messaging` (ADR-0021 — Reverb).
 *
 * Tách khỏi routes/channels.php để test được (ADR: sai authz = lộ tin nhắn tenant khác).
 * Route `/broadcasting/auth` chạy ở web group, KHÔNG qua EnsureTenant ⇒ permission gate
 * thiếu tenant context; ta tự resolve membership rồi set CurrentTenant để check `can()`.
 */
class MessagingChannelAuthorizer
{
    public function __construct(private CurrentTenant $currentTenant) {}

    /** User có được nghe realtime inbox của tenant này không (thành viên + quyền messaging.view). */
    public function canViewTenantMessaging(Authenticatable $user, int $tenantId): bool
    {
        $tenant = Tenant::query()->find($tenantId);
        if ($tenant === null) {
            return false;
        }

        $membership = TenantUser::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->getAuthIdentifier())
            ->first();
        if ($membership === null) {
            return false; // không phải thành viên ⇒ chặn (cross-tenant)
        }

        return (bool) $this->currentTenant->runAs(
            $tenant,
            fn (): bool => $this->currentTenant->can('messaging.view'),
            $membership,
        );
    }
}
