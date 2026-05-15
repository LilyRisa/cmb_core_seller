<?php

namespace CMBcoreSeller\Modules\Accounting\Models;

use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Tài khoản kế toán (Chart of Account). Phase 7.1 — SPEC 0019.
 *
 *  - `code` chuỗi (không phải số) để giữ zero đầu nếu cần ('0001').
 *  - `type` phân nhóm BCTC.
 *  - `is_postable=false` cho TK tổng; chỉ TK lá mới được dùng trong journal_lines.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property string $name
 * @property string $type            // asset|liability|equity|revenue|expense|cogs|contra_revenue|contra_asset|clearing
 * @property int|null $parent_id
 * @property string $normal_balance  // debit|credit
 * @property bool $is_postable
 * @property bool $is_active
 * @property string $vas_template
 * @property int $sort_order
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ChartAccount|null $parent
 * @property-read Collection<int, ChartAccount> $children
 */
class ChartAccount extends Model
{
    use BelongsToTenant;

    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY = 'equity';
    public const TYPE_REVENUE = 'revenue';
    public const TYPE_EXPENSE = 'expense';
    public const TYPE_COGS = 'cogs';
    public const TYPE_CONTRA_REVENUE = 'contra_revenue';
    public const TYPE_CONTRA_ASSET = 'contra_asset';
    public const TYPE_CLEARING = 'clearing';

    public const TYPES = [
        self::TYPE_ASSET, self::TYPE_LIABILITY, self::TYPE_EQUITY,
        self::TYPE_REVENUE, self::TYPE_EXPENSE, self::TYPE_COGS,
        self::TYPE_CONTRA_REVENUE, self::TYPE_CONTRA_ASSET, self::TYPE_CLEARING,
    ];

    public const NB_DEBIT = 'debit';
    public const NB_CREDIT = 'credit';

    protected $fillable = [
        'tenant_id', 'code', 'name', 'type', 'parent_id',
        'normal_balance', 'is_postable', 'is_active', 'vas_template',
        'sort_order', 'description',
    ];

    protected function casts(): array
    {
        return [
            'is_postable' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'parent_id' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('code');
    }

    /**
     * Type được tính như tăng Nợ (asset/expense/cogs/contra_revenue) ⇒ closing = opening + debit - credit.
     * Ngược lại (liability/equity/revenue/contra_asset) ⇒ closing = opening + credit - debit.
     */
    public function isDebitNormal(): bool
    {
        return $this->normal_balance === self::NB_DEBIT;
    }
}
