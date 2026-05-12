<?php

namespace CMBcoreSeller\Modules\Channels\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Channels\Http\Resources\SyncRunResource;
use CMBcoreSeller\Modules\Channels\Http\Resources\WebhookEventResource;
use CMBcoreSeller\Modules\Channels\Jobs\ProcessWebhookEvent;
use CMBcoreSeller\Modules\Channels\Jobs\SyncOrdersForShop;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\SyncRun;
use CMBcoreSeller\Modules\Channels\Models\WebhookEvent;
use CMBcoreSeller\Modules\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * /api/v1/sync-runs + /api/v1/webhook-events — the "Nhật ký đồng bộ" screen:
 * inspect poll/backfill runs and inbound webhooks, re-drive a failed one.
 * `sync_runs` is tenant-scoped via BelongsToTenant; `webhook_events` is an infra
 * log table (plain `tenant_id` column) so we filter it explicitly. See SPEC 0001
 * §6, docs/03-domain/order-sync-pipeline.md.
 */
class SyncLogController extends Controller
{
    /** GET /api/v1/sync-runs */
    public function runs(Request $request): JsonResponse
    {
        $this->authorizeView($request);

        $query = SyncRun::query()->with('channelAccount')->latest('started_at')->latest('id');

        if ($cid = $request->query('channel_account_id')) {
            $query->where('channel_account_id', (int) $cid);
        }
        if ($type = $request->query('type')) {
            $query->whereIn('type', $this->csv($type, [SyncRun::TYPE_POLL, SyncRun::TYPE_BACKFILL, SyncRun::TYPE_WEBHOOK]));
        }
        if ($status = $request->query('status')) {
            $query->whereIn('status', $this->csv($status, [SyncRun::STATUS_RUNNING, SyncRun::STATUS_DONE, SyncRun::STATUS_FAILED]));
        }

        return $this->paginated($request, $query, fn ($c) => SyncRunResource::collection($c));
    }

    /** GET /api/v1/webhook-events */
    public function webhookEvents(Request $request, CurrentTenant $tenant): JsonResponse
    {
        $this->authorizeView($request);

        $query = WebhookEvent::query()
            ->where('tenant_id', $tenant->id())
            ->with('channelAccount')
            ->latest('received_at')->latest('id');

        if ($cid = $request->query('channel_account_id')) {
            $query->where('channel_account_id', (int) $cid);
        }
        if ($provider = $request->query('provider')) {
            $query->where('provider', (string) $provider);
        }
        if ($eventType = $request->query('event_type')) {
            $query->whereIn('event_type', $this->csv($eventType));
        }
        if ($status = $request->query('status')) {
            $query->whereIn('status', $this->csv($status, [
                WebhookEvent::STATUS_PENDING, WebhookEvent::STATUS_PROCESSED, WebhookEvent::STATUS_IGNORED, WebhookEvent::STATUS_FAILED,
            ]));
        }
        if ($request->has('signature_ok')) {
            $query->where('signature_ok', $request->boolean('signature_ok'));
        }

        return $this->paginated($request, $query, fn ($c) => WebhookEventResource::collection($c));
    }

    /** POST /api/v1/webhook-events/{id}/redrive — re-queue ProcessWebhookEvent. */
    public function redriveWebhook(Request $request, int $id, CurrentTenant $tenant): JsonResponse
    {
        $this->authorizeManage($request);

        /** @var WebhookEvent $event */
        $event = WebhookEvent::query()->where('tenant_id', $tenant->id())->findOrFail($id);
        // Reset to pending so the job re-processes it (it short-circuits on PROCESSED).
        $event->forceFill(['status' => WebhookEvent::STATUS_PENDING, 'error' => null, 'processed_at' => null])->save();
        ProcessWebhookEvent::dispatch((int) $event->getKey());

        return response()->json(['data' => ['queued' => true, 'webhook_event_id' => $event->getKey()]]);
    }

    /** POST /api/v1/sync-runs/{id}/redrive — re-run a poll for that channel account. */
    public function redriveRun(Request $request, int $id): JsonResponse
    {
        $this->authorizeManage($request);

        /** @var SyncRun $run */
        $run = SyncRun::query()->findOrFail($id);
        /** @var ChannelAccount|null $account */
        $account = ChannelAccount::query()->find($run->channel_account_id);
        abort_unless($account && $account->isActive(), 409, 'Gian hàng không ở trạng thái hoạt động.');

        $type = $run->type === SyncRun::TYPE_BACKFILL ? SyncRun::TYPE_BACKFILL : SyncRun::TYPE_POLL;
        SyncOrdersForShop::dispatch((int) $account->getKey(), null, $type);

        return response()->json(['data' => ['queued' => true, 'channel_account_id' => $account->getKey(), 'type' => $type]]);
    }

    // --- helpers -------------------------------------------------------------

    /**
     * @param  list<string>  $allowed  optional whitelist
     * @return list<string>
     */
    private function csv(mixed $value, array $allowed = []): array
    {
        $values = array_values(array_filter(array_map('trim', explode(',', (string) $value))));

        return $allowed ? array_values(array_intersect($values, $allowed)) : $values;
    }

    private function paginated(Request $request, Builder $query, callable $resource): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $page = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $resource($page->getCollection()),
            'meta' => ['pagination' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'total_pages' => $page->lastPage(),
            ]],
        ]);
    }

    private function authorizeView(Request $request): void
    {
        abort_unless($request->user()?->can('channels.view'), 403, 'Bạn không có quyền xem nhật ký đồng bộ.');
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()?->can('channels.manage'), 403, 'Chỉ chủ sở hữu / quản trị mới chạy lại đồng bộ.');
    }
}
