<?php

namespace CMBcoreSeller\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Hình nền màn Desktop (SPEC 0039) do super-admin quản lý. KHÔNG tenant-scoped.
 * Người dùng chọn 1 preset active; URL lưu vào user_preferences.ui_desktop_bg.
 *
 * @property int $id
 * @property string $name
 * @property string $image_url
 * @property string $image_path
 * @property bool $is_active
 * @property int $position
 * @property int $created_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DesktopBackground extends Model
{
    protected $fillable = ['name', 'image_url', 'image_path', 'is_active', 'position', 'created_by_user_id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /** Preset đang bật, sắp theo thứ tự hiển thị. */
    public function scopeActiveOrdered(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('position')->orderBy('id');
    }
}
