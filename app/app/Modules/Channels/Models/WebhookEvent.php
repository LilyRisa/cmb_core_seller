<?php

namespace CMBcoreSeller\Modules\Channels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Raw inbound marketplace webhook. An infra/log table — `tenant_id` is a plain
 * column resolved during processing, not a global scope. Payload kept verbatim
 * for re-drive. See docs/03-domain/order-sync-pipeline.md §2.
 *
 * @property int $id
 * @property string $provider
 * @property string $event_type
 * @property string|null $external_id
 * @property string|null $external_shop_id
 * @property string|null $order_raw_status
 * @property string|null $raw_type
 * @property int|null $tenant_id
 * @property int|null $channel_account_id
 * @property bool $signature_ok
 * @property array|null $headers
 * @property array|null $payload
 * @property string $status
 * @property int $attempts
 * @property string|null $error
 * @property Carbon|null $received_at
 * @property Carbon|null $processed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ChannelAccount|null $channelAccount
 */
class WebhookEvent extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_IGNORED = 'ignored';     // recognised but nothing to do (unknown event type)

    public const STATUS_FAILED = 'failed';       // gave up after retries

    protected $fillable = [
        'provider', 'event_type', 'external_id', 'external_shop_id', 'order_raw_status', 'raw_type',
        'tenant_id', 'channel_account_id', 'signature_ok', 'headers', 'payload',
        'status', 'attempts', 'error', 'received_at', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'signature_ok' => 'boolean',
            'headers' => 'array',
            'payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function channelAccount(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class);
    }

    public function markProcessed(?string $status = self::STATUS_PROCESSED): void
    {
        $this->forceFill(['status' => $status, 'processed_at' => now()])->save();
    }

    public function markFailed(string $error): void
    {
        $this->forceFill(['status' => self::STATUS_FAILED, 'error' => $error, 'processed_at' => now()])->save();
    }
}
