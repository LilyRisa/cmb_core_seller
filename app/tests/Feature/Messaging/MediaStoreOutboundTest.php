<?php

namespace Tests\Feature\Messaging;

use CMBcoreSeller\Modules\Messaging\Services\MediaStorage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaStoreOutboundTest extends TestCase
{
    public function test_stores_bytes_on_messaging_disk_and_returns_path(): void
    {
        Storage::fake(config('messaging.media_disk', 'local'));
        $path = app(MediaStorage::class)->storeOutboundBytes(7, 9, 'IMGDATA', 'jpg');

        $this->assertStringContainsString('tenants/7/messaging/', $path);
        Storage::disk(config('messaging.media_disk', 'local'))->assertExists($path);
        $this->assertSame('IMGDATA', Storage::disk(config('messaging.media_disk', 'local'))->get($path));
    }
}
