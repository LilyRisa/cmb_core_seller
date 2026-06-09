<?php

namespace CMBcoreSeller\Integrations\Ads\Facebook;

/**
 * Biểu diễn chuẩn TOÀN BỘ một chiến dịch Facebook (cây campaign → ad sets → ads) ở tầng
 * Integrations. Dùng cho cả wizard, AI và publish: {@see validate} chặn tổ hợp FB sẽ reject
 * (placement chết, thiếu page_id/pixel, ngân sách sai chỗ), {@see sanitize} gỡ vị trí không
 * hợp lệ. KHÔNG phụ thuộc Modules — caller normalize shape cây trước khi truyền vào.
 *
 * Shape `payload`: { campaign:{budget_mode, daily_budget_major?}, adsets:[{name, budget,
 * targeting, placement_config, schedule, conversion?, ads:[{name, creative}]}] }
 */
final class FacebookCampaignBlueprint
{
    /** @param array<string,mixed> $payload */
    private function __construct(
        private readonly string $objective,
        private readonly array $payload,
    ) {}

    /** @param array<string,mixed> $payload đã ở shape cây (có khoá `adsets`) */
    public static function fromArray(array $payload, string $objective): self
    {
        return new self($objective, $payload);
    }

    public function objective(): string
    {
        return $this->objective;
    }

    /**
     * Trả danh sách lỗi (tiếng Việt). Rỗng = hợp lệ để gửi FB.
     *
     * @return list<string>
     */
    public function validate(): array
    {
        $errors = [];

        if (! in_array($this->objective, FacebookAdsCatalog::objectives(), true)) {
            $errors[] = "Mục tiêu không hợp lệ: {$this->objective}.";
        }

        /** @var list<array<string,mixed>> $adsets */
        $adsets = array_values(array_filter((array) ($this->payload['adsets'] ?? []), 'is_array'));
        if ($adsets === []) {
            $errors[] = 'Chiến dịch phải có ít nhất 1 nhóm quảng cáo.';

            return $errors;
        }

        $cbo = (($this->payload['campaign']['budget_mode'] ?? 'adset') === 'campaign');
        if ($cbo && (int) ($this->payload['campaign']['daily_budget_major'] ?? 0) <= 0) {
            $errors[] = 'Ngân sách chiến dịch (CBO) phải lớn hơn 0.';
        }

        foreach ($adsets as $i => $as) {
            $n = $i + 1;
            $ads = array_values(array_filter((array) ($as['ads'] ?? []), 'is_array'));
            if ($ads === []) {
                $errors[] = "Nhóm {$n}: phải có ít nhất 1 quảng cáo.";
            }
            if (! $cbo && (int) (($as['budget']['daily_major'] ?? 0)) <= 0) {
                $errors[] = "Nhóm {$n}: ngân sách/ngày phải lớn hơn 0.";
            }
            if (empty(((array) ($as['targeting'] ?? []))['geo_locations'])) {
                $errors[] = "Nhóm {$n}: cần nhắm vị trí địa lý (geo_locations).";
            }

            $firstCreative = (array) ($ads[0]['creative'] ?? []);
            if (in_array($this->objective, ['messages', 'engagement'], true) && empty($firstCreative['page_id'])) {
                $errors[] = "Nhóm {$n}: thiếu page_id cho mục tiêu {$this->objective}.";
            }
            if ($this->objective === 'conversions') {
                $conv = (array) ($as['conversion'] ?? []);
                if (empty($conv['pixel_id']) || empty($conv['custom_event_type'])) {
                    $errors[] = "Nhóm {$n}: mục tiêu chuyển đổi cần pixel_id + custom_event_type (vd COMPLETE_REGISTRATION).";
                }
                if (empty($firstCreative['link_url']) && empty($firstCreative['page_post_id'])) {
                    $errors[] = "Nhóm {$n}: mục tiêu chuyển đổi cần link đích (landing) hoặc bài viết đã có link.";
                }
            }
        }

        return $errors;
    }

    /** Gỡ vị trí không hợp lệ (khai tử + desktop-only khi chỉ mobile) khỏi mọi nhóm. */
    public function sanitize(): self
    {
        $payload = $this->payload;
        $adsets = (array) ($payload['adsets'] ?? []);
        foreach ($adsets as $i => $as) {
            if (! is_array($as)) {
                continue;
            }
            $pc = (array) ($as['placement_config'] ?? []);
            $fb = $pc['positions']['facebook'] ?? null;
            if (is_array($fb)) {
                $devices = array_values(array_filter((array) ($pc['device_platforms'] ?? []), 'is_string'));
                $payload['adsets'][$i]['placement_config']['positions']['facebook'] =
                    FacebookAdsCatalog::sanitizePlacements('facebook', array_values(array_filter($fb, 'is_string')), $devices);
            }
        }

        return new self($this->objective, $payload);
    }

    /** @return array<string,mixed> payload chuẩn cho AdDraftSpecMapper. */
    public function toPayload(): array
    {
        return $this->payload;
    }
}
