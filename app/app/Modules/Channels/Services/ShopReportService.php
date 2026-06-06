<?php

namespace CMBcoreSeller\Modules\Channels\Services;

use CMBcoreSeller\Integrations\Channels\ChannelRegistry;
use CMBcoreSeller\Integrations\Channels\Contracts\ShopReportConnector;
use CMBcoreSeller\Integrations\Channels\DTO\PenaltyPointDTO;
use CMBcoreSeller\Integrations\Channels\DTO\PunishmentDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ShopHealthDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ShopMetricDTO;
use CMBcoreSeller\Integrations\Channels\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Channels\Models\ShopPenaltyEvent;
use Throwable;

/**
 * "Báo cáo sàn" — gom sức khỏe/hiệu suất/điểm phạt của các gian hàng đã kết nối, read-only.
 * Mỗi sàn lộ dữ liệu khác nhau (xem ShopReportConnector); lỗi/thiếu-quyền được xử lý PER-SHOP
 * (available=false + note/error), không làm hỏng cả báo cáo. SPEC 2026-06-06-shop-report-multi-channel.
 */
class ShopReportService
{
    /** Sàn có khả năng báo cáo (TikTok/Lazada/Shopee). Facebook Page... không tính. */
    private const PROVIDERS = ['lazada', 'shopee', 'tiktok'];

    public function __construct(private readonly ChannelRegistry $registry) {}

    /** @return list<array<string,mixed>> một phần tử / gian hàng */
    public function forTenant(int $tenantId): array
    {
        $accounts = ChannelAccount::query()
            ->where('tenant_id', $tenantId)
            ->active()
            ->whereIn('provider', self::PROVIDERS)
            ->orderBy('id')
            ->get();

        return $accounts->map(fn (ChannelAccount $a) => $this->reportFor($a))->all();
    }

    /** Báo cáo cho 1 gian hàng (theo id, scoped tenant hiện tại) — dùng cho phân tích AI. */
    public function reportForAccountId(int $channelAccountId): ?array
    {
        $account = ChannelAccount::query()
            ->whereKey($channelAccountId)
            ->active()
            ->whereIn('provider', self::PROVIDERS)
            ->first();

        return $account ? $this->reportFor($account) : null;
    }

    /** @return array<string,mixed> */
    private function reportFor(ChannelAccount $account): array
    {
        $out = [
            'channel_account_id' => (int) $account->getKey(),
            'provider' => $account->provider,
            'shop_name' => $account->display_name ?: ($account->shop_name ?: $account->external_shop_id),
            'available' => false,
            'kind' => null,
            'overall_rating' => null,
            'overall_label' => null,
            'metrics' => [],
            'penalties' => [],
            'punishments' => [],
            'supports_penalty' => false,
            'recent_penalty_events' => $this->recentPenaltyEvents($account),
            'note' => null,
            'error' => null,
        ];

        if (! $this->registry->has($account->provider)) {
            return $out;
        }

        // Bọc TOÀN BỘ phần resolve connector + authContext() (giải mã token) + gọi API trong try —
        // một gian hàng lỗi (token hỏng/thiếu quyền) chỉ làm CHÍNH nó available=false, KHÔNG 500 cả báo cáo.
        try {
            $connector = $this->registry->for($account->provider);
            if (! $connector instanceof ShopReportConnector || ! $connector->supports('report.health')) {
                $out['note'] = 'Sàn này chưa hỗ trợ báo cáo.';

                return $out;
            }
            $auth = $account->authContext();
            $health = $connector->fetchShopHealth($auth);
            $out['available'] = true;
            $out['kind'] = $health->kind;
            $out['overall_rating'] = $health->overallRating;
            $out['overall_label'] = $health->overallLabel;
            $out['metrics'] = array_map([$this, 'metricArray'], $health->metrics);
            $out += $this->summary($health);
        } catch (Throwable $e) {
            $out['error'] = $this->friendlyError($account->provider, $e);

            return $out;
        }

        if ($connector->supports('report.penalty')) {
            $out['supports_penalty'] = true;
            try {
                $out['penalties'] = array_map([$this, 'penaltyArray'], $connector->fetchPenaltyPoints($auth));
                $out['punishments'] = array_map([$this, 'punishmentArray'], $connector->fetchPunishments($auth));
            } catch (UnsupportedOperation) {
                $out['supports_penalty'] = false;
            } catch (Throwable $e) {
                $out['penalty_error'] = $this->friendlyError($account->provider, $e);
            }
        }

        return $out;
    }

    /** @return array{passed_count:int,failed_count:int,total_metrics:int} */
    private function summary(ShopHealthDTO $health): array
    {
        $passed = 0;
        $failed = 0;
        foreach ($health->metrics as $m) {
            if ($m->passed === true) {
                $passed++;
            } elseif ($m->passed === false) {
                $failed++;
            }
        }

        return ['passed_count' => $passed, 'failed_count' => $failed, 'total_metrics' => count($health->metrics)];
    }

    /** @return array<string,mixed> */
    private function metricArray(ShopMetricDTO $m): array
    {
        return [
            'key' => $m->key,
            'name' => $m->name,
            'group' => $m->group,
            'value' => $m->value,
            'unit' => $m->unit,
            'target' => $m->target,
            'comparator' => $m->comparator,
            'passed' => $m->passed,
        ];
    }

    /** @return array<string,mixed> */
    private function penaltyArray(PenaltyPointDTO $p): array
    {
        return [
            'points' => $p->points,
            'violation_type' => $p->violationType,
            'violation_label' => $p->violationLabel,
            'issued_at' => $p->issuedAt?->toIso8601String(),
            'reference_id' => $p->referenceId,
        ];
    }

    /** @return array<string,mixed> */
    private function punishmentArray(PunishmentDTO $p): array
    {
        return [
            'type' => $p->type,
            'type_label' => $p->typeLabel,
            'tier' => $p->tier,
            'start_at' => $p->startAt?->toIso8601String(),
            'end_at' => $p->endAt?->toIso8601String(),
            'ongoing' => $p->ongoing,
        ];
    }

    /**
     * Cảnh báo điểm phạt/vi phạm gần đây nhận qua webhook (real-time) — 10 sự kiện mới nhất.
     *
     * @return list<array<string,mixed>>
     */
    private function recentPenaltyEvents(ChannelAccount $account): array
    {
        return ShopPenaltyEvent::query()
            ->where('channel_account_id', $account->getKey())
            ->orderByDesc('occurred_at')->orderByDesc('id')
            ->limit(10)
            ->get(['kind', 'points', 'violation_label', 'tier', 'item_name', 'occurred_at'])
            ->map(fn (ShopPenaltyEvent $e) => [
                'kind' => $e->kind,
                'points' => $e->points,
                'violation_label' => $e->violation_label,
                'tier' => $e->tier,
                'item_name' => $e->item_name,
                'occurred_at' => $e->occurred_at?->toIso8601String(),
            ])->all();
    }

    private function friendlyError(string $provider, Throwable $e): string
    {
        $msg = strtolower($e->getMessage());
        if ($provider === 'shopee' && (str_contains($msg, 'permission') || str_contains($msg, 'api_permission'))) {
            return 'Chưa được Shopee cấp quyền Account Health (module 103). Liên hệ Shopee Partner để bật.';
        }
        if (str_contains($msg, 'token') || str_contains($msg, 'expired') || str_contains($msg, 'auth')) {
            return 'Token gian hàng đã hết hạn — hãy kết nối lại ở mục Kênh bán.';
        }

        return 'Không lấy được dữ liệu từ sàn lúc này. Vui lòng thử lại sau.';
    }
}
