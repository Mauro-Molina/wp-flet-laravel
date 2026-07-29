<?php

namespace App\Domain\Content;

use RuntimeException;

class ContentProxyException extends RuntimeException
{
    /**
     * @param  list<array{code: string, message: string}>  $errors
     */
    public function __construct(
        string $message,
        public readonly int $statusCode = 502,
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }
}
