<?php

namespace Tests\Unit\Integrations\Ai;

use CMBcoreSeller\Integrations\Ai\Support\VisionModelGate;
use Tests\TestCase;

class VisionModelGateTest extends TestCase
{
    public function test_matches_vision_model_substrings(): void
    {
        config()->set('ai.vision.enabled', true);
        config()->set('ai.vision.models', ['gpt-5', 'gemini', 'claude-opus']);

        $this->assertTrue(VisionModelGate::enabledFor('ts/gpt-5.4-mini'));
        $this->assertTrue(VisionModelGate::enabledFor('ts/gemini-3.5-flash'));
        $this->assertFalse(VisionModelGate::enabledFor('mn/Minimax-M3'));
    }

    public function test_disabled_flag_forces_false(): void
    {
        config()->set('ai.vision.enabled', false);
        config()->set('ai.vision.models', ['gpt-5']);

        $this->assertFalse(VisionModelGate::enabledFor('ts/gpt-5.4-mini'));
    }
}
