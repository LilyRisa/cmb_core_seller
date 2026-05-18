<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering;

use CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Contracts\FieldType;

class FieldTypeRegistry
{
    /** @var array<string, FieldType> */
    private array $types = [];

    public function register(FieldType $type): void
    {
        $key = $type->key();
        if (isset($this->types[$key])) {
            throw new \InvalidArgumentException("FieldType [{$key}] đã được đăng ký.");
        }
        $this->types[$key] = $type;
    }

    public function get(string $key): ?FieldType
    {
        return $this->types[$key] ?? null;
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->types);
    }
}
