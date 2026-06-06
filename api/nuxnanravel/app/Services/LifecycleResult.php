<?php

namespace App\Services;

/**
 * LifecycleResult
 *
 * Immutable outcome of a CourseLifecycleService check. `reasonCode` is a stable
 * machine-readable constant from CourseLifecycleService::REASON_* and is what
 * the frontend should switch on; `message` is the human string.
 */
class LifecycleResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly string $reasonCode,
        public readonly string $message = '',
        public readonly ?string $hint = null,
    ) {}

    public static function allow(): self
    {
        return new self(true, CourseLifecycleService::REASON_OK);
    }

    public static function deny(string $reasonCode, string $message, ?string $hint = null): self
    {
        return new self(false, $reasonCode, $message, $hint);
    }

    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'code' => $this->reasonCode,
            'message' => $this->message,
            'hint' => $this->hint,
        ];
    }
}
