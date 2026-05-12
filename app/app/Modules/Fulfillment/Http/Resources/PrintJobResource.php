<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Resources;

use CMBcoreSeller\Modules\Fulfillment\Models\PrintJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PrintJob */
class PrintJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'scope' => $this->scope,
            'status' => $this->status,
            'file_url' => $this->file_url,
            'file_size' => $this->file_size,
            'error' => $this->error,
            'meta' => $this->meta ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
