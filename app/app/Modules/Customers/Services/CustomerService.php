<?php

namespace CMBcoreSeller\Modules\Customers\Services;

use CMBcoreSeller\Models\User;
use CMBcoreSeller\Modules\Customers\Events\CustomerBlocked;
use CMBcoreSeller\Modules\Customers\Events\CustomerUnblocked;
use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerNote;
use CMBcoreSeller\Modules\Customers\Support\ReputationCalculator;

/**
 * Staff-driven mutations on a customer: block/unblock, add/remove tags, add/delete
 * notes. (Matching/stats live in CustomerLinkingService; merge in CustomerMergeService.)
 * See SPEC 0002 §6.1.
 */
class CustomerService
{
    public function block(Customer $customer, ?User $by, ?string $reason): Customer
    {
        if (! $customer->is_blocked) {
            $customer->forceFill([
                'is_blocked' => true,
                'blocked_at' => now(),
                'blocked_by_user_id' => $by?->getKey(),
                'block_reason' => $reason ?: null,
                'reputation_label' => Customer::LABEL_BLOCKED,
            ])->save();
            CustomerBlocked::dispatch($customer, $by, $reason);
        }

        return $customer;
    }

    public function unblock(Customer $customer, ?User $by): Customer
    {
        if ($customer->is_blocked) {
            $rep = ReputationCalculator::evaluate($customer->lifetime_stats ?? [], false);
            $customer->forceFill([
                'is_blocked' => false,
                'blocked_at' => null,
                'blocked_by_user_id' => null,
                'block_reason' => null,
                'reputation_label' => $rep['label'],
            ])->save();
            CustomerUnblocked::dispatch($customer, $by);
        }

        return $customer;
    }

    public function addNote(Customer $customer, ?User $by, string $note, string $severity = CustomerNote::SEV_INFO, ?int $orderId = null): CustomerNote
    {
        return CustomerNote::create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->getKey(),
            'author_user_id' => $by?->getKey(),
            'kind' => CustomerNote::KIND_MANUAL,
            'severity' => in_array($severity, [CustomerNote::SEV_INFO, CustomerNote::SEV_WARNING, CustomerNote::SEV_DANGER], true) ? $severity : CustomerNote::SEV_INFO,
            'note' => $note,
            'order_id' => $orderId,
            'created_at' => now(),
        ]);
    }

    /** Manual notes only; auto-notes are immutable. Returns true if a row was deleted. */
    public function deleteNote(CustomerNote $note): bool
    {
        if ($note->isAuto()) {
            return false;
        }

        return (bool) $note->delete();
    }

    /**
     * @param  list<string>  $add
     * @param  list<string>  $remove
     */
    public function setTags(Customer $customer, array $add = [], array $remove = []): Customer
    {
        $tags = collect($customer->tags ?? [])
            ->merge($add)
            ->reject(fn ($t) => in_array($t, $remove, true))
            ->map(fn ($t) => trim((string) $t))->filter()->unique()->values()->all();
        $customer->forceFill(['tags' => $tags])->save();

        return $customer;
    }
}
