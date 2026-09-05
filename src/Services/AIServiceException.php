<?php

namespace YourVendor\AIRotationManager\Services;

use RuntimeException;
use Throwable;

class AIServiceException extends RuntimeException
{
    public function __construct(
        string $message,
        private ?int $statusCode = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }
}
