<?php

namespace Tests\Unit\Integrations\Ai;

use CMBcoreSeller\Integrations\Ai\DTO\AiContext;
use CMBcoreSeller\Integrations\Ai\Exceptions\UnsupportedOperation;
use CMBcoreSeller\Integrations\Ai\Manual\ManualAiAssistantConnector;
use Tests\TestCase;

class VisionAnalyzeTest extends TestCase
{
    public function test_manual_connector_does_not_support_vision_analyze(): void
    {
        $c = new ManualAiAssistantConnector('manual');
        $this->assertFalse($c->supports('vision.analyze'));
        $this->expectException(UnsupportedOperation::class);
        $c->analyzeImages(new AiContext(tenantId: 1, providerCode: 'manual'), ['data:image/png;base64,AAAA'], 'pick');
    }
}
