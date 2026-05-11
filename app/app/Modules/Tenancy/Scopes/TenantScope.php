<?php

namespace CMBcoreSeller\Modules\Tenancy\Scopes;

use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope applied by the BelongsToTenant trait: every query on a
 * tenant-owned model is constrained to the current tenant. Bypass with
 * Model::withoutTenantScope() — and review every such call.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(CurrentTenant::class)->id();

        // No current tenant (e.g. console, webhook before resolution): do not
        // leak data — constrain to an impossible id. Callers that legitimately
        // need cross-tenant access use withoutTenantScope() / runAs().
        $builder->where($model->getTable().'.tenant_id', $tenantId ?? 0);
    }

    public function extend(Builder $builder): void
    {
        $builder->macro('withoutTenantScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
