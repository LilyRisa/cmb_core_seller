<?php

namespace CMBcoreSeller\Modules\Tenancy\Models;

use CMBcoreSeller\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Preference cấp NGƯỜI DÙNG (không theo tenant) — vd lựa chọn vỏ giao diện v1/v2.
 * Ngoại lệ có chủ đích với BelongsToTenant: xem ADR-0027 / SPEC-0037.
 */
class UserPreference extends Model
{
    protected $fillable = ['user_id', 'key', 'value'];

    protected $casts = ['value' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
