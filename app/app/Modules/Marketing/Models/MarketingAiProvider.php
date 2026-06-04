<?php

namespace CMBcoreSeller\Modules\Marketing\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * AI provider dedicated to marketing analysis (forecast/strategy). System-level
 * (super-admin), separate from messaging `ai_providers`.
 *
 * @property string $code
 * @property ?string $display_name
 * @property string $adapter
 * @property ?string $api_key
 * @property ?string $base_url
 * @property ?string $default_model
 * @property bool $is_active
 */
class MarketingAiProvider extends Model
{
    protected $table = 'marketing_ai_providers';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['code', 'display_name', 'adapter', 'api_key', 'base_url', 'default_model', 'is_active'];

    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return ['api_key' => 'encrypted', 'is_active' => 'boolean'];
    }
}
