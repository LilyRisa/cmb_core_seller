<?php

namespace Tests\Unit\Marketing;

use CMBcoreSeller\Integrations\Ads\DTO\AdPreviewDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AudienceSizeDTO;
use CMBcoreSeller\Integrations\Ads\DTO\PagePostDTO;
use CMBcoreSeller\Integrations\Ads\DTO\PageRefDTO;
use CMBcoreSeller\Integrations\Ads\DTO\TargetingOptionDTO;
use PHPUnit\Framework\TestCase;

class AuthoringDtoTest extends TestCase
{
    public function test_dtos_construct(): void
    {
        $page = new PageRefDTO(id: '123', name: 'Shop', accessToken: 'PAGETOK');
        $this->assertSame('123', $page->id);
        $this->assertSame('PAGETOK', $page->accessToken);

        $post = new PagePostDTO(
            id: '123_456', message: 'Sale', createdTime: '2026-06-01T00:00:00+0000',
            mediaType: 'photo', imageUrl: 'https://img', videoId: null,
            likes: 1200, comments: 89, shares: 45,
        );
        $this->assertSame('123_456', $post->id);
        $this->assertSame(1200, $post->likes);
        $this->assertSame('photo', $post->mediaType);

        $opt = new TargetingOptionDTO(id: '6003', name: 'Thời trang', type: 'interests', audienceSize: 5000000);
        $this->assertSame('6003', $opt->id);

        $size = new AudienceSizeDTO(lowerBound: 1000000, upperBound: 2100000);
        $this->assertSame(2100000, $size->upperBound);

        $prev = new AdPreviewDTO(format: 'DESKTOP_FEED_STANDARD', body: '<iframe></iframe>');
        $this->assertSame('DESKTOP_FEED_STANDARD', $prev->format);
    }
}
