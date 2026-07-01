<?php

namespace CMBcoreSeller\Modules\Fulfillment\Http\Resources;

use CMBcoreSeller\Modules\Fulfillment\Models\CarrierAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CarrierAccount
 *
 * Phơi `credentials` (token/shop_id…) để form "Sửa tài khoản" hiển thị lại dữ liệu đã lưu — trước
 * đây form trống trơn khi sửa, và người dùng không thể xác nhận đã chọn đúng Gian hàng (ShopId).
 * Dữ liệu là credential của chính tenant, endpoint đã sau quyền `fulfillment.carriers` + tenant scope;
 * token nhạy cảm được che (Input.Password) và có thể bật/tắt hiển thị ở FE.
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
            'credentials' => (array) ($this->credentials ?? []),
            'credential_keys' => array_keys((array) ($this->credentials ?? [])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
