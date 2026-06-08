<?php

namespace CMBcoreSeller\Modules\Admin\Http\Resources;

use CMBcoreSeller\Modules\Admin\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Announcement cho trang admin (đầy đủ, SPEC 0037).
 *
 * @mixin Announcement
 */
class AdminAnnouncementResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body_html' => $this->body_html,
            'is_active' => (bool) $this->is_active,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'dismiss_label' => $this->dismiss_label,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
