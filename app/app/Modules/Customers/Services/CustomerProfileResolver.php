<?php

namespace CMBcoreSeller\Modules\Customers\Services;

use CMBcoreSeller\Modules\Customers\Contracts\CustomerProfileContract;
use CMBcoreSeller\Modules\Customers\DTO\CustomerProfileDTO;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerNote;
use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;

/**
 * Default implementation of CustomerProfileContract — the read boundary other
 * modules use. Always scopes by an explicit tenant_id. See SPEC 0002 §5.4.
 */
class CustomerProfileResolver implements CustomerProfileContract
{
    public function findById(int $tenantId, int $customerId, bool $withFullPhone = false): ?CustomerProfileDTO
    {
        $customer = Customer::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->whereKey($customerId)->first();

        return $customer ? CustomerProfileDTO::fromModel($customer, $withFullPhone, $this->latestWarningNote($customer)) : null;
    }

    public function findByPhone(int $tenantId, string $rawPhone, bool $withFullPhone = false): ?CustomerProfileDTO
    {
        $hash = CustomerPhoneNormalizer::normalizeAndHash($rawPhone);
        if ($hash === null) {
            return null;
        }
        $customer = Customer::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->where('phone_hash', $hash)->first();

        return $customer ? CustomerProfileDTO::fromModel($customer, $withFullPhone, $this->latestWarningNote($customer)) : null;
    }

    public function isBlocked(int $tenantId, int $customerId): bool
    {
        return Customer::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId)->whereKey($customerId)->where('is_blocked', true)->exists();
    }

    private function latestWarningNote(Customer $customer): ?CustomerNote
    {
        return CustomerNote::withoutGlobalScope(TenantScope::class)
            ->where('customer_id', $customer->getKey())
            ->whereIn('severity', [CustomerNote::SEV_WARNING, CustomerNote::SEV_DANGER])
            ->orderByDesc('id')->first();
    }
}
