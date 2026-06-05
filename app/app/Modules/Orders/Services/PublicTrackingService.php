<?php

namespace CMBcoreSeller\Modules\Orders\Services;

use CMBcoreSeller\Modules\Fulfillment\Models\Shipment;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;

/**
 * SPEC 0030 — builds the public (un-authenticated) order-tracking payload for a
 * manual order, looked up by `order_number`. Every PII field is masked here so a
 * shared link never leaks the full phone/address. The page is read-only: the
 * journey comes from already-synced carrier scans (ShipmentEvent) or, when the
 * seller ships it themselves, from the order status history — never a live
 * carrier API call.
 */
class PublicTrackingService
{
    /**
     * @return array<string,mixed>
     */
    public function build(Order $order): array
    {
        $shipment = $this->trackedShipment($order);
        $addr = (array) ($order->shipping_address ?? []);
        $carrierCode = $shipment !== null ? $shipment->carrier : $order->carrier;

        return [
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'placed_at' => $this->placedIso($order),
            'delivered_at' => $order->delivered_at?->toIso8601String(),
            'carrier_name' => $this->carrierName($carrierCode),
            'cod' => [
                'amount' => (int) $order->cod_amount,
                'is_cod' => (bool) $order->is_cod,
            ],
            'recipient' => [
                'name' => $this->maskName($addr['name'] ?? $order->buyer_name),
                'phone' => $this->maskPhone($addr['phone'] ?? $order->buyer_phone),
                'area' => $this->maskedArea($addr),
            ],
            'items' => $order->items->map(fn ($it) => [
                'name' => $it->name,
                'variation' => $it->variation ?: null,
                'qty' => (int) $it->quantity,
                'image' => $it->image ?: null,
            ])->all(),
            'steps' => $this->buildSteps($order->status),
            'timeline' => $this->buildTimeline($order, $shipment),
        ];
    }

    /**
     * The active shipment that actually went to a real carrier (GHN/GHTK/...),
     * i.e. not self-shipping (`manual`) and not in a pre-create / cancelled state.
     * Relations are eager-loaded by the controller (no tenant context here).
     */
    private function trackedShipment(Order $order): ?Shipment
    {
        return $order->shipments->first(function (Shipment $s) {
            $carrier = (string) $s->carrier;

            return $carrier !== '' && $carrier !== 'manual'
                && ! in_array($s->status, [Shipment::STATUS_PENDING, Shipment::STATUS_CANCELLED], true);
        });
    }

    /**
     * Journey timeline (newest first). Prefer real carrier scans; fall back to the
     * order status history; finally synthesise a single "order created" entry.
     *
     * @return list<array<string,mixed>>
     */
    private function buildTimeline(Order $order, ?Shipment $shipment): array
    {
        if ($shipment && $shipment->events->isNotEmpty()) {
            return $shipment->events
                ->sortByDesc('occurred_at')
                ->values()
                ->map(fn ($e) => [
                    'at' => $e->occurred_at->toIso8601String(),
                    'label' => $e->description ?: ($e->status ? Shipment::statusLabel((string) $e->status) : $e->code),
                    'source' => $e->source,
                ])->all();
        }

        $history = $order->statusHistory; // already ordered desc by changed_at
        if ($history->isNotEmpty()) {
            return $history->map(fn ($h) => [
                'at' => $h->changed_at?->toIso8601String(),
                'label' => $this->orderStatusLabel((string) $h->to_status),
                'source' => $h->source,
            ])->all();
        }

        return [[
            'at' => $this->placedIso($order),
            'label' => 'Đã tạo đơn',
            'source' => 'system',
        ]];
    }

    /** Best-effort "placed" timestamp ISO — falls back to created_at. */
    private function placedIso(Order $order): string
    {
        return ($order->placed_at ?? $order->created_at)->toIso8601String();
    }

    /**
     * Three-stage progress derived from the canonical order status.
     * state ∈ done | process | wait | error.
     *
     * @return list<array{key:string,label:string,state:string}>
     */
    private function buildSteps(StandardOrderStatus $status): array
    {
        $defs = [
            ['key' => 'processing', 'label' => 'Chờ xử lý'],
            ['key' => 'shipped', 'label' => 'Đang giao'],
            ['key' => 'delivered', 'label' => 'Đã giao'],
        ];

        [$activeIdx, $isError] = match ($status) {
            StandardOrderStatus::Unpaid,
            StandardOrderStatus::Pending,
            StandardOrderStatus::Processing,
            StandardOrderStatus::ReadyToShip => [0, false],
            StandardOrderStatus::Shipped => [1, false],
            StandardOrderStatus::Delivered,
            StandardOrderStatus::Completed => [2, false],
            StandardOrderStatus::DeliveryFailed,
            StandardOrderStatus::Returning,
            StandardOrderStatus::ReturnedRefunded => [1, true],
            StandardOrderStatus::Cancelled => [-1, true],
        };

        $allDone = in_array($status, [StandardOrderStatus::Delivered, StandardOrderStatus::Completed], true);
        $cancelled = $status === StandardOrderStatus::Cancelled;

        $steps = [];
        foreach ($defs as $i => $def) {
            $state = match (true) {
                $cancelled => 'wait',
                $allDone => 'done',
                $i < $activeIdx => 'done',
                $i === $activeIdx => $isError ? 'error' : 'process',
                default => 'wait',
            };
            $steps[] = ['key' => $def['key'], 'label' => $def['label'], 'state' => $state];
        }

        return $steps;
    }

    private function orderStatusLabel(string $code): string
    {
        return StandardOrderStatus::tryFrom($code)?->label() ?? $code;
    }

    /** Carrier code → display name; null when self-shipping / unknown-empty. */
    private function carrierName(?string $code): ?string
    {
        $code = str_replace('manual_', '', strtolower((string) $code));

        return match ($code) {
            'ghn' => 'GHN',
            'ghtk' => 'GHTK',
            'jt', 'jnt' => 'J&T Express',
            'vtp', 'viettelpost' => 'Viettel Post',
            'ninjavan' => 'Ninja Van',
            'spx' => 'Shopee Express',
            '', 'manual' => null,
            default => mb_strtoupper($code),
        };
    }

    /** Keep ward/district/province only — drop the house-number/street detail. */
    private function maskedArea(array $addr): ?string
    {
        $parts = array_filter([
            $addr['ward'] ?? null,
            $addr['district'] ?? null,
            $addr['province'] ?? null,
        ], fn ($p) => filled($p));

        return $parts === [] ? null : implode(', ', $parts);
    }

    private function maskName(?string $name): ?string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }
        $parts = preg_split('/\s+/', $name) ?: [$name];
        if (count($parts) === 1) {
            $only = $parts[0];

            return mb_substr($only, 0, 1).str_repeat('*', max(1, mb_strlen($only) - 1));
        }
        $parts[count($parts) - 1] = '***';

        return implode(' ', $parts);
    }

    private function maskPhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }
        $len = strlen($phone);

        return $len <= 4
            ? str_repeat('*', $len)
            : substr($phone, 0, 3).str_repeat('*', max(0, $len - 5)).substr($phone, -2);
    }
}
