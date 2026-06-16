<?php

namespace Tests\Unit\Integrations\Embedding;

use CMBcoreSeller\Integrations\Embedding\Image\Clip\ClipEmbedder;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClipEmbedderTest extends TestCase
{
    public function test_embed_posts_base64_and_returns_vector(): void
    {
        Http::fake([
            '*/embed' => Http::response(['vector' => [0.1, 0.2, 0.3], 'dim' => 3, 'model' => 'clip_vit_b32'], 200),
        ]);

        $e = new ClipEmbedder(['url' => 'http://clip:8000', 'model' => 'clip_vit_b32', 'dim' => 3, 'timeout' => 5]);
        $dto = $e->embedImage('RAWBYTES', 'image/jpeg');

        $this->assertSame([0.1, 0.2, 0.3], $dto->vector);
        $this->assertSame('clip_vit_b32', $dto->model);
        Http::assertSent(fn ($req) => $req->url() === 'http://clip:8000/embed'
            && $req->data()['image_base64'] === base64_encode('RAWBYTES'));
    }

    public function test_embed_throws_on_http_error(): void
    {
        Http::fake(['*' => Http::response('err', 500)]);
        $e = new ClipEmbedder(['url' => 'http://clip:8000', 'model' => 'm', 'dim' => 3, 'timeout' => 5]);
        $this->expectException(\RuntimeException::class);
        $e->embedImage('x', 'image/jpeg');
    }
}
