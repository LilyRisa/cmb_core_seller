<?php

namespace Tests\Unit\Customers;

use CMBcoreSeller\Modules\Customers\Support\CustomerPhoneNormalizer;
use PHPUnit\Framework\TestCase;

class CustomerPhoneNormalizerTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function test_normalize(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, CustomerPhoneNormalizer::normalize($input));
    }

    public static function cases(): array
    {
        return [
            'plain +84' => ['+84987654321', '0987654321'],
            'formatted +84' => ['(+84) 98-765-4321', '0987654321'],
            'spaced 0' => ['0987 654 321', '0987654321'],
            '84 prefix' => ['84987654321', '0987654321'],
            'already canonical' => ['0987654321', '0987654321'],
            'masked' => ['(+84) ****21', null],
            'masked x' => ['098765xxxx', null],
            'empty' => ['', null],
            'null' => [null, null],
            'letters' => ['abc', null],
            'foreign US' => ['+1 415 555 0123', '+14155550123'],
            'too short' => ['123', null],
            'whitespace only' => ['   ', null],
        ];
    }

    public function test_hash_is_deterministic(): void
    {
        $a = CustomerPhoneNormalizer::normalizeAndHash('+84987654321');
        $b = CustomerPhoneNormalizer::normalizeAndHash('0987 654 321');
        $this->assertNotNull($a);
        $this->assertSame($a, $b);                // same person, different formats
        $this->assertSame(64, strlen((string) $a));
        $this->assertNull(CustomerPhoneNormalizer::normalizeAndHash('(+84) ****21'));
    }
}
