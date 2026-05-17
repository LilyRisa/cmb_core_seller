<?php

namespace CMBcoreSeller\Modules\Tenancy\Models;

use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Append-only audit trail. Use AuditLog::record() for sensitive actions.
 *
 * Spec 2026-05-17 — actor có thể là tenant user (`user_id`) HOẶC super-admin
 * (`admin_user_id`). `record()` tự phân biệt qua guard `admin_web`: nếu admin
 * đang login → ghi `admin_user_id`, bỏ qua `tenant_id` và `user_id`; ngược lại
 * vẫn là hành vi cũ (tenant user trên route nghiệp vụ).
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id', 'user_id', 'admin_user_id',
        'action', 'auditable_type', 'auditable_id', 'changes', 'ip',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    /**
     * @param  array<string, mixed>|null  $changes
     */
    public static function record(string $action, ?Model $auditable = null, ?array $changes = null): self
    {
        $adminId = Auth::guard('admin_web')->id();

        return static::create([
            'tenant_id' => $adminId ? null : app(CurrentTenant::class)->id(),
            'user_id' => $adminId ? null : Auth::id(),
            'admin_user_id' => $adminId,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'changes' => $changes,
            'ip' => Request::ip(),
        ]);
    }
}
