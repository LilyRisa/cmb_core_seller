<?php

namespace CMBcoreSeller\Integrations\Ai\Support;

/**
 * Nguồn sự thật DUY NHẤT: model có khả năng vision (theo `config('ai.vision')`)?
 * Connector (Claude/OpenAI) và admin badge cùng gọi hàm này ⇒ badge luôn khớp
 * đúng điều kiện connector thực dùng để đính ảnh. So khớp substring (lowercase).
 */
class VisionModelGate
{
    public static function enabledFor(string $model): bool
    {
        if (! (bool) config('ai.vision.enabled', true)) {
            return false;
        }
        $m = strtolower($model);
        foreach ((array) config('ai.vision.models', []) as $needle) {
            $n = strtolower(trim((string) $needle));
            if ($n !== '' && str_contains($m, $n)) {
                return true;
            }
        }

        return false;
    }
}
