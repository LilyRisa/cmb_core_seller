<?php

namespace CMBcoreSeller\Modules\Channels\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Raw inbound marketplace webhook. An infra/log table — `tenant_id` is a plain
 * column resolved during processing, not a global scope. Payload kept verbatim
 * for re-drive. See docs/03-domain/order-sync-pipeline.md §2.
 */
class WebhookEvent extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_IGNORED = 'ignored';     // recognised but nothing to do (unknown event type)
    public const STATUS_FAILED = 'failed';       // gave up after retries

    protected $fillable = [
        'provider', 'event_type', 'external_id', 'external_shop_id', 'raw_type',
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

    public function markProcessed(?string $status = self::STATUS_PROCESSED): void
    {
        $this->forceFill(['status' => $status, 'processed_at' => now()])->save();
    }

    public function markFailed(string $error): void
    {
        $this->forceFill(['status' => self::STATUS_FAILED, 'error' => $error, 'processed_at' => now()])->save();
    }
}
