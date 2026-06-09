<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Messaging\Services\IntentClassifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Giám sát lỗi âm thầm: mỗi lần classify rơi vào AN TOÀN MẶC ĐỊNH (escalate "urgent"
 * vì lỗi/circuit mở/không hỗ trợ) phải GHI LOG để không còn vô hình.
 */
class IntentClassifierFallbackLogTest extends TestCase
{
    public function test_classify_logs_warning_when_circuit_open(): void
    {
        Cache::put('ai:intent:fail:prov_x', 5); // >= FAIL_THRESHOLD ⇒ mạch MỞ
        Log::spy();

        $result = app(IntentClassifier::class)->classify(1, 'prov_x', 'xin chao shop');

        $this->assertSame('urgent', $result->intent);
        $this->assertSame(0.0, $result->confidence);
        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []) => $message === 'messaging.intent.classify_failed')
            ->atLeast()->once();
    }
}
