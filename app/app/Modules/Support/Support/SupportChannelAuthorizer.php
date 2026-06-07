<?php

namespace CMBcoreSeller\Modules\Support\Support;

use CMBcoreSeller\Modules\Tenancy\Models\TenantUser;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Authorize private broadcast channel `tenant.{id}.support` (ADR-0021 — Reverb).
 *
 * Support/CSKH mở cho MỌI thành viên tenant (route `/api/v1/support/*` chỉ yêu cầu auth+tenant, KHÔNG
 * quyền riêng) ⇒ chỉ cần kiểm membership. Tách khỏi routes/channels.php để test được (sai authz =
 * lộ hội thoại CSKH của tenant khác).
 */
class SupportChannelAuthorizer
{
    /** User có phải thành viên tenant này không (đủ điều kiện nghe realtime support). */
    public function canViewTenantSupport(Authenticatable $user, int $tenantId): bool
    {
        return TenantUser::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->getAuthIdentifier())
            ->exists();
    }
}
