<?php

namespace CMBcoreSeller\Modules\Messaging\Services;

use CMBcoreSeller\Integrations\Ai\AiAssistantRegistry;
use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\DTO\IntentDTO;
use CMBcoreSeller\Integrations\Ai\Exceptions\ProviderNotConfigured;
use CMBcoreSeller\Modules\Billing\Contracts\AiCreditMeter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Guardrail cho auto-mode (SPEC-0024 §4.6): phân loại intent tin nhắn để quyết
 * có cho AI TỰ GỬI hay phải escalate cho NV.
 *
 * Intent escalate (KHÔNG auto-send — phải có người): complaint, refund, urgent,
 * legal_threat, abuse. Còn lại (order_status, price, smalltalk, other) → cho auto-send.
 *
 * AN TOÀN MẶC ĐỊNH: classify lỗi / provider không hỗ trợ ⇒ coi như ESCALATE
 * (thà để NV xử lý còn hơn AI tự trả nhầm tin nhạy cảm).
 */
class IntentClassifier
{
    /** @var list<string> */
    public const ESCALATE = ['complaint', 'refund', 'urgent', 'legal_threat', 'abuse'];

    /** @var list<string> */
    public const ALL = ['order_status', 'price', 'complaint', 'refund', 'urgent', 'legal_threat', 'abuse', 'smalltalk', 'other'];

    /** Ngưỡng lỗi liên tiếp để MỞ mạch (ngừng gọi provider chết). */
    private const FAIL_THRESHOLD = 5;

    public function __construct(private AiAssistantRegistry $registry, private AiCreditMeter $credits) {}

    public function classify(int $tenantId, string $providerCode, string $text): IntentDTO
    {
        $failKey = "ai:intent:fail:{$providerCode}";

        // Circuit MỞ: bỏ qua gọi provider, escalate an toàn (tránh hammer provider chết).
        if ((int) Cache::get($failKey, 0) >= self::FAIL_THRESHOLD) {
            return $this->escalateOnFailure($providerCode, 'circuit_open');
        }

        try {
            $connector = $this->registry->for($providerCode);
            if (! $connector->supports('intent.classify')) {
                return $this->escalateOnFailure($providerCode, 'unsupported');
            }

            $result = $connector->classifyIntent(new AiContext($tenantId, $providerCode), $text, self::ALL);
            Cache::forget($failKey); // thành công → reset bộ đếm
            // 1 request provider trả thành công = 1 lượt AI (SPEC 0032).
            $this->credits->record($tenantId, 1, 'intent');

            return $result;
        } catch (ProviderNotConfigured $e) {
            // Lỗi cấu hình: escalate, KHÔNG đếm (không phải provider chết).
            return $this->escalateOnFailure($providerCode, 'provider_not_configured', $e);
        } catch (\Throwable $e) {
            // Lỗi tạm (timeout/5xx): đếm; mở mạch 2 phút khi đạt ngưỡng.
            $count = (int) Cache::get($failKey, 0) + 1;
            Cache::put($failKey, $count, now()->addMinutes(2));

            return $this->escalateOnFailure($providerCode, 'exception', $e);
        }
    }

    /**
     * AN TOÀN MẶC ĐỊNH: escalate "urgent" khi không phân loại được — và GHI LOG để lỗi
     * provider không còn vô hình (escalate ngầm trông giống khách khẩn cấp thật). confidence=0.0
     * là dấu hiệu fallback (phân loại thật luôn > 0).
     */
    private function escalateOnFailure(string $providerCode, string $reason, ?\Throwable $e = null): IntentDTO
    {
        Log::warning('messaging.intent.classify_failed', [
            'provider' => $providerCode,
            'reason' => $reason,
            'error' => $e?->getMessage(),
        ]);

        return new IntentDTO(intent: 'urgent', confidence: 0.0);
    }

    public function shouldEscalate(IntentDTO $intent): bool
    {
        return in_array($intent->intent, self::ESCALATE, true);
    }
}
