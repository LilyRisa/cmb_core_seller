<?php

namespace CMBcoreSeller\Modules\Tenancy\Models;

use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Append-only audit trail. Use AuditLog::record() for sensitive actions.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id', 'user_id', 'action', 'auditable_type', 'auditable_id', 'changes', 'ip',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    /**
     * @param  array<string, mixed>|null  $changes
     */
    public static function record(string $action, ?Model $auditable = null, ?array $changes = null): self
    {
        return static::create([
            'tenant_id' => app(CurrentTenant::class)->id(),
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'changes' => $changes,
            'ip' => Request::ip(),
        ]);
    }
}
