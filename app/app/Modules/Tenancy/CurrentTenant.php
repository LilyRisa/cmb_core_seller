<?php

namespace CMBcoreSeller\Modules\Tenancy;

use CMBcoreSeller\Modules\Tenancy\Enums\Role;
use CMBcoreSeller\Modules\Tenancy\Models\Tenant;
use CMBcoreSeller\Modules\Tenancy\Models\TenantUser;
use RuntimeException;

/**
 * Holds the tenant the current request/job is operating on. Bound as a
 * scoped singleton in TenancyServiceProvider; set by the EnsureTenant
 * middleware (web) or explicitly by jobs.
 *
 * The global TenantScope and BelongsToTenant trait read from here, so every
 * query is automatically scoped to one tenant. See docs/01-architecture/multi-tenancy-and-rbac.md.
 */
class CurrentTenant
{
    protected ?Tenant $tenant = null;

    protected ?TenantUser $membership = null;

    public function set(Tenant $tenant, ?TenantUser $membership = null): void
    {
        $this->tenant = $tenant;
        $this->membership = $membership;
    }

    public function clear(): void
    {
        $this->tenant = null;
        $this->membership = null;
    }

    public function check(): bool
    {
        return $this->tenant !== null;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function getOrFail(): Tenant
    {
        return $this->tenant ?? throw new RuntimeException('No current tenant set for this request/job.');
    }

    public function id(): ?int
    {
        return $this->tenant?->getKey();
    }

    public function membership(): ?TenantUser
    {
        return $this->membership;
    }

    public function role(): ?Role
    {
        return $this->membership?->role;
    }

    public function can(string $permission): bool
    {
        return $this->role()?->can($permission) ?? false;
    }

    /** Run a callback as a given tenant, restoring the previous one after. */
    public function runAs(Tenant $tenant, callable $callback, ?TenantUser $membership = null): mixed
    {
        $prevTenant = $this->tenant;
        $prevMembership = $this->membership;
        $this->set($tenant, $membership);

        try {
            return $callback();
        } finally {
            $this->tenant = $prevTenant;
            $this->membership = $prevMembership;
        }
    }
}
