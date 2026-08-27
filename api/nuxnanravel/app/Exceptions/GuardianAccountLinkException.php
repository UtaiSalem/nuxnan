<?php

namespace App\Exceptions;

class GuardianAccountLinkException extends \RuntimeException
{
    private int $httpStatus;

    public function __construct(string $message, int $httpStatus = 400)
    {
        parent::__construct($message, $httpStatus);
        $this->httpStatus = $httpStatus;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public static function conflict(string $message): self
    {
        return new self($message, 409);
    }

    public static function forbidden(string $message): self
    {
        return new self($message, 403);
    }

    public static function invalid(string $message): self
    {
        return new self($message, 422);
    }
}
