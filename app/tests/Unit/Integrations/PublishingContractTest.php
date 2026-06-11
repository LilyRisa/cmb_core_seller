<?php

declare(strict_types=1);

namespace Tests\Unit\Integrations;

use CMBcoreSeller\Integrations\Channels\Contracts\ListingValidator;
use CMBcoreSeller\Integrations\Channels\Contracts\ProductPublishingConnector;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PublishingContractTest extends TestCase
{
    public function test_product_publishing_connector_is_interface(): void
    {
        $ref = new ReflectionClass(ProductPublishingConnector::class);
        $this->assertTrue($ref->isInterface());
    }

    /** @dataProvider productPublishingMethodsProvider */
    public function test_product_publishing_connector_has_method(string $method): void
    {
        $ref = new ReflectionClass(ProductPublishingConnector::class);
        $this->assertTrue($ref->hasMethod($method), "Missing method: {$method}");
    }

    /** @return array<string, array{0: string}> */
    public static function productPublishingMethodsProvider(): array
    {
        return [
            'getCategoryTree' => ['getCategoryTree'],
            'getCategoryAttributes' => ['getCategoryAttributes'],
            'getBrands' => ['getBrands'],
            'uploadMedia' => ['uploadMedia'],
            'createListing' => ['createListing'],
            'getListingStatus' => ['getListingStatus'],
        ];
    }

    public function test_listing_validator_is_interface(): void
    {
        $ref = new ReflectionClass(ListingValidator::class);
        $this->assertTrue($ref->isInterface());
    }

    public function test_listing_validator_has_validate_method(): void
    {
        $ref = new ReflectionClass(ListingValidator::class);
        $this->assertTrue($ref->hasMethod('validate'));
    }
}
