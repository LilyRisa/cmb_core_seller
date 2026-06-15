<?php

namespace CMBcoreSeller\Modules\Messaging\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * File đính kèm 1 message. File thực lưu MinIO ở `storage_path` theo prefix
 * `tenants/{id}/messaging/{yyyy/mm}/{conversation_id}/{uuid}.{ext}`.
 *
 * Inbound: `external_url` từ sàn TTL ngắn ⇒ `DownloadInboundMedia` job relay
 * vào MinIO → set `storage_path` + `status='downloaded'`.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $message_id
 * @property string $kind
 * @property string|null $mime
 * @property int|null $size_bytes
 * @property string|null $storage_path
 * @property string|null $external_url
 * @property string $status
 */
class MessageAttachment extends Model
{
    use BelongsToTenant;

    public const KIND_IMAGE = 'image';

    public const KIND_VIDEO = 'video';

    public const KIND_FILE = 'file';

    public const KIND_AUDIO = 'audio';

    public const STATUS_PENDING = 'pending';

    public const STATUS_DOWNLOADED = 'downloaded';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id', 'message_id', 'kind', 'mime', 'size_bytes',
        'storage_path', 'external_url', 'checksum',
        'width', 'height', 'duration_ms', 'filename',
        'status', 'error',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
