<?php

namespace CMBcoreSeller\Modules\Customers\Http\Resources;

use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Customer
 *
 * Full customer view. `phone` (plaintext) is only included when the caller has
 * `customers.view_phone`; otherwise only `phone_masked`. See SPEC 0002 §6.1.
 */
class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canViewPhone = (bool) $request->user()?->can('customers.view_phone');
        $anonymized = $this->isAnonymized();

        // latest warning/danger note (from the loaded notes relation, if present; notes come id-desc)
        $latestWarning = null;
        if ($this->relationLoaded('notes')) {
            $n = $this->notes->whereIn('severity', [CustomerNote::SEV_WARNING, CustomerNote::SEV_DANGER])->first();
            if ($n instanceof CustomerNote) {
                $latestWarning = ['kind' => $n->kind, 'note' => $n->note, 'severity' => $n->severity, 'created_at' => $n->created_at?->toIso8601String()];
            }
        }

        return [
            'id' => $this->id,
            'name' => $anonymized ? null : $this->name,
            'phone_masked' => $anonymized ? null : $this->maskedPhone(),
            'phone' => ($canViewPhone && ! $anonymized) ? $this->phone : null,
            'reputation' => ['score' => (int) $this->reputation_score, 'label' => $this->reputation_label],
            'is_blocked' => (bool) $this->is_blocked,
            'block_reason' => $this->block_reason,
            'blocked_at' => $this->blocked_at?->toIso8601String(),
            'tags' => array_values($this->tags ?? []),
            'lifetime_stats' => $this->lifetime_stats ?? [],
            'addresses_meta' => $anonymized ? [] : ($this->addresses_meta ?? []),
            'manual_note' => $anonymized ? null : $this->manual_note,
            'is_anonymized' => $anonymized,
            'first_seen_at' => $this->first_seen_at->toIso8601String(),
            'last_seen_at' => $this->last_seen_at->toIso8601String(),
            'merged_into_customer_id' => $this->merged_into_customer_id,
            'latest_warning_note' => $latestWarning,
            'notes' => CustomerNoteResource::collection($this->whenLoaded('notes')),
        ];
    }
}
