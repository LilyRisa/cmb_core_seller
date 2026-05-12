<?php

namespace CMBcoreSeller\Modules\Customers\DTO;

use CMBcoreSeller\Modules\Customers\Models\Customer;
use CMBcoreSeller\Modules\Customers\Models\CustomerNote;

/**
 * Thin, module-boundary view of a customer — everything Orders (and later
 * Settings/Notifications) needs to render the "Khách hàng" card without touching
 * the Customer model. See SPEC 0002 §5.4.
 */
final class CustomerProfileDTO
{
    /**
     * @param  array<string,mixed>  $lifetimeStats
     * @param  list<string>  $tags
     * @param  array{kind:string,note:string,severity:string,created_at:?string}|null  $latestWarningNote
     */
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly ?string $phoneMasked,
        public readonly ?string $phoneFull,        // only populated when caller has customers.view_phone
        public readonly int $reputationScore,
        public readonly string $reputationLabel,
        public readonly bool $isBlocked,
        public readonly ?string $blockReason,
        public readonly array $tags,
        public readonly array $lifetimeStats,
        public readonly bool $isAnonymized,
        public readonly ?string $manualNote,
        public readonly ?array $latestWarningNote,
    ) {}

    public static function fromModel(Customer $c, bool $withFullPhone = false, ?CustomerNote $latestWarning = null): self
    {
        return new self(
            id: (int) $c->getKey(),
            name: $c->name,
            phoneMasked: $c->maskedPhone(),
            phoneFull: $withFullPhone ? $c->phone : null,
            reputationScore: (int) $c->reputation_score,
            reputationLabel: $c->reputation_label,
            isBlocked: (bool) $c->is_blocked,
            blockReason: $c->block_reason,
            tags: array_values($c->tags ?? []),
            lifetimeStats: $c->lifetime_stats ?? [],
            isAnonymized: $c->isAnonymized(),
            manualNote: $c->manual_note,
            latestWarningNote: $latestWarning ? [
                'kind' => $latestWarning->kind,
                'note' => $latestWarning->note,
                'severity' => $latestWarning->severity,
                'created_at' => $latestWarning->created_at?->toIso8601String(),
            ] : null,
        );
    }

    /** Compact array used inside OrderResource (`customer` sub-object). */
    public function toOrderCard(): array
    {
        $s = $this->lifetimeStats;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone_masked' => $this->phoneMasked,
            'reputation' => ['score' => $this->reputationScore, 'label' => $this->reputationLabel],
            'is_blocked' => $this->isBlocked,
            'tags' => $this->tags,
            'is_anonymized' => $this->isAnonymized,
            'lifetime_stats' => [
                'orders_total' => (int) ($s['orders_total'] ?? 0),
                'orders_completed' => (int) ($s['orders_completed'] ?? 0),
                'orders_cancelled' => (int) ($s['orders_cancelled'] ?? 0),
                'orders_returned' => (int) ($s['orders_returned'] ?? 0),
                'orders_delivery_failed' => (int) ($s['orders_delivery_failed'] ?? 0),
            ],
            'manual_note' => $this->manualNote,
            'latest_warning_note' => $this->latestWarningNote,
        ];
    }
}
