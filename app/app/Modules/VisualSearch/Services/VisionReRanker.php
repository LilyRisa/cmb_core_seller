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

    /** Vision KHÔNG chắc (SP nhìn giống nhau / không đọc được mã in) ⇒ matcher trả ambiguous để AI hỏi lại. */
    public const AMBIGUOUS = -2;

    public function __construct(
        private AiAssistantRegistry $registry,
        private AiCreditMeter $credits,
    ) {}

    /**
     * @param  list<array{candidate:VisualItemCandidate, image:?string}>  $candidates
     * @return int itemId được chọn (>0) | self::NONE (0) | self::NOT_RUN (-1) | self::AMBIGUOUS (-2)
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

        // Nhiều SP cùng dòng nhìn GIỐNG HỆT nhau, chỉ khác MÃ/SỐ in trên sản phẩm (vd D800 vs D900) ⇒
        // yêu cầu vision ĐỌC mã/tên in trên SP rồi khớp theo mã, và tự báo KHÔNG CHẮC khi không đọc
        // được mã / nhiều ứng viên giống nhau ⇒ matcher trả ambiguous để AI HỎI LẠI (không chốt bừa).
        $instruction = "Ảnh ĐẦU TIÊN là ảnh KHÁCH gửi. Các ảnh tiếp theo là ứng viên theo thứ tự:\n"
            .implode("\n", $lines)
            ."\nNhiều sản phẩm có thể TRÔNG GIỐNG NHAU, chỉ khác MÃ/SỐ/CHỮ in trên thân sản phẩm (vd D800 vs D900). "
            .'Hãy ĐỌC kỹ mã/chữ in trên sản phẩm trong ảnh khách rồi khớp với ứng viên có mã/tên trùng — '
            .'TUYỆT ĐỐI không đoán theo hình dạng chung. Trả về DUY NHẤT JSON: '
            ."{\"match\": <số thứ tự 1..{$idx}, hoặc 0 nếu không ứng viên nào khớp>, "
            .'"sure": <true nếu ĐỌC RÕ mã/tên và khớp chắc chắn; false nếu KHÔNG đọc được mã hoặc nhiều ứng viên giống nhau không phân biệt được>}.';

        try {
            $out = $connector->analyzeImages($ctx, $images, $instruction);
        } catch (\Throwable $e) {
            Log::warning('visual_search.rerank_failed', ['error' => $e->getMessage()]);

            return self::NOT_RUN;
        }

        $this->credits->record($tenantId, 1, 'visual');

        $parsed = $this->parsePick($out);
        if ($parsed === null) {
            return self::NOT_RUN;
        }
        [$match, $sure] = $parsed;
        // Không chắc (SP giống nhau / không đọc được mã) ⇒ ambiguous ⇒ AI hỏi lại thay vì gửi nhầm SP.
        if (! $sure && $match !== 0) {
            return self::AMBIGUOUS;
        }
        if ($match === 0) {
            return $sure ? self::NONE : self::AMBIGUOUS;
        }

        return $indexToItem[$match] ?? self::NONE;
    }

    /**
     * Parse `{"match":N,"sure":bool}`. `sure` mặc định TRUE khi vắng (tương thích ngược + model cũ).
     *
     * @return array{0:int,1:bool}|null [match, sure]
     */
    private function parsePick(string $out): ?array
    {
        if (preg_match('/"match"\s*:\s*(\d+)/', $out, $m) === 1) {
            $match = (int) $m[1];
        } elseif (preg_match('/\d+/', $out, $m) === 1) {
            $match = (int) $m[0];
        } else {
            return null;
        }

        // Chỉ coi là KHÔNG chắc khi model nói rõ "sure": false.
        $sure = preg_match('/"sure"\s*:\s*false/i', $out) !== 1;

        return [$match, $sure];
    }
}
