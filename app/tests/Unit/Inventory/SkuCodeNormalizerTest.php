<?php

namespace Tests\Unit\Inventory;

use CMBcoreSeller\Modules\Inventory\Support\SkuCodeNormalizer;
use PHPUnit\Framework\TestCase;

class SkuCodeNormalizerTest extends TestCase
{
    public function test_normalize(): void
    {
        $this->assertSame('ABC123', SkuCodeNormalizer::normalize('  abc 123 '));
        $this->assertSame('ABC-1', SkuCodeNormalizer::normalize('abc-1'));
        $this->assertSame('', SkuCodeNormalizer::normalize(null));
        $this->assertSame('', SkuCodeNormalizer::normalize('   '));
    }

    public function test_matches(): void
    {
        $this->assertTrue(SkuCodeNormalizer::matches('SKU-1', '  sku-1 '));        // case + trim
        $this->assertTrue(SkuCodeNormalizer::matches('ao thun m', 'AO  THUN   M')); // whitespace stripped
        $this->assertFalse(SkuCodeNormalizer::matches('SKU-1', 'SKU 1'));           // hyphen ≠ space
        $this->assertFalse(SkuCodeNormalizer::matches('SKU-1', 'SKU-2'));
        $this->assertFalse(SkuCodeNormalizer::matches(null, 'SKU-1'));
        $this->assertFalse(SkuCodeNormalizer::matches('', ''));
    }
}
