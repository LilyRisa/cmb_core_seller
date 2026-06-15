<?php

declare(strict_types=1);

namespace CMBcoreSeller\Modules\Products\Services;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\Exceptions\ProviderNotConfigured;
use CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter;
use CMBcoreSeller\Modules\Products\Models\ChannelListing;
use CMBcoreSeller\Modules\Products\Models\ListingDraft;

/**
 * Gợi ý mô tả sản phẩm bằng AI — dùng chung cho nháp đăng sàn ({@see suggest}) lẫn
 * sản phẩm đã có trên sàn ({@see suggestForListing}).
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

    /** Gợi ý mô tả cho 1 nháp đăng sàn. @return array{description:string, provider:string} */
    public function suggest(ListingDraft $draft): array
    {
        $draft->loadMissing(['product', 'skus']);
        $product = $draft->product;

        $variants = $draft->skus
            ->map(fn ($s) => implode(' ', array_map('strval', array_values((array) ($s->sale_props ?? [])))))
            ->filter(fn ($v) => trim($v) !== '')
            ->unique()
            ->take(20)
            ->values()
            ->all();

        return $this->generate(
            (int) $draft->tenant_id,
            $this->buildPromptFromParts(
                name: (string) ($product->name ?? ''),
                variants: $variants,
                category: ! empty($product->category) ? (string) $product->category : null,
                currentDescription: trim((string) (($draft->attributes ?? [])['description'] ?? '')) ?: null,
            ),
        );
    }

    /**
     * Gợi ý mô tả cho 1 sản phẩm ĐÃ có trên sàn (ChannelListing). `$currentDescription` là
     * mô tả người dùng đang soạn (để AI cải thiện) — truyền từ trang sửa.
     *
     * @return array{description:string, provider:string}
     */
    public function suggestForListing(ChannelListing $listing, ?string $currentDescription = null): array
    {
        $variant = trim((string) ($listing->variation ?? ''));

        return $this->generate(
            (int) $listing->tenant_id,
            $this->buildPromptFromParts(
                name: (string) ($listing->title ?? ''),
                variants: $variant !== '' ? [$variant] : [],
                category: null,
                currentDescription: ($currentDescription !== null && trim($currentDescription) !== '') ? trim($currentDescription) : null,
            ),
        );
    }

    /** Chạy AI sinh mô tả: khóa gói + trừ 1 lượt ví. @return array{description:string, provider:string} */
    private function generate(int $tenantId, string $prompt): array
    {
        abort_unless($this->credits->aiEnabled($tenantId), 402, 'Gói hiện tại không có tính năng AI.');
        abort_unless($this->credits->canUse($tenantId, 1), 402, 'Đã hết lượt AI trong kỳ.');

        $code = $this->registry->activeProviders()[0] ?? null;
        if ($code === null) {
            throw new ProviderNotConfigured('Chưa cấu hình nhà cung cấp AI nào đang hoạt động.');
        }

        $reply = $this->registry->for($code)->generateText(
            new AiContext(tenantId: $tenantId, providerCode: $code, maxTokens: 1024, languageHint: 'vi'),
            $prompt,
            'Bạn là chuyên gia viết mô tả sản phẩm cho sàn TMĐT (Shopee/TikTok/Lazada) tại Việt Nam. '
                .'Viết tiếng Việt, thuyết phục, có gạch đầu dòng các đặc điểm nổi bật và hướng dẫn ngắn. '
                .'KHÔNG bịa giá, khuyến mãi hay thông số không có. CHỈ trả về nội dung mô tả, không thêm lời dẫn.',
        );

        $this->credits->record($tenantId, 1);

        return ['description' => trim($reply->body), 'provider' => $code];
    }

    /**
     * Dựng prompt từ các phần chung (tên / biến thể / ngành hàng / mô tả hiện có).
     *
     * @param  list<string>  $variants
     */
    private function buildPromptFromParts(string $name, array $variants, ?string $category, ?string $currentDescription): string
    {
        $lines = ['Tên sản phẩm: '.$name];

        if ($category !== null && $category !== '') {
            $lines[] = 'Ngành hàng (tham khảo): '.$category;
        }

        if ($variants !== []) {
            $lines[] = 'Phân loại/biến thể: '.implode('; ', $variants);
        }

        if ($currentDescription !== null && $currentDescription !== '') {
            $lines[] = "Mô tả hiện có (cải thiện, viết lại cho hấp dẫn hơn):\n".mb_substr($currentDescription, 0, 2000);
        }

        return implode("\n", $lines);
    }
}
