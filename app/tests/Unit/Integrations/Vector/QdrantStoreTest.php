<?php

namespace Tests\Unit\Integrations\Vector;

use CMBcoreSeller\Integrations\Vector\Qdrant\QdrantStore;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QdrantStoreTest extends TestCase
{
    public function test_disabled_when_no_url(): void
    {
        $store = new QdrantStore(['url' => '', 'api_key' => '', 'timeout' => 5]);
        $this->assertFalse($store->enabled());
        $this->assertSame([], $store->search('c', [0.1, 0.2], 5));
    }

    public function test_search_sends_tenant_filter_and_maps_result(): void
    {
        Http::fake([
            '*/collections/visual_training__m/points/search' => Http::response([
                'result' => [
                    ['id' => 'p1', 'score' => 0.91, 'payload' => ['item_id' => 7]],
                ],
            ], 200),
        ]);

        $store = new QdrantStore(['url' => 'http://qdrant:6333', 'api_key' => '', 'timeout' => 5]);
        $hits = $store->search('visual_training__m', [0.1, 0.2], 5, ['tenant_id' => 3]);

        $this->assertCount(1, $hits);
        $this->assertSame('p1', $hits[0]['id']);
        $this->assertSame(7, $hits[0]['payload']['item_id']);

        Http::assertSent(function ($req) {
            $body = $req->data();

            return $req->url() === 'http://qdrant:6333/collections/visual_training__m/points/search'
                && $body['filter']['must'][0]['key'] === 'tenant_id'
                && $body['filter']['must'][0]['match']['value'] === 3
                && $body['limit'] === 5;
        });
    }

    public function test_search_swallows_http_error(): void
    {
        Http::fake(['*' => Http::response('boom', 500)]);
        $store = new QdrantStore(['url' => 'http://qdrant:6333', 'api_key' => '', 'timeout' => 5]);
        $this->assertSame([], $store->search('c', [0.1], 5, ['tenant_id' => 1]));
    }
}
