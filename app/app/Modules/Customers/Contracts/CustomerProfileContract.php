<?php

namespace CMBcoreSeller\Modules\Customers\Contracts;

use CMBcoreSeller\Modules\Customers\DTO\CustomerProfileDTO;

/**
 * The only way other modules read the customer registry (Orders renders the
 * "Khách hàng" card via this; Settings rules engine reads reputation via this in
 * Phase 6). Implementation bound in CustomersServiceProvider.
 * See docs/01-architecture/modules.md §3 rule 2, SPEC 0002 §5.4.
 */
interface CustomerProfileContract
{
    public function findById(int $tenantId, int $customerId, bool $withFullPhone = false): ?CustomerProfileDTO;

    public function findByPhone(int $tenantId, string $rawPhone, bool $withFullPhone = false): ?CustomerProfileDTO;

    public function isBlocked(int $tenantId, int $customerId): bool;
}
