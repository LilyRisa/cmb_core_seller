<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Nodes;

/** Kết quả chạy 1 node: đi tiếp (theo handle) | chờ input | kết thúc | lỗi. */
final class NodeResult
{
    private function __construct(
        public readonly string $kind,      // advance|wait|end|fail
        public readonly ?string $handle = null,
        public readonly ?string $error = null,
    ) {}

    public static function advance(?string $handle = null): self
    {
        return new self('advance', handle: $handle);
    }

    public static function wait(): self
    {
        return new self('wait');
    }

    public static function end(): self
    {
        return new self('end');
    }

    public static function fail(string $error): self
    {
        return new self('fail', error: $error);
    }

    public function isAdvance(): bool
    {
        return $this->kind === 'advance';
    }

    public function isWait(): bool
    {
        return $this->kind === 'wait';
    }

    public function isEnd(): bool
    {
        return $this->kind === 'end';
    }

    public function isFail(): bool
    {
        return $this->kind === 'fail';
    }
}
