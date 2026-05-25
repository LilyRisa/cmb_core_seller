<?php

namespace CMBcoreSeller\Modules\Orders\Services;

use CMBcoreSeller\Integrations\Channels\DTO\ReturnDTO;
use CMBcoreSeller\Modules\Orders\Contracts\ReturnUpsertContract;
use CMBcoreSeller\Modules\Orders\Events\ReturnStatusChanged;
use CMBcoreSeller\Modules\Orders\Events\ReturnUpserted;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Modules\Orders\Models\OrderReturn;
use CMBcoreSeller\Modules\Tenancy\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent upsert of a normalized after-sales record into order_returns. Rules (SPEC 0025 §4):
 *  - unique key (source, channel_account_id, external_return_id); restore soft-deleted rows.
 *  - skip if DTO.sourceUpdatedAt <= existing.source_updated_at (late / out-of-order).
 *  - resolve order_id via (source, channel_account_id, external_order_id) when present.
 *  - keep orders.has_return = "có ≥1 đơn hoàn/hủy đang mở". KHÔNG đụng orders.status (chỉ gắn cờ — §12).
 *  - everything in one transaction; domain events fire afterCommit.
 */
class ReturnUpsertService implements ReturnUpsertContract
{
    public function upsert(ReturnDTO $dto, int $tenantId, ?int $channelAccountId, string $source): OrderReturn
    {
        [$return, $created, $statusChanged, $from] = DB::transaction(function () use ($dto, $tenantId, $channelAccountId) {
            /** @var OrderReturn|null $return */
            $return = OrderReturn::withoutGlobalScope(TenantScope::class)
                ->withTrashed()
                ->where('source', $dto->source)
                ->where('channel_account_id', $channelAccountId)
                ->where('external_return_id', $dto->externalReturnId)
                ->lockForUpdate()
                ->first();

            if ($return && $return->trashed()) {
                $return->restore();
            }
            $created = $return === null;
            $sourceUpdatedAt = $dto->sourceUpdatedAt;

            // Out-of-order / late: bỏ qua nếu đã có snapshot mới hơn (hoặc bằng).
            if (! $created && $sourceUpdatedAt && $return->source_updated_at && $sourceUpdatedAt->lessThanOrEqualTo($return->source_updated_at)) {
                return [$return, false, false, $return->status];
            }

            $orderId = $this->resolveOrderId($dto->source, $channelAccountId, $dto->externalOrderId);
            $previous = $created ? null : $return->status;

            $attrs = [
                'tenant_id' => $tenantId,
                'channel_account_id' => $channelAccountId,
                'order_id' => $orderId,
                'source' => $dto->source,
                'external_return_id' => $dto->externalReturnId,
                'external_order_id' => $dto->externalOrderId,
                'kind' => $dto->kind,
                'status' => $dto->status,
                'raw_status' => $dto->rawStatus,
                'reason' => $dto->reason,
                'refund_amount' => $dto->refundAmount,
                'currency' => $dto->currency ?: 'VND',
                'items' => $dto->items ?: null,
                'requested_at' => $dto->requestedAt,
                'decided_at' => $dto->decidedAt,
                'source_updated_at' => $sourceUpdatedAt,
                'raw' => $dto->raw ?: null,
            ];

            if ($created) {
                $return = new OrderReturn($attrs);
                $return->save();
            } else {
                // Giữ order_id cũ nếu lần này resolve không ra (đơn có thể chưa sync lúc trước).
                if ($orderId === null) {
                    unset($attrs['order_id']);
                }
                $return->forceFill($attrs)->save();
            }

            $statusChanged = $created || $previous !== $dto->status;

            // Cập nhật cờ has_return trên đơn gốc (nếu có): true nếu còn ≥1 return đang mở.
            $finalOrderId = $return->order_id;
            if ($finalOrderId) {
                $this->syncOrderHasReturn((int) $finalOrderId);
            }

            return [$return, $created, $statusChanged, $previous];
        });

        ReturnUpserted::dispatch($return, $created);
        if ($statusChanged) {
            ReturnStatusChanged::dispatch($return, $from, $return->status, $source);
        }

        return $return;
    }

    private function resolveOrderId(string $source, ?int $channelAccountId, ?string $externalOrderId): ?int
    {
        if (! $externalOrderId) {
            return null;
        }
        $id = Order::withoutGlobalScope(TenantScope::class)
            ->where('source', $source)
            ->where('channel_account_id', $channelAccountId)
            ->where('external_order_id', $externalOrderId)
            ->value('id');

        return $id ? (int) $id : null;
    }

    private function syncOrderHasReturn(int $orderId): void
    {
        $hasOpen = OrderReturn::withoutGlobalScope(TenantScope::class)
            ->where('order_id', $orderId)->open()->exists();
        Order::withoutGlobalScope(TenantScope::class)->where('id', $orderId)
            ->where('has_return', '!=', $hasOpen)->update(['has_return' => $hasOpen]);
    }
}
