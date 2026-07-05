<?php

namespace CMBcoreSeller\Modules\VisualSearch\Services;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualImageInput;
use CMBcoreSeller\Modules\VisualSearch\DTO\VisualItemCandidate;
use Illuminate\Support\Facades\Log;

/**
 * Precision: cho vision LLM xem ảnh khách + ảnh đại diện các ứng viên → chọn item khớp.
 * Tốn 1 credit/lượt (ghi nhận SAU khi provider thành công). Tách biệt — lỗi/hết credit
 * ⇒ trả NOT_RUN để matcher fallback recall (không phá luồng).
 */
class VisionReRanker
{
    /** Không chạy được (hết credit / provider không hỗ trợ / lỗi) ⇒ matcher fallback recall. */
    public const NOT_RUN = -1;

    /** Vision khẳng định KHÔNG ứng viên nào khớp. */
    public const NONE = 0;

    public function __construct(
        private AiAssistantRegistry $registry,
        private AiCreditMeter $credits,
    ) {}

    /**
     * @param  list<array{candidate:VisualItemCandidate, image:?string}>  $candidates
     * @return int itemId được chọn (>0) | self::NONE (0) | self::NOT_RUN (-1)
     */
    public function pick(int $tenantId, string $providerCode, AiContext $ctx, VisualImageInput $customer, array $candidates): int
    {
        if ($candidates === [] || ! $this->credits->canUse($tenantId, 1)) {
            return self::NOT_RUN;
        }

        // Provider AI RIÊNG cho re-rank (SPEC 2026-07-05). Rỗng/không active ⇒ giữ provider chat.
        $override = trim((string) system_setting('visual_search.rerank.provider_code', ''));
        if ($override !== '' && $override !== $providerCode && in_array($override, $this->registry->activeProviders('vision'), true)) {
            $providerCode = $override;
            $ctx = new AiContext(
                tenantId: $ctx->tenantId,
                providerCode: $override,
                model: null, // null ⇒ connector dùng default_model của provider re-rank
                meta: ['mode' => 'visual_rerank'],
            );
        }

        try {
            $connector = $this->registry->for($providerCode);
        } catch (\Throwable) {
            return self::NOT_RUN;
        }
        if (! $connector->supports('vision.analyze')) {
            return self::NOT_RUN;
        }

        $images = [$customer->toDataUrl()];
        $lines = [];
        $indexToItem = [];
        $idx = 0;
        foreach ($candidates as $c) {
            if (! is_string($c['image']) || $c['image'] === '') {
                continue;
            }
            $idx++;
            $images[] = $c['image'];
            $indexToItem[$idx] = $c['candidate']->itemId;
            $desc = $c['candidate']->description ? ' — '.mb_substr($c['candidate']->description, 0, 120) : '';
            $lines[] = "#{$idx}: {$c['candidate']->name}{$desc}";
        }
        if ($idx === 0) {
            return self::NOT_RUN;
        }

        $instruction = "Ảnh ĐẦU TIÊN là ảnh KHÁCH gửi. Các ảnh tiếp theo là ứng viên theo thứ tự:\n"
            .implode("\n", $lines)
            ."\nChọn ứng viên KHỚP NHẤT với sản phẩm trong ảnh khách. Trả về DUY NHẤT JSON: "
            ."{\"match\": <số thứ tự 1..{$idx}, hoặc 0 nếu không ứng viên nào khớp>}.";

        try {
            $out = $connector->analyzeImages($ctx, $images, $instruction);
        } catch (\Throwable $e) {
            Log::warning('visual_search.rerank_failed', ['error' => $e->getMessage()]);

            return self::NOT_RUN;
        }

        $this->credits->record($tenantId, 1, 'visual');

        $pick = $this->parsePick($out);
        if ($pick === null) {
            return self::NOT_RUN;
        }
        if ($pick === 0) {
            return self::NONE;
        }

        return $indexToItem[$pick] ?? self::NONE;
    }

    private function parsePick(string $out): ?int
    {
        if (preg_match('/"match"\s*:\s*(\d+)/', $out, $m) === 1) {
            return (int) $m[1];
        }
        if (preg_match('/\d+/', $out, $m) === 1) {
            return (int) $m[0];
        }

        return null;
    }
}
