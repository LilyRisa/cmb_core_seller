<?php

namespace CMBcoreSeller\Modules\Channels\Http\Resources;

use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\SyncRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SyncRun
 */
class SyncRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $stats = $this->stats ?? [];
        /** @var ChannelAccount|null $account */
        $account = $this->relationLoaded('channelAccount') ? $this->getRelation('channelAccount') : null;

        return [
            'id' => $this->id,
            'channel_account_id' => $this->channel_account_id,
            'shop_name' => $account ? ($account->shop_name ?? $account->external_shop_id) : null,
            'provider' => $account?->provider,
            'type' => $this->type,
            'status' => $this->status,
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'duration_seconds' => $this->started_at && $this->finished_at ? $this->finished_at->diffInSeconds($this->started_at) : null,
            'cursor' => $this->cursor,
            'stats' => [
                'fetched' => (int) ($stats['fetched'] ?? 0),
                'created' => (int) ($stats['created'] ?? 0),
                'updated' => (int) ($stats['updated'] ?? 0),
                'skipped' => (int) ($stats['skipped'] ?? 0),
                'errors' => (int) ($stats['errors'] ?? 0),
            ],
            'error' => $this->error,
        ];
    }
}
