<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Services;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\Exceptions\ProviderNotConfigured;
use CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter;
use CMBcoreSeller\Modules\Products\Models\ListingDraft;

/**
 * Gợi ý mô tả sản phẩm bằng AI cho nháp đăng sàn.
 *
 * Tuân luật module: chỉ dùng integration layer ({@see AiAssistantRegistry}) +
 * Contract của Billing ({@see AiCreditMeter}) — KHÔNG chạm Services nội bộ module khác.
 * Khóa theo gói + trừ 1 lượt ví AI (SPEC 0032) như Marketing/Messaging.
 */
final class ProductDescriptionService
{
    public function __construct(
        private AiAssistantRegistry $registry,
        private AiCreditMeter $credits,
    ) {}

    /** @return array{description:string, provider:string} */
    public function suggest(ListingDraft $draft): array
    {
        $tenantId = (int) $draft->tenant_id;

        abort_unless($this->credits->aiEnabled($tenantId), 402, 'Gói hiện tại không có tính năng AI.');
        abort_unless($this->credits->canUse($tenantId, 1), 402, 'Đã hết lượt AI trong kỳ.');

        $code = $this->registry->activeProviders()[0] ?? null;
        if ($code === null) {
            throw new ProviderNotConfigured('Chưa cấu hình nhà cung cấp AI nào đang hoạt động.');
        }

        $reply = $this->registry->for($code)->generateText(
            new AiContext(tenantId: $tenantId, providerCode: $code, maxTokens: 1024, languageHint: 'vi'),
            $this->buildPrompt($draft),
            'Bạn là chuyên gia viết mô tả sản phẩm cho sàn TMĐT (Shopee/TikTok/Lazada) tại Việt Nam. '
                .'Viết tiếng Việt, thuyết phục, có gạch đầu dòng các đặc điểm nổi bật và hướng dẫn ngắn. '
                .'KHÔNG bịa giá, khuyến mãi hay thông số không có. CHỈ trả về nội dung mô tả, không thêm lời dẫn.',
        );

        $this->credits->record($tenantId, 1);

        return ['description' => trim($reply->body), 'provider' => $code];
    }

    private function buildPrompt(ListingDraft $draft): string
    {
        $draft->loadMissing(['product', 'skus']);
        $product = $draft->product;

        $lines = ['Tên sản phẩm: '.(string) ($product->name ?? '')];

        if (! empty($product->category)) {
            $lines[] = 'Ngành hàng (tham khảo): '.(string) $product->category;
        }

        $variants = $draft->skus
            ->map(fn ($s) => implode(' ', array_map('strval', array_values((array) ($s->sale_props ?? [])))))
            ->filter(fn ($v) => trim($v) !== '')
            ->unique()
            ->take(20)
            ->values()
            ->all();
        if ($variants !== []) {
            $lines[] = 'Phân loại/biến thể: '.implode('; ', $variants);
        }

        $current = trim((string) (($draft->attributes ?? [])['description'] ?? ''));
        if ($current !== '') {
            $lines[] = "Mô tả hiện có (cải thiện, viết lại cho hấp dẫn hơn):\n".mb_substr($current, 0, 2000);
        }

        return implode("\n", $lines);
    }
}
