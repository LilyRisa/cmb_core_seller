<?php

namespace CMBcoreSeller\Modules\Billing\Services;

use CMBcoreSeller\Modules\Billing\Models\Plan;
use CMBcoreSeller\Modules\Billing\Models\Subscription;

/**
 * Phát hiện tenant đang vượt hạn mức + quản lý timer ân hạn 2 ngày trước khi khoá.
 * SPEC 0020 §4.
 *
 * - `detectFor($sub)` — chỉ tính toán, KHÔNG ghi DB. Trả `OverQuotaState`.
 * - `apply($sub)` — gọi từ scheduler hằng giờ; set/clear `over_quota_warned_at` theo state hiện tại.
 * - `isLockedFor($sub, $resource)` — middleware dùng để quyết định chặn write.
 *
 * Thiết kế mở rộng: `limitFor($plan, $resource)` switch theo resource; thêm cấp bậc mới
 * chỉ cần thêm case ở đây + add resource vào `config('billing.quota_resources')`.
 */
class OverQuotaCheckService
{
    public function __construct(protected UsageService $usage) {}

    /**
     * Hạn mức cho resource. Trả `null` ⇒ resource không quản lý hạn mức. `-1` ⇒ unlimited.
     */
    public function limitFor(Plan $plan, string $resource): ?int
    {
        return match ($resource) {
            'channel_accounts' => $plan->maxChannelAccounts(),
            default => null,
        };
    }

    /**
     * Kiểm 1 resource cho subscription/tenant. Trả `null` ⇒ không tính (unlimited / không quản lý).
     *
     * @return array{resource:string, used:int, limit:int, over:bool}|null
     */
    public function checkResource(Subscription $sub, string $resource): ?array
    {
        $plan = $sub->plan;
        if ($plan === null) {
            return null;
        }
        $limit = $this->limitFor($plan, $resource);
        if ($limit === null || $limit < 0) {
            return null;
        }
        $used = $this->usage->count((int) $sub->tenant_id, $resource);

        return ['resource' => $resource, 'used' => $used, 'limit' => $limit, 'over' => $used > $limit];
    }

    /**
     * Tenant có vượt mức ở BẤT KỲ resource nào trong `config('billing.quota_resources')`?
     *
     * @return list<array{resource:string, used:int, limit:int, over:bool}>
     */
    public function overResources(Subscription $sub): array
    {
        $resources = (array) config('billing.quota_resources', ['channel_accounts']);
        $over = [];
        foreach ($resources as $resource) {
            $r = $this->checkResource($sub, (string) $resource);
            if ($r !== null && $r['over']) {
                $over[] = $r;
            }
        }

        return $over;
    }

    /**
     * Đồng bộ `over_quota_warned_at` theo state hiện tại (idempotent — gọi mỗi giờ qua scheduler).
     *
     * - Đang vượt + chưa có timer ⇒ set `now()`.
     * - Đang vượt + đã có timer ⇒ giữ nguyên (tránh user reset bằng cách mở-đóng kênh để gia hạn).
     * - Không vượt + đã có timer ⇒ clear.
     */
    public function apply(Subscription $sub): Subscription
    {
        $over = $this->overResources($sub);

        if ($over !== []) {
            if ($sub->over_quota_warned_at === null) {
                $sub->forceFill(['over_quota_warned_at' => now()])->save();
            }
        } else {
            if ($sub->over_quota_warned_at !== null) {
                $sub->forceFill(['over_quota_warned_at' => null])->save();
            }
        }

        return $sub;
    }

    /**
     * Đã quá grace period chưa? (true ⇒ middleware chặn write).
     */
    public function isPastGrace(Subscription $sub, ?\Carbon\CarbonInterface $now = null): bool
    {
        if ($sub->over_quota_warned_at === null) {
            return false;
        }
        $graceHours = (int) config('billing.over_quota_grace_hours', 48);
        if ($graceHours <= 0) {
            return true; // test mode — khoá ngay
        }
        $now ??= now();

        return $sub->over_quota_warned_at->copy()->addHours($graceHours)->lessThanOrEqualTo($now);
    }
}
