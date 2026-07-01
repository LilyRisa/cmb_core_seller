<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Resources;

use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CarrierAccount
 *
 * KHÔNG phơi `credentials` thô (token/secret) trong list/create/update để tránh rò rỉ (xem test
 * "no credential leak"). Form "Sửa tài khoản" muốn hiển thị lại giá trị đã lưu thì gọi riêng endpoint
 * reveal `GET /carrier-accounts/{id}/credentials` (owner/quản lý ĐVVC) — token che & bật/tắt ở FE.
 */
class CarrierAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'carrier' => $this->carrier,
            'name' => $this->name,
            'default_service' => $this->default_service,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'meta' => $this->meta ?? [],
            'credential_keys' => array_keys((array) ($this->credentials ?? [])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
