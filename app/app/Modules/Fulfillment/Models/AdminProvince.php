<?php

namespace CMBcoreSeller\Modules\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tỉnh / Thành phố — danh mục hành chính VN (global, không tenant-scoped).
 *
 *  - `format='new'` ⇒ dữ liệu từ AddressKit (cas.so), chuẩn 2-cấp sau 2025.
 *  - `format='old'` ⇒ dữ liệu từ provinces.open-api.vn, chuẩn 3-cấp pre-2025.
 *
 * @property int $id
 * @property string $format
 * @property string $code
 * @property string $name
 * @property ?string $english_name
 * @property ?string $division_type
 * @property ?string $codename
 * @property ?int $phone_code
 * @property ?string $decree
 * @property int $sort_order
 */
class AdminProvince extends Model
{
    protected $table = 'admin_provinces';

    protected $fillable = ['format', 'code', 'name', 'english_name', 'division_type', 'codename', 'phone_code', 'decree', 'sort_order'];

    protected $casts = ['phone_code' => 'integer', 'sort_order' => 'integer'];
}
