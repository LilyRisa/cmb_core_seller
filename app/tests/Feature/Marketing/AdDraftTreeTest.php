<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Modules\Marketing\Support\AdDraftTree;
use PHPUnit\Framework\TestCase;

class AdDraftTreeTest extends TestCase
{
    public function test_tree_payload_passes_through(): void
    {
        $payload = ['adsets' => [
            ['key' => 'a1', 'name' => 'Nhóm 1', 'budget' => ['daily_major' => 100000],
                'targeting' => ['x' => 1], 'ads' => [['key' => 'd1', 'name' => 'QC', 'creative' => ['mode' => 'page_post']]]],
        ]];

        $tree = AdDraftTree::normalize($payload);

        $this->assertCount(1, $tree['adsets']);
        $this->assertSame('a1', $tree['adsets'][0]['key']);
        $this->assertCount(1, $tree['adsets'][0]['ads']);
    }

    public function test_legacy_flat_payload_is_wrapped_into_one_adset_one_ad(): void
    {
        $payload = [
            'budget' => ['daily_major' => 150000],
            'targeting' => ['geo_locations' => ['countries' => ['VN']]],
            'placements' => 'automatic',
            'schedule' => ['start_time' => null],
            'creative' => ['mode' => 'page_post', 'page_id' => '123', 'page_post_id' => '123_456', 'cta' => 'MESSAGE_PAGE'],
        ];

        $tree = AdDraftTree::normalize($payload);

        $this->assertCount(1, $tree['adsets']);
        $as = $tree['adsets'][0];
        $this->assertSame(150000, $as['budget']['daily_major']);
        $this->assertSame('automatic', $as['placements']);
        $this->assertSame(['geo_locations' => ['countries' => ['VN']]], $as['targeting']);
        $this->assertCount(1, $as['ads']);
        $this->assertSame('123_456', $as['ads'][0]['creative']['page_post_id']);
    }

    public function test_empty_payload_yields_empty_adsets(): void
    {
        $this->assertSame([], AdDraftTree::normalize([])['adsets']);
    }
}
