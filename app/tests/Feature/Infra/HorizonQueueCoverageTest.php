<?php

namespace Tests\Feature\Infra;

use CMBcoreSeller\Modules\Messaging\Listeners\PushWebOnNewMessage;
use Tests\TestCase;

class HorizonQueueCoverageTest extends TestCase
{
    public function test_push_web_listener_queue_is_consumed_by_a_supervisor(): void
    {
        $listenerQueue = (new \ReflectionClass(PushWebOnNewMessage::class))
            ->getDefaultProperties()['queue'] ?? null;
        $this->assertSame('messaging-bg', $listenerQueue, 'guard: listener queue name changed');

        $consumed = collect(config('horizon.defaults'))
            ->flatMap(fn ($sup) => (array) ($sup['queue'] ?? []))
            ->unique()
            ->all();

        $this->assertContains(
            $listenerQueue,
            $consumed,
            "Queue [{$listenerQueue}] is not consumed by any Horizon supervisor — its jobs would pile up.",
        );
    }
}
