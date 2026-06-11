<?php

namespace Tests\Unit\Integrations;

use CMBcoreSeller\Integrations\Channels\DTO\CategoryNodeDTO;
use CMBcoreSeller\Integrations\Channels\DTO\ListingDraftDTO;
use CMBcoreSeller\Integrations\Channels\DTO\MediaRefDTO;
use PHPUnit\Framework\TestCase;

class PublishingDtoTest extends TestCase
{
    public function test_listing_draft_dto_holds_fields(): void
    {
        $draft = new ListingDraftDTO(
            title: 'Áo thun',
            description: '<p>mô tả</p>',
            categoryId: '3',
            brandId: '40516',
            attributes: ['warranty_type' => 'No Warranty'],
            media: [new MediaRefDTO('https://cdn/x.jpg', 'cdn_url')],
            skus: [['price' => 100000, 'stock' => 10]],
            logistics: ['channels' => []],
        );

        $this->assertSame('3', $draft->categoryId);
        $this->assertStringContainsString('cdn', $draft->media[0]->ref);
    }

    public function test_category_node_dto_holds_fields(): void
    {
        $node = new CategoryNodeDTO('3', null, 'Áo', true);

        $this->assertTrue($node->isLeaf);
        $this->assertNull($node->parentId);
    }
}
