<?php

namespace CMBcoreSeller\Modules\Support\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * File/ảnh/video đính kèm 1 tin CSKH (SPEC-0028). Mô phỏng `message_attachments`
 * của Messaging (đơn giản hoá: bỏ width/height/duration/external_url — chỉ upload
 * trực tiếp, không relay inbound).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $support_message_id
 * @property string $kind image|video|file
 * @property string $mime
 * @property ?int $size_bytes
 * @property ?string $storage_path
 * @property ?string $checksum
 * @property ?string $filename
 * @property string $status stored|failed
 */
class SupportMessageAttachment extends Model
{
    use BelongsToTenant;

    public const KIND_IMAGE = 'image';

    public const KIND_VIDEO = 'video';

    public const KIND_FILE = 'file';

    public const STATUS_STORED = 'stored';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id', 'support_message_id', 'kind', 'mime', 'size_bytes',
        'storage_path', 'checksum', 'filename', 'status',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(SupportMessage::class, 'support_message_id');
    }
}
