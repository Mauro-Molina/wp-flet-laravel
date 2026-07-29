<?php

namespace App\Domain\Content;

final class ContentProxyResult
{
    public function __construct(
        public readonly int $statusCode,
        public readonly mixed $body,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
