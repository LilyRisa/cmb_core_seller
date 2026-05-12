<?php

namespace CMBcoreSeller\Modules\Customers\Services;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Customers\Events\CustomersMerged;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerNote;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Merge two customer records that are really the same person (buyer changed phone
 * number): move orders + notes from `remove` to `keep`, recompute, soft-delete
 * `remove` with a back-pointer. See SPEC 0002 §3.4, §6.1.
 */
class CustomerMergeService
{
    public function __construct(private CustomerLinkingService $linking) {}

    public function merge(Customer $keep, Customer $remove, ?User $by): Customer
    {
        if ($keep->getKey() === $remove->getKey()) {
            throw ValidationException::withMessages(['remove_id' => 'Không thể gộp một khách với chính nó.']);
        }
        if ((int) $keep->tenant_id !== (int) $remove->tenant_id) {
            throw ValidationException::withMessages(['remove_id' => 'Hai khách không thuộc cùng một workspace.']);
        }

        DB::transaction(function () use ($keep, $remove, $by) {
            Order::withoutGlobalScope(TenantScope::class)
                ->where('customer_id', $remove->getKey())
                ->update(['customer_id' => $keep->getKey()]);

            CustomerNote::withoutGlobalScope(TenantScope::class)
                ->where('customer_id', $remove->getKey())
                // drop dedupe_key so auto-notes don't collide on the (customer_id, dedupe_key) unique index
                ->update(['customer_id' => $keep->getKey(), 'dedupe_key' => null]);

            // Keep the earliest first_seen / latest last_seen / a non-null name.
            $keep->forceFill([
                'first_seen_at' => $keep->first_seen_at->lt($remove->first_seen_at) ? $keep->first_seen_at : $remove->first_seen_at,
                'last_seen_at' => $keep->last_seen_at->gt($remove->last_seen_at) ? $keep->last_seen_at : $remove->last_seen_at,
                'name' => $keep->name ?: $remove->name,
                'manual_note' => $keep->manual_note ?: $remove->manual_note,
            ])->save();

            CustomerNote::withoutGlobalScope(TenantScope::class)->create([
                'tenant_id' => $keep->tenant_id,
                'customer_id' => $keep->getKey(),
                'author_user_id' => $by?->getKey(),
                'kind' => CustomerNote::KIND_MERGE,
                'severity' => CustomerNote::SEV_INFO,
                'note' => "Đã gộp hồ sơ khách #{$remove->getKey()} vào hồ sơ này.",
                'created_at' => now(),
            ]);

            $remove->forceFill(['merged_into_customer_id' => $keep->getKey()])->save();
            $remove->delete(); // soft delete

            $this->linking->recompute($keep);
        });

        CustomersMerged::dispatch($keep->fresh(), $remove, $by);

        return $keep->fresh();
    }
}
