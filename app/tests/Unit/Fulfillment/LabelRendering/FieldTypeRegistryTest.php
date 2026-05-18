<?php

namespace Tests\Unit\Fulfillment\LabelRendering;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\DataContext;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldRenderHelpers;
use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\FieldTypeRegistry;
use PHPUnit\Framework\TestCase;

class FieldTypeRegistryTest extends TestCase
{
    public function test_register_and_get(): void
    {
        $r = new FieldTypeRegistry;
        $r->register(new FakeFieldType('foo'));
        $this->assertSame('foo', $r->get('foo')->key());
    }

    public function test_get_returns_null_for_unknown_key(): void
    {
        $this->assertNull((new FieldTypeRegistry)->get('nope'));
    }

    public function test_register_duplicate_key_throws(): void
    {
        $r = new FieldTypeRegistry;
        $r->register(new FakeFieldType('foo'));
        $this->expectException(\InvalidArgumentException::class);
        $r->register(new FakeFieldType('foo'));
    }

    public function test_keys_returns_all_registered(): void
    {
        $r = new FieldTypeRegistry;
        $r->register(new FakeFieldType('a'));
        $r->register(new FakeFieldType('b'));
        $this->assertSame(['a', 'b'], $r->keys());
    }
}

class FakeFieldType implements FieldType
{
    public function __construct(private readonly string $key) {}

    public function key(): string
    {
        return $this->key;
    }

    public function validateProps(array $props): array
    {
        return $props;
    }

    public function dataKeys(): array
    {
        return [];
    }

    public function renderHtml(array $field, DataContext $ctx, FieldRenderHelpers $h): string
    {
        return '';
    }
}
