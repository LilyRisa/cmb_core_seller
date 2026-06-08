<?php

namespace CMBcoreSeller\Modules\Admin\Models;

use CMBcoreSeller\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Popup thông báo toàn hệ thống do super-admin tạo (SPEC 0037). KHÔNG tenant-scoped.
 * `body_html` đã sanitize allowlist trước khi lưu (xem {@see HtmlSanitizer}).
 *
 * @property int $id
 * @property string $title
 * @property string $body_html
 * @property bool $is_active
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property string $dismiss_label
 * @property int $created_by_user_id
 * @property array<string,mixed>|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Announcement extends Model
{
    protected $fillable = [
        'title', 'body_html', 'is_active', 'starts_at', 'ends_at', 'dismiss_label', 'created_by_user_id', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /** Đang bật + trong cửa sổ chiếu (starts/ends nullable = không giới hạn). */
    public function scopeActiveNow(Builder $q): Builder
    {
        return $q->where('is_active', true)
            ->where(fn (Builder $w) => $w->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $w) => $w->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }
}
