<?php

namespace CMBcoreSeller\Modules\Messaging\Services\Flows\Steps;

/** Kết quả chạy 1 step: xong (sang bước kế) | chờ input | lỗi. */
final class StepResult
{
    private function __construct(
        public readonly string $kind,      // done|wait|fail
        private readonly ?string $handleValue = null,
        private readonly ?string $errorValue = null,
    ) {}

    public static function done(): self
    {
        return new self('done');
    }

    public static function wait(?string $handle = null): self
    {
        return new self('wait', handleValue: $handle);
    }

    public static function fail(string $error): self
    {
        return new self('fail', errorValue: $error);
    }

    public function isDone(): bool
    {
        return $this->kind === 'done';
    }

    public function isWait(): bool
    {
        return $this->kind === 'wait';
    }

    public function isFail(): bool
    {
        return $this->kind === 'fail';
    }

    public function handle(): ?string
    {
        return $this->handleValue;
    }

    public function error(): ?string
    {
        return $this->errorValue;
    }
}
