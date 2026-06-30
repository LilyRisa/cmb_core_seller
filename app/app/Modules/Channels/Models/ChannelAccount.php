<?php

namespace CMBcoreSeller\Modules\Channels\Models;

use CMBcoreSeller\Integrations\Channels\DTO\AuthContext;
use CMBcoreSeller\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A connected shop of a tenant on a marketplace. Tokens are encrypted at rest.
 * See docs/00-overview/glossary.md ("Channel Account").
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $provider
 * @property string $external_shop_id
 * @property string|null $shop_name
 * @property string|null $display_name
 * @property string|null $shop_region
 * @property string|null $seller_type
 * @property string $status
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property Carbon|null $token_expires_at
 * @property Carbon|null $refresh_token_expires_at
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $last_webhook_at
 * @property bool $messaging_enabled
 * @property bool $auto_rts_after_print
 * @property array|null $meta
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class ChannelAccount extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';     // token refresh failed — needs reconnect

    public const STATUS_REVOKED = 'revoked';     // seller deauthorized / disconnected (history kept)

    public const STATUS_DISABLED = 'disabled';   // paused by the user

    /**
     * Provider chỉ dùng để NHẮN TIN, KHÔNG phải gian hàng sàn TMĐT — loại khỏi
     * danh sách "Gian hàng" và khỏi đếm hạn mức gói (chỉ đếm sàn đã kết nối).
     * `facebook_page` (Messenger), `lazada_im` (Lazada IM ERP app riêng).
     */
    public const MESSAGING_ONLY_PROVIDERS = ['facebook_page', 'lazada_im', 'zalo_oa'];

    protected $fillable = [
        'tenant_id', 'provider', 'external_shop_id', 'shop_name', 'display_name', 'shop_region', 'seller_type',
        'status', 'access_token', 'refresh_token', 'token_expires_at', 'refresh_token_expires_at',
        'last_synced_at', 'last_webhook_at', 'meta', 'created_by',
        // SPEC-0024: bật messaging per-shop (cột do migration Messaging thêm).
        'messaging_enabled',
        'auto_rts_after_print',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    /** What the UI should show — the seller's alias if set, else the marketplace shop name. */
    public function effectiveName(): string
    {
        return $this->display_name ?: ($this->shop_name ?: $this->external_shop_id);
    }

    /**
     * Có khớp mã xác nhận "gõ đúng tên gian hàng" để gỡ kết nối không?
     *
     * Tên shop của sàn có thể lưu ở dạng Unicode NFD (tổ hợp dấu) trong khi chuỗi người dùng paste/gõ là
     * NFC ⇒ so sánh theo byte trượt dù NHÌN GIỐNG HỆT (bug "copy y nguyên tên shop vẫn không gỡ được").
     * Chuẩn hoá cả hai vế về NFC + bỏ khoảng trắng thừa + không phân biệt hoa/thường trước khi so.
     */
    public function matchesNameConfirmation(string $input): bool
    {
        return self::normalizeForConfirm($input) === self::normalizeForConfirm($this->effectiveName());
    }

    private static function normalizeForConfirm(string $s): string
    {
        $s = \Normalizer::normalize($s, \Normalizer::FORM_C) ?: $s; // NFC; polyfill symfony/polyfill-intl-normalizer
        $s = str_replace("\u{00A0}", ' ', $s);                      // NBSP → space
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;                 // gom khoảng trắng thừa

        return mb_strtolower(trim($s));
    }

    /** Mã messaging connector ứng với provider của gian hàng (ADR-0019), hoặc null nếu không hỗ trợ. */
    public function messagingConnectorCode(): ?string
    {
        return match ($this->provider) {
            // Lazada IM dùng app "IM ERP" RIÊNG (provider `lazada_im`, OAuth + token riêng) —
            // KHÔNG dùng chung token orders (`lazada`) vì Lazada gate quyền IM theo app
            // (xem docs/superpowers/specs/2026-06-04-lazada-im-chat-separate-app-design.md).
            'lazada_im' => 'lazada_chat',
            'tiktok' => 'tiktok_chat',
            'shopee' => 'shopee_chat',
            'facebook_page' => 'facebook_page',
            'zalo_oa' => 'zalo_oa',
            default => null,
        };
    }

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'refresh_token_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'last_webhook_at' => 'datetime',
            'meta' => 'array',
            'messaging_enabled' => 'boolean',
            'auto_rts_after_print' => 'boolean',
        ];
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_ACTIVE);
    }

    /** Chỉ gian hàng sàn TMĐT (loại kênh nhắn tin facebook_page/lazada_im). */
    public function scopeMarketplace(Builder $q): Builder
    {
        return $q->whereNotIn('provider', self::MESSAGING_ONLY_PROVIDERS);
    }

    public function scopeForProvider(Builder $q, string $provider): Builder
    {
        return $q->where('provider', $provider);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /** Everything a connector needs to make an authenticated call for this shop. */
    public function authContext(): AuthContext
    {
        return new AuthContext(
            channelAccountId: (int) $this->getKey(),
            provider: $this->provider,
            externalShopId: $this->external_shop_id,
            accessToken: (string) $this->access_token,
            region: $this->shop_region ?: 'VN',
            extra: array_filter([
                'shop_cipher' => $this->meta['shop_cipher'] ?? null,
                'open_id' => $this->meta['open_id'] ?? null,
            ], fn ($v) => $v !== null),
        );
    }

    public function tokenExpiresWithin(\DateInterval|int $window): bool
    {
        if (! $this->token_expires_at) {
            return false;
        }
        $threshold = is_int($window) ? now()->addSeconds($window) : now()->add($window);

        return $this->token_expires_at->lte($threshold);
    }
}
