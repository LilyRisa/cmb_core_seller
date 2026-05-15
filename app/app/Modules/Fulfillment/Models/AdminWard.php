<?php

namespace CMBcoreSeller\Modules\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Phường / Xã / Đặc khu / Thị trấn — phục vụ cả format='new' (cấp 2 cuối, parent = province)
 * và format='old' (cấp 3 cuối, parent = district).
 *
 * @property int $id
 * @property string $format
 * @property string $code
 * @property string $province_code
 * @property ?string $district_code
 * @property string $name
 * @property ?string $english_name
 * @property ?string $codename
 * @property ?string $division_type
 * @property ?string $decree
 */
class AdminWard extends Model
{
    protected $table = 'admin_wards';

    protected $fillable = ['format', 'code', 'province_code', 'district_code', 'name', 'english_name', 'codename', 'division_type', 'decree'];
}
