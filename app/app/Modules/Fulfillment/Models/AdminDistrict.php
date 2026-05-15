<?php

namespace CMBcoreSeller\Modules\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Quận / Huyện / Thị xã — chỉ phục vụ format='old' (3-cấp).
 *
 * @property int $id
 * @property string $province_code
 * @property string $code
 * @property string $name
 * @property ?string $codename
 * @property ?string $division_type
 */
class AdminDistrict extends Model
{
    protected $table = 'admin_districts';

    protected $fillable = ['province_code', 'code', 'name', 'codename', 'division_type'];
}
