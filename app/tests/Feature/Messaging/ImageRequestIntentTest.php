<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Integrations\Ai\DTO\IntentDTO;
use CMBcoreSeller\Modules\Messaging\Services\IntentClassifier;
use Tests\TestCase;

class ImageRequestIntentTest extends TestCase
{
    public function test_image_request_is_a_candidate_and_not_escalated(): void
    {
        $this->assertContains('image_request', IntentClassifier::ALL);
        $this->assertNotContains('image_request', IntentClassifier::ESCALATE);

        $classifier = app(IntentClassifier::class);
        $this->assertFalse($classifier->shouldEscalate(new IntentDTO(intent: 'image_request', confidence: 0.9)));
    }

    public function test_image_reply_max_images_config_default(): void
    {
        $this->assertSame(3, (int) config('messaging.ai.image_reply.max_images', 3));
    }
}
